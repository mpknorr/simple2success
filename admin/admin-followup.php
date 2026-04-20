<?php
session_start();
if (empty($_SESSION["userid"]) || empty($_SESSION["is_admin"])) {
    require_once '../includes/conn.php';
    header("Location: " . $baseurl . "/backoffice/index.php");
    exit();
}
require_once '../includes/conn.php';

$userid = $_SESSION["userid"];
$getuserdetails = mysqli_query($link, "SELECT * FROM users WHERE leadid = $userid");
foreach ($getuserdetails as $userData) {
    $name        = $userData["name"];
    $username    = $userData["username"];
    $useremail   = $userData["email"];
    $paidstatus  = $userData["paidstatus"];
    $profile_pic = $userData["profile_pic"];
}

// Ensure tables exist
mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_sequences (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    target       ENUM('lead','member') NOT NULL DEFAULT 'lead',
    day_offset   INT NOT NULL DEFAULT 1,
    subject      VARCHAR(255) NOT NULL,
    body         LONGTEXT NOT NULL,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
mysqli_query($link, "CREATE TABLE IF NOT EXISTS followup_log (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    sequence_id  INT NOT NULL,
    sent_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_seq (user_id, sequence_id)
)");

// Auto-insert default lead follow-up sequence if empty
$seq_count = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_sequences"))['c'];
if ($seq_count == 0) {
    $bannerUrl = 'https://www.simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';
    $defaultDays = [1,2,3,4,5,7,10,13,16,19,23,27,30];
    $defaultSubjects = [
        1  => 'Welcome to Simple2Success — Your Journey Starts Now!',
        2  => 'Have you logged in yet? Here\'s what to do next...',
        3  => 'Your FREE Account is waiting — Complete Step 1 today',
        4  => 'Still with us? Here\'s a quick tip to get started',
        5  => 'Success loves action — take your first step today!',
        7  => 'One week in — are you ready to earn?',
        10 => '10 days — here\'s how others are succeeding',
        13 => 'Don\'t let this opportunity pass you by',
        16 => 'A message from our community...',
        19 => 'How to share your link and get leads automatically',
        23 => 'This is what\'s possible with Simple2Success',
        27 => 'Your dreams are valid — here\'s proof',
        30 => 'It\'s been 30 days. Let\'s talk.',
    ];
    foreach ($defaultDays as $day) {
        $subj = mysqli_real_escape_string($link, $defaultSubjects[$day] ?? "Follow-Up Day $day");
        $body = '<!DOCTYPE html>
<html><head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;">
  <tr><td align="center" style="padding:20px 0;">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
      <tr><td><img src="' . $bannerUrl . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>
      <tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.6;">
        <h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>
        <p>This is your Day ' . $day . ' follow-up message. Edit this template in the Admin &rarr; Follow-Up section.</p>
        <p>Log in to your backoffice and complete your success steps today!</p>
        <div style="text-align:center;margin:28px 0;">
          <a href="https://www.simple2success.com/backoffice/" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">Login to Your Backoffice</a>
        </div>
        <p>Your Simple2Success Team</p>
      </td></tr>
      <tr><td style="background:#1a1a1a;padding:20px;text-align:center;color:#aaa;font-size:12px;">
        Copyright &copy; 2024 <a href="https://www.simple2success.com" style="color:#cb2ebc;text-decoration:none;">SIMPLE2SUCCESS</a>. All rights reserved.
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>';
        $esc_body = mysqli_real_escape_string($link, $body);
        mysqli_query($link, "INSERT INTO followup_sequences (target, day_offset, subject, body, is_active)
            VALUES ('lead', $day, '$subj', '$esc_body', 1)");
    }
}

$success = '';
$error   = '';

// ── POST handling ────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($action === 'save_sequence') {
    $edit_id    = (int)($_POST['edit_id'] ?? 0);
    $target     = in_array($_POST['target'] ?? '', ['lead','member']) ? $_POST['target'] : 'lead';
    $day_offset = max(1, (int)($_POST['day_offset'] ?? 1));
    $subj       = mysqli_real_escape_string($link, $_POST['subject'] ?? '');
    $body       = mysqli_real_escape_string($link, $_POST['body'] ?? '');
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if (empty($subj) || empty($_POST['body'])) {
        $error = 'Betreff und E-Mail-Text dürfen nicht leer sein.';
    } elseif ($edit_id > 0) {
        mysqli_query($link, "UPDATE followup_sequences SET target='$target', day_offset=$day_offset, subject='$subj', body='$body', is_active=$is_active WHERE id=$edit_id");
        $success = 'E-Mail aktualisiert.';
    } else {
        mysqli_query($link, "INSERT INTO followup_sequences (target, day_offset, subject, body, is_active) VALUES ('$target', $day_offset, '$subj', '$body', $is_active)");
        $success = 'Neue Follow-Up E-Mail gespeichert.';
    }
}

if ($action === 'delete_sequence') {
    $del_id = (int)($_POST['del_id'] ?? 0);
    if ($del_id > 0) {
        mysqli_query($link, "DELETE FROM followup_sequences WHERE id=$del_id");
        mysqli_query($link, "DELETE FROM followup_log WHERE sequence_id=$del_id");
        $success = 'E-Mail gelöscht.';
    }
}

if ($action === 'toggle_active') {
    $tog_id  = (int)($_POST['tog_id'] ?? 0);
    $tog_val = (int)($_POST['tog_val'] ?? 0);
    if ($tog_id > 0) {
        mysqli_query($link, "UPDATE followup_sequences SET is_active=$tog_val WHERE id=$tog_id");
    }
    header("Location: admin-followup.php");
    exit();
}

if ($action === 'reset_log') {
    $reset_id = (int)($_POST['reset_id'] ?? 0);
    if ($reset_id > 0) {
        mysqli_query($link, "DELETE FROM followup_log WHERE sequence_id=$reset_id");
        $success = 'Versandprotokoll für diese E-Mail zurückgesetzt.';
    }
}

// ── SEED: Lead-Sequenz (Step 2 Conversion) ──────────────────────────────────
if ($action === 'seed_lead_content') {
    $banner = 'https://www.simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';
    $ctaUrl = 'https://www.simple2success.com/backoffice/start.php';
    $ctaBtn = '<div style="text-align:center;margin:28px 0;"><a href="' . $ctaUrl . '" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">%s</a></div>';
    $footer = '<tr><td style="background:#1a1a1a;padding:20px;text-align:center;color:#aaa;font-size:12px;">Copyright &copy; 2025 <a href="https://www.simple2success.com" style="color:#cb2ebc;text-decoration:none;">SIMPLE2SUCCESS</a>. All rights reserved.<br><small>You are receiving this email because you signed up at Simple2Success.</small></td></tr>';

    function makeEmail($banner, $footer, $ctaBtn, $ctaLabel, $content) {
        $cta = sprintf($ctaBtn, $ctaLabel);
        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
            . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
            . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
            . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
            . '<tr><td><img src="' . $banner . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>'
            . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.8;">' . $content . $cta . '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p></td></tr>'
            . $footer
            . '</table></td></tr></table></body></html>';
    }

    $seeds = [
        // ── DAY 1: Momentum — Handlungsimpuls direkt nach Opt-in ──────────────
        // Neurowiss.: Dopamin-Peak direkt nach Entscheidung nutzen (Honeymoon-Effekt).
        // Psychologie: Commitment-Konsistenz — wer sich als "Handelnder" definiert, handelt.
        1 => [
            'subject' => 'Welcome to Simple2Success &mdash; You Just Took Step 1, {{name}}!',
            'cta'     => 'Complete Step 2 Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>You just did something most people never do &mdash; you <strong>took action</strong>. While others scroll and wonder, you signed up.</p>'
                . '<p>Now there is one more step standing between you and a fully active system that works for you: <strong>complete Step 2.</strong> It takes just a few minutes and unlocks your personal referral links, your dashboard, and your earning potential.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;"><strong>Momentum is everything.</strong> You have it right now &mdash; use it.</p>',
        ],
        // ── DAY 2: Social Proof + FOMO — Vergleich mit aktiven Peers ──────────
        // Neurowiss.: Spiegelneuronensystem — wir orientieren uns an Gleichgesinnten.
        // Psychologie: FOMO (Fear of Missing Out) aktiviert Amygdala, erhöht Handlungsbereitschaft.
        2 => [
            'subject' => '{{name}}, while you read this, others are already getting results...',
            'cta'     => 'I Want In &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Right now, other Simple2Success members are logging in, completing Step 2, and generating their first leads &mdash; using the exact same system <strong>you already have access to</strong>.</p>'
                . '<p>The only difference between you and them? They clicked.</p>'
                . '<p>You signed up for a reason. That reason is still valid. The system is still here. The only thing missing is <strong>your next step</strong>.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">Make Step 2 your next move &mdash; today, right now, in 5 minutes.</p>',
        ],
        // ── DAY 3: Loss Aversion — stärkster psychologischer Hebel ────────────
        // Neurowiss.: Verlust-Aversion ist 2× stärker als Gewinn-Motivation (Kahneman).
        // Psychologie: Opportunity Cost konkret benennen, nicht abstrakt lassen.
        3 => [
            'subject' => 'Heads up, {{name}}: Every day without Step 2 costs you leads',
            'cta'     => 'Activate My Account Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Every day without an active account is a day your system isn&rsquo;t working. Your referral link is inactive. Leads that could be yours are going elsewhere.</p>'
                . '<p>This doesn&rsquo;t have to stay that way. The system is ready. Your backoffice is waiting. All it takes is <strong>one click to complete Step 2</strong>.</p>'
                . '<p style="background:#fff3e0;border-left:4px solid #ff9800;padding:12px 16px;border-radius:4px;"><strong>Opportunity cost is real.</strong> Every day of delay is a day without leads, without activity, without progress. The good news: you can change that in the next 5 minutes.</p>',
        ],
        // ── DAY 4: Identität — Selbstbild als Handelnder stärken ──────────────
        // Neurowiss.: Identitäts-basierte Motivation aktiviert präfrontalen Kortex nachhaltig.
        // Psychologie: "Wer du bist" ist stärker als "was du tun sollst" (James Clear).
        4 => [
            'subject' => '{{name}}, you are not the type who gives up',
            'cta'     => 'I\'m Taking Action Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>The fact that you signed up says one thing loud and clear: <strong>you want more.</strong> You showed initiative where others stay passive.</p>'
                . '<p>Successful people share one habit: they act even when they don&rsquo;t have everything figured out. Completing Step 2 at Simple2Success is the moment you go <strong>from interested to activated</strong>.</p>'
                . '<p>You don&rsquo;t need to understand everything yet. You just need to take the next step. The system will guide you from there.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">You have the potential. Step 2 is your commitment to it.</p>',
        ],
        // ── DAY 5: Friction-Reduction — Aufwand minimieren ────────────────────
        // Neurowiss.: Kognitive Last reduzieren aktiviert Basal-Ganglien (Gewohnheitsbildung).
        // Psychologie: Je kleiner der wahrgenommene Aufwand, desto höher die Conversion.
        5 => [
            'subject' => 'Just 5 minutes, {{name}} &mdash; that\'s all Step 2 takes',
            'cta'     => 'Get It Done in 5 Min &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>We built Step 2 to be as frictionless as possible. No technical knowledge required. No long forms. No complicated setup.</p>'
                . '<p>All you need:</p>'
                . '<ul style="padding-left:20px;line-height:2;">'
                . '<li>5 minutes</li>'
                . '<li>Your device</li>'
                . '<li>One click on the button below</li>'
                . '</ul>'
                . '<p>That&rsquo;s it. After that, your system runs &mdash; even while you sleep.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;"><strong>5 minutes today &rarr; a system working for you &rarr; potential income tomorrow.</strong></p>',
        ],
        // ── DAY 7: Social Proof + Zeitreferenz — Woche 1 verstrichen ──────────
        // Neurowiss.: Zeitliche Anker aktivieren das episodische Gedächtnis (Hippocampus).
        // Psychologie: Konkrete Zeitreferenz ("1 Woche") erzeugt Bewusstsein für Verzögerung.
        7 => [
            'subject' => '1 week at Simple2Success &mdash; here\'s what active members achieved, {{name}}',
            'cta'     => 'Activate My Account &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>One week has passed since you signed up. In that same time, active members have generated their first leads &mdash; simply by sharing their personal link.</p>'
                . '<p>That becomes possible the moment Step 2 is complete. Your link. Your system. Your leads.</p>'
                . '<p>Here&rsquo;s what changes after Step 2:</p>'
                . '<ul style="padding-left:20px;line-height:2;">'
                . '<li>Your personal referral link goes live</li>'
                . '<li>Your dashboard shows real-time lead activity</li>'
                . '<li>The automated follow-up system activates for your leads</li>'
                . '<li>You appear on the team leaderboard</li>'
                . '</ul>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">Week two starts now. How do you want to use it?</p>',
        ],
        // ── DAY 10: Curiosity Gap — Neugier auf ungesehene Inhalte ────────────
        // Neurowiss.: Informationslücken aktivieren den Nucleus accumbens (Belohnungssystem).
        // Psychologie: Curiosity Gap (George Loewenstein) — das Gehirn will Lücken schließen.
        10 => [
            'subject' => 'What you haven\'t seen yet inside Simple2Success, {{name}}...',
            'cta'     => 'Show Me Everything &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Inside the Simple2Success member area, there are tools and resources waiting for you that you haven&rsquo;t discovered yet:</p>'
                . '<ul style="padding-left:20px;line-height:2;">'
                . '<li><strong>Your personal referral links</strong> — ready to share immediately</li>'
                . '<li><strong>Ready-to-use swipe copy</strong> — 12 proven email templates for solo ads</li>'
                . '<li><strong>Traffic strategies</strong> — step-by-step guides that actually work</li>'
                . '<li><strong>Live dashboard</strong> — see your leads and team activity in real time</li>'
                . '<li><strong>Team leaderboard</strong> — track your progress and compete with others</li>'
                . '</ul>'
                . '<p>All of it unlocks <strong>after Step 2</strong>. It&rsquo;s all waiting for you &mdash; right now.</p>',
        ],
        // ── DAY 13: Urgency + Konsequenz — Inaktivität hat Kosten ─────────────
        // Neurowiss.: Konkrete Konsequenzen aktivieren den anterioren cingulären Kortex (Entscheidung).
        // Psychologie: "Incomplete account" framing erzeugt kognitive Dissonanz → Auflösungsdrang.
        13 => [
            'subject' => '{{name}}, your account is still incomplete &mdash; here\'s what that means',
            'cta'     => 'Complete My Account Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Incomplete accounts are flagged as <strong>inactive</strong> in our system. In practice, this means leads that could come through your link are not being assigned to you.</p>'
                . '<p>All you need to do is complete Step 2 &mdash; then your account is fully active and <strong>every lead belongs to you</strong>.</p>'
                . '<p>The process is straightforward:</p>'
                . '<ol style="padding-left:20px;line-height:2;">'
                . '<li>Log in to your backoffice</li>'
                . '<li>Follow the Step 2 instructions (takes ~5 minutes)</li>'
                . '<li>Your system goes live immediately</li>'
                . '</ol>'
                . '<p style="background:#fff3e0;border-left:4px solid #ff9800;padding:12px 16px;border-radius:4px;">Your account won&rsquo;t expire &mdash; but the opportunities you don&rsquo;t claim today won&rsquo;t come back.</p>',
        ],
        // ── DAY 16: Future Pacing — Gehirn in die Zukunft führen ──────────────
        // Neurowiss.: Mentale Simulation aktiviert dieselben Areale wie echte Erfahrung (fMRI-Studien).
        // Psychologie: Vivid future = höhere Handlungsbereitschaft (Temporal Motivation Theory).
        16 => [
            'subject' => 'Imagine this, {{name}}: 90 days from now...',
            'cta'     => 'My Future Starts Today &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Close your eyes for a second and picture this: 90 days from now you open your backoffice. You see leads. You see activity. You see <strong>your system doing the work</strong> &mdash; even when you aren&rsquo;t.</p>'
                . '<p>That&rsquo;s not a fantasy. That&rsquo;s what active Simple2Success members experience after following the steps consistently.</p>'
                . '<p>And it all begins with one step today: <strong>completing Step 2.</strong></p>'
                . '<p>The system is already built. The traffic strategies are already documented. The team is already active. You just need to activate your account.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;"><strong>90 days will pass either way.</strong> The question is: what will you have built?</p>',
        ],
        // ── DAY 19: Einwandbehandlung — häufigste Hindernisse direkt ansprechen
        // Neurowiss.: Direkte Ansprache von Zweifeln reduziert Amygdala-Aktivierung (Angst).
        // Psychologie: Pre-emptive objection handling erhöht Vertrauen und Konversionsrate.
        19 => [
            'subject' => '{{name}}, we know why you\'re still hesitating &mdash; let\'s talk',
            'cta'     => 'My Questions Are Answered &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>We&rsquo;ve spoken with many members who started exactly where you are. The most common concerns were:</p>'
                . '<p style="line-height:2.2;">'
                . '&ldquo;It&rsquo;s too complicated.&rdquo; &rarr; <strong>Step 2 takes 5 minutes. No technical skills needed.</strong><br>'
                . '&ldquo;I don&rsquo;t have time.&rdquo; &rarr; <strong>The system works for you, not the other way around.</strong><br>'
                . '&ldquo;I&rsquo;m not sure it will work for me.&rdquo; &rarr; <strong>Trying costs nothing. Your account is free.</strong><br>'
                . '&ldquo;I need to think about it.&rdquo; &rarr; <strong>You&rsquo;ve had 19 days. The thinking is done.</strong>'
                . '</p>'
                . '<p>And what every single one of them said after completing Step 2? <em>&ldquo;Why did I wait so long?&rdquo;</em></p>',
        ],
        // ── DAY 23: Belonging + Community — soziale Zugehörigkeit ─────────────
        // Neurowiss.: Soziale Ausgrenzung aktiviert dieselben Areale wie physischer Schmerz.
        // Psychologie: Belonging ist ein Grundbedürfnis (Maslow) — Community-Framing ist hocheffektiv.
        23 => [
            'subject' => 'Your community is waiting for you, {{name}}',
            'cta'     => 'Join the Community Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Hundreds of active Simple2Success members are connecting, supporting each other, and <strong>celebrating wins together</strong>.</p>'
                . '<p>You&rsquo;re registered. You&rsquo;re invited. Just one step separates you from <strong>truly belonging</strong>.</p>'
                . '<p>There&rsquo;s something powerful about being part of a community of like-minded people all working toward the same goal: <strong>financial freedom and time flexibility.</strong></p>'
                . '<p>Active members get access to the team leaderboard, community updates, and direct support from the team. All of that is one step away.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">The community is here. Step 2 is your door.</p>',
        ],
        // ── DAY 27: Scarcity of Attention — letzter aktiver Kontakt ───────────
        // Neurowiss.: Drohender Verlust von Aufmerksamkeit aktiviert Reaktanz (Brehm).
        // Psychologie: "Wir hören bald auf zu schreiben" ist stärker als "Jetzt kaufen".
        27 => [
            'subject' => '{{name}}, almost too late &mdash; important',
            'cta'     => 'I\'m In &mdash; Final Step &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>I&rsquo;m reaching out because we genuinely don&rsquo;t want to lose you. You signed up for a reason &mdash; that reason hasn&rsquo;t changed.</p>'
                . '<p>We&rsquo;re nearing the end of our automated sequence. That doesn&rsquo;t mean your account gets deleted &mdash; but it does mean <strong>we won&rsquo;t be actively reaching out much longer</strong>.</p>'
                . '<p>If there&rsquo;s anything holding you back, reply to this email and tell us. We read every reply. We want to help.</p>'
                . '<p style="background:#fff3e0;border-left:4px solid #ff9800;padding:12px 16px;border-radius:4px;">You still have the opportunity. Your account is ready. Step 2 is one click away.</p>',
        ],
        // ── DAY 30: Würde + offene Tür — kein Druck, aber Einladung ───────────
        // Neurowiss.: Autonomie-Respektierung reduziert Reaktanz, erhöht intrinsische Motivation.
        // Psychologie: "Soft close" — Entscheidung dem Nutzer überlassen, Tür offen lassen.
        30 => [
            'subject' => 'Final message, {{name}} &mdash; we respect your decision',
            'cta'     => 'I\'m Ready &mdash; Complete Step 2 &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>This is our last automated message. We respect that life sometimes takes unexpected turns.</p>'
                . '<p>But if you ever decide you&rsquo;re ready &mdash; <strong>your account is not deleted. It&rsquo;s waiting for you.</strong> No need to start over. Just log in, complete Step 2, and you&rsquo;re live.</p>'
                . '<p>We believe in the potential inside every person who signs up at Simple2Success. That belief doesn&rsquo;t expire.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">Whenever you&rsquo;re ready &mdash; Simple2Success is ready too.</p>',
        ],
    ];

    $updated = 0;
    foreach ($seeds as $day => $data) {
        $emailHtml = makeEmail($banner, $footer, $ctaBtn, $data['cta'], $data['content']);
        $esc_subj  = mysqli_real_escape_string($link, $data['subject']);
        $esc_body  = mysqli_real_escape_string($link, $emailHtml);
        $r = mysqli_query($link, "UPDATE followup_sequences SET subject='$esc_subj', body='$esc_body' WHERE target='lead' AND day_offset=$day");
        if ($r && mysqli_affected_rows($link) > 0) $updated++;
    }
    $success = "$updated lead sequences updated with optimized Step-2 conversion content.";
}

// ── SEED: Member-Sequenz (Step 4 Conversion) ────────────────────────────────
if ($action === 'seed_member_content') {
    $banner  = 'https://www.simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';
    $ctaUrl  = 'https://www.simple2success.com/backoffice/start.php';
    $ctaBtn  = '<div style="text-align:center;margin:28px 0;"><a href="' . $ctaUrl . '" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">%s</a></div>';
    $footer  = '<tr><td style="background:#1a1a1a;padding:20px;text-align:center;color:#aaa;font-size:12px;">Copyright &copy; 2025 <a href="https://www.simple2success.com" style="color:#cb2ebc;text-decoration:none;">SIMPLE2SUCCESS</a>. All rights reserved.<br><small>You are receiving this email because you are a Simple2Success member.</small></td></tr>';

    if (!function_exists('makeEmail')) {
        function makeEmail($banner, $footer, $ctaBtn, $ctaLabel, $content) {
            $cta = sprintf($ctaBtn, $ctaLabel);
            return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
                . '<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;">'
                . '<table width="100%" cellpadding="0" cellspacing="0" style="background:#f5f5f5;"><tr><td align="center" style="padding:20px 0;">'
                . '<table width="600" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);">'
                . '<tr><td><img src="' . $banner . '" width="600" alt="Simple2Success" style="display:block;width:100%;max-width:600px;"></td></tr>'
                . '<tr><td style="padding:30px 40px;color:#333;font-size:15px;line-height:1.8;">' . $content . $cta . '<p style="color:#888;font-size:13px;">Your Simple2Success Team</p></td></tr>'
                . $footer
                . '</table></td></tr></table></body></html>';
        }
    }

    $member_seeds = [
        // ── MEMBER DAY 1: Gratulation + nächster logischer Schritt ────────────
        // Neurowiss.: Positive Verstärkung direkt nach Abschluss festigt Verhalten (Dopamin).
        // Psychologie: Foot-in-the-door — wer Step 2 abschloss, ist bereit für Step 3/4.
        1 => [
            'subject' => 'Congratulations, {{name}} &mdash; Step 2 is done. Here\'s what\'s next.',
            'cta'     => 'See My Next Steps &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>You did it. Step 2 is complete and your account is now fully active. Your referral link is live. Your dashboard is tracking activity. The system is working for you.</p>'
                . '<p>Now it&rsquo;s time to look at <strong>Steps 3, 4, and 5</strong> &mdash; the steps that turn your active account into a growing income.</p>'
                . '<p><strong>Step 3</strong> is about traffic &mdash; getting people to your link consistently.<br>'
                . '<strong>Step 4</strong> is about activating your product subscription &mdash; the foundation of your long-term income.<br>'
                . '<strong>Step 5</strong> is about keeping the momentum going.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">The hardest step is behind you. The most rewarding ones are ahead.</p>',
        ],
        // ── MEMBER DAY 3: Step 4 Framing — Produkt als Fundament ─────────────
        // Neurowiss.: Eigene Erfahrung mit Produkt aktiviert Insula (Authentizität-Signal).
        // Psychologie: Wer selbst Kunde ist, empfiehlt glaubwürdiger → höhere Konversionsrate im Team.
        3 => [
            'subject' => '{{name}}, here\'s why Step 4 changes everything',
            'cta'     => 'Activate Step 4 Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>There&rsquo;s a reason Step 4 is called "Activate Your Product Start" &mdash; it&rsquo;s the step that transforms you from a referrer into a <strong>genuine product advocate</strong>.</p>'
                . '<p>When you personally use and experience the products you recommend, two things happen:</p>'
                . '<ol style="padding-left:20px;line-height:2;">'
                . '<li>Your recommendations become authentic &mdash; people feel the difference</li>'
                . '<li>You unlock the full compensation structure, including recurring bonuses</li>'
                . '</ol>'
                . '<p>Members who complete Step 4 consistently outperform those who don&rsquo;t &mdash; not because of luck, but because <strong>belief is contagious</strong>.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">Step 4 is where the system shifts from passive to powerful.</p>',
        ],
        // ── MEMBER DAY 7: Traffic-Fokus — Step 3 aktivieren ──────────────────
        // Neurowiss.: Konkrete Handlungsanweisungen reduzieren kognitive Last (Entscheidungslähmung).
        // Psychologie: Implementation Intention — "Wann ich X tue, mache ich Y" erhöht Follow-through.
        7 => [
            'subject' => 'Week 1 done, {{name}} &mdash; time to get your first leads',
            'cta'     => 'Order My Traffic &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Your first week as an active member is behind you. Now it&rsquo;s time to focus on the one thing that drives everything else: <strong>traffic.</strong></p>'
                . '<p>Step 3 gives you access to our trusted traffic sources &mdash; the same sources that active members use to generate consistent leads every week.</p>'
                . '<p>Here&rsquo;s the simple formula:</p>'
                . '<p style="background:#f0f8ff;border-left:4px solid #1877F2;padding:12px 16px;border-radius:4px;font-size:16px;font-weight:bold;">Traffic &rarr; Leads &rarr; Active Members &rarr; Income</p>'
                . '<p>You&rsquo;re already past the first two steps. Step 3 is where the engine starts running.</p>',
        ],
        // ── MEMBER DAY 14: Step 4 Urgency — Einkommensstruktur erklären ───────
        // Neurowiss.: Konkrete Zahlen aktivieren den präfrontalen Kortex stärker als abstrakte Versprechen.
        // Psychologie: Anchoring — erste genannte Zahl beeinflusst alle weiteren Bewertungen.
        14 => [
            'subject' => '{{name}}, here\'s what you\'re leaving on the table without Step 4',
            'cta'     => 'Unlock My Full Income Potential &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Two weeks in, and your system is active. But there&rsquo;s a part of the income structure that only activates with Step 4 &mdash; and it&rsquo;s significant.</p>'
                . '<p>Without Step 4:</p>'
                . '<ul style="padding-left:20px;line-height:2;">'
                . '<li>You earn referral commissions &mdash; but only at the base level</li>'
                . '<li>Your team&rsquo;s activity generates income for others above you, not for you</li>'
                . '</ul>'
                . '<p>With Step 4 active:</p>'
                . '<ul style="padding-left:20px;line-height:2;">'
                . '<li>You unlock the full compensation structure</li>'
                . '<li>Your team&rsquo;s growth directly benefits you</li>'
                . '<li>Recurring monthly income becomes possible</li>'
                . '</ul>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">The system is already running. Step 4 is what makes it run <em>for you</em>.</p>',
        ],
        // ── MEMBER DAY 21: Consistency + Compound Effect ──────────────────────
        // Neurowiss.: Gewohnheitsbildung nach ~21 Tagen (Basal-Ganglien-Automatisierung).
        // Psychologie: Compound Effect — kleine tägliche Aktionen akkumulieren sich exponentiell.
        21 => [
            'subject' => '21 days in, {{name}} &mdash; the compound effect is starting',
            'cta'     => 'Keep the Momentum Going &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>21 days. That&rsquo;s how long it takes for a new behavior to start becoming automatic &mdash; and you&rsquo;re right at that threshold.</p>'
                . '<p>The members who see the biggest results aren&rsquo;t the ones who work the hardest. They&rsquo;re the ones who stay <strong>consistent</strong>.</p>'
                . '<p>A small daily action &mdash; sharing your link, engaging with your leads, checking your dashboard &mdash; compounds over time into something significant.</p>'
                . '<p>If you haven&rsquo;t completed Step 4 yet, now is the right time. It&rsquo;s the step that makes your consistency pay off financially.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">Consistency + the right system = results. You have the system. Keep showing up.</p>',
        ],
        // ── MEMBER DAY 30: Leaderboard + Wettbewerb ───────────────────────────
        // Neurowiss.: Wettbewerb aktiviert das Belohnungssystem (Dopamin bei Rangverbesserung).
        // Psychologie: Gamification + Social Comparison erhöhen Engagement nachhaltig.
        30 => [
            'subject' => '{{name}}, check where you stand on the leaderboard',
            'cta'     => 'View My Leaderboard Position &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>One month as an active Simple2Success member. That&rsquo;s worth celebrating &mdash; and worth building on.</p>'
                . '<p>Have you checked the leaderboard lately? It shows the top performers ranked by new members and leads generated. It&rsquo;s a powerful motivator to see where you stand &mdash; and what&rsquo;s possible.</p>'
                . '<p>The members at the top share three things:</p>'
                . '<ol style="padding-left:20px;line-height:2;">'
                . '<li>They completed all 5 steps (including Step 4)</li>'
                . '<li>They send traffic consistently</li>'
                . '<li>They stay in the system long enough for the compound effect to kick in</li>'
                . '</ol>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">Month one is done. Month two is where things get interesting.</p>',
        ],
    ];

    // Insert or update member sequences
    $inserted = 0;
    $updated_m = 0;
    foreach ($member_seeds as $day => $data) {
        $emailHtml = makeEmail($banner, $footer, $ctaBtn, $data['cta'], $data['content']);
        $esc_subj  = mysqli_real_escape_string($link, $data['subject']);
        $esc_body  = mysqli_real_escape_string($link, $emailHtml);
        // Check if member sequence for this day exists
        $exists = mysqli_fetch_assoc(mysqli_query($link, "SELECT id FROM followup_sequences WHERE target='member' AND day_offset=$day"));
        if ($exists) {
            mysqli_query($link, "UPDATE followup_sequences SET subject='$esc_subj', body='$esc_body' WHERE target='member' AND day_offset=$day");
            $updated_m++;
        } else {
            mysqli_query($link, "INSERT INTO followup_sequences (target, day_offset, subject, body, is_active) VALUES ('member', $day, '$esc_subj', '$esc_body', 1)");
            $inserted++;
        }
    }
    $success = "Member-Sequenz: $inserted neue E-Mails erstellt, $updated_m aktualisiert. Step-4-Conversion-Sequenz ist jetzt aktiv.";
}

// Load sequence for editing
$edit_seq = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $edit_seq = mysqli_fetch_assoc(mysqli_query($link, "SELECT * FROM followup_sequences WHERE id=$eid"));
}

