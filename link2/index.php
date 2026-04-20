<?php
require_once __DIR__ . '/../includes/conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="apple-touch-icon" sizes="180x180" href="https://www.simple2success.com/backoffice/app-assets/img/ico/apple-touch-icon.png">
  <link rel="icon" type="image/png" sizes="32x32" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon-16x16.png">
  <link rel="manifest" href="https://www.simple2success.com/backoffice/app-assets/img/ico/site.webmanifest">
  <link rel="mask-icon" href="https://www.simple2success.com/backoffice/app-assets/img/ico/safari-pinned-tab.svg" color="#5bbad5">
  <link rel="shortcut icon" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon.ico">
  <meta name="msapplication-TileColor" content="#9f00a7">
  <meta name="msapplication-config" content="https://www.simple2success.com/backoffice/app-assets/img/ico/browserconfig.xml">
  <meta name="theme-color" content="#ffffff">
  <title>Screech if you LOVE data safety!</title>
  <style>
    body, html {
      height: 100%;
      margin: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-family: "Arial", sans-serif;
    }

    .container {
      max-width: 80%;
      width: 60%;
      min-height: 200px;
      background-color: #f7f7f7;
      border: 1px solid #333;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding-bottom: 20px;
    }

    @media only screen and (max-width: 600px) {
      .container {
        width: 90%;
      }
    }

    .responsive-image {
      max-width: 100%;
      height: auto;
    }

    .button {
      display: inline-block;
      padding: 10px 20px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-size: 1.2rem;
    }
  </style>

  <style>
    /* Lokale Schriftart */
    @font-face {
      font-family: 'Exo';
      src: url('<?= $baseurl ?>/font/exo/static/Exo-Medium.ttf') format('truetype');
      font-weight: 100;
    }

    /* Lokaler Hintergrund mit animierter CSS-Variablen */
     body {
      --s: 100px; /* control the size */
      --c1: #a8a8a8;
      --c2: #78223d;
      
      --_s: calc(2*var(--s)) calc(2*var(--s));
      --_g: var(--_s) conic-gradient(at 40% 40%,#0000 75%,var(--c1) 0);
      --_p: var(--_s) conic-gradient(at 20% 20%,#0000 75%,var(--c2) 0);
      background:
        calc( .9*var(--s)) calc( .9*var(--s))/var(--_p),
        calc(-.1*var(--s)) calc(-.1*var(--s))/var(--_p),
        calc( .7*var(--s)) calc( .7*var(--s))/var(--_g),
        calc(-.3*var(--s)) calc(-.3*var(--s))/var(--_g),
        conic-gradient(from 90deg at 20% 20%,var(--c2) 25%,var(--c1) 0) 
         0 0/var(--s) var(--s);
      animation: m 3s infinite;
      color: #black;
      font: 400 16px/1.5 'Exo', ubuntu, "segoe ui", helvetica, arial, sans-serif;
      text-align: center;
    }

    /* Animations */
    @keyframes m {
      0% {
       background-position: 
        calc( .9*var(--s)) calc( .9*var(--s)),
        calc(-.1*var(--s)) calc(-.1*var(--s)),
        calc( .7*var(--s)) calc( .7*var(--s)),
        calc(-.3*var(--s)) calc(-.3*var(--s)),0 0
      }
      25% {
       background-position: 
        calc(1.9*var(--s)) calc( .9*var(--s)),
        calc(-1.1*var(--s)) calc(-.1*var(--s)),
        calc(1.7*var(--s)) calc( .7*var(--s)),
        calc(-1.3*var(--s)) calc(-.3*var(--s)),0 0
      }
      50% {
       background-position: 
        calc(1.9*var(--s)) calc(-.1*var(--s)),
        calc(-1.1*var(--s)) calc( .9*var(--s)),
        calc(1.7*var(--s)) calc(-.3*var(--s)),
        calc(-1.3*var(--s)) calc( .7*var(--s)),0 0
      }
      75% {
       background-position: 
        calc(2.9*var(--s)) calc(-.1*var(--s)),
        calc(-2.1*var(--s)) calc( .9*var(--s)),
        calc(2.7*var(--s)) calc(-.3*var(--s)),
        calc(-2.3*var(--s)) calc( .7*var(--s)),0 0
      }
      100% {
       background-position: 
        calc(2.9*var(--s)) calc(-1.1*var(--s)),
        calc(-2.1*var(--s)) calc(1.9*var(--s)),
        calc(2.7*var(--s)) calc(-1.3*var(--s)),
        calc(-2.3*var(--s)) calc(1.7*var(--s)),0 0
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <img src="<?= $baseurl ?>/<?= $page?>/eagle3.jpg" class="responsive-image">
    <center>
<h2>Wealth seekers who want to soar as high as the eagles?<br>Join our elite team!</h2><br>

      <?php
      if (isset($_GET["err"])) {
        if ($_GET["err"] == "eae") { ?>
          <h3 style="color: red">Email address is already registered.</h3>
      <?php }
      }
      ?>

      <script>
        function showCap() {
          document.getElementById("ctaspot").innerHTML = document.getElementById("optinform").innerHTML;
        }
      </script>

      <div id="ctaspot">
        <a href="javascript:showCap();" class="button">Join Our Community</a>
      </div>

      <div id="optinform" style="display:none;">
        <center>
          <form method="POST" action="<?= $baseurl ?>/includes/postlead.php" target="_top">
            <input type="hidden" name="a" value="1">
            <input type="hidden" name="tr" value="2">
            <input type="hidden" name="page" value="link2">
            <input type="hidden" name="lang" value="en">
            Enter Your Email to Receive Your Access Code:<br>
            <input type="text" name="email" value="" width="90%" placeholder="Email address"><br>
            <input type="hidden" name="referer" value="<?= isset($referer) ? $referer : '' ?>" width="90%" placeholder="Referer"><br>
            <input type="hidden" name="source" value="<?= isset($_GET['source']) ? $_GET['source'] : '' ?>" width="90%"><br>
            <br>
            <input type="submit" class="button" value="Get Access Now" />
          </form>
        </center>
      </div>
    </center>
  </div>
  <div style="margin-top:12px;text-align:center;font-size:11px;color:#999;white-space:nowrap;">
    &copy; <?= date('Y') ?> Simple2Success. All Rights Reserved. &nbsp;&nbsp;&nbsp;
    <a href="<?= $baseurl ?>/impress.php" style="color:#999;text-decoration:none;margin:0 5px;">Impressum</a> |
    <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy" style="color:#999;text-decoration:none;margin:0 5px;">Privacy Policy</a> |
    <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use" style="color:#999;text-decoration:none;margin:0 5px;">Terms of Use</a>
  </div>
</body>
</html>
