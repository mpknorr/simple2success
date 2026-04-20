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

if ($action === 'seed_lead_content') {
    $banner  = 'https://www.simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg';
    $ctaUrl  = 'https://www.simple2success.com/backoffice/start.php';
    $ctaBtn  = '<div style="text-align:center;margin:28px 0;"><a href="' . $ctaUrl . '" style="background:#cb2ebc;color:white;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:15px;">%s</a></div>';
    $footer  = '<tr><td style="background:#1a1a1a;padding:20px;text-align:center;color:#aaa;font-size:12px;">Copyright &copy; 2025 <a href="https://www.simple2success.com" style="color:#cb2ebc;text-decoration:none;">SIMPLE2SUCCESS</a>. All rights reserved.<br><small>You are receiving this email because you signed up at Simple2Success.</small></td></tr>';

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
        1 => [
            'subject' => 'Welcome to Simple2Success &mdash; You Just Took Step 1, {{name}}!',
            'cta'     => 'Start Step 2 Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>You just did something most people never do &mdash; you <strong>took action</strong>. While others scroll and wonder, you signed up.</p>'
                . '<p>Now there is one more step standing between you and a fully active system that works for you: <strong>complete Step 2.</strong> It takes just a few minutes and unlocks your personal referral links, your dashboard, and your earning potential.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">Momentum is everything. You have it right now &mdash; use it.</p>',
        ],
        2 => [
            'subject' => '{{name}}, while you read this, others are already getting started...',
            'cta'     => 'I Want In &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Right now, other Simple2Success members are logging in, completing Step 2, and generating their first leads &mdash; using the exact same system <strong>you already have access to</strong>.</p>'
                . '<p>The only difference between you and them? One of you clicked.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">Make Step 2 your next move &mdash; today, right now, in 5 minutes.</p>',
        ],
        3 => [
            'subject' => 'Heads up, {{name}}: You are leaving money on the table',
            'cta'     => 'Complete Step 2 Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Every day without an active account is a day your system isn&rsquo;t working. Your link is inactive. Leads that could be yours are going elsewhere.</p>'
                . '<p>This doesn&rsquo;t have to stay that way. The system is ready. Your backoffice is waiting. All it takes is <strong>one click to complete Step 2</strong>.</p>'
                . '<p style="background:#fff3e0;border-left:4px solid #ff9800;padding:12px 16px;border-radius:4px;"><strong>Opportunity cost is real.</strong> Every day of delay is a day without a system. No judgment &mdash; just a fact.</p>',
        ],
        4 => [
            'subject' => '{{name}}, you are not the type who gives up',
            'cta'     => 'I\'m Taking Action Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>The fact that you signed up says one thing loud and clear: <strong>you want more.</strong> You showed initiative where others stay passive.</p>'
                . '<p>Successful people share one habit: they act even when they don&rsquo;t have everything figured out. Completing Step 2 at Simple2Success is the moment you go <strong>from interested to activated</strong>.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">You have the potential. Step 2 is your commitment to it.</p>',
        ],
        5 => [
            'subject' => 'Just 5 minutes, {{name}} &mdash; that\'s all Step 2 takes',
            'cta'     => 'Get It Done in 5 Min &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>We built Step 2 to be as frictionless as possible. No technical knowledge required. No long forms. No complicated setup.</p>'
                . '<p>All you need: 5 minutes &middot; your device &middot; one click on the button below.</p>'
                . '<p>That&rsquo;s it. After that, your system runs &mdash; even while you sleep.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">5 minutes today &rarr; a system working for you &rarr; potential income tomorrow.</p>',
        ],
        7 => [
            'subject' => '1 week at Simple2Success &mdash; here\'s what\'s possible, {{name}}',
            'cta'     => 'Activate My Account &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>One week has passed since you signed up. In that same time, active members have generated their first leads &mdash; simply by sharing their personal link.</p>'
                . '<p>That becomes possible the moment Step 2 is complete. Your link. Your system. Your leads.</p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">Week two starts now. How do you want to use it?</p>',
        ],
        10 => [
            'subject' => 'What you haven\'t seen yet, {{name}}...',
            'cta'     => 'Show Me Everything &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Inside the Simple2Success member area, there are things waiting for you that you haven&rsquo;t discovered yet:</p>'
                . '<ul style="padding-left:20px;">'
                . '<li>Your personal referral links</li>'
                . '<li>Ready-to-use swipe copy for social media &amp; email</li>'
                . '<li>Traffic strategies that actually work</li>'
                . '<li>Your live dashboard showing leads and activity</li>'
                . '</ul>'
                . '<p>All of it unlocks <strong>after Step 2</strong>. It&rsquo;s all waiting for you.</p>',
        ],
        13 => [
            'subject' => '{{name}}, your account is still incomplete &mdash; important',
            'cta'     => 'Complete My Account Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Incomplete accounts are flagged as <strong>inactive</strong> in our system. In practice, this means leads that could come through your link are not being assigned to you.</p>'
                . '<p>All you need to do is complete Step 2 &mdash; then your account is fully active and <strong>every lead belongs to you</strong>.</p>'
                . '<p style="background:#fff3e0;border-left:4px solid #ff9800;padding:12px 16px;border-radius:4px;">Your account won&rsquo;t expire &mdash; but the opportunities you don&rsquo;t claim today won&rsquo;t come back.</p>',
        ],
        16 => [
            'subject' => 'Imagine this, {{name}}: 90 days from now...',
            'cta'     => 'My Future Starts Today &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Close your eyes for a second and picture this: 90 days from now you open your backoffice. You see leads. You see activity. You see <strong>your system doing the work</strong> &mdash; even when you aren&rsquo;t.</p>'
                . '<p>That&rsquo;s not a fantasy. That&rsquo;s what active Simple2Success members experience.</p>'
                . '<p>And it all begins with one step today: <strong>completing Step 2.</strong></p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">90 days will pass either way. The question is: what will you have built?</p>',
        ],
        19 => [
            'subject' => '{{name}}, we know why you\'re still hesitating',
            'cta'     => 'My Questions Are Answered &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>We&rsquo;ve spoken with many members who started exactly where you are. The most common concerns were:</p>'
                . '<p>&ldquo;It&rsquo;s too complicated.&rdquo; &rarr; <strong>Step 2 takes 5 minutes.</strong><br>'
                . '&ldquo;I don&rsquo;t have time.&rdquo; &rarr; <strong>The system works for you, not the other way around.</strong><br>'
                . '&ldquo;I&rsquo;m not sure it will work for me.&rdquo; &rarr; <strong>Trying costs nothing.</strong></p>'
                . '<p>And what every single one of them said afterwards? <em>&ldquo;Why did I wait so long?&rdquo;</em></p>',
        ],
        23 => [
            'subject' => 'Your community is waiting for you, {{name}}',
            'cta'     => 'Join the Community Now &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>Hundreds of active Simple2Success members are connecting, supporting each other, and <strong>celebrating wins together</strong>.</p>'
                . '<p>You&rsquo;re registered. You&rsquo;re invited. Just one step separates you from <strong>truly belonging</strong>.</p>'
                . '<p>There&rsquo;s something powerful about being part of a community of like-minded people all working toward the same goal: <strong>freedom and financial success.</strong></p>'
                . '<p style="background:#f9f0ff;border-left:4px solid #cb2ebc;padding:12px 16px;border-radius:4px;">The community is here. Step 2 is your door.</p>',
        ],
        27 => [
            'subject' => '{{name}}, almost too late...',
            'cta'     => 'I\'m In &mdash; Final Step &rarr;',
            'content' => '<h2 style="color:#cb2ebc;margin-top:0;">Hi {{name}},</h2>'
                . '<p>I&rsquo;m reaching out because we genuinely don&rsquo;t want to lose you. You signed up for a reason &mdash; that reason hasn&rsquo;t changed.</p>'
                . '<p>We&rsquo;re nearing the end of our automated sequence. That doesn&rsquo;t mean your account gets deleted &mdash; but it does mean <strong>we won&rsquo;t be actively reaching out much longer</strong>.</p>'
                . '<p style="background:#fff3e0;border-left:4px solid #ff9800;padding:12px 16px;border-radius:4px;">You still have the opportunity. Your account is ready. Step 2 is one click away.</p>',
        ],
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
    $success = "$updated lead sequences updated with professional English content.";
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
        <p class="text-muted mb-0">Automatische E-Mails an Leads und Member nach Anzahl Tage seit Registrierung</p>
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
            <div class="text-muted" style="font-size:12px;">Follow-Up E-Mails gesamt gesendet</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Cron Info -->
<div class="row px-2 mb-2">
  <div class="col-12">
    <div class="card" style="background:#1a1a2e;color:#aaa;">
      <div class="card-body py-2" style="font-size:13px;">
        <i class="ft-clock" style="color:#cb2ebc;"></i>
        <strong style="color:#fff;">Cron Job Setup:</strong>
        Richte einen stündlichen Cron ein, der folgendes aufruft:<br>
        <code style="color:#0f0;background:#111;padding:2px 8px;border-radius:4px;">/usr/bin/php <?= htmlspecialchars(realpath(__DIR__ . '/../cron/followup.php') ?: '/path/to/cron/followup.php') ?></code>
        &nbsp;oder per HTTP:&nbsp;
        <code style="color:#0f0;background:#111;padding:2px 8px;border-radius:4px;"><?= htmlspecialchars($baseurl) ?>/cron/followup.php?token=CHANGE_ME_FOLLOWUP_TOKEN</code>
        <br><small>Token in <code>cron/followup.php</code> anpassen.</small>
      </div>
    </div>
  </div>
</div>

<!-- Editor -->
<div class="row px-2">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header" style="background:#cb2ebc;">
        <h5 class="card-title m-0" style="color:white;">
          <?= $edit_seq ? '<i class="ft-edit"></i> E-Mail bearbeiten (Tag ' . $edit_seq['day_offset'] . ')' : '<i class="ft-plus-circle"></i> Neue Follow-Up E-Mail hinzufügen' ?>
        </h5>
      </div>
      <div class="card-body">
        <form method="POST" id="seqForm">
          <input type="hidden" name="action" value="save_sequence">
          <input type="hidden" name="edit_id" value="<?= $edit_seq ? $edit_seq['id'] : 0 ?>">
          <input type="hidden" name="body" id="bodyInput">

          <div class="row">
            <div class="col-md-3">
              <div class="form-group">
                <label><strong>Zielgruppe</strong></label>
                <select name="target" class="form-control">
                  <option value="lead" <?= ($edit_seq && $edit_seq['target'] === 'lead') ? 'selected' : '' ?>>Leads (Step 2 nicht abgeschlossen)</option>
                  <option value="member" <?= ($edit_seq && $edit_seq['target'] === 'member') ? 'selected' : '' ?>>Member (Step 2 abgeschlossen)</option>
                </select>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label><strong>Tag (day_offset)</strong></label>
                <input type="number" name="day_offset" min="1" max="365" class="form-control" value="<?= $edit_seq ? $edit_seq['day_offset'] : 1 ?>" required>
                <small class="text-muted">Tage seit Registrierung</small>
              </div>
            </div>
            <div class="col-md-5">
              <div class="form-group">
                <label><strong>Betreff</strong></label>
                <input type="text" name="subject" class="form-control" value="<?= htmlspecialchars($edit_seq['subject'] ?? '') ?>" placeholder="E-Mail Betreff ({{name}}, {{email}} erlaubt)" required>
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group">
                <label><strong>Aktiv</strong></label><br>
                <div class="custom-control custom-switch mt-1">
                  <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" <?= (!$edit_seq || $edit_seq['is_active']) ? 'checked' : '' ?>>
                  <label class="custom-control-label" for="is_active">Aktiviert</label>
                </div>
              </div>
            </div>
          </div>

          <!-- Editor Tabs -->
          <ul class="nav nav-tabs mb-0" id="editorTabs">
            <li class="nav-item"><a class="nav-link active" href="#" data-target="visual">Visual</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-target="source">HTML Source</a></li>
            <li class="nav-item"><a class="nav-link" href="#" data-target="preview">Vorschau</a></li>
          </ul>

          <div id="tabVisual" style="border:1px solid #ccc;border-top:none;background:#fff;">
            <div id="quillEditor" style="height:320px;font-size:14px;"></div>
          </div>
          <div id="tabSource" style="display:none;border:1px solid #ccc;border-top:none;">
            <textarea id="sourceTextarea" style="width:100%;height:340px;font-family:monospace;font-size:12px;border:none;padding:12px;" placeholder="HTML-Code hier eingeben..."></textarea>
          </div>
          <div id="tabPreview" style="display:none;border:1px solid #ccc;border-top:none;background:#f5f5f5;padding:0;">
            <iframe id="previewFrame" style="width:100%;height:400px;border:none;background:#fff;"></iframe>
          </div>

          <div class="mt-2 d-flex" style="gap:8px;">
            <button type="submit" class="btn btn-lg" style="background:#cb2ebc;color:white;" onclick="syncBodyField()">
              <i class="ft-save"></i> <?= $edit_seq ? 'Aktualisieren' : 'Speichern' ?>
            </button>
            <?php if ($edit_seq): ?>
              <a href="admin-followup.php" class="btn btn-lg btn-secondary"><i class="ft-x"></i> Abbrechen</a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Sequences: Leads -->
<div class="row px-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title m-0"><i class="ft-users" style="color:#cb2ebc;"></i> Sequenz: Leads (Step 2 nicht abgeschlossen)</h5>
        <form method="POST" onsubmit="return confirm('Alle Lead-Sequenzen mit professionellen Inhalten überschreiben?')">
          <input type="hidden" name="action" value="seed_lead_content">
          <button type="submit" class="btn btn-sm" style="background:#cb2ebc;color:white;"><i class="ft-zap"></i> Profi-Inhalte einfügen</button>
        </form>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead style="background:#222;color:#fff;">
            <tr>
              <th style="width:60px;">Tag</th>
              <th>Betreff</th>
              <th style="width:100px;">Gesendet</th>
              <th style="width:80px;">Aktiv</th>
              <th style="width:180px;">Aktionen</th>
            </tr>
          </thead>
          <tbody>
          <?php if (mysqli_num_rows($sequences_lead) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Keine Sequenzen vorhanden.</td></tr>
          <?php else: while ($seq = mysqli_fetch_assoc($sequences_lead)): ?>
            <tr <?= $edit_seq && $edit_seq['id'] == $seq['id'] ? 'style="background:#fff3e0;"' : '' ?>>
              <td><span class="badge badge-pill" style="background:#cb2ebc;color:white;font-size:13px;">Tag <?= $seq['day_offset'] ?></span></td>
              <td style="font-size:13px;"><?= htmlspecialchars($seq['subject']) ?></td>
              <td class="text-center"><span class="badge badge-pill badge-success"><?= $seq['sent_count'] ?></span></td>
              <td class="text-center">
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="tog_id" value="<?= $seq['id'] ?>">
                  <input type="hidden" name="tog_val" value="<?= $seq['is_active'] ? 0 : 1 ?>">
                  <button type="submit" class="btn btn-sm <?= $seq['is_active'] ? 'btn-success' : 'btn-secondary' ?>" title="<?= $seq['is_active'] ? 'Deaktivieren' : 'Aktivieren' ?>">
                    <i class="ft-<?= $seq['is_active'] ? 'check' : 'x' ?>"></i>
                  </button>
                </form>
              </td>
              <td>
                <a href="admin-followup.php?edit=<?= $seq['id'] ?>" class="btn btn-sm btn-primary"><i class="ft-edit"></i></a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Versandprotokoll zurücksetzen?')">
                  <input type="hidden" name="action" value="reset_log">
                  <input type="hidden" name="reset_id" value="<?= $seq['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-warning" title="Log zurücksetzen"><i class="ft-rotate-ccw"></i></button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('E-Mail wirklich löschen?')">
                  <input type="hidden" name="action" value="delete_sequence">
                  <input type="hidden" name="del_id" value="<?= $seq['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger"><i class="ft-trash-2"></i></button>
                </form>
              </td>
            </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Sequences: Members -->
<div class="row px-2">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title m-0"><i class="ft-star" style="color:#1877F2;"></i> Sequenz: Member (Step 2 abgeschlossen)</h5>
      </div>
      <div class="card-body p-0">
        <table class="table table-hover mb-0">
          <thead style="background:#222;color:#fff;">
            <tr>
              <th style="width:60px;">Tag</th>
              <th>Betreff</th>
              <th style="width:100px;">Gesendet</th>
              <th style="width:80px;">Aktiv</th>
              <th style="width:180px;">Aktionen</th>
            </tr>
          </thead>
          <tbody>
          <?php if (mysqli_num_rows($sequences_member) === 0): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Keine Member-Sequenzen vorhanden. Klicke oben auf "Neue Follow-Up E-Mail" und wähle "Member".</td></tr>
          <?php else: while ($seq = mysqli_fetch_assoc($sequences_member)): ?>
            <tr <?= $edit_seq && $edit_seq['id'] == $seq['id'] ? 'style="background:#fff3e0;"' : '' ?>>
              <td><span class="badge badge-pill" style="background:#1877F2;color:white;font-size:13px;">Tag <?= $seq['day_offset'] ?></span></td>
              <td style="font-size:13px;"><?= htmlspecialchars($seq['subject']) ?></td>
              <td class="text-center"><span class="badge badge-pill badge-success"><?= $seq['sent_count'] ?></span></td>
              <td class="text-center">
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="tog_id" value="<?= $seq['id'] ?>">
                  <input type="hidden" name="tog_val" value="<?= $seq['is_active'] ? 0 : 1 ?>">
                  <button type="submit" class="btn btn-sm <?= $seq['is_active'] ? 'btn-success' : 'btn-secondary' ?>">
                    <i class="ft-<?= $seq['is_active'] ? 'check' : 'x' ?>"></i>
                  </button>
                </form>
              </td>
              <td>
                <a href="admin-followup.php?edit=<?= $seq['id'] ?>" class="btn btn-sm btn-primary"><i class="ft-edit"></i></a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Versandprotokoll zurücksetzen?')">
                  <input type="hidden" name="action" value="reset_log">
                  <input type="hidden" name="reset_id" value="<?= $seq['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-warning" title="Log zurücksetzen"><i class="ft-rotate-ccw"></i></button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('E-Mail wirklich löschen?')">
                  <input type="hidden" name="action" value="delete_sequence">
                  <input type="hidden" name="del_id" value="<?= $seq['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger"><i class="ft-trash-2"></i></button>
                </form>
              </td>
            </tr>
          <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

</div><!-- /content-wrapper -->
</div><!-- /main-content -->

<?php require_once "../backoffice/parts/footer.php"; ?>

<button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>
</div><!-- /main-panel -->
</div><!-- /wrapper -->

<div class="sidenav-overlay"></div>
<div class="drag-target"></div>

<script src="../backoffice/app-assets/vendors/js/vendors.min.js"></script>
<script src="../backoffice/app-assets/vendors/js/quill.min.js"></script>
<script src="../backoffice/app-assets/js/core/app-menu.js"></script>
<script src="../backoffice/app-assets/js/core/app.js"></script>
<script src="../backoffice/app-assets/js/notification-sidebar.js"></script>
<script src="../backoffice/app-assets/js/scroll-top.js"></script>
<script src="../backoffice/assets/js/scripts.js"></script>

<script>
// ── Quill Editor ─────────────────────────────────────────────────────────────
var quill = new Quill('#quillEditor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ header: [1,2,3,false] }],
            ['bold','italic','underline','strike'],
            [{ color:[] },{ background:[] }],
            [{ align:[] }],
            ['link','image'],
            ['blockquote','code-block'],
            [{ list:'ordered' },{ list:'bullet' }],
            ['clean']
        ]
    }
});