// Load all sequences
$sequences_lead   = mysqli_query($link, "SELECT f.*, (SELECT COUNT(*) FROM followup_log WHERE sequence_id=f.id) AS sent_count FROM followup_sequences f WHERE target='lead' ORDER BY day_offset ASC");
$sequences_member = mysqli_query($link, "SELECT f.*, (SELECT COUNT(*) FROM followup_log WHERE sequence_id=f.id) AS sent_count FROM followup_sequences f WHERE target='member' ORDER BY day_offset ASC");

// Stats
$total_leads   = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM users WHERE (username IS NULL OR username = '')"))['c'];
$total_members = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM users WHERE username IS NOT NULL AND username != ''"))['c'];
$total_sent    = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_log"))['c'];

// A/B + Click Tracking Stats (tables may not exist yet — safe fallback)
$ab_a_count   = 0; $ab_b_count   = 0;
$click_count  = 0; $trigger_count = 0;
$ab_table = mysqli_query($link, "SHOW TABLES LIKE 'followup_ab_assignments'");
if ($ab_table && mysqli_num_rows($ab_table) > 0) {
    $ab_a_count  = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_ab_assignments WHERE variant='A'"))['c'];
    $ab_b_count  = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_ab_assignments WHERE variant='B'"))['c'];
}
$click_table = mysqli_query($link, "SHOW TABLES LIKE 'followup_clicks'");
if ($click_table && mysqli_num_rows($click_table) > 0) {
    $click_count = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_clicks"))['c'];
}
$trigger_table = mysqli_query($link, "SHOW TABLES LIKE 'followup_trigger_log'");
if ($trigger_table && mysqli_num_rows($trigger_table) > 0) {
    $trigger_count = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS c FROM followup_trigger_log"))['c'];
}
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<link rel="stylesheet" href="../backoffice/app-assets/vendors/css/quill.snow.css">
<body class="vertical-layout vertical-menu 2-columns navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">
<?php require_once "parts/navbar.php"; ?>

