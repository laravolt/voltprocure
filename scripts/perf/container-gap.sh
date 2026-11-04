#!/usr/bin/env bash
# Where does the production container spend the CPU that the in-process
# profile does not see? Runs the image in four setups on this machine and
# prints, per page, throughput, p50 latency and CPU milliseconds per request,
# split by process: the container (FrankenPHP: Caddy and the PHP workers),
# docker-proxy, the Docker daemons, wrk itself and the whole machine.
#
#   scripts/perf/container-gap.sh <image> [duration]
#
# Setups: 1 worker / 1 connection and 2 workers / 8 connections, each through
# a published port (docker-proxy, as docker-compose.prod.yml does) and with
# host networking; then 2 workers with zstd, br and gzip responses. Also profiles the pages in-process inside the container
# with the image's own PHP, to compare with the same script run on the host.
set -euo pipefail

image=${1:?usage: container-gap.sh <image> [duration]}
duration=${2:-10s}
pages=(/up /home /purchase-requests /purchase-orders /items /suppliers)
email=${PERF_USER:-andi@voltprocure.test}
out=${PERF_OUT:-perf-gap}
mkdir -p "$out"
summary="$out/summary.md"
: > "$summary"
hz=$(getconf CLK_TCK)
# Bash's `time` prints user and system CPU seconds of wrk.
TIMEFORMAT='%U %S'
key="base64:$(openssl rand -base64 32)"

# CPU time in ms used so far by every process with this command name.
proc_ms() {
  local total=0 pid
  local -a f
  for pid in $(pgrep -x "$1" || true); do
    # Fields 14 and 15 of /proc/<pid>/stat are utime and stime in clock ticks.
    if read -r -a f < "/proc/$pid/stat" 2> /dev/null; then
      total=$((total + f[13] + f[14]))
    fi
  done
  echo $((total * 1000 / hz))
}

docker_ms() {
  echo $(($(proc_ms dockerd) + $(proc_ms containerd) + $(proc_ms containerd-shim)))
}

# Busy CPU time in ms of the whole machine, all cores.
machine_ms() {
  local _cpu user nice system idle iowait irq softirq steal _rest
  read -r _cpu user nice system idle iowait irq softirq steal _rest < /proc/stat
  echo $(((user + nice + system + irq + softirq + steal) * 1000 / hz))
}

# CPU time in ms used by a container, from its cgroup (v2).
cgroup_ms() {
  if [ -n "$1" ] && [ -r "$1/cpu.stat" ]; then
    awk '/^usage_usec/ {print int($2 / 1000)}' "$1/cpu.stat"
  else
    echo 0
  fi
}

login() {
  local base=$1 jar token location
  jar=$(mktemp)
  token=$(curl -s -c "$jar" -b "$jar" "$base/auth/login" | grep -o '<input[^>]*_token[^>]*>' | grep -o 'value="[^"]*"' | head -1 | cut -d'"' -f2)
  [ -n "$token" ] || { echo "no CSRF token on the login page" >&2; return 1; }
  location=$(curl -s -o /dev/null -w '%{redirect_url}' -c "$jar" -b "$jar" -X POST "$base/auth/login" \
    --data-urlencode "_token=$token" --data-urlencode "email=$email" --data-urlencode "password=password")
  case "$location" in */auth/login* | "") echo "login as $email failed (redirect: $location)" >&2; return 1 ;; esac
  sed -e 's/^#HttpOnly_//' -e '/^#/d' -e '/^$/d' "$jar" | awk -F'\t' '{printf "%s=%s; ", $6, $7}'
  rm -f "$jar"
}

# start <name> <workers> <bridge|host>: prints the base URL once /up answers.
start() {
  local name=$1 workers=$2 network=$3 port base i
  local -a net
  if [ "$network" = host ]; then
    port=8080
    net=(--network host)
  else
    port=8090
    net=(-p "127.0.0.1:$port:8080")
  fi
  base="http://127.0.0.1:$port"
  docker run -d --name "$name" "${net[@]}" \
    -e APP_KEY="$key" -e APP_ENV=production -e APP_DEBUG=false -e APP_URL="$base" \
    -e APP_DEMO=true -e APP_FORCE_HTTPS=false -e SESSION_DRIVER=cookie -e CACHE_STORE=file \
    -e OCTANE_WORKERS="$workers" "$image" > /dev/null
  for i in $(seq 1 90); do
    if curl -sf -o /dev/null "$base/up"; then
      echo "$base"
      return 0
    fi
    sleep 2
  done
  echo "$name did not answer /up" >&2
  docker logs "$name" 2>&1 | tail -50 >&2
  return 1
}

