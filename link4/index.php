<?php
require_once __DIR__ . '/../includes/conn.php';
require_once __DIR__ . '/../includes/legal.php';
$disclaimerText = getLegalFooterSnippet($link, 'income-disclaimer');
require_once __DIR__ . '/../includes/lang.php';
$_eae      = isset($_GET['err']) && $_GET['err'] === 'eae';
$show_form = !$_eae;
$_pg_lang  = isset($_GET['lang']) && isset($s2s_lang['err_eae'][$_GET['lang']]) ? $_GET['lang'] : 'en';
$errorMsg  = ''; // kept for backwards compat, not used for display
$source    = htmlspecialchars(isset($_GET['source']) ? $_GET['source'] : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require_once __DIR__ . '/../includes/head-tracking.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Simple2Success – Capture Page 4 | Your Income Starts Here</title>
<meta name="description" content="Join the Simple2Success Eagle Team — a proven, step-by-step system that turns daily actions into lasting income. 100% free to start.">
<link rel="apple-touch-icon" sizes="180x180" href="https://www.simple2success.com/backoffice/app-assets/img/ico/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon-16x16.png">
<link rel="shortcut icon" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon.ico">
<meta name="msapplication-TileColor" content="#9333ea">
<meta name="theme-color" content="#050508">
<link rel="stylesheet" href="<?= $baseurl ?>/link4/fonts/fonts.css">
<link rel="stylesheet" href="<?= $baseurl ?>/link4/css/style.css">
</head>
<body>

<div class="page-wrap">

  <!-- ═══════════════════════════════════════════════════════
       LEFT PANEL — Value Proposition
  ════════════════════════════════════════════════════════ -->
  <div class="left-panel">
    <div class="left-grid"></div>
    <div class="left-content">

      <div class="eagle-logo">🦅</div>

      <span class="left-label">Simple2Success · Eagle Team</span>

      <h1 class="left-h1">
        Your Income<br>
        Starts <span class="hl">Here.</span>
      </h1>

      <p class="left-desc">
        A proven, step-by-step system that gives ordinary people
        the tools, team, and traffic to build real online income —
        starting for free, today.
      </p>

      <ul class="feature-list">
        <li>
          <div class="feature-icon">🎯</div>
          <div class="feature-text">
            <strong>Clear Daily Actions</strong>
            <span>No guessing. You always know exactly what to do next.</span>
          </div>
        </li>
        <li>
          <div class="feature-icon">⚙️</div>
          <div class="feature-text">
            <strong>Done-For-You Funnel System</strong>
            <span>Professional pages and email sequences — ready to use with your link.</span>
          </div>
        </li>
        <li>
          <div class="feature-icon">💰</div>
          <div class="feature-text">
            <strong>Multiple Income Streams</strong>
            <span>Training bonuses, first-line bonuses, and deep team rewards.</span>
          </div>
        </li>
        <li>
          <div class="feature-icon">🌍</div>
          <div class="feature-text">
            <strong>Global Team in 40+ Countries</strong>
            <span>A community that supports, motivates, and duplicates your success.</span>
          </div>
        </li>
      </ul>

      <div class="stats-row">
        <div class="stat-item">
          <span class="num">10K+</span>
          <span class="lbl">Members</span>
        </div>
        <div class="stat-item">
          <span class="num">40+</span>
          <span class="lbl">Countries</span>
        </div>
        <div class="stat-item">
          <span class="num">100%</span>
          <span class="lbl">Free Start</span>
        </div>
      </div>

    </div>
  </div>

  <!-- ═══════════════════════════════════════════════════════
       RIGHT PANEL — Opt-In Form
  ════════════════════════════════════════════════════════ -->
  <div class="right-panel">
    <div class="form-card">

      <!-- Progress -->
      <div class="progress-bar">
        <div class="progress-step active">
          <div class="p-dot">1</div>
          <span>Register</span>
        </div>
        <div class="p-line"></div>
        <div class="progress-step">
          <div class="p-dot">2</div>
          <span>Activate</span>
        </div>
        <div class="p-line"></div>
        <div class="progress-step">
          <div class="p-dot">3</div>
          <span>Traffic</span>
        </div>
        <div class="p-line"></div>
        <div class="progress-step">
          <div class="p-dot">4</div>
          <span>Earn</span>
        </div>
      </div>

      <!-- Social proof mini -->
      <div class="mini-proof">
        <div class="mini-proof-avatars">
          <div class="mini-avatar">MK</div>
          <div class="mini-avatar">SL</div>
          <div class="mini-avatar">TM</div>
          <div class="mini-avatar">+</div>
        </div>
        <div class="mini-proof-text">
          <strong>47 people</strong> registered in the last 24 hours.<br>
          Spots are limited this month.
        </div>
      </div>

      <!-- Form header -->
      <div class="form-header">
        <h2>Get Your <span style="background:linear-gradient(135deg,#a855f7,#e879f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Free Account</span></h2>
        <p>Enter your details below to claim your free position in the Eagle Team and access the full system.</p>
      </div>

      <?php if ($_eae): ?>
        <div style="background:rgba(0,207,232,.08);border:1px solid rgba(0,207,232,.3);border-radius:8px;padding:16px 20px;margin:12px 0 16px;text-align:center;">
          <p style="margin:0 0 10px;font-size:15px;"><?= htmlspecialchars($s2s_lang['err_eae'][$_pg_lang]) ?></p>
          <a href="<?= rtrim($baseurl,'/') ?>/backoffice/login.php" style="display:inline-block;background:#cb2ebc;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:700;font-size:14px;"><?= htmlspecialchars($s2s_lang['login_here'][$_pg_lang]) ?></a>
        </div>
      <?php endif; ?>

      <?php if ($show_form): ?>
      <!-- Form -->
      <form method="POST" action="<?= $baseurl ?>/includes/postlead.php" target="_top">
        <input type="hidden" name="a" value="1">
        <input type="hidden" name="tr" value="">
        <input type="hidden" name="page" value="link4">
        <input type="hidden" name="lang" value="en">
        <input type="hidden" name="referer" value="<?= isset($referer) ? htmlspecialchars($referer) : '' ?>">
        <input type="hidden" name="source" value="<?= $source ?>">

        <div class="form-group">
          <label class="form-label" for="fname">First Name</label>
          <input class="form-input" id="fname" name="name" type="text" placeholder="Your first name" autocomplete="given-name">
        </div>

        <div class="form-group">
          <label class="form-label" for="femail">Email Address</label>
          <input class="form-input" id="femail" name="email" type="email" placeholder="your@email.com" required autocomplete="email">
        </div>

        <button type="submit" class="submit-btn">
          Claim My Free Position
          <span style="font-size:1.2rem;">→</span>
        </button>
      </form>
      <?php endif; ?>

      <!-- Trust badges -->
      <div class="trust-badges">
        <div class="trust-badge"><span class="icon">✓</span> 100% Free</div>
        <div class="trust-badge"><span class="icon">✓</span> No Spam</div>
        <div class="trust-badge"><span class="icon">✓</span> Instant Access</div>
      </div>

      <div class="form-divider">
        <span>Powered by</span>
      </div>

      <div style="text-align:center; font-size:0.82rem; color:rgba(148,163,184,0.5);">
        Simple2Success · Eagle Team System
      </div>

    </div>
  </div>

</div>

<!-- ═══════════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════════ -->
<footer>
  <div class="footer-links">
    <?php
    @include_once __DIR__ . '/../includes/legal.php';
    $_fb = function_exists('getLegalFooterLinks') ? getLegalFooterLinks($link) : [];
    if (!empty($_fb) && function_exists('getLegalPageUrl')):
        foreach ($_fb as $fl):
            echo '<a href="' . htmlspecialchars(getLegalPageUrl($baseurl, $fl['slug'])) . '">' . htmlspecialchars($fl['title']) . '</a>';
        endforeach;
    else: ?>
    <a href="<?= $baseurl ?>/impress.php">Legal Notice</a>
    <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy">Privacy Policy</a>
    <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use">Terms of Use</a>
    <?php endif; ?>
  </div>
  <p class="footer-copy">&copy; <?= date('Y') ?> Simple2Success. All rights reserved.</p>
  <p class="footer-disclaimer"><?= $disclaimerText ?></p>
</footer>

</body>
</html>