<div class="wrapper">
<?php require_once "parts/sidebar.php"; ?>

<div class="main-panel">
<div class="main-content">
<div class="content-wrapper">

<!-- Page Header -->
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title m-0">Follow-Up E-Mail Sequenzen</h4>
        <p class="text-muted mb-0">Automatische E-Mails an Leads (Step-2-Conversion) und Member (Step-4-Conversion)</p>
      </div>
      <div class="card-body pt-0 pb-2">
        <div class="d-flex flex-wrap gap-2">
          <form method="POST" onsubmit="return confirm('Lead-Sequenz mit optimierten Step-2-Conversion-Texten überschreiben?');" style="display:inline;">
            <input type="hidden" name="action" value="seed_lead_content">
            <button type="submit" class="btn btn-sm btn-outline-primary mr-2">
              <i class="ft-refresh-cw mr-1"></i> Lead-Sequenz optimieren (Step 2)
            </button>
          </form>
          <form method="POST" onsubmit="return confirm('Member-Sequenz mit Step-4-Conversion-Texten erstellen/aktualisieren?');" style="display:inline;">
            <input type="hidden" name="action" value="seed_member_content">
            <button type="submit" class="btn btn-sm btn-outline-success mr-2">
              <i class="ft-refresh-cw mr-1"></i> Member-Sequenz erstellen (Step 4)
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success alert-dismissible fade show mx-2" role="alert">
  <i class="ft-check-circle"></i> <?= htmlspecialchars($success) ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show mx-2" role="alert">
  <i class="ft-alert-circle"></i> <?= htmlspecialchars($error) ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="row px-2 mb-1">
  <div class="col-lg-4 col-md-4 col-sm-12">
    <div class="card" style="border-left:4px solid #cb2ebc;">
      <div class="card-body py-2">
        <div class="d-flex align-items-center">
          <i class="ft-users" style="font-size:28px;color:#cb2ebc;margin-right:12px;"></i>
          <div>
            <div style="font-size:22px;font-weight:bold;"><?= $total_leads ?></div>
            <div class="text-muted" style="font-size:12px;">Aktive Leads (kein Step 2)</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 col-md-4 col-sm-12">
    <div class="card" style="border-left:4px solid #1877F2;">
      <div class="card-body py-2">
        <div class="d-flex align-items-center">
          <i class="ft-star" style="font-size:28px;color:#1877F2;margin-right:12px;"></i>
          <div>
            <div style="font-size:22px;font-weight:bold;"><?= $total_members ?></div>
            <div class="text-muted" style="font-size:12px;">Member (Step 2 abgeschlossen)</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4 col-md-4 col-sm-12">
    <div class="card" style="border-left:4px solid #25D366;">
      <div class="card-body py-2">
        <div class="d-flex align-items-center">
          <i class="ft-send" style="font-size:28px;color:#25D366;margin-right:12px;"></i>
          <div>
            <div style="font-size:22px;font-weight:bold;"><?= $total_sent ?></div>
            <div class="text-muted" style="font-size:12px;">E-Mails gesendet (gesamt)</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- A/B Test + Click Tracking Stats Row -->
