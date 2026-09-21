<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

require_once '../includes/conn.php';
require_once '../includes/onboarding.php';

$userid = (int)$_SESSION['userid'];
$userRow = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT name, email, username, paidstatus, profile_pic, referer, step1_at, step2_at
     FROM users WHERE leadid=$userid LIMIT 1")) ?: [];
$name = (string)($userRow['name'] ?? '');
$username = (string)($userRow['username'] ?? '');
$useremail = (string)($userRow['email'] ?? '');
$paidstatus = (string)($userRow['paidstatus'] ?? 'Free');
$profile_pic = (string)($userRow['profile_pic'] ?? 'user_default.png');
$activation = s2sActivationState($userRow);
$profile = s2sGetOnboardingProfile($link, $userid);
$csrfToken = s2sCsrfToken();

$currentUserPm = trim((string)($userRow['username'] ?? ''));
$step1Started = !empty($userRow['step1_at']);
$step2Complete = $activation['key'] === 'activated';
$pmLocked = $step2Complete && preg_match('/^\d+$/', $currentUserPm);
$isAdmin = !empty($_SESSION['is_admin']);

$sponsorPartnerId = '';
$refererId = (int)($userRow['referer'] ?? 0);
if ($refererId > 0) {
    $sponsorRow = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT username FROM users WHERE leadid=$refererId LIMIT 1"));
    $candidateSponsor = trim((string)($sponsorRow['username'] ?? ''));
    if (preg_match('/^\d+$/', $candidateSponsor)) {
        $sponsorPartnerId = $candidateSponsor;
    }
}

$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (stripos($referer, '/backoffice/index.php') !== false || preg_match('~/backoffice/?$~', $referer)) {
    s2sLogLeadEvent($link, $userid, 'start_step_click', 'backoffice/index.php', $activation['key']);
}

$step2Error = '';
$errorCode = $_GET['err'] ?? '';
if ($errorCode === 'locked') {
    $step2Error = 'Your Partner ID is already saved. Please contact support if a correction is necessary.';
} elseif ($errorCode === 'invalidpm') {
    $step2Error = 'Enter the numeric Partner ID exactly as shown in your confirmation.';
} elseif ($errorCode === 'csrf') {
    $step2Error = 'Your session expired. Please reload the page and try again.';
} elseif ($errorCode === 'duplicatepm') {
    $step2Error = 'This Partner ID is already connected to another Simple2Success account. Please check the number or contact support.';
} elseif ($errorCode === 'sponsor') {
    $step2Error = 'We could not verify your sponsoring partner. Please contact support before registering so your account is connected correctly.';
}

$step2Success = (($_GET['step2'] ?? '') === 'done');
$commitmentOptions = s2sCommitmentOptions();
$commitmentLabel = $commitmentOptions[$profile['commitment_code'] ?? ''] ?? '';
$pageStylesheets = ['assets/css/activation.css'];
?>
<?php require_once 'parts/head.php'; ?>
<body class="vertical-layout vertical-menu 2-columns navbar-static layout-dark"
      data-menu="vertical-menu" data-col="2-columns">

<?php require_once 'parts/navbar.php'; ?>

