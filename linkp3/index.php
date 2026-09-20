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
$referer   = isset($referer) && is_numeric($referer) ? (int)$referer : 0;
$_eae      = isset($_GET['err']) && $_GET['err'] === 'eae';
$show_form = !$_eae;
$_pg_lang  = isset($_GET['lang']) && isset($s2s_lang['err_eae'][$_GET['lang']]) ? $_GET['lang'] : 'en';
$errorMsg  = ''; // kept for backwards compat
$source    = htmlspecialchars($_GET['source'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require_once __DIR__ . '/../includes/head-tracking.php'; ?>
  <meta charset="utf-8">
  <title>Explore Simple2Success — One Clear First Step</title>
  <meta content="Explore a clear Simple2Success activation path, practical tools and a first action you can review at your own pace." name="description">
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
            <div class="s2s-form-eyebrow">Your free Simple2Success access is ready:</div>
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
        <input type="hidden" name="utm_source" value="<?= htmlspecialchars($_GET['utm_source'] ?? '') ?>">
        <input type="hidden" name="utm_medium" value="<?= htmlspecialchars($_GET['utm_medium'] ?? '') ?>">
        <input type="hidden" name="utm_campaign" value="<?= htmlspecialchars($_GET['utm_campaign'] ?? '') ?>">
        <input type="hidden" name="email_notice_version" value="mission-v2">
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">
        <div class="s2s-form-prompt">Where Should We Send The Info?</div>
        <input type="text" class="s2s-input-field name" maxlength="100" name="name" placeholder="Your First Name" required autocomplete="given-name">
        <input type="email" class="s2s-input-field" maxlength="256" name="email" placeholder="Your Best Email" required autocomplete="email">
        <input type="submit" value="Show Me My First Step!" class="s2s-btn">
            <p style="margin:10px 0 0;font-size:12px;opacity:.75;text-align:center;">✓ 100% free &nbsp;·&nbsp; ✓ No credit card &nbsp;·&nbsp; ✓ Takes 60 seconds</p>
            <p style="margin:7px 0 0;font-size:11px;line-height:1.45;opacity:.58;text-align:center;">Includes account access and action-focused follow-up emails. Unsubscribe anytime. No earnings guarantee.</p>
      </form>
      <?php endif; ?>
      <div class="w-form-done">
        <div>Thank you! Check your email for next steps.</div>
      </div>
      <div class="w-form-fail">
        <div>Oops! Something went wrong. Please try again.</div>
      </div>
      <div class="s2s-privacy-note">
        <div class="s2s-privacy-text"><strong class="s2s-bold">Privacy: You receive account access and action-focused follow-up. Unsubscribe at any time.</strong></div>
      </div>
      <div class="s2s-secure-badge"></div>
    </div>
  </div>

  <div class="s2s-main-content">
    <div class="s2s-logo-banner"></div>
    <div class="s2s-title-bar">
      <div class="s2s-section-title">Explore Simple2Success One Step at a Time</div>
    </div>
    <div class="s2s-perks-banner">
      <div class="s2s-description-wrap">
        <p class="s2s-tagline">See a clear, step-by-step system designed to replace guesswork with one practical next action — 100% free to explore.</p>
        <p style="margin:8px 0 0;font-size:14px;font-weight:600;color:#cb2ebc;">🦅 Mission 1000 Families: one clear system, real support and a path toward a personal additional-income goal.</p>
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
            <div class="s2s-benefit-text">Practical Onboarding Support</div>
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
            <div class="s2s-benefit-text">See Your Next Action Immediately</div>
          </div>
        </div>
        <div class="s2s-benefit-item">
          <div class="s2s-check-icon"></div>
          <div>
            <div class="s2s-benefit-text">Understand Partner Compensation Before You Decide</div>
          </div>
        </div>
        <div class="s2s-benefit-item">
          <div class="s2s-check-icon"></div>
          <div>
            <div class="s2s-benefit-text">Build Skills and a Routine You Can Repeat</div>
          </div>
        </div>
      </div>
    </div>
    <div class="s2s-timer-wrap">
      <div class="s2s-timer-section">
        <div class="s2s-team-label">No countdown. No fake scarcity. Review the system and decide when you are ready.</div>
      </div>
    </div>
    <div class="s2s-cta-row">
      <div class="s2s-arrow-left"></div>
      <a href="#" class="s2s-main-btn" id="s2s-open-modal">Show Me the System</a>
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
  })();
  </script>
</body>
</html>