<div class="row px-2 mb-2">
  <div class="col-12">
    <div class="card" style="border-left:4px solid #ff9800;">
      <div class="card-header py-2">
        <h6 class="card-title m-0" style="color:#ff9800;"><i class="ft-bar-chart-2 mr-1"></i> A/B-Test &amp; Verhaltens-Tracking (neu)</h6>
      </div>
      <div class="card-body py-2">
        <div class="row">
          <div class="col-lg-3 col-sm-6 mb-2">
            <div class="d-flex align-items-center">
              <span style="font-size:22px;font-weight:bold;color:#cb2ebc;margin-right:8px;"><?= $ab_a_count ?></span>
              <div><div style="font-size:12px;font-weight:600;">Variante A</div><div class="text-muted" style="font-size:11px;">Original-Betreff</div></div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 mb-2">
            <div class="d-flex align-items-center">
              <span style="font-size:22px;font-weight:bold;color:#1877F2;margin-right:8px;"><?= $ab_b_count ?></span>
              <div><div style="font-size:12px;font-weight:600;">Variante B</div><div class="text-muted" style="font-size:11px;">Curiosity-Gap-Betreff</div></div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 mb-2">
            <div class="d-flex align-items-center">
              <span style="font-size:22px;font-weight:bold;color:#25D366;margin-right:8px;"><?= $click_count ?></span>
              <div><div style="font-size:12px;font-weight:600;">Link-Klicks</div><div class="text-muted" style="font-size:11px;">Getrackte E-Mail-Klicks</div></div>
            </div>
          </div>
          <div class="col-lg-3 col-sm-6 mb-2">
            <div class="d-flex align-items-center">
              <span style="font-size:22px;font-weight:bold;color:#ff9800;margin-right:8px;"><?= $trigger_count ?></span>
              <div><div style="font-size:12px;font-weight:600;">Trigger-E-Mails</div><div class="text-muted" style="font-size:11px;">Verhaltensbasiert gesendet</div></div>
            </div>
          </div>
        </div>
        <p class="text-muted mb-0" style="font-size:11px;">A/B-Test läuft automatisch (50/50 Split). Trigger-E-Mails werden durch Klick-Verhalten ausgelöst. Klick-Tracking ist in allen neuen Follow-up-E-Mails aktiv.</p>
      </div>
    </div>
  </div>
