# Operational Procedures

Runbooks for infrequent admin tasks.

---

## Deleting a Group Created in Error

Groups are occasionally created by mistake and need to be removed. Follow these checks before proceeding.

### Pre-flight checks

- **Reason:** dormant groups are usually kept as historical placeholders rather than deleted. Only delete if the group was genuinely created in error.
- **Past events:** if the group has any events with volunteer or device data, preserve it unless circumstances are exceptional.
- **Upcoming events:** if events exist, the host must notify RSVP'd participants before deletion.
- **Volunteers:** remove all volunteers via the Restarters interface before running the SQL below.

### Deletion (requires DB access)

Open a DB connection (see `docs/fly-deployment.md` — Database Access):

```sql
-- 1. Verify no user associations remain
SELECT * FROM users_groups WHERE group = <group_id>;

-- 2. Verify no events are linked
SELECT * FROM events WHERE group = <group_id>;

-- 3. Remove user-group relationships
DELETE FROM users_groups WHERE group = <group_id>;

-- 4. Remove network associations
DELETE FROM group_network WHERE group_id = <group_id>;

-- 5. Remove group tags
DELETE FROM group_tags WHERE group_id = <group_id>;

-- 6. Delete the group
DELETE FROM groups WHERE idgroups = <group_id>;
```

### WordPress cleanup

If the group was synced to therestartproject.org, a WordPress administrator must separately remove the corresponding content — there is no automatic cleanup.