<div class="wrapper">
  <?php require_once 'parts/sidebar.php'; ?>

  <div class="main-panel">
    <div class="main-content">
      <div class="content-overlay"></div>
      <main class="content-wrapper mission-page">

        <?php if ($step2Success): ?>
          <div class="mission-flash" role="status">
            <i class="ft-check-circle"></i>
            <span><strong>Account setup complete.</strong> Your Partner ID has been saved. Continue below with Steps 3, 4 and 5.</span>
          </div>
        <?php endif; ?>

        <?php if ($step2Error !== ''): ?>
          <div class="mission-flash" role="alert" style="border-color:rgba(234,84,85,.35);background:rgba(234,84,85,.09);">
            <i class="ft-alert-circle" style="color:#ea5455;"></i>
            <span><?= htmlspecialchars($step2Error) ?></span>
          </div>
        <?php endif; ?>

        <header class="activation-header">
          <span class="mission-eyebrow">Mission 1000 Families · Your five-step path</span>
          <h1 class="activation-heading"><?= htmlspecialchars($activation['title']) ?></h1>
          <p><?= htmlspecialchars($activation['body']) ?></p>
        </header>

        <section aria-label="Activation progress">
          <div class="card mission-progress-card">
            <div class="mission-progress-top">
              <div>
                <strong>Account setup · Steps 1 and 2</strong><br>
                <span>After setup, continue with Steps 3, 4 and 5 below.</span>
              </div>
              <span class="mission-progress-value">Setup: <?= (int)$activation['percent'] ?>% · account + Steps 1–2</span>
            </div>
            <div class="mission-progress-track" aria-hidden="true">
              <span style="width:<?= (int)$activation['percent'] ?>%;"></span>
            </div>
            <div class="mission-progress-steps">
              <div class="mission-progress-step is-done"><b>✓</b><span>Simple2Success account</span></div>
              <div class="mission-progress-step <?= $step1Started || $step2Complete ? 'is-done' : 'is-current' ?>">
                <b><?= $step1Started || $step2Complete ? '✓' : '1' ?></b><span>Step 1 · Open registration</span>
              </div>
              <div class="mission-progress-step <?= $step2Complete ? 'is-done' : ($step1Started ? 'is-current' : '') ?>">
                <b><?= $step2Complete ? '✓' : '2' ?></b><span>Step 2 · Save Partner ID</span>
              </div>
            </div>
          </div>
        </section>

        <?php if (!$step2Complete): ?>
          <section class="activation-status">
            <i class="ft-info"></i>
            <div>
              <strong>You stay in control.</strong>
              <span>Read the current partner terms on the official registration page before submitting. A learnable system can improve execution; it cannot guarantee income.</span>
            </div>
          </section>
        <?php endif; ?>

        <section aria-labelledby="ai-scanner-title">
          <details id="ai-scanner-opportunity" class="activation-opportunity">
            <summary>
              <span><i class="ft-star" aria-hidden="true"></i> Current product opportunity · FitLine AI Scanner</span>
              <small>Optional · customer conversation tool</small>
            </summary>
            <div class="activation-opportunity__body">
              <h2 id="ai-scanner-title">Show a personal result instead of guessing.</h2>
              <p>Official partner information describes an app-based FitLine AI Scanner for individualized skincare. It evaluates more than 100 skin points and features and shows the products that match the customer’s profile.</p>
              <ul class="activation-list">
                <li>Review the current starter-set access, price, app version and partner terms before presenting it.</li>
                <li>Use the same Team Partner account for the starter-set purchase and the FitLine App sign-in.</li>
                <li>The launch material describes the scanner as patent pending and the formulas as based on NTC&reg;; use the official page for the current details.</li>
                <li>Use the scan as a clear conversation aid. A scan does not guarantee a customer result, purchase or income.</li>
              </ul>
              <div class="activation-opportunity__links">
                <a class="mission-secondary" href="https://www.pm-international.com/de/de-de/partner/news/every-skin-is-different" target="_blank" rel="noopener noreferrer">Official launch information <i class="ft-external-link"></i></a>
                <a class="mission-secondary" href="https://icnk.io/u/_1TAGZbbzkp7/" target="_blank" rel="noopener noreferrer">Setup guide <i class="ft-external-link"></i></a>
                <a class="mission-secondary" href="https://cdn.brandfolder.io/F2QEPJ72/as/43wq59jfbzcn7kccxgbsqj/DE_Skin_Understood.mp4" target="_blank" rel="noopener noreferrer">Watch the launch video <i class="ft-play-circle"></i></a>
              </div>
              <p class="activation-form-note">Other partner programs, including PM DirectCash and vehicle or retirement options, have separate eligibility, payout and cost terms. Review the current official conditions before presenting them.</p>
            </div>
          </details>
        </section>

        <section class="activation-flow" aria-label="Step 1 and Step 2">
          <article id="step1" class="activation-step <?= $step1Started || $step2Complete ? 'is-done' : 'is-current' ?>">
            <div class="activation-step__top">
              <span class="activation-step__number"><?= $step1Started || $step2Complete ? '✓' : '1' ?></span>
              <span class="activation-pill"><?= $step1Started || $step2Complete ? 'Registration opened' : 'Do this now' ?></span>
            </div>
            <h2>Review and open your PM-International partner registration</h2>
            <p>
              PM-International is the product and compensation-plan partner. Simple2Success provides the onboarding, tools and repeatable workflow around it.
            </p>
            <ul class="activation-list">
              <li>Your sponsoring partner is passed to the official registration page.</li>
              <li>You review the current terms and decide before submitting.</li>
              <li>After confirmation, PM-International provides your Partner ID.</li>
            </ul>

            <?php if ($sponsorPartnerId !== ''): ?>
              <div class="activation-alert">
                <strong>Important:</strong> The official page should show sponsoring Partner ID
                <strong><?= htmlspecialchars($sponsorPartnerId) ?></strong>. Do not replace it, or your account may not connect to the correct Eagle Team sponsor.
              </div>
              <form method="post" action="step1-click.php" target="_blank">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <button id="step1-registration" class="mission-primary" type="submit">
                  <?= $step1Started ? 'Open Registration Again' : 'Open Official Registration' ?> <i class="ft-external-link"></i>
                </button>
              </form>
              <p class="activation-form-note">Opens the official PM-International page in a new tab. Keep this page open for Step 2.</p>
            <?php else: ?>
              <div class="activation-alert">
                <strong>Connection check required:</strong> We could not verify a sponsoring Partner ID for this account. Please contact support before registering.
              </div>
              <a class="mission-secondary" href="support.php"><i class="ft-help-circle"></i> Contact Support</a>
            <?php endif; ?>
          </article>

          <article id="step2" class="activation-step <?= $step2Complete ? 'is-done' : ($step1Started ? 'is-current' : '') ?>">
            <div class="activation-step__top">
              <span class="activation-step__number"><?= $step2Complete ? '✓' : '2' ?></span>
              <span class="activation-pill"><?= $step2Complete ? 'Complete' : ($step1Started ? 'Your next action' : 'After registration') ?></span>
            </div>
            <h2>Save your personal Partner ID</h2>

            <?php if ($pmLocked && !$isAdmin): ?>
              <div class="activation-success">
                <strong><i class="ft-check-circle"></i> Step 2 complete.</strong><br>
                Partner ID <?= htmlspecialchars($currentUserPm) ?> is saved and protected against accidental changes.
              </div>
              <p>If this ID is incorrect, contact support. It cannot be changed from your account after activation.</p>
            <?php else: ?>
              <?php if ($isAdmin && $pmLocked): ?>
                <div class="activation-alert"><strong>Admin override:</strong> You can correct the saved Partner ID.</div>
              <?php endif; ?>
              <p>
                Already received your confirmation? Copy the numeric Partner ID exactly as shown and paste it below. This is the only field required for Step 2.
              </p>
              <form method="post" action="welcome.php" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <label class="activation-id-label" for="partner-id">Your PM Partner ID</label>
                <input id="partner-id" class="activation-id-input" type="text" name="root"
                       value="<?= htmlspecialchars($currentUserPm) ?>"
                       placeholder="e.g. 6304013" inputmode="numeric" pattern="[0-9]{4,12}"
                       minlength="4" maxlength="12" required>
                <p class="activation-form-note">Numbers only. Check every digit before saving; the ID is locked after activation.</p>
                <button type="submit" class="mission-primary"><i class="ft-lock"></i> Save Partner ID and Activate</button>
              </form>
            <?php endif; ?>
          </article>
        </section>

        <?php if (!$step2Complete): ?>
          <section>
            <details class="activation-video">
              <summary>
                <div><i class="ft-play-circle" style="color:#d87bee;margin-right:.5rem;"></i>Want context before deciding?</div>
                <span>Watch the business overview (optional)</span>
              </summary>
              <div class="activation-video__body">
                <p style="color:var(--mission-muted);font-size:.86rem;">Use this overview to understand the company and model. The registration decision remains yours.</p>
                <div class="activation-video__frame">
                  <iframe id="vimeo-start-1"
                          src="https://player.vimeo.com/video/1183822471?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479&amp;title=0&amp;byline=0&amp;portrait=0"
                          frameborder="0" allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share"
                          referrerpolicy="strict-origin-when-cross-origin" title="Simple2Success business overview"></iframe>
                </div>
              </div>
            </details>
          </section>
        <?php endif; ?>

        <section id="after-activation" class="activation-next">
          <span class="mission-eyebrow"><?= $step2Complete ? 'Setup complete · Continue in order' : 'Your next steps · Preview' ?></span>
          <h2 style="color:#fff;font-weight:800;margin-bottom:.55rem;">
            <?= $step2Complete ? 'Follow Steps 3, 4 and 5.' : 'After Step 2, your path continues.' ?>
          </h2>
          <p style="color:var(--mission-muted);max-width:800px;line-height:1.65;">
            <?= $step2Complete
              ? 'Start with Step 3, then review Step 4 and make Step 5 your routine. The setup bar above does not measure these ongoing actions.'
              : 'Complete Steps 1 and 2 first. Then use the actions below in order: traffic, product subscription and consistent activity.' ?>
          </p>
          <div class="activation-next-grid" aria-label="Steps 3, 4 and 5">
            <article id="step3" class="activation-step <?= $step2Complete ? 'is-current' : '' ?>">
              <div class="activation-step__top">
                <span class="activation-step__number">3</span>
                <span class="activation-pill"><?= $step2Complete ? 'Start here' : 'After Step 2' ?></span>
              </div>
              <h2>Order Traffic</h2>
              <p>Choose a traffic source and use your personal capture-page link. Start with a budget you can afford and measure qualified leads before increasing it.</p>
              <?php if ($step2Complete): ?>
                <p><a class="mission-secondary" href="links.php">Check My Personal Link</a></p>
                <a class="mission-primary" href="traffic.php">Choose Traffic <i class="ft-arrow-right"></i></a>
              <?php else: ?>
                <span class="mission-meta"><i class="ft-lock"></i> Available after Step 2</span>
              <?php endif; ?>
              <p class="activation-form-note">Traffic can cost money. Buying visitors does not guarantee registrations or income.</p>
            </article>
            <article id="step4" class="activation-step">
              <div class="activation-step__top">
                <span class="activation-step__number">4</span>
                <span class="activation-pill"><?= $step2Complete ? 'Then review' : 'After Step 2' ?></span>
              </div>
              <h2>Review Your Product Subscription</h2>
              <p>Check your product start or subscription in your partner account. If you already arranged one during registration, review the existing order instead of creating a second one.</p>
              <?php if ($step2Complete && $pmLocked): ?>
                <a class="mission-secondary" href="https://www.fitline.com/autoship/create?sponsor=<?= rawurlencode($currentUserPm) ?>&amp;productId=9700732" target="_blank" rel="noopener noreferrer">Review Product Subscription <i class="ft-external-link"></i></a>
                <p><a class="mission-secondary" href="#ai-scanner-opportunity">See the current AI Scanner opportunity</a></p>
              <?php elseif ($step2Complete): ?>
                <p class="activation-alert">A numeric Partner ID is required for your personal product link. <a href="support.php">Contact Support</a> to check your saved ID.</p>
              <?php else: ?>
                <span class="mission-meta"><i class="ft-lock"></i> Available after Step 2</span>
              <?php endif; ?>
              <p class="activation-form-note">Opens the partner website. Check current availability, prices, recurring charges and cancellation terms before placing an order. Opening this link does not confirm a subscription.</p>
            </article>
            <article id="step5" class="activation-step">
              <div class="activation-step__top">
                <span class="activation-step__number">5</span>
                <span class="activation-pill">Repeat regularly</span>
              </div>
              <h2>Keep the Momentum Going</h2>
              <p>Review your leads and their progress each week. Follow up using the provided templates, improve your approach and repeat Step 3 when the results and your budget justify it.</p>
              <?php if ($step2Complete): ?>
                <p><a class="mission-secondary" href="leads.php">Review My Leads</a></p>
                <p><a class="mission-secondary" href="swipe.php">Open Follow-up Templates</a></p>
                <a class="mission-secondary" href="traffic.php">Return to Traffic Sources</a>
              <?php else: ?>
                <span class="mission-meta"><i class="ft-lock"></i> Available after Step 2</span>
              <?php endif; ?>
              <p class="activation-form-note">This is an ongoing routine, not a one-time task. Traffic purchases and product subscriptions are not automatically verified here.</p>
            </article>
          </div>
          <?php if (!$step2Complete): ?>
            <span class="mission-meta"><i class="ft-lock"></i> Complete Step 2 to unlock the next-phase actions.</span>
          <?php endif; ?>
          <?php if ($commitmentLabel !== ''): ?>
            <div class="mission-commitment-saved"><i class="ft-check-circle"></i>Your plan: <?= htmlspecialchars($commitmentLabel) ?></div>
          <?php endif; ?>
        </section>

        <section aria-labelledby="activation-faq-title">
          <span class="mission-eyebrow">Questions before you continue</span>
          <h2 id="activation-faq-title" style="color:#fff;font-weight:800;margin-bottom:1rem;">Clear answers build better decisions.</h2>
          <div class="activation-faq">
            <details>
              <summary>Is income guaranteed?</summary>
              <p>No. Simple2Success provides a process and tools. Results vary based on effort, skill, time, market conditions and expenses.</p>
            </details>
            <details>
              <summary>Why do I need a Partner ID?</summary>
              <p>It confirms your partner registration and connects your Simple2Success account to the correct workflow and sponsor relationship.</p>
            </details>
            <details>
              <summary>What happens after I save it?</summary>
              <p>Steps 3, 4 and 5 become available: start traffic with your personal link, review your product subscription, then follow up and repeat your activity. The 100% indicator refers only to account setup.</p>
            </details>
            <details>
              <summary>Can I change the ID later?</summary>
              <p>Not from your account. This protects the sponsor connection from accidental changes. Support can review genuine corrections.</p>
            </details>
          </div>
        </section>

        <p class="mission-disclaimer" style="margin:1.25rem 0 2rem;">
          Mission 1000 Families describes our support goal. $1,000+ per month, travel and vehicle-program participation are possible objectives—not typical or guaranteed outcomes. Always review current partner terms, qualification rules and costs.
        </p>

      </main>
    </div>

    <?php require_once 'parts/footer.php'; ?>
    <button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>
  </div>