</div>

<!-- Lead Sequences -->
<div class="row px-2 mb-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title m-0"><i class="ft-mail mr-1" style="color:#cb2ebc;"></i> Lead-Sequenz &mdash; Step-2-Conversion (<?= mysqli_num_rows($sequences_lead) ?> E-Mails)</h5>
        <small class="text-muted">Gesendet an: Nutzer ohne Step 2 (username leer)</small>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0">
          <thead><tr>
            <th style="width:60px;">Tag</th>
            <th>Betreff</th>
            <th style="width:80px;">Gesendet</th>
            <th style="width:80px;">Status</th>
            <th style="width:160px;">Aktionen</th>
          </tr></thead>
          <tbody>
          <?php while ($seq = mysqli_fetch_assoc($sequences_lead)): ?>
          <tr>
            <td><span class="badge badge-secondary">Tag <?= $seq['day_offset'] ?></span></td>
            <td style="font-size:13px;"><?= htmlspecialchars($seq['subject']) ?></td>
            <td><span class="badge badge-info"><?= $seq['sent_count'] ?></span></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="tog_id" value="<?= $seq['id'] ?>">
                <input type="hidden" name="tog_val" value="<?= $seq['is_active'] ? 0 : 1 ?>">
                <button type="submit" class="btn btn-xs <?= $seq['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                  <?= $seq['is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                </button>
              </form>
            </td>
            <td>
              <a href="admin-followup.php?edit=<?= $seq['id'] ?>" class="btn btn-xs btn-outline-primary mr-1">Bearbeiten</a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('E-Mail löschen?');">
                <input type="hidden" name="action" value="delete_sequence">
                <input type="hidden" name="del_id" value="<?= $seq['id'] ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger">Löschen</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Member Sequences -->
