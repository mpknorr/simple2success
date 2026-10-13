# Mission 1000 – rollout and remaining evidence

## Completion contract
Step 1 is a registration-link click. A saved Partner ID (`users.username`) completes the combined Step 1/Step 2 setup. No additional PM verification is required for the internal KPI. The target is at least 2% saved IDs per lead. A setup is not an income result.
The admin KPI uses all recorded leads in the selected acquisition cohort and completions so far; it is not a matured 30-day conversion estimate. Audit duplicates/source quality before interpreting it. Monthly leaderboard completions now use step2_at, not acquisition date.

## Implemented
- State-aware index/start calls to action; completed users no longer receive the Step-1 registration card. Existing click fields/events remain compatible. Step-2 corrections preserve first activation time.
- Only real leaderboard records, no administrative fake-padding toggle. Existing synthetic generator file is unused.
- Eight landing variants: mission as goal, free-account/later-cost distinction, email expectations. Unsupported membership counts, static scarcity and unverified testimonials removed where present.
- Follow-up reminders use three new template keys, seeded only if missing. Setup help follows a Step-1 click; the next-action reminder makes no claim about Step-4 purchase status. No product-order tracking exists in the inspected code, so no purchase is inferred.
- All automated follow-up sequences/triggers share a rolling 24-hour attempt limit. An atomic database reservation prevents concurrent workers from contacting the same user twice. Ambiguous failures remain reserved for 24 hours; this intentionally prefers fewer messages over duplicate sends. Welcome, newsletter and daily-lead senders are outside this cap.
- Only the current day-offset window is eligible; overdue messages are skipped. Member sequence age starts at step2_at. Legacy members with unknown dates are not backfilled or sent old onboarding.
- Three reminders take priority over scheduled sequences. Existing trigger history prevents duplicate onboarding after deployment.
- Separate configurable Reply-To in Admin > Email. Existing sender identity is preserved.
- Reviewed replacement subjects and bodies for the 20 standard sequence offsets in migration v2. Unknown custom offsets remain unchanged pending review.

## Deployment – files to upload (all in this PR)
Upload the changed PHP files from admin/, backoffice/, includes/ and link1–4/linkp1–4 together. New includes/onboarding.php, followupPolicy.php and followupTemplates.php are required. The repository diff is the authoritative file list.
Do not upload tests/ or docs/ as public application routes; keep operator material outside the public document root.

| Database item | Action | Mode |
|---|---|---|
| followup_contact_limits | Durable per-user last-attempt table | Automatically created by follow-up sender |
| email_templates | Add trigger_setup_help_v2, trigger_next_action_v2, trigger_overview_v2 if absent | Automatically seeded; existing keys remain untouched |
| followup_sequences | Replace copy for the 20 known offsets, preserving IDs, timing and active flags | Manual: migrations/mission_email_update_v2.sql |
| mission1000_v2_sequence_backup | Preserve first pre-v2 sequence snapshot by primary key | Created by manual migration |
| settings.smtp_reply_to | Set an actually monitored reply mailbox | Admin > Email |

1. Export the live templates/statistics with docs/mission1000-export-readonly.sql. No raw users table or passwords/API keys are needed.
2. Back up the database, pause follow-up cron and review custom sequences/offsets. Confirm actual production schema and MySQL/MariaDB compatibility.
3. Deploy PHP files together; run migration v2 once after review. Do not rerun migration v1 afterwards. v2 does not change the sender name. Do not apply v2 during concurrent template editing.
4. Configure the real sender and monitored Reply-To. Check Brevo domain authentication and representative inboxes.
5. Smoke-test with test accounts and a test delivery sink: new lead, click without ID, saved ID, wrong ID, late activation, old lead, opted-out lead, simultaneous cron invocations and an ambiguous provider failure.
6. Resume cron, inspect logs and observe the 24-hour cap. Compare Step-2 conversion by source, alongside complaints/unsubscribes. No live sending or production migration was performed by this change.

## Rollback
Pause cron, restore the previous code, and restore sequence subject/subject_b/body from mission1000_v2_sequence_backup by ID (do not overwrite newer content without reviewing it). Retain contact limits/logs and new template rows; old code ignores them. Keep backups outside the public document root.

## Still needed
Live templates, actual Brevo event/export counts, domain-authentication results, real country-specific registration/product/car conditions, and browser QA against a staging copy. Export is MySQL/MariaDB in this repository; a Supabase dump would only be relevant if production has since migrated. No external email was sent. No production database was accessed.

## Validation performed
PHP syntax checks passed for changed/new PHP files using PHP WASM. Fourteen deterministic behaviour/query tests passed using SQLite with a MySQL date-syntax adapter (tests/mission1000.php). The migration was exercised twice against 21 fixtures: 20 standard offsets changed, one custom offset retained, active flags retained and the original backup preserved. Public-page partner-brand scan and git diff whitespace check passed. These checks do not replace a real MySQL/MariaDB staging run or browser/inbox QA.

Hostinger is the confirmed production host. The connector skills are present, but no Hostinger account tools were exposed in this session, so account/database access could not be attempted.

## Hostinger export reconciliation (13 October 2026)
The uploaded snapshot contains all 20 expected sequence IDs/offsets and the fields needed by the changes. Active subject lines had already been softened; bodies still contained urgency/unsupported claims. The v2 migration now checks SHA-256 hashes of the 20 reviewed subjects, alternative subjects and bodies inside a locked transaction. It applies nothing if any expected row differs; reruns recognise the intended replacement text. Only the expected IDs are updated. The first backup is retained. No original template bodies, account data or credentials are committed.

Additional findings corrected: email-click.php selected a nonexistent followup_sequences.name column; it now uses subject. The Follow-up admin counts measured opens independently via opened_at and counts unique users per sequence for clicks. Matching drill-downs use the same definitions. Clicks remain unfiltered tracking events, not verified human engagement. The conversion KPI excludes admin accounts. New reminder templates are explicitly categorised as marketing.

The user confirmed on 13 October 2026 that GitHub and Hostinger FTP contain identical code. No additional production-file ZIP or source synchronisation is required. Historical database tables and trigger records alone do not prove a source mismatch. No schema dates or missing legacy step2_at values are backfilled. Do not classify suspicious traffic as bots without verification.

The user also confirmed that no genuine lead has completed Step 2 so far. Existing saved Partner IDs are not genuine acquisition conversions. The current UI count is a raw count of saved IDs excluding admins; it must not be presented as the real acquisition conversion rate until test/existing accounts are explicitly classified. The real baseline is zero confirmed genuine completions. Do not infer genuine status from an ID alone.
