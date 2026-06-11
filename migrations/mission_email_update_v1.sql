-- ════════════════════════════════════════════════════════════════════
-- Mission 1000 Families — Email Optimization v1
-- Run on local AND live DB. Idempotent (safe to run twice).
--
-- 1. Personal sender name (biggest open-rate lever)
-- 2. New subject lines A + B variants for all 20 sequences
-- 3. Trigger template subjects
-- 4. Mission paragraph injected into key emails (lead day 1,
--    member day 1, trigger clicked-not-converted)
-- ════════════════════════════════════════════════════════════════════

-- ── 1. Sender name ──────────────────────────────────────────────────
-- ADJUST THE NAME if needed — personal first name + brand:
UPDATE settings SET setting_value = 'Michael | Simple2Success'
WHERE setting_key = 'smtp_from_name';

-- ── 2. Lead sequence subjects (target Step 1 + Step 2) ──────────────
UPDATE followup_sequences SET
  subject   = '{{name}}, your system is 90% ready',
  subject_b = 'one step left, {{name}}'
WHERE target='lead' AND day_offset=1;

UPDATE followup_sequences SET
  subject   = 'while you were away, {{name}}...',
  subject_b = 'others started today, {{name}}'
WHERE target='lead' AND day_offset=2;

UPDATE followup_sequences SET
  subject   = 'every day costs you leads, {{name}}',
  subject_b = '{{name}}, this is slipping away'
WHERE target='lead' AND day_offset=3;

UPDATE followup_sequences SET
  subject   = '{{name}}, you''re not the type who quits',
  subject_b = 'I almost gave up too, {{name}}'
WHERE target='lead' AND day_offset=4;

UPDATE followup_sequences SET
  subject   = '5 minutes, {{name}}. that''s all.',
  subject_b = 'the 5-minute step, {{name}}'
WHERE target='lead' AND day_offset=5;

UPDATE followup_sequences SET
  subject   = 'why we chose this partner, {{name}}',
  subject_b = '{{name}}, the company behind your system'
WHERE target='lead' AND day_offset=6;

UPDATE followup_sequences SET
  subject   = 'week 1: what active members did, {{name}}',
  subject_b = '{{name}}, 7 days — here''s the difference'
WHERE target='lead' AND day_offset=7;

UPDATE followup_sequences SET
  subject   = 'what you haven''t seen yet, {{name}}',
  subject_b = '{{name}}, you''re missing this part'
WHERE target='lead' AND day_offset=10;

UPDATE followup_sequences SET
  subject   = '{{name}}, your account is incomplete',
  subject_b = 'something''s missing, {{name}}'
WHERE target='lead' AND day_offset=13;

UPDATE followup_sequences SET
  subject   = 'imagine: 90 days from now, {{name}}',
  subject_b = '{{name}}, picture this'
WHERE target='lead' AND day_offset=16;

UPDATE followup_sequences SET
  subject   = '{{name}}, can we be honest?',
  subject_b = 'what''s really stopping you, {{name}}?'
WHERE target='lead' AND day_offset=19;

UPDATE followup_sequences SET
  subject   = 'your team is asking about you, {{name}}',
  subject_b = '{{name}}, the team noticed'
WHERE target='lead' AND day_offset=23;

UPDATE followup_sequences SET
  subject   = '{{name}}, last chance to keep your spot',
  subject_b = 'almost too late, {{name}}'
WHERE target='lead' AND day_offset=27;

UPDATE followup_sequences SET
  subject   = 'should I close your account, {{name}}?',
  subject_b = 'is this goodbye, {{name}}?'
WHERE target='lead' AND day_offset=30;

-- ── Member sequence subjects (target Step 3–5) ──────────────────────
UPDATE followup_sequences SET
  subject   = 'you did it, {{name}} — here''s what''s next',
  subject_b = 'step 2 done. now this, {{name}}'
WHERE target='member' AND day_offset=1;

UPDATE followup_sequences SET
  subject   = '{{name}}, step 4 changes everything',
  subject_b = 'the step most people skip, {{name}}'
WHERE target='member' AND day_offset=3;

UPDATE followup_sequences SET
  subject   = 'week 1 done — time for your first leads',
  subject_b = '{{name}}, ready for your first leads?'
WHERE target='member' AND day_offset=7;

UPDATE followup_sequences SET
  subject   = '{{name}}, you''re leaving money on the table',
  subject_b = 'what step 4 is worth, {{name}}'
WHERE target='member' AND day_offset=14;

UPDATE followup_sequences SET
  subject   = '21 days in — the compound effect, {{name}}',
  subject_b = '{{name}}, momentum check'
WHERE target='member' AND day_offset=21;

UPDATE followup_sequences SET
  subject   = 'where do you stand, {{name}}?',
  subject_b = '{{name}}, your leaderboard position'
WHERE target='member' AND day_offset=30;

-- ── 3. Behavioral trigger subjects ──────────────────────────────────
UPDATE email_templates SET subject = '{{name}}, one field away from done'
WHERE template_key = 'trigger_clicked_not_converted';

UPDATE email_templates SET subject = '{{name}}, step 2 done — one thing missing'
WHERE template_key = 'trigger_step2_done_no_step4';

UPDATE email_templates SET subject = '{{name}}, 2 minutes that explain everything'
WHERE template_key = 'trigger_no_video_watched';

-- ── 4. Mission paragraph in key emails (idempotent) ─────────────────
-- Lead day 1 (welcome):
UPDATE followup_sequences SET body = REPLACE(body,
  '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p>',
  '<p style="background:#faf0fc;border-left:4px solid #cb2ebc;padding:14px 18px;border-radius:4px;font-size:14px;line-height:1.8;"><strong>Why we do this:</strong> Our mission is to help 1,000 families build real freedom &mdash; $1,000+ per month, more time, more life. Your family could be one of them. It starts with one step &mdash; not with another new system.</p><p style="color:#888;font-size:13px;">Your Simple2Success Team</p>')
WHERE target='lead' AND day_offset=1 AND body NOT LIKE '%1,000 families%';

-- Member day 1 (step 2 congratulations — identity):
UPDATE followup_sequences SET body = REPLACE(body,
  '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p>',
  '<p style="background:#faf0fc;border-left:4px solid #cb2ebc;padding:14px 18px;border-radius:4px;font-size:14px;line-height:1.8;"><strong>Welcome to Mission 1000:</strong> Your family is now officially one of the 1,000 families we are helping build real freedom. You stopped searching. You started building. That is the difference.</p><p style="color:#888;font-size:13px;">Your Simple2Success Team</p>')
WHERE target='member' AND day_offset=1 AND body NOT LIKE '%Mission 1000%';

-- Trigger: clicked but not converted (Step 1 without Step 2):
UPDATE email_templates SET body = REPLACE(body,
  '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p>',
  '<p style="background:#faf0fc;border-left:4px solid #cb2ebc;padding:14px 18px;border-radius:4px;font-size:14px;line-height:1.8;"><strong>One more thing:</strong> 1,000 families will build their freedom with this system. The only difference between them and everyone else? They finished the setup. You are one field away.</p><p style="color:#888;font-size:13px;">Your Simple2Success Team</p>')
WHERE template_key='trigger_clicked_not_converted' AND body NOT LIKE '%1,000 families%';