<div class="row px-2 mb-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title m-0"><i class="ft-star mr-1" style="color:#1877F2;"></i> Member-Sequenz &mdash; Step-4-Conversion (<?= mysqli_num_rows($sequences_member) ?> E-Mails)</h5>
        <small class="text-muted">Gesendet an: Nutzer mit Step 2 (username gesetzt) &mdash; Ziel: Step 4 Aktivierung</small>
      </div>
      <div class="card-body p-0">
        <?php if (mysqli_num_rows($sequences_member) === 0): ?>
        <div class="p-3 text-muted">
          Noch keine Member-Sequenz vorhanden. Klicke oben auf <strong>"Member-Sequenz erstellen (Step 4)"</strong> um die optimierte Step-4-Conversion-Sequenz zu aktivieren.
        </div>
        <?php else: ?>
        <table class="table table-sm mb-0">
          <thead><tr>
            <th style="width:60px;">Tag</th>
            <th>Betreff</th>
            <th style="width:80px;">Gesendet</th>
            <th style="width:80px;">Status</th>
            <th style="width:160px;">Aktionen</th>
          </tr></thead>
          <tbody>
          <?php while ($seq = mysqli_fetch_assoc($sequences_member)): ?>
          <tr>
            <td><span class="badge badge-primary">Tag <?= $seq['day_offset'] ?></span></td>
            <td style="font-size:13px;"><?= htmlspecialchars($seq['subject']) ?></td>
            <td><span class="badge badge-info"><?= $seq['sent_count'] ?></span></td>
            <td>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle_active">
                <input type="hidden" name="tog_id" value="<?= $seq['id'] ?>">
                <input type="hidden" name="tog_val" value="<?= $seq['is_active'] ? 0 : 1 ?>">
                <button type="submit" class="btn btn-xs <?= $seq['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                  <?= $seq['is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                </button>
              </form>
            </td>
            <td>
              <a href="admin-followup.php?edit=<?= $seq['id'] ?>" class="btn btn-xs btn-outline-primary mr-1">Bearbeiten</a>
              <form method="POST" style="display:inline;" onsubmit="return confirm('E-Mail löschen?');">
                <input type="hidden" name="action" value="delete_sequence">
                <input type="hidden" name="del_id" value="<?= $seq['id'] ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger">Löschen</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Edit Form -->
