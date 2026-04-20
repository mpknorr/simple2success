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

  <title>Eagle Guide</title>
  <style>
    body, html {
      height: 100%;
      margin: 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-family: "Open Sans", sans-serif;
    }

    .container {
      max-width: 80%;
      width: 60%;
      min-height: 200px;
      background-color: white;
      border: 1px solid black;
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
      background-image: linear-gradient(to bottom, #77DD77, #00AA00);
      color: white;
      text-decoration: none;
      border-radius: 5px;
      font-size: large;
    }

    body {
      color: #999;
      font: 400 16px/1.5 exo, ubuntu, "segoe ui", helvetica, arial, sans-serif;
      text-align: center;
       background: url('<?= $baseurl ?>/<?= $page ?>/checkerboard-cross.webp') repeat 0 0;
      -webkit-animation: bg-scrolling-reverse 0.92s infinite;
      -moz-animation: bg-scrolling-reverse 0.92s infinite;
      -o-animation: bg-scrolling-reverse 0.92s infinite;
      animation: bg-scrolling-reverse 0.92s infinite;
      -webkit-animation-timing-function: linear;
      -moz-animation-timing-function: linear;
      -o-animation-timing-function: linear;
      animation-timing-function: linear;
    }

    @-webkit-keyframes bg-scrolling-reverse {
      100% {
        background-position: 50px 50px;
      }
    }
    @-moz-keyframes bg-scrolling-reverse {
      100% {
        background-position: 50px 50px;
      }
    }
    @-o-keyframes bg-scrolling-reverse {
      100% {
        background-position: 50px 50px;
      }
    }
    @keyframes bg-scrolling-reverse {
      100% {
        background-position: 50px 50px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <img src="<?= $baseurl ?>/<?= $page ?>/eagle1b.jpg" class="responsive-image">
    <center>
      <h2>Navigate the Vast Skies of Wealth:<br>Let The Eagle Guide You to Financial Freedom!</h2><br>

      <?php
      if (isset($_GET["err"])) {
        if ($_GET["err"] == "eae") { ?>
          <h3 style="color: red">E-mail address is already registered.</h3>
      <?php }
      }
      ?>

      <script>
        function showCap() {
          document.getElementById("ctaspot").innerHTML = document.getElementById("optinform").innerHTML;
        }
      </script>

      <div id="ctaspot">
        <a href="javascript:showCap();" class="button">Join The Team</a>
      </div>

      <div id="optinform" style="display:none;">
        <center>
          <form method="POST" action="<?= $baseurl ?>/includes/postlead.php" target="_top">
            <input type="hidden" name="a" value="1">
            <input type="hidden" name="tr" value="2">
            <input type="hidden" name="page" value="link1">
            <input type="hidden" name="lang" value="en">
            Enter The Email Where We Can Send Your Access Code:<br>
            <input type="text" name="email" value="" width="90%" placeholder="email address"><br>
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
