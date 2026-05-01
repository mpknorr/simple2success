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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require_once __DIR__ . '/../includes/head-tracking.php'; ?>
  <meta charset="utf-8">
  <title>Ignite Your Eagle Journey — Simple2Success</title>
  <meta content="Soar Above the Rest with the Simple2Success Team. Free system, real results." name="description">
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="shortcut icon" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon.ico">
  <link href="<?= $baseurl ?>/linkp1/css/normalize.css" rel="stylesheet" type="text/css">
  <link href="<?= $baseurl ?>/linkp1/css/components.css" rel="stylesheet" type="text/css">
  <link href="<?= $baseurl ?>/linkp1/css/s2s-cp.css?v=<?= filemtime(__DIR__ . '/css/s2s-cp.css') ?>" rel="stylesheet" type="text/css">
  <link href="<?= $baseurl ?>/linkp1/fonts/fonts.css" rel="stylesheet" type="text/css">
</head>
<body class="s2s-body">
  <div class="s2s-section">
    <div class="s2s-wrap">
      <div class="s2s-content">
        <div class="s2s-logo-wrap"></div>
        <h1 class="s2s-heading">Ignite Your Eagle Journey</h1>
        <div class="s2s-tagline">Soar Above the Rest with the Simple2Success Team!<br></div>
        <div class="s2s-subline">Start Building Your Income Online — Step by Step, 100% Free</div>
        <div class="s2s-form-outer w-form">
          <?php if ($_eae): ?>
            <div style="background:rgba(0,207,232,.08);border:1px solid rgba(0,207,232,.3);border-radius:8px;padding:16px 20px;margin:12px 0 16px;text-align:center;">
              <p style="margin:0 0 10px;font-size:15px;"><?= htmlspecialchars($s2s_lang['err_eae'][$_pg_lang]) ?></p>
              <a href="<?= rtrim($baseurl,'/') ?>/backoffice/login.php" style="display:inline-block;background:#cb2ebc;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:700;font-size:14px;"><?= htmlspecialchars($s2s_lang['login_here'][$_pg_lang]) ?></a>
            </div>
          <?php endif; ?>
          <?php if ($show_form): ?>
          <form method="POST" class="s2s-form" action="<?= $baseurl ?>/includes/postlead.php" target="_top">
            <input type="hidden" name="a" value="1">
            <input type="hidden" name="tr" value="">
            <input type="hidden" name="page" value="linkp1">
            <input type="hidden" name="lang" value="en">
            <input type="hidden" name="referer" value="<?= $referer ?>">
            <input type="hidden" name="source" value="<?= $source ?>">
            <div class="s2s-inputs">
              <div class="s2s-input-wrap"><input type="text" class="s2s-input-base s2s-input" maxlength="256" name="name" placeholder="Your First Name" autocomplete="given-name"></div>
              <div class="s2s-input-wrap"><input type="email" class="s2s-input-base s2s-input" maxlength="256" name="email" placeholder="Your Best Email" required autocomplete="email"></div>
            </div>
            <input type="submit" value="Claim My Free Position →" class="s2s-btn">
          </form>
          <?php endif; ?>
        </div>
        <div class="s2s-cta-label">
          <div class="s2s-cta-hint">Lock-In My FREE Position Now!</div>
        </div>
        <div class="s2s-benefit-row">
          <div class="s2s-check-icon topcheck"></div>
          <div class="s2s-benefit-text top">Free Cutting-Edge Team System</div>
        </div>
        <div class="s2s-benefit-row">
          <div class="s2s-check-icon"></div>
          <div class="s2s-benefit-text">A Full-Time Income From Home</div>
        </div>
        <div class="s2s-benefit-row">
          <div class="s2s-check-icon"></div>
          <div class="s2s-benefit-text">Recurring Monthly Income Potential</div>
        </div>
        <div class="s2s-benefit-row last">
          <div class="s2s-check-icon"></div>
          <div class="s2s-benefit-text">Multiple Income Streams</div>
        </div>
        <div style="margin-top:14px;padding-top:12px;border-top:1px solid rgba(255,255,255,.1);font-size:.75rem;opacity:.45;text-align:center;">
          Trusted Team System &mdash; Active in 40+ Countries &mdash; Free to Join
        </div>
      </div>
      <div class="s2s-bg-col"></div>
    </div>
  </div>
  <div class="s2s-footer">
    <div class="s2s-disclaimer-box">
      <div class="s2s-disclaimer"><?= $disclaimerText ?></div>
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
  <script src="<?= $baseurl ?>/linkp1/js/jquery-3.5.1.min.js" type="text/javascript"></script>
  <script src="<?= $baseurl ?>/linkp1/js/s2s-cp.js" type="text/javascript"></script>
</body>
</html>