<?php if ($edit_seq): ?>
<div class="row px-2 mb-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title m-0">E-Mail bearbeiten &mdash; Tag <?= $edit_seq['day_offset'] ?> (<?= $edit_seq['target'] ?>)</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="save_sequence">
          <input type="hidden" name="edit_id" value="<?= $edit_seq['id'] ?>">
          <div class="form-group">
            <label>Zielgruppe</label>
            <select name="target" class="form-control">
              <option value="lead" <?= $edit_seq['target']==='lead' ? 'selected' : '' ?>>Lead (kein Step 2)</option>
              <option value="member" <?= $edit_seq['target']==='member' ? 'selected' : '' ?>>Member (Step 2 abgeschlossen)</option>
            </select>
          </div>
          <div class="form-group">
            <label>Tag-Offset (Tage nach Registrierung)</label>
            <input type="number" name="day_offset" class="form-control" value="<?= $edit_seq['day_offset'] ?>" min="1">
          </div>
          <div class="form-group">
            <label>Betreff</label>
            <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($edit_seq['subject']) ?>">
          </div>
          <div class="form-group">
            <label>E-Mail-Body (HTML)</label>
            <textarea name="body" class="form-control" rows="20" style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($edit_seq['body']) ?></textarea>
          </div>
          <div class="form-group">
            <label class="d-flex align-items-center">
              <input type="checkbox" name="is_active" value="1" <?= $edit_seq['is_active'] ? 'checked' : '' ?> class="mr-2"> Aktiv
            </label>
          </div>
          <button type="submit" class="btn btn-primary">Speichern</button>
          <a href="admin-followup.php" class="btn btn-secondary ml-2">Abbrechen</a>
          <form method="POST" style="display:inline;margin-left:8px;" onsubmit="return confirm('Versandprotokoll zurücksetzen?');">
            <input type="hidden" name="action" value="reset_log">
            <input type="hidden" name="reset_id" value="<?= $edit_seq['id'] ?>">
            <button type="submit" class="btn btn-outline-warning btn-sm">Versandlog zurücksetzen</button>
          </form>
        </form>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Add New -->
