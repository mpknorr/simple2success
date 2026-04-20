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

    /* Animierter Hintergrund */
    body {
      --s: 25vmin;
      --p: calc(var(--s) / 2);
      --c1: pink;
      --c2: dodgerblue;
      --c3: white;
      --bg: var(--c3);
      --d: 4000ms;
      --e: cubic-bezier(0.76, 0, 0.24, 1);
  
      background-color: var(--bg);
      background-image:
        linear-gradient(45deg, var(--c1) 25%, transparent 25%),
        linear-gradient(-45deg, var(--c1) 25%, transparent 25%),
        linear-gradient(45deg, transparent 75%, var(--c2) 75%),
        linear-gradient(-45deg, transparent 75%, var(--c2) 75%);
      background-size: var(--s) var(--s);
      background-position: 
        calc(var(--p) *  1) calc(var(--p) *  0), 
        calc(var(--p) * -1) calc(var(--p) *  1), 
        calc(var(--p) *  1) calc(var(--p) * -1), 
        calc(var(--p) * -1) calc(var(--p) *  0);
      animation: 
        color var(--d) var(--e) infinite,
        position var(--d) var(--e) infinite;
    }
    
    @keyframes color {
      0%, 25% {
        --bg: var(--c3);
      }
      26%, 50% {
        --bg: var(--c1);
      }
      51%, 75% {
        --bg: var(--c3);
      }
      76%, 100% {
        --bg: var(--c2);
      }
    }
    
    @keyframes position {
      0% {
        background-position: 
          calc(var(--p) *  1) calc(var(--p) *  0), 
          calc(var(--p) * -1) calc(var(--p) *  1), 
          calc(var(--p) *  1) calc(var(--p) * -1), 
          calc(var(--p) * -1) calc(var(--p) *  0);
      }
      25% {
        background-position: 
          calc(var(--p) *  1) calc(var(--p) *  4), 
          calc(var(--p) * -1) calc(var(--p) *  5), 
          calc(var(--p) *  1) calc(var(--p) *  3), 
          calc(var(--p) * -1) calc(var(--p) *  4);
      }
      50% {
        background-position: 
          calc(var(--p) *  3) calc(var(--p) * 8), 
          calc(var(--p) * -3) calc(var(--p) * 9), 
          calc(var(--p) *  2) calc(var(--p) * 7), 
          calc(var(--p) * -2) calc(var(--p) * 8);
      }
      75% {
        background-position: 
          calc(var(--p) *  3) calc(var(--p) * 12), 
          calc(var(--p) * -3) calc(var(--p) * 13), 
          calc(var(--p) *  2) calc(var(--p) * 11), 
          calc(var(--p) * -2) calc(var(--p) * 12);
      }
      100% {    
        background-position: 
          calc(var(--p) *  5) calc(var(--p) * 16), 
          calc(var(--p) * -5) calc(var(--p) * 17), 
          calc(var(--p) *  5) calc(var(--p) * 15), 
          calc(var(--p) * -5) calc(var(--p) * 16);
      }
    }
    
    @media (prefers-reduced-motion) {
      body {
        animation: none;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <img src="<?= $baseurl ?>/<?= $page?>/eagle1b.jpg" class="responsive-image">
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
            <input type="hidden" name="page" value="link3">
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