// Pre-fill editor when editing
var existingBody = <?= json_encode($edit_seq ? $edit_seq['body'] : '') ?>;
if (existingBody) {
    quill.clipboard.dangerouslyPasteHTML(existingBody);
    document.getElementById('sourceTextarea').value = existingBody;
}

function syncBodyField() {
    var activeTab = document.querySelector('#editorTabs .nav-link.active').dataset.target;
    if (activeTab === 'source') {
        document.getElementById('bodyInput').value = document.getElementById('sourceTextarea').value;
    } else if (activeTab === 'preview') {
        try {
            var frame = document.getElementById('previewFrame');
            var body = frame.contentDocument && frame.contentDocument.body;
            document.getElementById('bodyInput').value = body ? body.innerHTML : document.getElementById('sourceTextarea').value;
        } catch(e) {
            document.getElementById('bodyInput').value = document.getElementById('sourceTextarea').value;
        }
    } else {
        document.getElementById('bodyInput').value = quill.root.innerHTML;
    }
}

// ── Tab switching ─────────────────────────────────────────────────────────────
document.querySelectorAll('#editorTabs .nav-link').forEach(function(tab) {
    tab.addEventListener('click', function(e) {
        e.preventDefault();

        // Sync away from current tab before switching
        var prevTab = document.querySelector('#editorTabs .nav-link.active');
        var prevTarget = prevTab ? prevTab.dataset.target : 'visual';
        if (prevTarget === 'preview') {
            // Sync iframe edits → source textarea
            var frame = document.getElementById('previewFrame');
            try {
                var body = frame.contentDocument && frame.contentDocument.body;
                if (body) document.getElementById('sourceTextarea').value = body.innerHTML;
            } catch(e) {}
        }

        document.querySelectorAll('#editorTabs .nav-link').forEach(function(t){ t.classList.remove('active'); });
        this.classList.add('active');
        var target = this.dataset.target;

        document.getElementById('tabVisual').style.display  = 'none';
        document.getElementById('tabSource').style.display  = 'none';
        document.getElementById('tabPreview').style.display = 'none';

        if (target === 'visual') {
            document.getElementById('tabVisual').style.display = 'block';
            var src = document.getElementById('sourceTextarea').value;
            if (src) quill.clipboard.dangerouslyPasteHTML(src);
        } else if (target === 'source') {
            document.getElementById('tabSource').style.display = 'block';
            document.getElementById('sourceTextarea').value = quill.root.innerHTML;
        } else if (target === 'preview') {
            document.getElementById('tabPreview').style.display = 'block';
            var srcVal = document.getElementById('sourceTextarea').value || quill.root.innerHTML;
            var frame = document.getElementById('previewFrame');
            frame.srcdoc = srcVal;
            frame.onload = function() {
                try { frame.contentDocument.designMode = 'on'; } catch(e) {}
            };
        }
    });
});

// Form submit
document.getElementById('seqForm').addEventListener('submit', function() {
    syncBodyField();
});
</script>
</body>
</html>