<div class="row px-2 mb-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title m-0"><i class="ft-plus mr-1"></i> Neue Follow-Up E-Mail hinzufügen</h5>
      </div>
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="action" value="save_sequence">
          <input type="hidden" name="edit_id" value="0">
          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Zielgruppe</label>
              <select name="target" class="form-control">
                <option value="lead">Lead (kein Step 2)</option>
                <option value="member">Member (Step 2 abgeschlossen)</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label>Tag-Offset</label>
              <input type="number" name="day_offset" class="form-control" value="1" min="1">
            </div>
            <div class="form-group col-md-6">
              <label>Betreff</label>
              <input type="text" name="subject" class="form-control" placeholder="E-Mail-Betreff">
            </div>
          </div>
          <div class="form-group">
            <label>E-Mail-Body (HTML)</label>
            <textarea name="body" class="form-control" rows="10" style="font-family:monospace;font-size:12px;" placeholder="HTML-Body der E-Mail..."></textarea>
          </div>
          <div class="form-group">
            <label class="d-flex align-items-center">
              <input type="checkbox" name="is_active" value="1" checked class="mr-2"> Aktiv
            </label>
          </div>
          <button type="submit" class="btn btn-success">E-Mail hinzufügen</button>
        </form>
      </div>
    </div>
  </div>
</div>

</div></div></div></div>

<?php require_once "parts/footer.php"; ?>
</body>
</html>
