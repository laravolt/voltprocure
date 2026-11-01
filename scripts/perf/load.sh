#!/usr/bin/env bash
# Measures how many requests a running VoltProcure serves, page by page, with
# wrk. Logs in as the demo procurement officer first and replays the session
# cookies, so the pages are the ones a signed-in user gets.
#
#   scripts/perf/load.sh <base-url> [duration] [connections] [label]
#
# Prints a Markdown table (and appends it to the GitHub job summary).
set -euo pipefail

base=${1:?usage: load.sh <base-url> [duration] [connections] [label]}
duration=${2:-20s}
connections=${3:-8}
label=${4:-}
email=${PERF_USER:-andi@voltprocure.test}

jar=$(mktemp)
summary=$(mktemp)
trap 'rm -f "$jar" "$summary"' EXIT

token=$(curl -s -c "$jar" -b "$jar" "$base/auth/login" | grep -o '<input[^>]*_token[^>]*>' | grep -o 'value="[^"]*"' | head -1 | cut -d'"' -f2)
[ -n "$token" ] || { echo "no CSRF token on the login page" >&2; exit 1; }
location=$(curl -s -o /dev/null -w '%{redirect_url}' -c "$jar" -b "$jar" -X POST "$base/auth/login" \
  --data-urlencode "_token=$token" --data-urlencode "email=$email" --data-urlencode "password=password")
case "$location" in */auth/login*|"") echo "login as $email failed (redirect: $location)" >&2; exit 1 ;; esac

# Netscape cookie jar: name and value are the last two fields.
cookie=$(sed -e 's/^#HttpOnly_//' -e '/^#/d' -e '/^$/d' "$jar" | awk -F'\t' '{printf "%s=%s; ", $6, $7}')

{
  echo "### wrk: ${label:-$base} (${connections} connections, ${duration} per page, user \`$email\`)"
  echo
  echo "| Page | req/s | req/min | p50 | p90 | p99 | Errors |"
  echo "|---|---|---|---|---|---|---|"
} > "$summary"

for path in /up /home /purchase-requests /purchase-orders /items /suppliers; do
  code=$(curl -s -o /dev/null -w '%{http_code}' -H "Cookie: $cookie" "$base$path")
  [ "$code" = 200 ] || { echo "$path returned $code before the run" >&2; exit 1; }

  # Warm every worker before measuring.
  wrk -t2 -c"$connections" -d3s -H "Cookie: $cookie" "$base$path" > /dev/null
  out=$(wrk -t2 -c"$connections" -d"$duration" --latency -H "Cookie: $cookie" "$base$path")

  rps=$(awk '/^Requests\/sec:/ {print $2}' <<< "$out")
  p50=$(awk '$1 == "50%" {print $2}' <<< "$out")
  p90=$(awk '$1 == "90%" {print $2}' <<< "$out")
  p99=$(awk '$1 == "99%" {print $2}' <<< "$out")
  non2xx=$(awk '/Non-2xx or 3xx responses:/ {print $NF}' <<< "$out")
  socket=$(awk '/Socket errors:/ {print $3, $4, $5, $6, $7, $8, $9}' <<< "$out")
  errors="${non2xx:-0}${socket:+ (socket: $socket)}"
  rpm=$(awk -v r="$rps" 'BEGIN {printf "%d", r * 60}')

  echo "| \`$path\` | $rps | $rpm | $p50 | $p90 | $p99 | $errors |" >> "$summary"
done

echo >> "$summary"
cat "$summary"
if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
  cat "$summary" >> "$GITHUB_STEP_SUMMARY"
fi
