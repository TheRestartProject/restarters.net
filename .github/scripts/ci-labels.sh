#!/usr/bin/env bash
#
# Labels open PRs "CI: Running", "CI: Passed" or "CI: Failed" from the CircleCI statuses on their head commit.
#
#   ci-labels.sh <sha> <pr>   that PR, whose head is that commit
#   ci-labels.sh <sha>        the open PRs whose head is that commit
#   ci-labels.sh              every open PR
#
# Needs GH_TOKEN and REPO (owner/name). DRY_RUN=1 prints the changes instead of making them.

set -euo pipefail

: "${REPO:?REPO (owner/name) is needed}"

LABELS=("CI: Running" "CI: Passed" "CI: Failed")

# A failed change is reported and skipped rather than stopping the run: the next status puts it right.
run() {
  if [ "${DRY_RUN:-}" = 1 ]; then
    echo "  would run: $*"
  elif ! "$@" > /dev/null; then
    echo "::warning::Failed: $*"
  fi
}

# Failed as soon as any CircleCI job has failed, Running while any is still going, and Passed once they all have.
# Empty when CircleCI hasn't reported on the commit.
ci_label() {
  local states
  states=$(gh api "repos/$REPO/commits/$1/status" \
    --jq '[.statuses[] | select(.context | startswith("ci/circleci")) | .state] | join(" ")')

  case " $states " in
    "  ") echo "" ;;
    *" failure "* | *" error "*) echo "CI: Failed" ;;
    *" pending "*) echo "CI: Running" ;;
    *) echo "CI: Passed" ;;
  esac
}

# With no CircleCI status yet (a push CI hasn't picked up), the PR keeps no CI label rather than the last commit's.
label_pr() {
  local pr=$1 sha=$2 want current label
  want=$(ci_label "$sha")
  current=$(gh api "repos/$REPO/issues/$pr/labels" --jq '.[].name')

  for label in "${LABELS[@]}"; do
    if [ "$label" != "$want" ] && grep -qxF "$label" <<< "$current"; then
      run gh api -X DELETE "repos/$REPO/issues/$pr/labels/$(jq -rn --arg l "$label" '$l | @uri')"
    fi
  done

  if [ -n "$want" ] && ! grep -qxF "$want" <<< "$current"; then
    run gh api -X POST "repos/$REPO/issues/$pr/labels" -f "labels[]=$want"
  fi

  echo "#$pr: ${want:-no CircleCI status yet}"
}

# Open PRs whose head is still this commit, so that a late status for an earlier push can't relabel a PR. Just after
# a push GitHub can take a few seconds to link the commit to its PR, so an empty answer is tried again.
prs_for() {
  local prs attempt
  for attempt in 1 2 3 4; do
    prs=$(gh api "repos/$REPO/commits/$1/pulls" \
      --jq ".[] | select(.state == \"open\" and .head.sha == \"$1\") | .number")
    if [ -n "$prs" ] || [ "$attempt" = 4 ]; then
      echo "$prs"
      return
    fi
    sleep 10
  done
}

sha=${1:-}
pr=${2:-}

if [ -n "$sha" ] && ! [[ $sha =~ ^[0-9a-f]{40}$ ]]; then
  echo "Not a commit SHA: $sha" >&2
  exit 1
fi

if [ -n "$pr" ] && ! [[ $pr =~ ^[0-9]+$ ]]; then
  echo "Not a PR number: $pr" >&2
  exit 1
fi

if [ -n "$pr" ]; then
  label_pr "$pr" "$sha"
elif [ -n "$sha" ]; then
  for pr in $(prs_for "$sha"); do
    label_pr "$pr" "$sha"
  done
else
  gh api "repos/$REPO/pulls?state=open&per_page=100" --paginate --jq '.[] | "\(.number) \(.head.sha)"' |
    while read -r pr sha; do
      label_pr "$pr" "$sha"
    done
fi
