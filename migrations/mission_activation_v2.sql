-- Mission 1000 Families — activation funnel + trust-first email copy (v2)
-- Run once on the live database after deploying the PHP files.
-- Safe to run repeatedly.

-- Personal sender identity. Keep the sending email/domain unchanged.
UPDATE settings
SET setting_value = 'Marc-Philipp | Simple2Success'
WHERE setting_key = 'smtp_from_name';

-- The welcome message is transactional and should state the next action plainly.
UPDATE email_templates
SET subject = '{{name}}, your Simple2Success access is ready'
WHERE template_key = 'welcome_user';

-- Behavioral state: account created, Step 1 not opened after four hours.
INSERT INTO email_templates (name, template_key, subject, body)
VALUES (
  'Trigger: Account Ready, Step 1 Not Started',
  'trigger_account_no_start',
  '{{name}}, your first step is ready',
  '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;"><table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);"><tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.75;"><h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2><p>Your Simple2Success account is ready, but Step 1 has not been opened yet.</p><p>You do not need to understand the whole system today. Open Mission Control, review the first step and decide at your own pace.</p><div style="text-align:center;margin:28px 0;"><a href="{{cta_url}}" style="background:#cb2ebc;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">Show My First Step &rarr;</a></div><p style="color:#888;font-size:13px;">Marc-Philipp | Simple2Success</p></td></tr></table></td></tr></table></body></html>'
)
ON DUPLICATE KEY UPDATE
  name = VALUES(name),
  subject = VALUES(subject),
  body = VALUES(body),
  updated_at = CURRENT_TIMESTAMP;

-- Behavioral state: official registration was opened, but no Partner ID exists.
UPDATE email_templates
SET
  subject = '{{name}}, finish the setup you started',
  body = '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head><body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;"><table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;"><table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);"><tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.75;"><h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2><p>You opened the official partner registration, but your Partner ID is not connected to Simple2Success yet.</p><p>If your registration is confirmed, copy the numeric Partner ID from your confirmation and save it in Step 2. If you have not finished registering, the same page takes you back to Step 1.</p><div style="text-align:center;margin:28px 0;"><a href="{{cta_url}}" style="background:#cb2ebc;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">Continue My Setup &rarr;</a></div><p style="color:#888;font-size:13px;">One clear next step. No pressure.<br>Marc-Philipp | Simple2Success</p></td></tr></table></td></tr></table></body></html>',
  updated_at = CURRENT_TIMESTAMP
WHERE template_key = 'trigger_clicked_not_converted';

-- Trust-first A/B subject lines. One email = one idea = one next action.
UPDATE followup_sequences SET subject='{{name}}, your first step is ready', subject_b='Your Simple2Success access is ready'
WHERE target='lead' AND day_offset=1;
UPDATE followup_sequences SET subject='The 3-step activation path, {{name}}', subject_b='What happens after your free account'
WHERE target='lead' AND day_offset=2;
UPDATE followup_sequences SET subject='A quick question, {{name}}', subject_b='What would progress change for you?'
WHERE target='lead' AND day_offset=3;
UPDATE followup_sequences SET subject='Your next step is still saved', subject_b='Continue where you left off, {{name}}'
WHERE target='lead' AND day_offset=4;
UPDATE followup_sequences SET subject='Step 1 or Step 2—which one is open?', subject_b='Need help with your setup, {{name}}?'
WHERE target='lead' AND day_offset=5;
UPDATE followup_sequences SET subject='Why this process starts with clarity', subject_b='Before you decide, read this'
WHERE target='lead' AND day_offset=6;
UPDATE followup_sequences SET subject='One week in: choose one next action', subject_b='Your Mission Control is ready, {{name}}'
WHERE target='lead' AND day_offset=7;
UPDATE followup_sequences SET subject='Can I help with Step 1 or Step 2?', subject_b='Where did the setup become unclear?'
WHERE target='lead' AND day_offset=10;
UPDATE followup_sequences SET subject='Your Simple2Success account is still ready', subject_b='Pick up your setup here, {{name}}'
WHERE target='lead' AND day_offset=13;
UPDATE followup_sequences SET subject='A 90-day plan starts with one decision', subject_b='Consistency starts smaller than you think'
WHERE target='lead' AND day_offset=16;
UPDATE followup_sequences SET subject='What is holding up your next step?', subject_b='One honest question, {{name}}'
WHERE target='lead' AND day_offset=19;
UPDATE followup_sequences SET subject='Your Mission Control is ready', subject_b='Your saved next step, {{name}}'
WHERE target='lead' AND day_offset=23;
UPDATE followup_sequences SET subject='Do you still want the next steps?', subject_b='Should we pause your onboarding emails?'
WHERE target='lead' AND day_offset=27;
UPDATE followup_sequences SET subject='One last setup reminder, {{name}}', subject_b='Your account stays yours'
WHERE target='lead' AND day_offset=30;

UPDATE followup_sequences SET subject='Activation complete—here is your next phase', subject_b='Your 90-day focus starts here, {{name}}'
WHERE target='member' AND day_offset=1;
UPDATE followup_sequences SET subject='Set up the tools you will actually use', subject_b='Keep the next phase simple, {{name}}'
WHERE target='member' AND day_offset=3;
UPDATE followup_sequences SET subject='Week one: measure actions, not hype', subject_b='Your first weekly review, {{name}}'
WHERE target='member' AND day_offset=7;
UPDATE followup_sequences SET subject='What is your biggest bottleneck?', subject_b='Improve one step before changing direction'
WHERE target='member' AND day_offset=14;
UPDATE followup_sequences SET subject='21-day consistency check', subject_b='What have you repeated for 21 days?'
WHERE target='member' AND day_offset=21;
UPDATE followup_sequences SET subject='Your 30-day progress review', subject_b='Keep, improve or remove—your monthly review'
WHERE target='member' AND day_offset=30;
