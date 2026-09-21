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
    "SELECT name, email, username, paidstatus, profile_pic, step1_at, step2_at
     FROM users WHERE leadid=$userid LIMIT 1")) ?: [];
$name = (string)($userRow['name'] ?? '');
$username = (string)($userRow['username'] ?? '');
$useremail = (string)($userRow['email'] ?? '');
$paidstatus = (string)($userRow['paidstatus'] ?? 'Free');
$profile_pic = (string)($userRow['profile_pic'] ?? 'user_default.png');
$userName = trim((string)($userRow['name'] ?? ''));
$firstName = $userName !== '' ? preg_split('/\s+/', $userName)[0] : '';
$profile = s2sGetOnboardingProfile($link, $userid);
$activation = s2sActivationState($userRow);
$goalOptions = s2sGoalOptions();
$commitmentOptions = s2sCommitmentOptions();
$selectedGoal = $profile['goal_code'] ?? '';
$selectedCommitment = $profile['commitment_code'] ?? '';
$csrfToken = s2sCsrfToken();

$step1Done = $activation['key'] !== 'step1';
$step2Done = $activation['key'] === 'activated';
$goalLabel = isset($goalOptions[$selectedGoal]) ? $goalOptions[$selectedGoal]['label'] : '';
$commitmentLabel = $commitmentOptions[$selectedCommitment] ?? '';
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

        <?php if (($_GET['profile'] ?? '') === 'saved'): ?>
          <div class="mission-flash" role="status">
            <i class="ft-check-circle"></i>
            <span>Your personal goal has been saved. We will use it to keep your next steps relevant.</span>
          </div>
        <?php endif; ?>

        <?php if (($_GET['plan'] ?? '') === 'saved'): ?>
          <div class="mission-flash" role="status">
            <i class="ft-check-circle"></i>
            <span>Your next-step plan is set. Keep the promise small, specific and doable.</span>
          </div>
        <?php endif; ?>

        <?php if (($_GET['profile'] ?? '') === 'error' || ($_GET['plan'] ?? '') === 'error'): ?>
          <div class="mission-flash" role="alert" style="border-color:rgba(234,84,85,.35);background:rgba(234,84,85,.09);">
            <i class="ft-alert-circle" style="color:#ea5455;"></i>
            <span>We could not save that choice. Please try again or contact support if the problem continues.</span>
          </div>
        <?php endif; ?>

        <section class="mission-hero" aria-labelledby="mission-title">
          <div class="mission-hero__content">
            <span class="mission-eyebrow">Simple2Success · Eagle Team · Mission 1000 Families</span>
            <h1 id="mission-title">
              <?= $firstName !== '' ? 'Welcome, ' . htmlspecialchars($firstName) . '.' : 'Welcome.' ?><br>
              <span class="mission-gradient"><?= htmlspecialchars($activation['title']) ?></span>
            </h1>
            <p class="mission-hero__lead"><?= htmlspecialchars($activation['body']) ?></p>
            <?php if ($goalLabel !== ''): ?>
              <p class="mission-hero__note">
                Your reason: <strong style="color:#fff;"><?= htmlspecialchars($goalLabel) ?></strong>.
                Keep that reason visible; focus only on the next action.
              </p>
            <?php else: ?>
              <p class="mission-hero__note">
                You do not need to master the whole business today. Choose your reason, take one clear action and build from there.
              </p>
            <?php endif; ?>
            <div class="mission-actions">
              <a class="mission-primary" href="<?= htmlspecialchars($activation['cta_url']) ?>">
                <?= htmlspecialchars($activation['cta_label']) ?> <i class="ft-arrow-right"></i>
              </a>
              <span class="mission-meta">One clear next step · no pressure · full transparency</span>
            </div>
          </div>
        </section>

        <section aria-label="Your activation progress">
          <div class="card mission-progress-card">
            <div class="mission-progress-top">
              <div>
                <strong>Your account setup</strong><br>
                <span><?= htmlspecialchars($activation['eyebrow']) ?></span>
              </div>
              <span class="mission-progress-value">Setup: <?= (int)$activation['percent'] ?>% · account + Steps 1–2</span>
            </div>
            <div class="mission-progress-track" aria-hidden="true">
              <span style="width:<?= (int)$activation['percent'] ?>%;"></span>
            </div>
            <div class="mission-progress-steps">
              <div class="mission-progress-step is-done"><b>✓</b><span>Simple2Success account</span></div>
              <div class="mission-progress-step <?= $step1Done ? 'is-done' : 'is-current' ?>">
                <b><?= $step1Done ? '✓' : '1' ?></b><span>Step 1 · Open partner registration</span>
              </div>
              <div class="mission-progress-step <?= $step2Done ? 'is-done' : ($step1Done ? 'is-current' : '') ?>">
                <b><?= $step2Done ? '✓' : '2' ?></b><span>Step 2 · Save Partner ID</span>
              </div>
            </div>
            <p class="mission-meta">This measures account setup only. Your next actions are Steps 3–5: traffic, product subscription and consistent activity.</p>
          </div>
        </section>

        <section class="mission-grid" aria-label="Your reason and our mission">
          <div class="mission-card">
            <span class="mission-eyebrow">Make the mission personal</span>
            <h2>What would meaningful progress change for you?</h2>
            <p>A personal reason is easier to act on than a generic income promise. Choose the outcome that matters most right now.</p>

            <?php if ($goalLabel !== ''): ?>
              <div class="mission-goal-current">
                <i class="<?= htmlspecialchars($goalOptions[$selectedGoal]['icon']) ?>"></i>
                <span><strong>Your current focus:</strong> <?= htmlspecialchars($goalLabel) ?></span>
              </div>
              <details>
                <summary style="cursor:pointer;color:rgba(255,255,255,.62);font-size:.82rem;">Change my focus</summary>
            <?php endif; ?>

            <form method="post" action="onboarding-action.php">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
              <input type="hidden" name="action" value="set_goal">
              <div class="mission-goal-grid">
                <?php foreach ($goalOptions as $code => $option): ?>
                  <label class="mission-goal-option">
                    <input type="radio" name="goal" value="<?= htmlspecialchars($code) ?>"
                           <?= $selectedGoal === $code ? 'checked' : '' ?> required>
                    <span><i class="<?= htmlspecialchars($option['icon']) ?>"></i><?= htmlspecialchars($option['label']) ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
              <button type="submit" class="mission-secondary">
                <i class="ft-check"></i> Save My Focus
              </button>
            </form>

            <?php if ($goalLabel !== ''): ?></details><?php endif; ?>
          </div>

          <aside class="mission-card mission-card--accent">
            <span class="mission-eyebrow">Our shared north star</span>
            <div class="mission-number"><span>1,000</span> families</div>
            <h3>More choice, built one repeatable action at a time.</h3>
            <p>
              Our mission is to support 1,000 families worldwide as they build business skills, a consistent routine and a path toward at least $1,000 in additional monthly income.
            </p>
            <p>
              Greater flexibility, travel and partner incentives can be meaningful milestones, but they are earned outcomes with separate qualification requirements—not automatic benefits.
            </p>
            <p class="mission-disclaimer">
              This is a mission target, not an earnings guarantee. Results vary with effort, skills, market conditions, time and expenses.
            </p>
          </aside>
        </section>

        <?php if (!$step2Done): ?>
          <section>
            <div class="mission-card mission-card--accent">
              <span class="mission-eyebrow">Turn intention into action</span>
              <h2>When will you take your next step?</h2>
              <p>Choose a realistic moment. A specific plan is more useful than another burst of motivation.</p>
              <?php if ($commitmentLabel !== ''): ?>
                <div class="mission-commitment-saved"><i class="ft-check-circle"></i><?= htmlspecialchars($commitmentLabel) ?></div>
              <?php endif; ?>
              <form method="post" action="onboarding-action.php" class="mission-commitments">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="set_commitment">
                <?php foreach ($commitmentOptions as $code => $label): ?>
                  <button type="submit" name="commitment" value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></button>
                <?php endforeach; ?>
              </form>
            </div>
          </section>
        <?php endif; ?>

        <section aria-labelledby="system-principles-title">
          <span class="mission-eyebrow">How Simple2Success should feel</span>
          <h2 id="system-principles-title" style="color:#fff;font-weight:800;margin-bottom:1rem;">Clear enough to start. Simple enough to repeat.</h2>
          <div class="mission-principles">
            <article class="mission-principle">
              <i class="ft-crosshair"></i>
              <h3>One next action</h3>
              <p>The system shows the action that matters now instead of presenting every tool at once.</p>
            </article>
            <article class="mission-principle">
              <i class="ft-shield"></i>
              <h3>Transparent decisions</h3>
              <p>You see what happens next, review current terms yourself and stay in control of every decision.</p>
            </article>
            <article class="mission-principle">
              <i class="ft-repeat"></i>
              <h3>Consistency over hype</h3>
              <p>Skills, follow-up and repeatable daily actions matter more than switching to the next opportunity.</p>
            </article>
          </div>
        </section>

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
</body>
</html>
