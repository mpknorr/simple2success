<?php
require_once __DIR__ . '/../includes/conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require_once __DIR__ . '/../includes/head-tracking.php'; ?>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="apple-touch-icon" sizes="180x180" href="https://www.simple2success.com/backoffice/app-assets/img/ico/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon-16x16.png">
  <link rel="manifest" href="https://www.simple2success.com/backoffice/app-assets/img/ico/site.webmanifest">
  <link rel="shortcut icon" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon.ico">
  <meta name="msapplication-TileColor" content="#9f00a7">
  <meta name="theme-color" content="#0d0d0d">
  <title>Join the Eagle Team — Simple2Success</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-family: "Arial", ubuntu, "segoe ui", helvetica, arial, sans-serif;
      font-size: 16px;
      line-height: 1.5;
      color: #e0e0e0;
      /* Calm dark gradient — no distracting animation */
      background: linear-gradient(160deg, #0d0d1a 0%, #1a0a2e 50%, #0d0d1a 100%);
      padding: 20px;
    }

    .container {
      width: 100%;
      max-width: 520px;
      background: rgba(26,10,46,.85);
      border: 1px solid rgba(183,0,224,.35);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 0 40px rgba(183,0,224,.15);
    }

    .responsive-image {
      width: 100%;
      height: auto;
      display: block;
    }

    .card-body {
      padding: 24px 28px 28px;
      text-align: center;
    }

    h2 {
      font-size: 1.3rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 6px;
      line-height: 1.35;
    }

    .subheadline {
      font-size: .92rem;
      color: rgba(255,255,255,.55);
      margin-bottom: 20px;
    }

    .error-msg {
      background: rgba(220,50,50,.15);
      border: 1px solid rgba(220,50,50,.4);
      color: #ff6b6b;
      border-radius: 6px;
      padding: 10px 14px;
      margin-bottom: 14px;
      font-size: .9rem;
    }

    .form-group {
      margin-bottom: 12px;
      text-align: left;
    }

    input[type="text"],
    input[type="email"] {
      width: 100%;
      padding: 12px 16px;
      background: rgba(255,255,255,.07);
      border: 1px solid rgba(183,0,224,.3);
      border-radius: 7px;
      color: #fff;
      font-size: .97rem;
      outline: none;
      transition: border-color .2s;
    }

    input[type="text"]:focus,
    input[type="email"]:focus {
      border-color: #b700e0;
    }

    input[type="text"]::placeholder,
    input[type="email"]::placeholder {
      color: rgba(255,255,255,.35);
    }

    .btn-cta {
      display: block;
      width: 100%;
      padding: 14px 20px;
      margin-top: 6px;
      background: linear-gradient(135deg, #b700e0, #7b00a0);
      color: #fff;
      font-size: 1.05rem;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      text-decoration: none;
      transition: opacity .2s, transform .1s;
    }

    .btn-cta:hover { opacity: .9; transform: translateY(-1px); }
    .btn-cta:active { transform: translateY(0); }

    .micro-copy {
      font-size: .78rem;
      color: rgba(255,255,255,.38);
      margin-top: 10px;
    }

    .social-proof {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 8px 16px;
      margin-top: 18px;
      padding-top: 16px;
      border-top: 1px solid rgba(255,255,255,.08);
    }

    .sp-item {
      font-size: .78rem;
      color: rgba(255,255,255,.45);
    }

    .sp-item::before {
      content: "✓ ";
      color: #b700e0;
    }

    .footer-bar {
      margin-top: 16px;
      text-align: center;
      font-size: 11px;
      color: #666;
    }

    .footer-bar a {
      color: #666;
      text-decoration: none;
      margin: 0 4px;
    }

    .footer-bar a:hover { color: #999; }
  </style>
</head>
<body>
  <div class="container">
    <img src="<?= $baseurl ?>/<?= $page ?>/eagle3.jpg" class="responsive-image" alt="Eagle Team">
    <div class="card-body">
      <h2>Join 10,000+ People Already Building Income With the Simple2Success Eagle Team</h2>
      <p class="subheadline">Your free spot is waiting — enter your details to get access</p>

      <?php if (isset($_GET["err"]) && $_GET["err"] == "eae"): ?>
        <div class="error-msg">This email address is already registered. Please use a different email.</div>
      <?php endif; ?>

      <form method="POST" action="<?= $baseurl ?>/includes/postlead.php" target="_top">
        <input type="hidden" name="a" value="1">
        <input type="hidden" name="tr" value="2">
        <input type="hidden" name="page" value="link2">
        <input type="hidden" name="lang" value="en">
        <input type="hidden" name="referer" value="<?= isset($referer) ? htmlspecialchars($referer) : '' ?>">
        <input type="hidden" name="source" value="<?= isset($_GET['source']) ? htmlspecialchars($_GET['source']) : '' ?>">

        <div class="form-group">
          <input type="text" name="name" placeholder="Your First Name" autocomplete="given-name">
        </div>
        <div class="form-group">
          <input type="email" name="email" placeholder="Your Best Email" required autocomplete="email">
        </div>
        <input type="submit" class="btn-cta" value="Get My Free Access Now →">
      </form>

      <p class="micro-copy">🔒 100% Free — No Credit Card Required. We respect your privacy.</p>

      <div class="social-proof">
        <span class="sp-item">10,000+ Members</span>
        <span class="sp-item">40+ Countries</span>
        <span class="sp-item">Free to Join</span>
        <span class="sp-item">Proven System Since 2023</span>
      </div>
    </div>
  </div>

  <div class="footer-bar">
    &copy; <?= date('Y') ?> Simple2Success. All Rights Reserved. &nbsp;
    <a href="<?= $baseurl ?>/impress.php">Impressum</a> |
    <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy">Privacy Policy</a> |
    <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use">Terms of Use</a>
  </div>
</body>
</html>
