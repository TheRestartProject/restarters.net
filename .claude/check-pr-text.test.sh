#!/bin/bash
# Tests for check-pr-text.sh. Self-contained: writes its own fixtures.
#
#   bash .claude/check-pr-text.test.sh
#
# The command strings under test contain "gh pr create", so this lives in a file
# rather than being typed at a shell - sibling hooks react to that literal on a
# command line and would fire on the test harness itself.

HOOK="$(cd "$(dirname "$0")" && pwd)/check-pr-text.sh"
TMP=$(mktemp -d); trap 'rm -rf "$TMP"' EXIT
G="gh"; PRC="$G pr create"
pass=0; fail=0

run() { printf '%s' "$(jq -nc --arg c "$1" '{tool_input:{command:$c}}')" | "$HOOK" >/dev/null 2>"$TMP/err"; echo $?; }

check() { # name  want_exit  command  [must_contain]
  local name="$1" want="$2" cmd="$3" needle="${4:-}" got ok=1
  got=$(run "$cmd")
  [ "$got" = "$want" ] || ok=0
  [ -n "$needle" ] && ! grep -qi -- "$needle" "$TMP/err" && ok=0
  if [ "$ok" = "1" ]; then
    printf '  PASS  %s\n' "$name"; pass=$((pass+1))
  else
    printf '  FAIL  %s (exit %s, wanted %s%s)\n' "$name" "$got" "$want" "${needle:+, needle '$needle'}"
    sed 's/^/          /' "$TMP/err" | head -6; fail=$((fail+1))
  fi
}

echo "== the body must be readable, or nothing else here means anything =="
check "unreadable \$VAR path blocks" 2 \
  "$PRC --base master --head x --title t --body-file \$SP/body.md" "cannot read"
printf 'We added single-flight so callers share the work.\n' > "$TMP/jargon.md"
check "body inlined by \$(cat FILE) is still read" 2 \
  "$G api -X PATCH repos/O/R/pulls/1 -f body=\"\$(cat $TMP/jargon.md)\"" "single-flight"
check "-F body=@FILE is read" 2 \
  "$G api -X PATCH repos/O/R/pulls/1 -F body=@$TMP/jargon.md" "single-flight"

echo
echo "== plain English =="
check "jargon caught, with a plain replacement offered" 2 \
  "$PRC --base master --head x --title t --body-file $TMP/jargon.md" "callers asking the same question"

printf 'Three guards:\n\n- process-wide concurrency cap;\n- a cache keyed on the URL;\n' > "$TMP/frag.md"
check "short semicolon fragments caught" 2 \
  "$PRC --base master --head x --title t --body-file $TMP/frag.md" "verb taken out"

# A complete sentence may legitimately end in a semicolon inside a list. The rule
# only fires on bullets of 100 chars or less, so the fixture has to be longer than
# that: 113 here versus 55 for the fragments this targets.
#
# It is also deliberately PLAIN. The earlier fixture was lifted verbatim from a
# merged PR and read at grade 16.2, so it failed check 6 rather than the rule this
# case is named after - and the case then proved nothing about semicolons. Check 6
# was right about that sentence; the fixture was testing two things at once.
{ printf -- '- '; printf 'the poll runs every minute even when nothing has changed, and each run asks the server for the same list again;\n'; } > "$TMP/longsemi.md"
check "long sentence ending in ';' not flagged" 0 \
  "PR_TEXT_VERIFIED=1 $PRC --base master --head x --title t --body-file $TMP/longsemi.md"

printf 'It remembers the answer.\n\n```\nNo cursor stored yet - upsert watermark no-op\n```\n' > "$TMP/fence.md"
check "fenced output exempt" 0 \
  "PR_TEXT_VERIFIED=1 $PRC --base master --head x --title t --body-file $TMP/fence.md"

printf 'The `users_expected` table and the `cursor` column are named in backticks.\n' > "$TMP/ticks.md"
check "backticked spans exempt" 0 \
  "PR_TEXT_VERIFIED=1 $PRC --base master --head x --title t --body-file $TMP/ticks.md"

# House style asks for the Discourse link in the description, so a hyphenated topic
# slug must not read as a coined compound. This one failed on "feedback-leaving".
printf 'Prompted by a question from a volunteer.\n\nhttps://talk.restarters.net/t/feedback-leaving-groups-request/10157/4\n' > "$TMP/link.md"
check "URL slug not read as prose" 0 \
  "PR_TEXT_VERIFIED=1 $PRC --base master --head x --title t --body-file $TMP/link.md"

check "override works" 0 \
  "PR_PLAIN_ENGLISH_OK=1 PR_TEXT_VERIFIED=1 $PRC --base master --head x --title t --body-file $TMP/jargon.md"

echo
echo "== house style and stale claims =="
printf 'A clear description.\n\nGenerated with [Claude Code](https://claude.com/claude-code)\n' > "$TMP/ai.md"
check "AI attribution caught" 2 "$PRC --base master --head x --title t --body-file $TMP/ai.md" "AI attribution"

printf 'This converts 42 sites and adds 7 tests.\n' > "$TMP/counts.md"
check "counts caught" 2 "$PRC --base master --head x --title t --body-file $TMP/counts.md" "asserts counts"

printf 'I then discovered the cause and eventually fixed it.\n' > "$TMP/narr.md"
check "narrative caught" 2 "$PRC --base master --head x --title t --body-file $TMP/narr.md" "narrative"

echo
echo "== must not fire =="
# A commit message, doc or fixture routinely quotes an example PR command. This
# fired on a git commit before the trigger learned to ignore heredoc bodies.
QUOTED="git commit -F - <<'MSG'
fix: read the body

    $PRC --body-file \$SP/body.md
    $G api ... -f body=\"\$(cat body.md)\"

That used no-op writes and an upsert watermark.
MSG"
check "git commit quoting a PR command ignored" 0 "$QUOTED"
check "gh pr view ignored"        0 "$G pr view 1328 --json body"
check "unrelated command ignored" 0 "ls -la"
check "label-only edit ignored"   0 "$G pr edit 1328 --add-label perf"
printf 'A plain description of what the branch does and how to check it.\n' > "$TMP/good.md"
check "clean body passes"         0 "$PRC --base master --head x --title t --body-file $TMP/good.md"

echo
echo "== before and after =="
printf -- '- Add showMoreButton prop.\n- Wire it into the map.\n- Pass it from the page.\n- Add a lang key.\n' > "$TMP/terse.md"
check "terse bullets with no before/after caught" 2 \
  "$PRC --base master --head x --title t --body-file $TMP/terse.md" "what changes"
printf -- '- Before: the list grew as you scrolled.\n- After: a button shows more.\n- The groups page is unchanged.\n' > "$TMP/ba.md"
check "bullets saying before and after pass" 0 \
  "$PRC --base master --head x --title t --body-file $TMP/ba.md"
check "before/after override works" 0 \
  "PR_BEFORE_AFTER_OK=1 $PRC --base master --head x --title t --body-file $TMP/terse.md"

echo
echo "  $pass passed, $fail failed"
[ "$fail" = "0" ]
