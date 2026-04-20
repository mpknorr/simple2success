<?php
include '../includes/conn.php';

// ── Step 1 click-tracking handler (AJAX POST) ────────────────────────────────
if (isset($_POST["click"])) {
    $clickTime = $_POST["click"];
    $userid    = (int)$_POST["userid"];
    $step1ip   = mysqli_real_escape_string($link, getClientIp());
    mysqli_query($link, "UPDATE users SET signuproot='$clickTime', step1_at=NOW(), step1_ip='$step1ip' WHERE leadid=$userid");
    die();
}

// ── Auth ─────────────────────────────────────────────────────────────────────
if (!isset($_SESSION)) { session_start(); }
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

$userid = $_SESSION['userid'];

// ── Sponsor's PM partner number (for Step 1 registration link TP=) ───────────
$user_detailsdata = mysqli_query($link, "SELECT referer FROM users WHERE leadid = $userid");
$user_referer = '';
foreach ($user_detailsdata as $userData) { $user_referer = $userData["referer"]; }

$referer_username = '';
if (!empty($user_referer) && is_numeric($user_referer)) {
    $get_refererdata = mysqli_query($link, "SELECT username FROM users WHERE leadid = $user_referer");
    foreach ($get_refererdata as $referData) { $referer_username = $referData["username"]; }
}

// ── Current user data (PM number, Step 1 tracking, admin flag, error msg) ────
$current_user_row = mysqli_fetch_assoc(mysqli_query($link, "SELECT username, step1_at FROM users WHERE leadid = $userid"));
$current_user_pm  = $current_user_row['username'] ?? '';
$step1_clicked    = !empty($current_user_row['step1_at']);
$pm_locked        = !empty($current_user_pm) && preg_match('/^\d+$/', trim($current_user_pm));
$is_admin         = !empty($_SESSION['is_admin']);

