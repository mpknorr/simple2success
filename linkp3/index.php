<?php
require_once __DIR__ . '/../includes/conn.php';
require_once __DIR__ . '/../includes/legal.php';
$_premSnips = function_exists('getLegalPremiumSnippets') ? getLegalPremiumSnippets($link) : [];
if (!empty($_premSnips)) {
    $disclaimerText = '';
    foreach ($_premSnips as $ps) {
        $disclaimerText .= '<div>' . $ps['footer_snippet'] . '</div>';
    }
} else {
    $disclaimerText = getLegalFooterSnippet($link, 'income-disclaimer');
}
require_once __DIR__ . '/../includes/lang.php';
$_eae      = isset($_GET['err']) && $_GET['err'] === 'eae';
$show_form = !$_eae;
$_pg_lang  = isset($_GET['lang']) && isset($s2s_lang['err_eae'][$_GET['lang']]) ? $_GET['lang'] : 'en';
$errorMsg  = ''; // kept for backwards compat
$source    = htmlspecialchars($_GET['source'] ?? '');
$countdownSeconds = 48 * 3600;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require_once __DIR__ . '/../includes/head-tracking.php'; ?>
  <meta charset="utf-8">
  <title>Join Simple2Success — Don't Miss This</title>
  <meta content="Take the first step towards achieving your dreams. Join an exclusive Simple2Success team of experts who will help propel you towards unparalleled success." name="description">
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="shortcut icon" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon.ico">
  <link href="<?= $baseurl ?>/linkp3/css/normalize.css" rel="stylesheet" type="text/css">
  <link href="<?= $baseurl ?>/linkp3/css/components.css" rel="stylesheet" type="text/css">
  <link href="<?= $baseurl ?>/linkp3/css/s2s-smpl.css" rel="stylesheet" type="text/css">
  <link href="<?= $baseurl ?>/linkp3/fonts/fonts.css" rel="stylesheet" type="text/css">