# measure <label> <container> <base-url> <connections> [accept-encoding]
measure() {
  local label=$1 name=$2 base=$3 conns=$4 encoding=${5:-} threads cookie cgroup path
  local -a headers
  threads=$((conns < 2 ? conns : 2))
  cookie=$(login "$base")
  headers=(-H "Cookie: $cookie")
  if [ -n "$encoding" ]; then
    headers+=(-H "Accept-Encoding: $encoding")
  fi
  cgroup=$(find /sys/fs/cgroup -maxdepth 3 -type d -name "*$(docker inspect -f '{{.Id}}' "$name")*" 2> /dev/null | head -1)
  {
    echo "#### $label: $conns connection(s), $duration per page"
    echo
    echo "CPU ms per request, by process. Container = FrankenPHP (Caddy + PHP workers)."
    echo
    echo "| Page | KB sent | req/s | p50 | Errors | container | docker-proxy | dockerd+containerd | wrk | machine |"
    echo "|---|---|---|---|---|---|---|---|---|---|"
  } >> "$summary"

  for path in "${pages[@]}"; do
    wrk -t"$threads" -c"$conns" -d3s "${headers[@]}" "$base$path" > /dev/null
    local c0 p0 d0 m0 c1 p1 d1 m1 wrk_out wrk_cpu requests rps p50 errors bytes
    bytes=$(curl -s -o /dev/null -w '%{size_download}' "${headers[@]}" "$base$path")
    c0=$(cgroup_ms "$cgroup") p0=$(proc_ms docker-proxy) d0=$(docker_ms) m0=$(machine_ms)
    wrk_cpu=$({ time wrk -t"$threads" -c"$conns" -d"$duration" --latency "${headers[@]}" "$base$path" > "$out/wrk.txt" 2>&1; } 2>&1)
    c1=$(cgroup_ms "$cgroup") p1=$(proc_ms docker-proxy) d1=$(docker_ms) m1=$(machine_ms)
    wrk_out=$(cat "$out/wrk.txt")
    requests=$(awk '/requests in/ {print $1}' <<< "$wrk_out")
    rps=$(awk '/^Requests\/sec:/ {print $2}' <<< "$wrk_out")
    p50=$(awk '$1 == "50%" {print $2}' <<< "$wrk_out")
    errors=$(awk '/Non-2xx or 3xx responses:/ {print $NF}' <<< "$wrk_out")
    awk -v page="$path" -v kb="$bytes" -v n="${requests:-0}" -v rps="$rps" -v p50="$p50" -v err="${errors:-0}" \
      -v c=$((c1 - c0)) -v p=$((p1 - p0)) -v d=$((d1 - d0)) -v m=$((m1 - m0)) -v w="$wrk_cpu" '
      BEGIN {
        split(w, t, " "); wrk = (t[1] + t[2]) * 1000
        if (n == 0) n = 1
        printf "| `%s` | %.1f | %s | %s | %s | %.2f | %.2f | %.2f | %.2f | %.2f |\n", page, kb / 1024, rps, p50, err, c / n, p / n, d / n, wrk / n, m / n
      }' >> "$summary"
  done
  echo >> "$summary"
}

run() { # run <label> <workers> <bridge|host> <connections> [accept-encoding]
  local name=gap base
  base=$(start "$name" "$2" "$3")
  measure "$1" "$name" "$base" "$4" "${5:-}"
  if [ "$2" = 1 ] && [ "$3" = host ]; then
    {
      echo "#### In-process profile inside the container (the image's PHP, no HTTP)"
      echo
      echo '```'
      docker exec "$name" sh -c 'php -v | head -1; php -i | grep -E "^Thread Safety"; frankenphp version 2> /dev/null || true'
      echo '```'
      echo
      docker exec -e PERF_LABEL=container "$name" php -d opcache.enable_cli=1 -d opcache.jit=tracing \
        -d opcache.jit_buffer_size=64M -d memory_limit=512M scripts/perf/profile.php 200 /tmp/perf-profile \
        || echo "(profile inside the container failed)"
    } >> "$summary"
  fi
  docker rm -f "$name" > /dev/null
}

{
  echo "### Container CPU gap: $(nproc) vCPU, $(free -m | awk '/^Mem:/ {print $2}') MB"
  echo
  echo "Docker $(docker version --format '{{.Server.Version}}'); userland proxy: $(grep -o '"userland-proxy"[^,}]*' /etc/docker/daemon.json 2> /dev/null || echo 'default (on)')."
  echo "CPU siblings: $(cat /sys/devices/system/cpu/cpu*/topology/thread_siblings_list 2> /dev/null | sort -u | tr '\n' ' ')"
  echo
  echo "Caddyfile:"
  echo
  echo '```'
  docker run --rm --entrypoint cat "$image" /etc/frankenphp/Caddyfile 2> /dev/null || echo "(not found)"
  echo '```'
  echo
} >> "$summary"

run "1 worker, published port (docker-proxy)" 1 bridge 1
run "1 worker, host network" 1 host 1
run "2 workers, published port (docker-proxy)" 2 bridge 8
run "2 workers, host network" 2 host 8
# wrk sends no Accept-Encoding; browsers do, and the Caddyfile compresses.
for encoding in zstd br gzip; do
  run "2 workers, host network, Accept-Encoding: $encoding" 2 host 8 "$encoding"
done

cat "$summary"
if [ -n "${GITHUB_STEP_SUMMARY:-}" ]; then
  cat "$summary" >> "$GITHUB_STEP_SUMMARY"
fi
