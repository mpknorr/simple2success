<?php
require_once __DIR__ . '/../includes/conn.php';
require_once __DIR__ . '/../includes/legal.php';
$disclaimerText = getLegalFooterSnippet($link, 'income-disclaimer');
require_once __DIR__ . '/../includes/lang.php';
$referer   = isset($referer) && is_numeric($referer) ? (int)$referer : 0;
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
<title>Simple2Success – One Clear Plan | Free Access</title>
<meta name="description" content="See the Simple2Success Eagle Team system: a clear activation path, practical tools and one next action. Free to explore; results are not guaranteed.">
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
        One Plan.<br>
        One <span class="hl">Next Step.</span>
      </h1>

      <p class="left-desc">
        A step-by-step system designed to replace guesswork with
        practical tools, team support and a routine you can repeat —
        starting with free access today.
      </p>

      <p style="margin:0 0 18px;font-size:14px;font-weight:600;color:#cb2ebc;">🦅 Mission 1000 Families: helping families build skills, consistency and a path toward a personal additional-income goal.</p>

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
            <strong>Trackable Progress</strong>
            <span>Follow account → registration → activation without guessing what comes next.</span>
          </div>
        </li>
        <li>
          <div class="feature-icon">🌍</div>
          <div class="feature-text">
            <strong>Team Support</strong>
            <span>A community focused on skills, accountability and repeatable actions.</span>
          </div>
        </li>
      </ul>

      <div class="stats-row">
        <div class="stat-item">
          <span class="num">1,000</span>
          <span class="lbl">Family Mission</span>
        </div>
        <div class="stat-item">
          <span class="num">3</span>
          <span class="lbl">Setup Steps</span>
        </div>
        <div class="stat-item">
          <span class="num">100%</span>
          <span class="lbl">Free Access</span>
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
          <span>Account</span>
        </div>
        <div class="p-line"></div>
        <div class="progress-step">
          <div class="p-dot">2</div>
          <span>Review</span>
        </div>
        <div class="p-line"></div>
        <div class="progress-step">
          <div class="p-dot">3</div>
          <span>Activate</span>
        </div>
        <div class="p-line"></div>
        <div class="progress-step">
          <div class="p-dot">4</div>
          <span>Build</span>
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
          <strong>No countdown. No pressure.</strong><br>
          See the process and decide with clear expectations.
        </div>
      </div>

      <!-- Form header -->
      <div class="form-header">
        <h2>Get Your <span style="background:linear-gradient(135deg,#a855f7,#e879f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Free Account</span></h2>
        <p>Enter your details to open your Mission Control and see the exact first step.</p>
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
        <input type="hidden" name="utm_source" value="<?= htmlspecialchars($_GET['utm_source'] ?? '') ?>">
        <input type="hidden" name="utm_medium" value="<?= htmlspecialchars($_GET['utm_medium'] ?? '') ?>">
        <input type="hidden" name="utm_campaign" value="<?= htmlspecialchars($_GET['utm_campaign'] ?? '') ?>">
        <input type="hidden" name="email_notice_version" value="mission-v2">
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">

        <div class="form-group">
          <label class="form-label" for="fname">First Name</label>
          <input class="form-input" id="fname" name="name" type="text" placeholder="Your first name" maxlength="100" required autocomplete="given-name">
        </div>

        <div class="form-group">
          <label class="form-label" for="femail">Email Address</label>
          <input class="form-input" id="femail" name="email" type="email" placeholder="your@email.com" required autocomplete="email">
        </div>

        <button type="submit" class="submit-btn">
          Show My Next Step
          <span style="font-size:1.2rem;">→</span>
        </button>
      </form>
      <?php endif; ?>

      <!-- Trust badges -->
      <div class="trust-badges">
        <div class="trust-badge"><span class="icon">✓</span> 100% Free</div>
        <div class="trust-badge"><span class="icon">✓</span> Unsubscribe Anytime</div>
        <div class="trust-badge"><span class="icon">✓</span> Instant Access</div>
      </div>

      <p style="margin:12px 0 0;font-size:11px;line-height:1.5;color:rgba(148,163,184,.55);text-align:center;">Includes account access and action-focused follow-up emails. Results, including income, are not guaranteed.</p>

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