</head>
<body class="s2s-body">
  <div class="s2s-modal" style="display:none;">
    <div class="s2s-close-btn"></div>
    <div class="s2s-form-box w-form">
      <div class="s2s-form-header">
        <div class="s2s-header-inner">
          <div class="s2s-eyebrow-wrap">
            <div class="s2s-form-eyebrow">Your sponsor has reserved your free access:</div>
          </div>
          <div class="s2s-heading-wrap">
            <h1 class="s2s-modal-heading">You&#x27;re One Step Away From Your Free Account</h1>
          </div>
        </div>
      </div>
      <div class="s2s-icon-wrap">
        <div class="s2s-icon-badge grade-2 popup">
          <div class="s2s-arrow-icon"></div>
        </div>
      </div>

      <?php if ($_eae): ?>
        <div style="background:rgba(0,207,232,.08);border:1px solid rgba(0,207,232,.3);border-radius:8px;padding:16px 20px;margin:12px 0 16px;text-align:center;">
          <p style="margin:0 0 10px;font-size:15px;"><?= htmlspecialchars($s2s_lang['err_eae'][$_pg_lang]) ?></p>
          <a href="<?= rtrim($baseurl,'/') ?>/backoffice/login.php" style="display:inline-block;background:#cb2ebc;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:700;font-size:14px;"><?= htmlspecialchars($s2s_lang['login_here'][$_pg_lang]) ?></a>
        </div>
      <?php endif; ?>
      <?php if ($show_form): ?>
      <form method="POST" action="<?= $baseurl ?>/includes/postlead.php" class="s2s-form" target="_top">
        <input type="hidden" name="a" value="1">
        <input type="hidden" name="tr" value="">
        <input type="hidden" name="page" value="linkp3">
        <input type="hidden" name="lang" value="en">
        <input type="hidden" name="referer" value="<?= $referer ?>">
        <input type="hidden" name="source" value="<?= $source ?>">
        <div class="s2s-form-prompt">Where Should We Send The Info?</div>
        <input type="text" class="s2s-input-field name" maxlength="256" name="name" placeholder="Your First Name" autocomplete="given-name">
        <input type="email" class="s2s-input-field" maxlength="256" name="email" placeholder="Your Best Email" required autocomplete="email">
        <input type="submit" value="Claim My Free Position!" class="s2s-btn">
      </form>
      <?php endif; ?>
      <div class="w-form-done">
        <div>Thank you! Check your email for next steps.</div>
      </div>
      <div class="w-form-fail">
        <div>Oops! Something went wrong. Please try again.</div>
      </div>
      <div class="s2s-privacy-note">
        <div class="s2s-privacy-text"><strong class="s2s-bold">Privacy Policy: We hate spam and promise to keep your email address safe.</strong></div>
      </div>
      <div class="s2s-secure-badge"></div>
    </div>
  </div>

  <div class="s2s-main-content">
    <div class="s2s-logo-banner"></div>
    <div class="s2s-title-bar">
      <div class="s2s-section-title">Join Simple2Success &amp; Soar to New Heights!</div>
    </div>
    <div class="s2s-perks-banner">
      <div class="s2s-description-wrap">
        <p class="s2s-tagline">Join a proven step-by-step system used by 10,000+ members in 40+ countries to build real income online — 100% free to start.</p>
      </div>
    </div>
    <div class="s2s-benefits-grid">
      <div class="s2s-benefits-col-left">
        <div class="s2s-benefit-item">
          <div class="s2s-check-icon"></div>
          <div>
            <div class="s2s-benefit-text">100% FREE Team System</div>
          </div>
        </div>
        <div class="s2s-benefit-item">
          <div class="s2s-check-icon"></div>
          <div>
            <div class="s2s-benefit-text">Decades of Experience</div>
          </div>
        </div>
        <div class="s2s-benefit-item">
          <div class="s2s-check-icon"></div>
          <div>
            <div class="s2s-benefit-text">The Power of a Team</div>
          </div>
        </div>
      </div>
      <div class="s2s-benefits-col-right">
        <div class="s2s-benefit-item">
          <div class="s2s-check-icon"></div>
          <div>
            <div class="s2s-benefit-text">Get Your First Leads Within 7 Days</div>
          </div>
        </div>
        <div class="s2s-benefit-item">
          <div class="s2s-check-icon"></div>
          <div>
            <div class="s2s-benefit-text">Earn Commissions on Every New Team Member</div>
          </div>
        </div>
        <div class="s2s-benefit-item">
          <div class="s2s-check-icon"></div>
          <div>
            <div class="s2s-benefit-text">Build a Residual Income That Grows Without You</div>
          </div>
        </div>
      </div>
    </div>
    <div class="s2s-timer-wrap">
      <div class="s2s-timer-section">
        <div class="s2s-team-label">Your sponsor has reserved a spot for you — this reservation expires in:</div>
        <div class="s2s-countdown-row">
          <div class="s2s-time-unit">
            <div class="s2s-time-circle">
              <div class="s2s-time-inner">
                <div class="s2s-time-number" id="s2s-hours">00</div>
              </div>
            </div>
            <div class="s2s-time-label">Hrs</div>
          </div>
          <div class="s2s-time-unit">
            <div class="s2s-time-circle">
              <div class="s2s-time-inner">
                <div class="s2s-time-number" id="s2s-minutes">10</div>
              </div>
            </div>
            <div class="s2s-time-label">Min</div>
          </div>
          <div class="s2s-time-unit">
            <div class="s2s-time-circle">
              <div class="s2s-time-inner">
                <div class="s2s-time-number" id="s2s-seconds">00</div>
              </div>
            </div>
            <div class="s2s-time-label">Sec</div>
          </div>
        </div>
      </div>
    </div>
    <div class="s2s-cta-row">
      <div class="s2s-arrow-left"></div>
      <a href="#" class="s2s-main-btn" id="s2s-open-modal">Claim My Free Position!</a>
      <div class="s2s-arrow-right"></div>
    </div>
  </div>

  <div class="s2s-footer">
    <div class="s2s-disclaimer-box">
      <div class="s2s-disclaimer"><?= $disclaimerText ?></div>
      <div class="s2s-divider"></div>
    </div>
    <div class="s2s-footer-nav">
      <div class="w-clearfix">
        <p class="s2s-footer-para">&copy; <?= date('Y') ?> Simple2Success. All Rights Reserved.</p>
      </div>
      <div class="w-clearfix">
        <p class="s2s-footer-para right">
          <?php
          $_fb = function_exists('getLegalFooterLinks') ? getLegalFooterLinks($link) : [];
          if (!empty($_fb) && function_exists('getLegalPageUrl')):
              foreach ($_fb as $i => $fl):
                  echo $i > 0 ? ' | ' : '';
                  echo '<a href="' . htmlspecialchars(getLegalPageUrl($baseurl, $fl['slug'])) . '" class="s2s-footer-link">' . htmlspecialchars($fl['title']) . '</a>';
              endforeach;
          else: ?>
          <a href="<?= $baseurl ?>/impress.php" class="s2s-footer-link">Legal Notice</a> |
          <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy" class="s2s-footer-link">Privacy Policy</a> |
          <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use" class="s2s-footer-link">Terms of Use</a>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>

  <script src="<?= $baseurl ?>/linkp3/js/jquery-3.5.1.min.js" type="text/javascript"></script>
  <script>
  (function() {
    // Open modal when CTA button clicked
    var openBtn = document.getElementById('s2s-open-modal');
    var modal   = document.querySelector('.s2s-modal');
    var closeBtn = document.querySelector('.s2s-close-btn');
    if (openBtn && modal) {
      openBtn.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'flex';
      });
    }
    if (closeBtn && modal) {
      closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
      });
    }
    // Countdown timer (48h, stored in sessionStorage)
    var KEY = 's2s_cd_end_p3';
    var DURATION = <?= $countdownSeconds ?>;
    var endTime = sessionStorage.getItem(KEY);
    if (!endTime) {
      endTime = Date.now() + DURATION * 1000;
      sessionStorage.setItem(KEY, endTime);
    } else {
      endTime = parseInt(endTime, 10);
    }
    function pad(n) { return n < 10 ? '0' + n : n; }
    function tick() {
      var remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
      var h = Math.floor(remaining / 3600);
      var m = Math.floor((remaining % 3600) / 60);
      var s = remaining % 60;
      var elH = document.getElementById('s2s-hours');
      var elM = document.getElementById('s2s-minutes');
      var elS = document.getElementById('s2s-seconds');
      if (elH) elH.textContent = pad(h);
      if (elM) elM.textContent = pad(m);
      if (elS) elS.textContent = pad(s);
      if (remaining > 0) setTimeout(tick, 1000);
    }
    tick();
  })();
  </script>
</body>
</html>
