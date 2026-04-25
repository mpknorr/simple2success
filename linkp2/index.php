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
$errorMsg = '';
if (isset($_GET['err']) && $_GET['err'] === 'eae') {
    $errorMsg = 'This email address is already registered.';
}
$source = htmlspecialchars($_GET['source'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require_once __DIR__ . '/../includes/head-tracking.php'; ?>
  <meta charset="utf-8">
  <title>Are You Ready to Soar? — Simple2Success</title>
  <meta content="Something life-changing is happening. Get your 100% free Simple2Success account and see for yourself." name="description">
  <meta content="width=device-width, initial-scale=1" name="viewport">
  <link rel="shortcut icon" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon.ico">
  <link href="<?= $baseurl ?>/linkp2/css/normalize.css" rel="stylesheet" type="text/css">
  <link href="<?= $baseurl ?>/linkp2/css/components.css" rel="stylesheet" type="text/css">
  <link href="<?= $baseurl ?>/linkp2/css/s2s-video.css" rel="stylesheet" type="text/css">
  <link href="<?= $baseurl ?>/linkp2/fonts/fonts.css" rel="stylesheet" type="text/css">
</head>
<body class="s2s-body">
  <div class="s2s-section">
    <div class="s2s-banner-wrap">
      <div class="s2s-banner-text">The Team System That Builds Your Income <span class="s2s-span-accent">While You Sleep</span></div>
      <p class="s2s-subtitle">See how it works — your 100% FREE Simple2Success account is ready for you</p>
    </div>
  </div>
  <div class="s2s-section-3">
    <div class="s2s-layout">
      <div class="s2s-video-col">
        <div style="padding-top:56.27659574468085%" class="w-video w-embed"><iframe class="embedly-embed" src="https://cdn.embedly.com/widgets/media.html?src=https%3A%2F%2Fplayer.vimeo.com%2Fvideo%2F842300814%3Fh%3D7345ff97d7%26app_id%3D122963&dntp=1&display_name=Vimeo&url=https%3A%2F%2Fvimeo.com%2F842300814%2F7345ff97d7&image=https%3A%2F%2Fi.vimeocdn.com%2Fvideo%2F1693334612-9bb034e73b15b1b12757626ad085aaebfbf54134718055cc2e3558c169dc64fa-d_1280&key=96f1f04c5f4143bcb0f2e68c87d65feb&type=text%2Fhtml&schema=vimeo" scrolling="no" allowfullscreen="" title="Simple2Success Opportunity"></iframe></div>
      </div>
      <div class="s2s-form-col">
        <div class="s2s-form-col-wrap">
          <div class="s2s-container">
            <h1 class="s2s-heading"><span class="s2s-span-reserve">RESERVE</span><br><span style="white-space:nowrap;">YOUR SPOT</span></h1>
            <p class="s2s-subtitle action">Free spots are limited by sponsor capacity — yours has been reserved</p>
            <div class="s2s-form-inner">
              <div class="s2s-form-block w-form">
                <?php if ($errorMsg): ?>
                  <div class="s2s-form-error"><?= $errorMsg ?></div>
                <?php endif; ?>
                <form method="POST" action="<?= $baseurl ?>/includes/postlead.php" target="_top">
                  <input type="hidden" name="a" value="1">
                  <input type="hidden" name="tr" value="">
                  <input type="hidden" name="page" value="linkp2">
                  <input type="hidden" name="lang" value="en">
                  <input type="hidden" name="referer" value="<?= $referer ?>">
                  <input type="hidden" name="source" value="<?= $source ?>">
                  <input type="text" class="s2s-name-field" maxlength="256" name="name" placeholder="Your First Name" autocomplete="given-name">
                  <div class="s2s-divider"></div>
                  <input type="email" class="s2s-email-field" maxlength="256" name="email" placeholder="Your Best Email" required autocomplete="email">
                  <div class="s2s-divider"></div>
                  <input type="submit" value="Reserve My Free Spot →" class="s2s-btn">
                </form>
              </div>
            </div>
            <div class="s2s-cta-label">Get Your FREE Account!</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <section class="s2s-perks-section">
    <div class="s2s-perks-wrap">
      <div class="s2s-perks-col">
        <div class="s2s-perks-text">Active in 40+ Countries <span class="s2s-span-sep">//</span> 100% FREE Team System <span class="s2s-span-sep">//</span> Multiple Income Streams</div>
      </div>
      <div class="s2s-logo-col"></div>
    </div>
  </section>
  <div class="s2s-section-2">
    <div class="s2s-globe"></div>
  </div>
  <div class="s2s-footer-section">
    <div class="s2s-footer-lower w-container">
      <div class="s2s-copyright">&copy; <?= date('Y') ?> Simple2Success. All Rights Reserved.</div>
      <div class="s2s-footer-links">
        <?php
        $_fb = function_exists('getLegalFooterLinks') ? getLegalFooterLinks($link) : [];
        if (!empty($_fb) && function_exists('getLegalPageUrl')):
            foreach ($_fb as $i => $fl):
                echo $i > 0 ? ' | ' : '';
                echo '<a href="' . htmlspecialchars(getLegalPageUrl($baseurl, $fl['slug'])) . '" class="s2s-link">' . htmlspecialchars($fl['title']) . '</a>';
            endforeach;
        else: ?>
        <a href="<?= $baseurl ?>/impress.php" class="s2s-link">Impressum</a> |
        <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy" class="s2s-link">Privacy Policy</a> |
        <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use" class="s2s-link">Terms of Use</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="s2s-disclaimer-wrap">
      <div class="s2s-disclaimer"><?= $disclaimerText ?></div>
    </div>
  </div>
  <script src="<?= $baseurl ?>/linkp2/js/jquery-3.5.1.min.js" type="text/javascript"></script>
  <script src="<?= $baseurl ?>/linkp2/js/s2s-video.js" type="text/javascript"></script>
</body>
</html>
