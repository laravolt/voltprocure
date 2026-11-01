#!/usr/bin/env bash
# Compares two perf runs made on the same machine and prints a Markdown table
# (also appended to the GitHub job summary).
#
#   scripts/perf/compare.sh profile <main/results.json> <branch/results.json>
#   scripts/perf/compare.sh load <main.txt> <branch.txt>
#
# Runners differ in speed by up to 2x, so only numbers from the same job are
# compared.
set -euo pipefail

kind=${1:?usage: compare.sh profile|load <main> <branch>}
base=${2:?}
head=${3:?}

{
  if [ "$kind" = profile ]; then
    echo "### CPU time per request: main vs this branch (same runner, one worker)"
    echo
    echo "| Page | main ms | branch ms | speed-up | main req/s per core | branch req/s per core |"
    echo "|---|---|---|---|---|---|"
    jq -r --slurpfile branch "$head" '
      to_entries[]
      | .key as $page
      | .value.mean_ms as $m
      | $branch[0][$page].mean_ms as $b
      | "| `\($page)` | \($m) | \($b) | \(($m / $b * 100 | round) / 100)x | \(1000 / $m | round) | \(1000 / $b | round) |"
    ' "$base"
  else
    echo "### wrk: main vs this branch (same runner)"
    echo
    echo "| Page | main req/s | branch req/s | speed-up |"
    echo "|---|---|---|---|"
    join <(sort "$base") <(sort "$head") \
      | awk '{printf "| `%s` | %.0f | %.0f | %.2fx |\n", $1, $2, $4, $4 / $2}'
  fi
  echo
} | tee -a "${GITHUB_STEP_SUMMARY:-/dev/null}"
