---
name: ralph
description: "MUST use for any non-trivial development task in FreegleDocker - implements iterative development with status tracking, session logging, validation, and one-task-at-a-time approach."
---

# Ralph Iterative Development Approach

## 1. Session Log (in CLAUDE.md)

On start: Read CLAUDE.md session log. If it references an active plan file, READ THAT PLAN FILE. Resume from where you left off.

During work: Update session log after significant progress with date, status, completed items, next steps, blockers.

Before context compaction: Update with current state, uncommitted changes, running commands, exact next steps.

## 2. Break Down & Track

Create a status table: `| # | Task | Status | Notes |` with ⬜ Pending, 🔄 In Progress, ✅ Complete, ❌ Blocked. Work ONE task at a time. Update after each.

For parallel work across branches, add a Branch column. Always `git branch --show-current` before editing.

## 3. TDD for Bug Fixes

Write failing test FIRST → verify it fails → minimal fix → verify it passes. Tests written after code prove nothing.

## 4. Validate Before Marking Complete

Front-end: Chrome DevTools MCP. Emails: MailPit. Backend: 90%+ test coverage on touched modules.

## 5. Code Quality Review (before declaring done)

Check for: logic errors, security issues, performance problems, missing error handling. Verify consistency with existing patterns. No duplication — extract shared logic. Tests should test behavior, not implementation.

## 6. Completion Criteria

All subtasks ✅. All tests pass. Quality review done. Changes validated. No duplication introduced.

## 7. PRs

Format: Summary (bullets), Code Quality Review, Future Improvements, Test Plan. No AI attribution. Use `gh api` REST endpoints (not `gh pr edit` which uses broken GraphQL). Check for existing comments before updating PR body.

## 8. Following a Plan

Plan files in `plans/active/` are the master progress tracker. Update status markers as you go. Re-read the plan after every resume/compaction — it contains steps you may have forgotten.

## 9. Database Migrations

Laravel migrations are source of truth (dev/CI). Production needs idempotent SQL in `*_migration.sql` files.

## 10. Automated Execution

`./ralph.sh -t "task description"` or `./ralph.sh plans/active/plan.md`. Config in `.ralphy/config.yaml`.

Now analyse the user's request and create your status table to begin work.