</div>

<div class="sidenav-overlay"></div>
<div class="drag-target"></div>

<script src="app-assets/vendors/js/vendors.min.js"></script>
<script src="app-assets/vendors/js/switchery.min.js"></script>
<script src="app-assets/js/core/app-menu.js"></script>
<script src="app-assets/js/core/app.js"></script>
<script src="app-assets/js/notification-sidebar.js"></script>
<script src="app-assets/js/customizer.js"></script>
<script src="app-assets/js/scroll-top.js"></script>
<script src="assets/js/scripts.js"></script>
<?php if (!$step2Complete): ?>
<script src="https://player.vimeo.com/api/player.js"></script>
<script>
(function () {
  if (typeof Vimeo === 'undefined') return;
  var element = document.getElementById('vimeo-start-1');
  if (!element) return;
  var player = new Vimeo.Player(element);
  var fired = {};
  function send(eventName) {
    if (fired[eventName]) return;
    fired[eventName] = true;
    fetch('../includes/track-video.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      credentials: 'same-origin',
      body: 'event=' + encodeURIComponent(eventName)
        + '&video=' + encodeURIComponent('Business Overview')
        + '&page=' + encodeURIComponent(window.location.pathname)
    }).catch(function () {});
  }
  player.on('play', function () { send('video_play'); });
  player.on('timeupdate', function (data) {
    if (!data) return;
    if (data.percent >= .25) send('video_25');
    if (data.percent >= .50) send('video_50');
    if (data.percent >= .75) send('video_75');
  });
  player.on('ended', function () { send('video_complete'); });
})();
</script>
<?php endif; ?>
</body>
</html>
