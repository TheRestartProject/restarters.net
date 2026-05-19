---
name: ralph
description: "MUST use for any non-trivial development task in restarters.net - implements iterative development with status tracking, session logging, validation, and one-task-at-a-time approach."
---

# Ralph Iterative Development Approach

## 1. Session Log (in .claude-session.md)

On start: Read `.claude-session.md` (gitignored local file, NOT CLAUDE.md) for the session log. If it references an active plan file, READ THAT PLAN FILE. Resume from where you left off.

During work: Update `.claude-session.md` after significant progress with date, status, completed items, next steps, blockers. Never write session log entries to CLAUDE.md — that file is committed to git and updating it makes open PRs go BEHIND.

Before context compaction: Update `.claude-session.md` with current state, uncommitted changes, running commands, exact next steps.

## 2. Break Down & Track

Create a status table: `| # | Task | Status | Notes |` with ⬜ Pending, 🔄 In Progress, ✅ Complete, ❌ Blocked. Work ONE task at a time. Update after each.

For parallel work across branches, add a Branch column. Always `git branch --show-current` before editing.

## 3. TDD for Bug Fixes

Write failing test FIRST → verify it fails → minimal fix → verify it passes. Tests written after code prove nothing.

## 4. Validate Before Marking Complete

Front-end: Chrome DevTools MCP. Emails: Mailhog at http://localhost:8025 (requires `task docker:up-debug`). Backend: `task docker:test:phpunit`. JS: `task docker:test:jest`. E2E: `task docker:test:playwright`.

## 5. Code Quality Review (before declaring done)

Check for: logic errors, security issues, performance problems, missing error handling. Verify consistency with existing patterns. No duplication — extract shared logic. Tests should test behavior, not implementation.

## 6. Completion Criteria

All subtasks ✅. All tests pass. Quality review done. Changes validated. No duplication introduced.

## 7. PRs

Format: Summary (bullets), Code Quality Review, Future Improvements, Test Plan. No AI attribution. Use `gh api` REST endpoints (not `gh pr edit` which uses broken GraphQL). Check for existing comments before updating PR body.

## 8. Following a Plan

Plan files in `plans/active/` are the master progress tracker. Update status markers as you go. Re-read the plan after every resume/compaction — it contains steps you may have forgotten.

## 9. Database Migrations

Laravel migrations are the source of truth. Run with `task docker:run:artisan -- migrate`. For fresh installs: `task docker:run:artisan -- migrate:fresh`.

## 10. Docker Commands

Always use `task` commands — never `docker-compose` or `docker exec` directly.

```bash
task docker:up-core                  # Start core services
task docker:shell                    # Open shell in container
task docker:run:artisan -- <cmd>     # Run artisan commands
task docker:run:bash -- "<cmd>"      # Run bash commands
task docker:test:phpunit             # Run PHP tests
```

Now analyse the user's request and create your status table to begin work.
