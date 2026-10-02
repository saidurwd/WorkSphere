<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo seed volumes
    |--------------------------------------------------------------------------
    |
    | How many rows each high-volume seeder aims for. A demo database needs to be
    | big enough that pagination, filtering and the reports have something real to
    | chew on, but small enough that `db:seed` still finishes while someone waits.
    | These are the defaults for that middle ground; override them per environment
    | when a fuller dataset is wanted.
    |
    */

    'volumes' => [
        'projects' => 25,
        'tasks' => 600,
        'time_entries' => 900,
        'task_watchers' => 500,
        'task_transfers' => 120,
        'task_remarks' => 260,
        'task_comments' => 400,
        'task_notification_logs' => 300,
        'meetings' => 150,
        'meeting_templates' => 10,
        'meeting_recurrences' => 40,
        'meeting_versions' => 60,
        'meeting_notification_logs' => 320,
        'obligations' => 300,
        'obligation_responsibilities' => 800,
        'obligation_documents' => 600,
        'obligation_renewals' => 180,
        'obligation_activity_logs' => 900,
        'notification_logs' => 700,
        'todos' => 350,
        'todo_checklist_items' => 450,
        'todo_watchers' => 320,
        'todo_links' => 220,
        'todo_comments' => 180,
        'todo_reminders' => 260,
        'tags' => 22,
        'taggables' => 320,
        'activity_logs' => 800,
        'login_logs' => 420,
        'audit_logs' => 320,
        'notifications' => 520,
        'notification_preferences' => 1200,
        'attachments' => 180,
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo accounts
    |--------------------------------------------------------------------------
    |
    | Every seeded account shares this password. It is a development and
    | demonstration credential only: `SEED_DEMO_PASSWORD` should be set to
    | something else — or the seeding run simply skipped — for any database that
    | is reachable by anyone other than the person running it.
    |
    */

    'password' => env('SEED_DEMO_PASSWORD', 'password'),

    /*
    |--------------------------------------------------------------------------
    | Seeded employee accounts
    |--------------------------------------------------------------------------
    |
    | How many roster members get a user account. The roster in
    | `FoundationSeeder::ROSTER` is the single source of truth for both the
    | employee record and the account, so this only caps how much of it is
    | promoted to a login.
    |
    */

    'accounts' => env('SEED_ACCOUNTS', 64),

];