$step2_error   = '';
$step2_success = '';
if (isset($_GET['err'])) {
    if ($_GET['err'] === 'locked')    $step2_error = 'Your Partner ID is already saved and cannot be changed. Contact support if an update is needed.';
    if ($_GET['err'] === 'invalidpm') $step2_error = 'Please enter a valid PM Partner ID (numbers only, e.g. 6304013).';
}
if (isset($_GET['step2']) && $_GET['step2'] === 'done') {
    $step2_success = 'Your PM Partner ID has been saved successfully. Step 2 is complete — continue with the steps below.';
}
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<body class="vertical-layout vertical-menu 2-columns navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">
<link rel="stylesheet" href="app-assets/css/pages/ex-component-media-player.css">
<link rel="stylesheet" type="text/css" href="app-assets/vendors/css/plyr.css">
<style>
  /* ── Step progress bar ─────────────────────────────────────── */
  .s2s-step-bar { display:flex; align-items:flex-start; justify-content:center; padding:1.5rem 1rem 0.5rem; gap:0; }
  .s2s-step-bar .s2s-step { flex:1; text-align:center; position:relative; }
  .s2s-step-bar .s2s-step:not(:last-child)::after {
    content:''; position:absolute; top:18px; left:50%; width:100%; height:3px;
    background:rgba(255,255,255,.12); z-index:0;
  }
  .s2s-step-bar .s2s-step.active:not(:last-child)::after { background:#b700e0; }
  .s2s-step-bar .s2s-step .s2s-step-circle {
    width:38px; height:38px; border-radius:50%; display:inline-flex; align-items:center;
    justify-content:center; font-weight:700; font-size:1rem; position:relative; z-index:1;
    background:rgba(255,255,255,.1); color:rgba(255,255,255,.4); border:2px solid rgba(255,255,255,.15);
  }
  .s2s-step-bar .s2s-step.active .s2s-step-circle {
    background:#b700e0; color:#fff; border-color:#b700e0;
    box-shadow:0 0 0 5px rgba(183,0,224,.25), 0 0 18px rgba(183,0,224,.4);
    width:42px; height:42px; font-size:1.05rem;
  }
  .s2s-step-bar .s2s-step.done .s2s-step-circle {
    background:rgba(183,0,224,.3); color:#b700e0; border-color:#b700e0;
  }
  .s2s-step-bar .s2s-step-label {
    display:block; font-size:var(--s2s-size-eyebrow); margin-top:0.4rem;
    color:var(--s2s-text-42); line-height:var(--s2s-lh-label);
  }
  .s2s-step-bar .s2s-step.active .s2s-step-label { color:var(--s2s-brand); font-weight:600; }
  .s2s-step-bar .s2s-step.done .s2s-step-label  { color:rgba(183,0,224,.7); }

  /* ── Primary action cards ──────────────────────────────────── */
  .s2s-primary-card {
    border-left: 4px solid #b700e0 !important;
    border-top:none; border-right:none; border-bottom:none;
  }

  /* ── Trust grid ────────────────────────────────────────────── */
  .s2s-trust-item { display:flex; align-items:flex-start; gap:14px; padding:var(--s2s-sp-4) 0; }
  .s2s-trust-item i { font-size:1.5rem; color:var(--s2s-brand); flex-shrink:0; margin-top:2px; }
  .s2s-trust-item h6 { margin:0 0 2px; font-weight:600; font-size:var(--s2s-size-h4); }
  .s2s-trust-item p  { margin:0; font-size:var(--s2s-size-body-sm); color:var(--s2s-text-65); line-height:var(--s2s-lh-body); }

  /* ── Step 2 input group ────────────────────────────────────── */
  .s2s-id-prefix {
    background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.15);
    border-right:none; border-radius:4px 0 0 4px;
    padding:0.55rem 0.75rem; font-size:var(--s2s-size-eyebrow); color:var(--s2s-text-50);
    white-space:nowrap; display:flex; align-items:center;
  }
  .s2s-id-input {
    flex:1; background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.15);
    border-radius:0 4px 4px 0; padding:0.55rem 0.75rem; color:var(--s2s-text-100); font-size:var(--s2s-size-body) !important;
    min-width:0;
  }
  .s2s-id-input:focus { outline:none; border-color:#b700e0; background:rgba(183,0,224,.08); }
  .s2s-id-input::placeholder { color:rgba(255,255,255,.35); }

  /* ── Secondary steps ───────────────────────────────────────── */
  .s2s-secondary-card { opacity:.9; }
  .s2s-secondary-card .card-header h4 { font-size:var(--s2s-size-h4); }

  /* ── Trust footer strip ────────────────────────────────────── */
  .s2s-trust-strip {
    border-top:1px solid rgba(255,255,255,.08);
    padding:var(--s2s-sp-4) var(--s2s-sp-6); display:flex; align-items:center;
    justify-content:center; gap:2rem; flex-wrap:wrap;
    font-size:var(--s2s-size-small); color:var(--s2s-text-42);
  }
  .s2s-trust-strip span { display:flex; align-items:center; gap:6px; }
  .s2s-trust-strip i { color:#b700e0; }
</style>

<?php require_once "parts/navbar.php"; ?>
<div class="wrapper">
  <?php require_once "parts/sidebar.php"; ?>
  <div class="main-panel">
    <div class="main-content">
      <div class="content-overlay"></div>
      <div class="content-wrapper">


        <!-- ═══════════════════════════════════════════════════════════
             SECTION 1 — HERO
        ════════════════════════════════════════════════════════════ -->
        <section class="s2s-hero">
          <div class="row">
            <div class="col-12">
              <div class="card s2s-primary-card" style="background:linear-gradient(135deg,#0d0d1a 0%,#1a1a2e 60%,#200a30 100%);overflow:hidden;position:relative;">
                <!-- Eagle decorative -->
                <img src="app-assets/img/photos/eagle6c.jpg" alt=""
                     style="position:absolute;right:0;bottom:0;height:100%;max-height:340px;
                            object-fit:contain;opacity:.22;pointer-events:none;
                            filter:grayscale(20%) contrast(1.1);">
                <div class="card-body" style="padding:2.5rem 2rem 2.25rem;position:relative;z-index:2;">
                  <div class="row align-items-center">
                    <div class="col-lg-9 col-12">
                      <h1 style="color:var(--s2s-text-100);font-size:var(--s2s-size-h1);font-weight:800;margin-bottom:var(--s2s-sp-2);line-height:var(--s2s-lh-tight);">
                        Complete <span style="color:var(--s2s-brand);">Step 1</span> and Unlock Step 2
                      </h1>
                      <p style="color:var(--s2s-text-80);font-size:var(--s2s-size-body-lg);max-width:580px;margin:0 0 1.6rem;line-height:var(--s2s-lh-body);">
                        Sign up with PM-International using the button below.<br>
                        Then return here, enter your Partner ID, and save it to complete Step 2.
                      </p>
                      <a id="sforpm"
                         href="https://www.pmebusiness.com/registrationv2/?TP=<?= htmlspecialchars($referer_username) ?>"
                         target="_blank"
                         class="btn btn-lg"
                         style="background:#b700e0;border-color:#b700e0;color:#fff;font-size:1.05rem;padding:.75rem 2.25rem;box-shadow:0 4px 20px rgba(183,0,224,.4);font-weight:600;">
                        <i class="ft-external-link mr-2"></i> Join PM-International Now
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>




        <!-- ═══════════════════════════════════════════════════════════
             SECTION 3 — STEP PROGRESS INDICATOR
        ════════════════════════════════════════════════════════════ -->
        <section class="s2s-progress" style="margin-top:.5rem;">
          <div class="row">
            <div class="col-12">
              <div class="card" style="background:rgba(255,255,255,.03);">
                <div class="s2s-step-bar">
                  <div class="s2s-step active">
                    <div class="s2s-step-circle">1</div>
                    <span class="s2s-step-label">Register<br>with PM</span>
                  </div>
                  <div class="s2s-step active">
                    <div class="s2s-step-circle">2</div>
                    <span class="s2s-step-label">Enter<br>Partner ID</span>
                  </div>
                  <div class="s2s-step">
                    <div class="s2s-step-circle">3</div>
                    <span class="s2s-step-label">Order<br>Traffic</span>
                  </div>
                  <div class="s2s-step">
                    <div class="s2s-step-circle">4</div>
                    <span class="s2s-step-label">Product<br>Start</span>
                  </div>
                  <div class="s2s-step">
                    <div class="s2s-step-circle">5</div>
                    <span class="s2s-step-label">Keep<br>Going</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>


        <!-- ═══════════════════════════════════════════════════════════
             SECTION 4 — WHY PM-INTERNATIONAL (trust points)
        ════════════════════════════════════════════════════════════ -->
        <section class="s2s-why-pm" style="margin-top:.5rem;">
          <div class="row">
            <div class="col-12">
              <div class="card">
                <div class="card-content">
                  <div class="card-header">
                    <h4 class="card-title">Why PM-International?</h4>
                  </div>
                  <div class="card-body" style="padding-top:.5rem;">
                    <div class="row">
                      <div class="col-xl-3 col-md-6 col-12">
                        <div class="s2s-trust-item">
                          <i class="ft-calendar"></i>
                          <div>
                            <h6>Founded in 1993</h6>
                            <p>Over 30 years of operational experience in the international health and nutrition market.</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-xl-3 col-md-6 col-12">
                        <div class="s2s-trust-item">
                          <i class="ft-package"></i>
                          <div>
                            <h6>1 Billion+ Products Sold</h6>
                            <p>More than one billion FitLine products sold worldwide — a globally proven brand.</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-xl-3 col-md-6 col-12">
                        <div class="s2s-trust-item">
                          <i class="ft-shield"></i>
                          <div>
                            <h6>70+ Registered Patents</h6>
                            <p>Proprietary NTC technology protected by more than 70 patents across multiple markets.</p>
                          </div>
                        </div>
                      </div>
                      <div class="col-xl-3 col-md-6 col-12">
                        <div class="s2s-trust-item">
                          <i class="ft-award"></i>
                          <div>
                            <h6>1,000+ Top Athletes</h6>
                            <p>Trusted by over 1,000 professional athletes and sports organisations worldwide.</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>


        <!-- ═══════════════════════════════════════════════════════════
             SECTION 5 — BUSINESS PRESENTATION (videos)
        ════════════════════════════════════════════════════════════ -->
        <section class="s2s-presentation" style="margin-top:.5rem;">
          <div class="row match-height">

            <div class="col-12">
              <div class="card">
                <div class="card-header">
                  <h4 class="card-title">Watch the Business Presentation</h4>
                </div>
                <div class="card-body" style="padding-bottom:.25rem;">
                  <p style="opacity:.8;margin-bottom:1.25rem;">
                    Understand the opportunity and why Step 1 matters before you move forward.
                    The presentation below gives you the full picture on PM-International and the Eagle Team system.
                  </p>
                </div>
              </div>
            </div>

            <!-- Video 1 -->
            <div class="col-lg-6 col-12">
              <div class="card">
                <div class="card-content">
                  <div style="padding:62.5% 0 0 0;position:relative;">
                    <iframe src="https://player.vimeo.com/video/1183822471?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479&amp;title=0&amp;byline=0&amp;portrait=0"
                            frameborder="0"
                            allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            style="position:absolute;top:0;left:0;width:100%;height:100%;"
                            title="PM-International Presentation - English"></iframe>
                  </div>
                  <script src="https://player.vimeo.com/api/player.js"></script>
                  <div class="card-body">
                    <h5 style="margin-bottom:.4rem;">Your Partnership with PM-International and the Eagle Team</h5>
                    <p style="font-size:.95rem;opacity:.8;margin:0;">
                      Our team works in partnership with PM-International — a globally established company with a proven track record
                      in health, wellness and nutrition products. We handle the marketing system. You follow the plan.
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Video 2 -->
            <div class="col-lg-6 col-12">
              <div class="card">
                <div class="card-content">
                  <div style="padding:62.5% 0 0 0;position:relative;">
                    <iframe src="https://player.vimeo.com/video/1183845597?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479&amp;title=0&amp;byline=0&amp;portrait=0"
                            frameborder="0"
                            allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share"
                            referrerpolicy="strict-origin-when-cross-origin"
                            style="position:absolute;top:0;left:0;width:100%;height:100%;"
                            title="PM-International Presentation 2"></iframe>
                  </div>
                  <div class="card-body">
                    <h5 style="margin-bottom:.4rem;">Discover PM-International</h5>
                    <p style="font-size:.95rem;opacity:.8;margin:0;">
                      PM-International is active in over 40 countries with a strong partner network and a clear focus
                      on high-quality health and nutrition products. When you register, you become part of this network.
                    </p>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </section>


        <!-- ═══════════════════════════════════════════════════════════
             SECTION 6 — STEP 1 ACTION CARD (primary)
        ════════════════════════════════════════════════════════════ -->
        <section class="s2s-step1-action" style="margin-top:.5rem;">
          <div class="row">
            <div class="col-12">
              <div class="card s2s-primary-card" style="background:rgba(183,0,224,.06);">
                <div class="card-content">
                  <div class="card-header" style="border-bottom:1px solid rgba(183,0,224,.2);">
                    <h4 class="card-title" style="color:#b700e0;">
                      <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:#b700e0;color:#fff;font-size:.9rem;margin-right:10px;">1</span>
                      Create Your PM-International Account & Choose Your Start
                    </h4>
                  </div>
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-lg-8 col-12">
                         <p style="font-size:1rem;margin-bottom:.75rem;">
                          Click the button to open the PM-International registration page in a new tab.
                          While creating your account is free, you will need the mandatory
                          <strong>Starter Kit (Demo Bag)</strong> for approx. $26.80 to activate your partner status.
                        </p>

                        <p style="font-size:1rem;margin-bottom:.5rem;">
                          <strong>🔥 Fast-Track Your Success (Highly Recommended):</strong>  

                          To start earning commissions immediately, we highly recommend choosing one of the
                          product start options directly during registration:
                        </p>

                        <ul style="opacity:.9;font-size:.92rem;padding-left:1.2rem;margin-bottom:1rem;">
                          <li><strong>Teampartner Start</strong> — The standard way to begin your journey</li>
                          <li><strong>Manager Quickstart</strong> — The fastest way to maximize your commissions from day one</li>
                        </ul>

                        <ol style="opacity:.8;font-size:.92rem;padding-left:1.2rem;margin-bottom:1rem;">
                          <li>Click the button (opens in a new tab — keep this page open)</li>
                          <li>Complete the registration &amp; select your preferred Start Option</li>
                          <li>You will receive your personal PM Partner ID</li>
                          <li><strong>Return here immediately</strong> to enter your ID and complete this step!</li>
                        </ol>
                      </div>
                      <div class="col-lg-4 col-12 text-center">
                        <a id="sforpm"
                           href="https://www.pmebusiness.com/registrationv2/?TP=<?= htmlspecialchars($referer_username) ?>"
                           target="_blank"
                           class="btn btn-lg btn-block"
                           style="background:#b700e0;border-color:#b700e0;color:#fff;font-size:1.05rem;padding:.75rem 1.5rem;white-space:normal;">
                          <i class="ft-external-link mr-2"></i>
                          Join PM-International Now
                        </a>
                        <?php if (empty($referer_username)): ?>
                        <small style="color:rgba(255,200,0,.7);display:block;margin-top:.5rem;">
                          <i class="ft-info mr-1"></i> No sponsor ID linked — you will register directly with PM-International.
                        </small>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>


        <!-- ═══════════════════════════════════════════════════════════
             SECTION 7 — STEP 2 ACTION CARD (primary)
        ════════════════════════════════════════════════════════════ -->
        <section class="s2s-step2-action" style="margin-top:.5rem;">
          <div class="row">
            <div class="col-12">
              <div class="card" style="border:2px solid #b700e0;background:rgba(183,0,224,.06);box-shadow:0 0 28px rgba(183,0,224,.18);">
                <div class="card-content">
                  <div class="card-header" style="border-bottom:1px solid rgba(183,0,224,.25);background:rgba(183,0,224,.08);">
                    <h4 class="card-title" style="color:#b700e0;">
                      <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:#b700e0;color:#fff;font-size:.9rem;margin-right:10px;box-shadow:0 0 0 4px rgba(183,0,224,.25);">2</span>
                      Enter Your PM Partner ID
                      <span style="margin-left:.75rem;font-size:.72rem;font-weight:600;background:#b700e0;color:#fff;padding:.2rem .6rem;border-radius:20px;letter-spacing:.04em;vertical-align:middle;">STEP 2</span>
                    </h4>
                  </div>
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-lg-8 col-12">

                        <?php if ($step2_success): ?>
                        <div class="alert bg-light-success mb-2" role="alert" style="font-size:.95rem;">
                          <i class="ft-check-circle mr-2"></i><strong>Step 2 complete.</strong> <?= htmlspecialchars($step2_success) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($step2_error): ?>
                        <div class="alert bg-light-danger mb-2 py-1 px-2" role="alert" style="font-size:.88rem;">
                          <i class="ft-alert-circle mr-1"></i> <?= htmlspecialchars($step2_error) ?>
                        </div>
                        <?php endif; ?>

                        <?php if ($pm_locked && !$is_admin): ?>
                        <!-- ── STATE A: Locked ── -->
                        <div class="alert bg-light-success mb-0" role="alert" style="font-size:.97rem;">
                          <i class="ft-lock mr-2"></i>
                          <strong>Step 2 complete.</strong>
                          Your Partner ID <strong><?= htmlspecialchars($current_user_pm) ?></strong> is saved and locked.
                        </div>
                        <p style="font-size:.85rem;opacity:.6;margin-top:.6rem;margin-bottom:0;">
                          Partner IDs cannot be changed once saved. If you need to update it for an important reason,
                          please contact support.
                        </p>

                        <?php else: ?>
                        <!-- ── STATE C: Form available (always shown when not locked) ── -->
                        <?php if ($is_admin && $pm_locked): ?>
                        <div class="alert bg-light-warning mb-2 py-1 px-2" style="font-size:.83rem;">
                          <i class="ft-shield mr-1"></i>
                          <strong>Admin override active.</strong> You can update the Partner ID even though it is locked.
                        </div>
                        <?php endif; ?>
                        <p style="font-size:1rem;margin-bottom:.75rem;">
                          After completing Step 1, PM-International will provide your personal Partner ID.
                          Enter it below and save it to activate your marketing system.
                        </p>
                        <p style="opacity:.75;font-size:.9rem;margin-bottom:1.25rem;">
                          Once saved, your Partner ID is permanently locked and cannot be changed by you.
                          Contact support if an update is ever needed.
                        </p>
                        <form method="POST" action="welcome.php" style="max-width:520px;">
                          <input type="hidden" name="userid" value="<?= isset($userid) ? (int)$userid : '' ?>">
                          <label style="font-size:.9rem;font-weight:600;color:#b700e0;margin-bottom:.5rem;display:block;letter-spacing:.02em;">
                            YOUR PM PARTNER ID
                          </label>
                          <div style="display:flex;margin-bottom:1rem;">
                            <span class="s2s-id-prefix" style="font-size:.75rem;padding:.75rem .85rem;">pmebusiness.com/…/?TP=</span>
                            <input type="text"
                                   class="s2s-id-input"
                                   name="root"
                                   placeholder="e.g. 6304013"
                                   required
                                   style="font-size:1.15rem;padding:.75rem 1rem;font-weight:600;letter-spacing:.04em;"
                                   value="<?= !empty($current_user_pm) ? htmlspecialchars($current_user_pm) : '' ?>">
                          </div>
                          <button type="submit"
                                  class="btn btn-lg"
                                  style="background:#b700e0;border-color:#b700e0;color:#fff;padding:.75rem 2rem;font-weight:600;font-size:1rem;box-shadow:0 4px 16px rgba(183,0,224,.35);">
                            <i class="ft-save mr-2"></i> Save Partner ID
                          </button>
                        </form>
                        <?php endif; ?>

                      </div>
                      <div class="col-lg-4 col-12 d-none d-lg-flex align-items-center justify-content-center">
                        <div style="text-align:center;opacity:.4;">
                          <i class="ft-key" style="font-size:5rem;color:#b700e0;display:block;margin-bottom:.75rem;"></i>
                          <span style="font-size:.82rem;">Your Partner ID is your key to activating your system</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>


        <!-- ═══════════════════════════════════════════════════════════
             INCOME EXAMPLE — Why Step 3 to 5 matter
        ════════════════════════════════════════════════════════════ -->
        <section class="s2s-income-example" style="margin-top:1.25rem;">
          <div class="row">
            <div class="col-12">
              <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.85rem;padding-left:.15rem;">
                <span style="font-size:.72rem;font-weight:700;letter-spacing:.08em;color:rgba(255,255,255,.35);text-transform:uppercase;">Next Steps — After Steps 1 &amp; 2 are complete</span>
                <span style="flex:1;height:1px;background:rgba(255,255,255,.08);"></span>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <div class="card" style="background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);">
                <div class="card-body" style="padding:var(--s2s-card-pad-lg);">

                  <!-- Headline -->
                  <h3 style="color:var(--s2s-text-100);font-weight:800;margin-bottom:.5rem;text-align:center;">
                    <i class="ft-trending-up" style="color:var(--s2s-brand);margin-right:10px;"></i>
                    Why Step 3 to 5 Are Your Keys to Financial Freedom
                  </h3>
                  <p style="color:var(--s2s-text-80);font-size:var(--s2s-size-body-lg);text-align:center;margin-bottom:2.5rem;max-width:800px;margin-left:auto;margin-right:auto;line-height:var(--s2s-lh-body);">
                    The Simple2Success system is designed to unlock <strong>multiple income streams simultaneously</strong> through the PM-International compensation plan. Here is exactly what happens when you turn on your traffic:
                  </p>

                  <div class="row" style="align-items:stretch;">

                    <!-- Box 1: Immediate Cash Flow -->
                    <div class="col-lg-4 col-md-12 mb-4">
                      <div style="background:rgba(255,255,255,.04);padding:var(--s2s-card-pad);border-radius:var(--s2s-radius-lg);border-top:4px solid rgba(183,0,224,.5);height:100%;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:-15px;right:-15px;opacity:.08;"><i class="ft-zap" style="font-size:6rem;color:var(--s2s-brand);"></i></div>
                        <p style="color:rgba(183,0,224,.7);font-weight:800;font-size:var(--s2s-size-eyebrow);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.8rem;">
                          1. Immediate Cash Flow
                        </p>
                        <h4 style="color:var(--s2s-text-100);font-weight:700;margin-bottom:var(--s2s-sp-4);">Training Bonus (EAB)</h4>
                        <p style="color:var(--s2s-text-80);font-size:var(--s2s-size-body);margin-bottom:var(--s2s-sp-4);line-height:var(--s2s-lh-compact);">
                          Every time your automated traffic generates a new active partner, PM pays you a direct <strong>€60 Training Bonus</strong>.
                        </p>
                        <div style="background:var(--s2s-brand-subtle);padding:10px;border-radius:var(--s2s-radius-sm);border-left:3px solid rgba(183,0,224,.5);">
                          <p style="color:var(--s2s-text-100);font-size:1.1rem;font-weight:700;margin-bottom:0;">
                            5 Partners = <span style="color:rgba(183,0,224,.9);">€300 instantly</span>
                          </p>
                          <small style="color:var(--s2s-text-65);">Covers your traffic costs immediately.</small>
                        </div>
                      </div>
                    </div>

                    <!-- Box 2: Monthly Passive Income -->
                    <div class="col-lg-4 col-md-12 mb-4">
                      <div style="background:rgba(255,255,255,.04);padding:var(--s2s-card-pad);border-radius:var(--s2s-radius-lg);border-top:4px solid rgba(183,0,224,.75);height:100%;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:-15px;right:-15px;opacity:.08;"><i class="ft-refresh-cw" style="font-size:6rem;color:var(--s2s-brand);"></i></div>
                        <p style="color:rgba(183,0,224,.85);font-weight:800;font-size:var(--s2s-size-eyebrow);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.8rem;">
                          2. Monthly Passive Income
                        </p>
                        <h4 style="color:var(--s2s-text-100);font-weight:700;margin-bottom:var(--s2s-sp-4);">First-Line Bonus (EB)</h4>
                        <p style="color:var(--s2s-text-80);font-size:var(--s2s-size-body);margin-bottom:var(--s2s-sp-4);line-height:var(--s2s-lh-compact);">
                          You earn a <strong>10% Bonus</strong> on the business volume of your direct partners' monthly product subscriptions (Autoship).
                        </p>
                        <div style="background:var(--s2s-brand-subtle);padding:10px;border-radius:var(--s2s-radius-sm);border-left:3px solid rgba(183,0,224,.75);">
                          <p style="color:var(--s2s-text-100);font-size:1.1rem;font-weight:700;margin-bottom:0;">
                            Paid <span style="color:rgba(183,0,224,.9);">Month After Month</span>
                          </p>
                          <small style="color:var(--s2s-text-65);">Builds your secure financial foundation.</small>
                        </div>
                      </div>
                    </div>

                    <!-- Box 3: Exponential Wealth -->
                    <div class="col-lg-4 col-md-12 mb-4">
                      <div style="background:rgba(255,255,255,.04);padding:var(--s2s-card-pad);border-radius:var(--s2s-radius-lg);border-top:4px solid var(--s2s-brand);height:100%;position:relative;overflow:hidden;">
                        <div style="position:absolute;top:-15px;right:-15px;opacity:.08;"><i class="ft-star" style="font-size:6rem;color:var(--s2s-brand);"></i></div>
                        <p style="color:var(--s2s-brand);font-weight:800;font-size:var(--s2s-size-eyebrow);text-transform:uppercase;letter-spacing:.1em;margin-bottom:.8rem;">
                          3. Exponential Wealth
                        </p>
                        <h4 style="color:var(--s2s-text-100);font-weight:700;margin-bottom:var(--s2s-sp-4);">Deep Bonuses &amp; Lifestyle</h4>
                        <p style="color:var(--s2s-text-80);font-size:var(--s2s-size-body);margin-bottom:var(--s2s-sp-4);line-height:var(--s2s-lh-compact);">
                          When your partners duplicate this system, you unlock <strong>Deep Bonuses (3-5%)</strong> and <strong>Management Bonuses (2-21%)</strong> on your entire organization.
                        </p>
                        <div style="background:var(--s2s-brand-subtle);padding:10px;border-radius:var(--s2s-radius-sm);border-left:3px solid var(--s2s-brand);">
                          <p style="color:var(--s2s-text-100);font-size:1.1rem;font-weight:700;margin-bottom:0;">
                            <span style="color:var(--s2s-brand);">+ Car Bonus &amp; Travel</span>
                          </p>
                          <small style="color:var(--s2s-text-65);">Drive your dream car paid by PM.</small>
                        </div>
                      </div>
                    </div>

                  </div><!-- /row -->

                  <!-- CTA summary -->
                  <div style="margin-top:var(--s2s-sp-4);padding:var(--s2s-card-pad);background:rgba(183,0,224,.15);border-radius:var(--s2s-radius);border:1px solid rgba(183,0,224,.5);text-align:center;box-shadow:inset 0 0 20px rgba(183,0,224,0.1);">
                    <h4 style="color:var(--s2s-text-100);font-weight:800;margin-bottom:.5rem;">
                      The Math is Simple: <span style="color:var(--s2s-brand);">Traffic = Duplication = Freedom</span>
                    </h4>
                    <p style="color:var(--s2s-text-80);font-size:var(--s2s-size-body);margin-bottom:0;max-width:750px;margin-left:auto;margin-right:auto;line-height:var(--s2s-lh-body);">
                      If you don't order traffic (Step 3) and activate your product (Step 4), the system stops here.
                      <strong>Turn on your traffic now</strong> to start the engine and let the Simple2Success automation build your team!
                    </p>
                  </div>

                  <!-- Link to full compensation plan -->
                  <div style="text-align:center;margin-top:1.25rem;">
                    <a href="/simple2success/docs/en/EN_PM_IncomePlan_Brochure-APAC-2026-2.pdf"
                       target="_blank"
                       rel="noopener"
                       style="color:rgba(255,255,255,.35);font-size:.82rem;text-decoration:none;border-bottom:1px dashed rgba(255,255,255,.2);padding-bottom:2px;transition:color .2s;">
                      <i class="ft-file-text mr-1"></i>
                      View the full PM-International Income Plan (PDF) →
                    </a>
                  </div>

                </div>
              </div>
            </div>
          </div>
        </section>
 

        
        <!-- ═══════════════════════════════════════════════════════════
             SECTION 8 — STEPS 3 / 4 / 5 (secondary)
        ════════════════════════════════════════════════════════════ -->
        <section class="s2s-next-steps" style="margin-top:1.25rem;">
          <!-- Pure CSS flex row — no match-height JS. Bootstrap 4 .row is already
               display:flex;flex-wrap:wrap;align-items:stretch, so all columns
               equalise to the tallest. height:100% on .card fills the column,
               then the flex chain propagates all the way to the CTA. -->
          <div class="row" style="align-items:stretch;">

            <!-- STEP 3 -->
            <div class="col-lg-4 col-md-6 col-12" style="display:flex;flex-direction:column;">
              <div class="card s2s-secondary-card" style="border-top:3px solid rgba(183,0,224,.4);flex:1;display:flex;flex-direction:column;">
                <div class="card-header" style="padding-bottom:.5rem;flex-shrink:0;">
                  <h4 class="card-title" style="display:flex;align-items:center;gap:10px;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:rgba(183,0,224,.25);color:#b700e0;font-size:.82rem;font-weight:700;flex-shrink:0;">3</span>
                    Order Traffic
                  </h4>
                </div>
                <div class="card-content" style="flex:1;display:flex;flex-direction:column;">
                  <img class="img-fluid" src="app-assets/img/photos/step3.jpg" alt="Step 3" style="opacity:.88;flex-shrink:0;">
                  <div class="card-body" style="flex:1;display:flex;flex-direction:column;">
                    <div style="flex:1;">
                      <p style="font-size:.92rem;margin-bottom:0;">
                        Boost your reach with our trusted traffic sources. More traffic means more leads,
                        and more leads means more growth.
                      </p>
                    </div>
                    <div style="margin-top:auto;padding-top:1rem;">
                      <a href="traffic.php" class="btn btn-primary btn-block">
                        <i class="ft-trending-up mr-1"></i> Order Traffic
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

           <!-- STEP 4 -->
            <div class="col-lg-4 col-md-6 col-12" style="display:flex;flex-direction:column;">
              <div class="card s2s-secondary-card" style="border-top:3px solid rgba(183,0,224,.4);flex:1;display:flex;flex-direction:column;">
                <div class="card-header" style="padding-bottom:.5rem;flex-shrink:0;">
                  <h4 class="card-title" style="display:flex;align-items:center;gap:10px;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:rgba(183,0,224,.25);color:#b700e0;font-size:.82rem;font-weight:700;flex-shrink:0;">4</span>
                    Activate Your Product Subscription
                  </h4>
                </div>
                <div class="card-content" style="flex:1;display:flex;flex-direction:column;">
                  <img class="img-fluid" src="app-assets/img/photos/step4.jpg" alt="Step 4" style="opacity:.88;flex-shrink:0;">
                  <div class="card-body" style="flex:1;display:flex;flex-direction:column;">
                    <div style="flex:1;">
                      <p style="font-size:.92rem;margin-bottom:.5rem;">
                        Not yet started with a product subscription? Activate it now to unlock your full earning potential.
                        Choose between <strong>Teampartner Start</strong> or <strong>Manager Quickstart</strong> — your monthly autoship activates your commissions and gives you authentic product experience.
                      </p>
                      <p style="font-size:.82rem;opacity:.6;margin-bottom:0;">
                        Already selected a start option during registration? Then you're all set — your autoship will ship automatically next month.
                      </p>
                    </div>
                    <div style="margin-top:auto;padding-top:1rem;">
                      <?php if (!empty($current_user_pm)): ?>
                        <a href="https://www.fitline.com/autoship/create?sponsor=<?= urlencode($current_user_pm ) ?>&productId=9700732"
                           target="_blank"
                           class="btn btn-primary btn-block">
                          <i class="ft-external-link mr-1"></i> Activate My Product Subscription
                        </a>
                      <?php else: ?>
                        <button class="btn btn-secondary btn-block" disabled>
                          <i class="ft-lock mr-1"></i> Complete Step 2 First
                        </button>
                        <small style="opacity:.5;display:block;margin-top:.4rem;font-size:.78rem;text-align:center;">
                          Save your Partner ID in Step 2 to unlock this.
                        </small>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- STEP 5 -->
            <div class="col-lg-4 col-md-6 col-12" style="display:flex;flex-direction:column;">
              <div class="card s2s-secondary-card" style="border-top:3px solid rgba(183,0,224,.4);flex:1;display:flex;flex-direction:column;">
                <div class="card-header" style="padding-bottom:.5rem;flex-shrink:0;">
                  <h4 class="card-title" style="display:flex;align-items:center;gap:10px;">
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:rgba(183,0,224,.25);color:#b700e0;font-size:.82rem;font-weight:700;flex-shrink:0;">5</span>
                    Keep the Momentum Going
                  </h4>
                </div>
                <div class="card-content" style="flex:1;display:flex;flex-direction:column;">
                  <img class="img-fluid" src="app-assets/img/photos/step5.jpg" alt="Step 5" style="opacity:.88;flex-shrink:0;">
                  <div class="card-body" style="flex:1;display:flex;flex-direction:column;">
                    <div style="flex:1;">
                      <p style="font-size:.92rem;margin-bottom:0;">
                        Repeat Step 3 — order traffic from our trusted traffic sources.
                        Consistency is the key to long-term success. Keep the leads coming and keep moving forward.
                      </p>
                    </div>
                    <div style="margin-top:auto;padding-top:1rem;">
                      <a href="traffic.php" class="btn btn-primary btn-block">
                        <i class="ft-repeat mr-1"></i> Show Traffic Sources
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </section>


        <!-- ═══════════════════════════════════════════════════════════
             SECTION 9 — TRUST FOOTER STRIP
        ════════════════════════════════════════════════════════════ -->
        <section class="s2s-trust-footer" style="margin-top:.25rem;margin-bottom:1.5rem;">
          <div class="row">
            <div class="col-12">
              <div class="card" style="background:rgba(255,255,255,.02);">
                <div class="s2s-trust-strip">
                  <span><i class="ft-shield"></i> In partnership with PM-International</span>
                  <span><i class="ft-calendar"></i> Est. 1993</span>
                  <span><i class="ft-globe"></i> Active in 40+ countries</span>
                  <span><i class="ft-award"></i> 1,000+ top athletes trust FitLine</span>
                </div>
              </div>
            </div>
          </div>
        </section>


      </div><!-- /.content-wrapper -->
    </div><!-- /.main-content -->

    <?php require_once "parts/footer.php"; ?>
    <button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>
  </div><!-- /.main-panel -->
</div><!-- /.wrapper -->

<div class="sidenav-overlay"></div>
<div class="drag-target"></div>

<script src="app-assets/vendors/js/vendors.min.js"></script>
<script src="app-assets/vendors/js/switchery.min.js"></script>
<script src="app-assets/vendors/js/chartist.min.js"></script>
<script src="app-assets/vendors/js/plyr.min.js"></script>
<script src="app-assets/js/core/app-menu.js"></script>
<script src="app-assets/js/core/app.js"></script>
<script src="app-assets/js/notification-sidebar.js"></script>
<script src="app-assets/js/customizer.js"></script>
<script src="app-assets/js/scroll-top.js"></script>
<script src="app-assets/js/dashboard1.js"></script>
<script src="app-assets/js/ex-component-media-player.js"></script>
<script src="assets/js/scripts.js"></script>

<script>
  // Init Plyr video players
  document.addEventListener('DOMContentLoaded', () => {
    new Plyr('#plyr-video-player');
    new Plyr('#plyr-video-player-2');
  });

  // Track Step 1 PM-International registration click
  $(document).ready(function () {
    $("#sforpm").on("click", function () {
      $.post("<?= $baseurl ?>/backoffice/start.php", {
        click:  "<?= date('Y-m-d H:i:s') ?>",
        userid: "<?= (int)$_SESSION['userid'] ?>"
      }, function (data, status) {
        console.log("Step 1 tracked: " + status);
      });
    });
  });
</script>
</body>
</html>
