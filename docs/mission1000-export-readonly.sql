-- Read-only MySQL/MariaDB exports. No credentials, names or email addresses requested.
-- Result 1: active copy/schedules for review (content may include a sender signature).
SELECT id, target, day_offset, subject, subject_b, body, is_active
FROM followup_sequences ORDER BY target, day_offset, id;
SELECT template_key, subject, body FROM email_templates
WHERE template_key LIKE 'trigger_%' OR template_key = 'welcome_user';
-- Result 2: daily aggregate send outcomes (status-derived, not independent event counts).
SELECT DATE(sent_at) AS sent_day, sequence_id, variant, status, COUNT(*) AS messages
FROM followup_log WHERE sent_at >= NOW() - INTERVAL 90 DAY
GROUP BY DATE(sent_at), sequence_id, variant, status;
SELECT DATE(sent_at) AS sent_day, trigger_type, status, COUNT(*) AS messages
FROM followup_trigger_log WHERE sent_at >= NOW() - INTERVAL 90 DAY
GROUP BY DATE(sent_at), trigger_type, status;
-- Result 3: source quality. Suppress tiny groups before sharing if needed.
SELECT DATE(timestamp) AS lead_day, source, page, lang, country_detected,
 COUNT(*) AS leads, SUM(step1_at IS NOT NULL) AS step1_clicks,
 SUM(username IS NOT NULL AND username != '') AS saved_partner_ids,
 SUM(username IS NOT NULL AND username != '' AND step2_at <= timestamp + INTERVAL 30 DAY) AS saved_within_30_days
FROM users WHERE timestamp >= NOW() - INTERVAL 90 DAY
GROUP BY DATE(timestamp), source, page, lang, country_detected;
-- Result 4: missing legacy activation dates (do not guess dates in the migration).
SELECT COUNT(*) AS members_without_activation_date FROM users
WHERE username IS NOT NULL AND username != '' AND step2_at IS NULL;
