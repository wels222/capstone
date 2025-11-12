# Archiving (Soft Delete) Behavior

This update replaces destructive deletes with archiving across core entities (Users, Events, Tasks).

What changed:

- All Delete buttons are now Archive if the record is active.
- Archived records show a badge and expose Restore and Delete (permanent) actions.
- Active lists hide archived items for employees; admin views include archived with state.

Database columns added (idempotent):

- is_archived TINYINT(1) NOT NULL DEFAULT 0
- archived_at DATETIME NULL

Tables covered:

- users, events, tasks

New/updated API endpoints:

- api/delete_event.php: now archives events
- api/restore_event.php: restores archived event
- api/delete_event_permanent.php: permanently deletes event
- api/tasks_delete.php: now archives a task
- api/tasks_restore.php: restores archived task
- api/tasks_delete_permanent.php: permanently deletes task
- api/super_admin_users.php: actions archive, restore, delete_permanent
- api/super_admin_events.php: actions archive, restore, delete_permanent

Listing endpoints updated to include archived state or exclude archived by default:

- api/get_events.php returns is_archived; employee and dashboards filter archived out in UI
- api/tasks_list.php returns is_archived for dept head view
- api/tasks_list_employee.php excludes archived tasks
- api/get_employees.php and api/get_users.php exclude archived users by default

Migration:

- Run once in browser: /api/migrate_add_archiving.php
  This will add the new columns if they don't already exist. All changes are idempotent and safe to re-run.

Notes:

- Existing URLs that previously deleted now archive instead, preserving compatibility.
- Permanent delete remains available only where explicitly exposed in admin/owner UIs.
