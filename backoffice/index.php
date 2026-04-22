<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}
include '../includes/conn.php';
$userid  = (int)$_SESSION['userid'];
$userRow = mysqli_fetch_assoc(mysqli_query($link, "SELECT name FROM users WHERE leadid=$userid"));
$userName = !empty($userRow['name']) ? htmlspecialchars($userRow['name']) : '';
?>
<?php require_once "parts/head.php"; ?>
<body class="vertical-layout vertical-menu 2-columns navbar-static layout-dark"
      data-menu="vertical-menu" data-col="2-columns">

<style>
/* ═══════════════════════════════════════════════════════════════════
   EAGLE TEAM ONBOARDING — custom premium template
   index.php
═══════════════════════════════════════════════════════════════════ */

/* ── Base resets for this page ─────────────────────────────────── */
.et-page .card { border-radius: 8px; }
.et-page section { margin-bottom: .5rem; }

/* ── Shared: CTA button ────────────────────────────────────────── */
.et-btn {
  display: inline-block;
  background: var(--s2s-brand);
  color: var(--s2s-text-100) !important;
  font-size: var(--s2s-size-body-lg);
  font-weight: 700;
  padding: .82rem 2.2rem;
  border-radius: var(--s2s-radius-sm);
  text-decoration: none !important;
  box-shadow: 0 4px 22px var(--s2s-brand-glow);
  transition: background .18s, box-shadow .18s, transform .12s;
  letter-spacing: .02em;
  border: none;
  cursor: pointer;
}
.et-btn:hover {
  background: var(--s2s-brand-hover);
  box-shadow: 0 6px 30px rgba(183,0,224,.65);
  transform: translateY(-1px);
  color: var(--s2s-text-100) !important;
}

/* ── Shared: split section ─────────────────────────────────────── */
.et-split {
  display: flex;
  border-radius: 8px;
  overflow: hidden;
  min-height: 380px;
}
.et-split-text {
  flex: 0 0 50%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 3.5rem 3rem;
}
.et-split-img {
  flex: 0 0 50%;
  position: relative;
  overflow: hidden;
}
.et-split-img img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center top;
  display: block;
}
/* gradient seam — left-to-right (text left, image right) */
.et-split-img.seam-left::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to right, #0f0f1c 0%, transparent 28%);
  z-index: 2;
  pointer-events: none;
}
/* gradient seam — right-to-left (image left, text right) */
.et-split-img.seam-right::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(to left, #0f0f1c 0%, transparent 28%);
  z-index: 2;
  pointer-events: none;
}
.et-split-img::after {
  content: '';
  position: absolute;
  inset: 0;
  background: rgba(8,4,18,.28);
  z-index: 1;
  pointer-events: none;
}

/* ── Section 1: Hero Banner ────────────────────────────────────── */
.et-hero-banner {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  min-height: 440px;
  background-image: url('app-assets/img/photos/eagle10.png');
  background-size: cover;
  background-position: center right;
  background-repeat: no-repeat;
  box-shadow: 0 0 0 1px rgba(183,0,224,.28), 0 8px 48px rgba(183,0,224,.16);
  display: flex;
  align-items: center;
}
/* dark-to-transparent left overlay — keeps text readable */
.et-hero-banner::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to right,
    rgba(6,3,16,.97) 0%,
    rgba(6,3,16,.90) 28%,
    rgba(6,3,16,.54) 62%,
    rgba(6,3,16,.10) 78%,
    transparent 100%
  );
  z-index: 1;
  pointer-events: none;
}
.et-hero-content {
  position: relative;
  z-index: 2;
  padding: 4rem 2.25rem 4rem 3.75rem;
  max-width: 760px;
}
.et-hero-eyebrow {
  font-size: var(--s2s-size-eyebrow);
  font-weight: 700;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--s2s-brand);
  margin-bottom: var(--s2s-section-eyebrow-gap);
}
.et-hero-title {
  font-size: var(--s2s-size-h1);
  font-weight: 800;
  color: var(--s2s-text-100);
  line-height: var(--s2s-lh-tight);
  margin-bottom: var(--s2s-sp-3);
}
.et-hero-sub {
  font-size: var(--s2s-size-body-lg);
  color: var(--s2s-text-80);
  line-height: var(--s2s-lh-body);
  margin-bottom: var(--s2s-sp-2);
}
.et-hero-note {
  font-size: var(--s2s-size-body-sm);
  color: var(--s2s-text-42);
  margin-bottom: var(--s2s-sp-3);
  line-height: var(--s2s-lh-compact);
}

/* Hero bullet list */
.et-hero-bullets {
  list-style: none;
  padding: 0;
  margin: 0 0 1.75rem;
}
.et-hero-bullets li {
  display: flex;
  align-items: center;
  gap: .6rem;
  font-size: var(--s2s-size-body);
  color: var(--s2s-text-80);
  font-weight: 600;
  margin-bottom: .45rem;
}
.et-hero-bullets li::before {
  content: '';
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #b700e0;
  flex-shrink: 0;
  box-shadow: 0 0 8px rgba(183,0,224,.7);
}

/* ── Section 2: Status bar ─────────────────────────────────────── */
.et-status {
  background: linear-gradient(90deg, #0a2a12 0%, #0d3318 100%);
  border-left: 4px solid #28c76f;
  border-radius: var(--s2s-radius-sm);
  padding: .85rem var(--s2s-sp-6);
  display: flex;
  align-items: center;
  gap: var(--s2s-sp-3);
  font-size: var(--s2s-size-body);
  color: var(--s2s-text-80);
}
.et-status i { color: #28c76f; font-size: 1.15rem; flex-shrink: 0; }
.et-status strong { color: #28c76f; }

/* ── Section 3: Aspiration grid ────────────────────────────────── */
/* shared eyebrow used in aspiration + solution sections */
.et-section-eyebrow {
  font-size: var(--s2s-size-eyebrow);
  font-weight: 700;
  letter-spacing: .16em;
  text-transform: uppercase;
  color: var(--s2s-brand);
  margin-bottom: var(--s2s-section-eyebrow-gap);
  display: block;
}
.et-section-header {
  text-align: center;
  padding: var(--s2s-section-pt) var(--s2s-sp-8) var(--s2s-section-pb);
}
.et-section-header h3 {
  font-size: var(--s2s-size-h3);
  font-weight: 800;
  color: var(--s2s-text-100);
  margin-bottom: var(--s2s-section-title-gap);
}
.et-section-header p {
  font-size: var(--s2s-size-body);
  color: var(--s2s-text-50);
  margin: 0 auto;
  max-width: 600px;
  line-height: var(--s2s-lh-body);
}

.et-aspiration-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
  margin-top: 1.75rem;
}
/* 4-column variant (Section 3) */
.et-aspiration-grid--4col {
  grid-template-columns: repeat(4, 1fr);
}
/* default: subtle border tiles (Section 5) */
.et-aspiration-card {
  background: transparent;
  border: 1px solid rgba(255,255,255,.07);
  padding: 1.4rem 1.25rem;
  display: flex;
  align-items: flex-start;
  gap: 18px;
  transition: background .18s, border-color .18s;
}
.et-aspiration-card:hover {
  background: rgba(183,0,224,.05);
  border-color: rgba(183,0,224,.22);
}
/* frameless/open variant (Section 3) */
.et-grid-open .et-aspiration-card {
  border: none;
  padding: 1.85rem 1.5rem;
}
.et-grid-open .et-aspiration-card:hover {
  background: transparent;
  border: none;
}
.et-aspiration-card i {
  font-size: 1.6rem;
  color: var(--s2s-brand);
  flex-shrink: 0;
  margin-top: 2px;
}
.et-aspiration-card h5 {
  color: var(--s2s-text-100);
  font-size: var(--s2s-size-h4);
  font-weight: 700;
  margin: 0 0 var(--s2s-sp-1);
}
.et-aspiration-card p {
  color: var(--s2s-text-50);
  font-size: var(--s2s-size-body-sm);
  margin: 0;
  line-height: var(--s2s-lh-body);
}

/* ── Section 4: Why Most People — reversed split ───────────────── */
.et-why {
  background: #0f0f1c;
  border-right: 5px solid #b700e0;
}
.et-why .et-split-text { padding: 3.5rem 3rem; }
.et-why-title {
  font-size: var(--s2s-size-h2);
  font-weight: 800;
  color: var(--s2s-text-100);
  line-height: var(--s2s-lh-tight);
  margin-bottom: var(--s2s-sp-4);
}
.et-why-title span { color: var(--s2s-brand); }
.et-why p {
  font-size: var(--s2s-size-body);
  color: var(--s2s-text-65);
  line-height: var(--s2s-lh-loose);
  margin-bottom: var(--s2s-sp-3);
}
.et-why p:last-child { margin-bottom: 0; }

/* ── Section 5: Solution block ─────────────────────────────────── */
.et-solution-wrap {
  background: linear-gradient(135deg, #10082a 0%, #1a1040 100%);
  border-radius: 8px;
  overflow: hidden;
}
.et-solution-header {
  text-align: center;
  padding: 3rem 2.5rem 2.5rem;
  border-bottom: 1px solid rgba(255,255,255,.07);
}
.et-solution-header .et-section-eyebrow { margin-bottom: var(--s2s-section-eyebrow-gap); }
.et-solution-header h3 {
  font-size: var(--s2s-size-h3);
  font-weight: 800;
  color: var(--s2s-text-100);
  margin: 0 0 var(--s2s-section-title-gap);
  line-height: var(--s2s-lh-tight);
}
.et-solution-header p {
  font-size: var(--s2s-size-body);
  color: var(--s2s-text-50);
  margin: 0 auto;
  max-width: 560px;
  line-height: var(--s2s-lh-body);
}
.et-solution-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
}
.et-solution-item {
  padding: 2.5rem 2.25rem;
  border-right: 1px solid rgba(255,255,255,.07);
}
.et-solution-item:last-child { border-right: none; }
.et-solution-item i {
  display: block;
  font-size: 1.8rem;
  color: #b700e0;
  margin-bottom: 1rem;
}
.et-solution-item h5 {
  font-size: var(--s2s-size-h4);
  font-weight: 800;
  color: var(--s2s-text-100);
  margin: 0 0 var(--s2s-sp-2);
  line-height: var(--s2s-lh-heading);
}
.et-solution-item p {
  font-size: var(--s2s-size-body-sm);
  color: var(--s2s-text-50);
  line-height: var(--s2s-lh-body);
  margin: 0;
}

/* ── Section 6: Final CTA ──────────────────────────────────────── */
.et-cta {
  background: linear-gradient(135deg, #0a0a16 0%, #140828 100%);
  border-left: 5px solid #b700e0;
  border-radius: 8px;
  overflow: hidden;
  min-height: 420px;
}
.et-cta .et-split-text {
  padding: 3.5rem 2.75rem 3.5rem 4rem;
  align-items: flex-start;
}
/* guarantee button never stretches — belt + braces */
.et-cta .et-btn {
  align-self: flex-start;
  width: auto;
  max-width: 280px;
}
/* ensure image column has visible height */
.et-cta .et-split-img {
  min-height: 420px;
}
.et-cta-eyebrow {
  font-size: var(--s2s-size-eyebrow);
  letter-spacing: .14em;
  text-transform: uppercase;
  color: var(--s2s-brand);
  font-weight: 700;
  margin-bottom: var(--s2s-section-eyebrow-gap);
}
.et-cta-title {
  font-size: var(--s2s-size-h2);
  font-weight: 800;
  color: var(--s2s-text-100);
  line-height: var(--s2s-lh-tight);
  margin-bottom: var(--s2s-sp-3);
}
.et-cta-body {
  font-size: var(--s2s-size-body);
  color: var(--s2s-text-65);
  line-height: var(--s2s-lh-body);
  margin-bottom: var(--s2s-sp-8);
}

/* ── Tablet ─────────────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .et-hero-content {
    max-width: 660px;
    padding: 3rem 2rem 3rem 3rem;
  }

  .et-cta .et-split-text {
    padding: 3rem 2rem 3rem 3rem;
  }

  /* font-size + section header padding handled by token overrides in style.css */

  .et-aspiration-grid--4col {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* ── Mobile ────────────────────────────────────────────────────── */
@media (max-width: 767px) {
  .et-split {
    flex-direction: column;
    min-height: auto;
  }

  .et-split-text {
    flex: none;
    padding: 2rem 1.5rem !important;
  }

  .et-split-img {
    flex: none;
    min-height: 220px;
  }

  .et-why {
    flex-direction: column-reverse !important;
    border-right: none;
    border-left: 4px solid var(--s2s-brand);
  }

  .et-hero-banner {
    min-height: 340px;
    background-position: 70% center;
  }

  .et-hero-banner::before {
    background: linear-gradient(
      to bottom,
      rgba(6,3,16,.85) 0%,
      rgba(6,3,16,.70) 60%,
      rgba(6,3,16,.55) 100%
    );
  }

  .et-hero-content {
    padding: 2rem 1.5rem;
    max-width: 100%;
  }

  /* all font-sizes now scale via token overrides in style.css */

  .et-grid-open .et-aspiration-card {
    padding: 1.5rem 1.25rem;
  }

  .et-aspiration-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .et-aspiration-grid--4col {
    grid-template-columns: 1fr;
  }

  .et-solution-grid {
    grid-template-columns: 1fr;
  }

  .et-solution-item {
    border-right: none;
    border-bottom: 1px solid rgba(255,255,255,.07);
    padding: 2rem 1.75rem;
  }

  .et-solution-item:last-child {
    border-bottom: none;
  }

  .et-cta .et-split-img {
    min-height: 240px;
  }

  .et-cta .et-btn {
    max-width: 100%;
  }
}
@media (max-width: 480px) {
  .et-aspiration-grid { grid-template-columns: 1fr; }
}
</style>

<?php require_once "parts/navbar.php"; ?>

<div class="wrapper">
  <?php require_once "parts/sidebar.php"; ?>

  <div class="main-panel">
    <div class="main-content">
      <div class="content-overlay"></div>
      <div class="content-wrapper et-page">


<!-- ═══════════════════════════════════════════════════════════
     SECTION 2 — STATUS BAR
════════════════════════════════════════════════════════════ -->
<section>
  <div class="et-status">
    <i class="ft-check-circle"></i>
    <span><strong>Your free account is active.</strong> Your next step is ready.</span>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════════
     SECTION 1 — HERO: full-bleed banner, eagle10.png background
════════════════════════════════════════════════════════════ -->
<section>
  <div class="et-hero-banner" style="margin-bottom:0;">
    <div class="et-hero-content">
      <div class="et-hero-eyebrow">Eagle Team &bull; Simple2Success</div>
      <h1 class="et-hero-title">
        Welcome<?= $userName ? ', ' . $userName : '' ?> —<br>
        To The Simple2Success Eagle Team.
      </h1>
      <p class="et-hero-sub">
        You now have access to a proven step-by-step system designed to help ordinary people build additional income online.
      </p>
      <p class="et-hero-note">
        You do not need to know everything today.
        You do not need to keep changing the plan.
        You simply need the next step and the discipline to follow it.
      </p>
      <ul class="et-hero-bullets">
        <li>A Proven Step-By-Step System</li>
        <li>Clear Daily Actions</li>
        <li>A Real Path You Can Follow</li>
      </ul>
      <a href="start.php" class="et-btn">Continue To Your Next Step &rarr;</a>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════════
     SECTION 3 — ASPIRATION GRID
════════════════════════════════════════════════════════════ -->
<section>
  <div class="card" style="margin-bottom:0;padding-bottom:2.5rem;">
    <div class="et-section-header">
      <span class="et-section-eyebrow">The Opportunity</span>
      <h3>What Would More Progress Mean For You?</h3>
      <p>You came here for a reason. Let that reason pull you forward.</p>
    </div>
    <div class="et-aspiration-grid et-aspiration-grid--4col et-grid-open" style="margin:0 2.5rem 0;">
      <div class="et-aspiration-card">
        <i class="ft-crosshair"></i>
        <div><h5>More Clarity</h5><p>Know exactly what to do next.</p></div>
      </div>
      <div class="et-aspiration-card">
        <i class="ft-repeat"></i>
        <div><h5>More Consistency</h5><p>Repeat the right steps instead of changing direction.</p></div>
      </div>
      <div class="et-aspiration-card">
        <i class="ft-sliders"></i>
        <div><h5>More Control</h5><p>Your progress depends on your actions.</p></div>
      </div>
      <div class="et-aspiration-card">
        <i class="ft-activity"></i>
        <div><h5>More Momentum</h5><p>Small daily actions add up over time.</p></div>
      </div>
      <div class="et-aspiration-card">
        <i class="ft-trending-up"></i>
        <div><h5>More Income</h5><p>Build additional income step by step.</p></div>
      </div>
      <div class="et-aspiration-card">
        <i class="ft-globe"></i>
        <div><h5>More Flexibility</h5><p>Work from anywhere, on your own schedule.</p></div>
      </div>
      <div class="et-aspiration-card">
        <i class="ft-anchor"></i>
        <div><h5>More Stability</h5><p>Build on a system, not on motivation.</p></div>
      </div>
      <div class="et-aspiration-card">
        <i class="ft-sun"></i>
        <div><h5>More Freedom</h5><p>Create more choice for your future.</p></div>
      </div>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════════
     SECTION 4 — WHY MOST PEOPLE FAIL: reversed split
     Image LEFT, text RIGHT
════════════════════════════════════════════════════════════ -->
<section>
  <div class="card et-split et-why" style="margin-bottom:0;flex-direction:row-reverse;">

    <!-- Right: text (appears right on desktop due to row-reverse) -->
    <div class="et-split-text">
      <h2 class="et-why-title">
        Why Most People<br>
        <span>Never Get Results</span>
      </h2>
      <p>
        They try one thing, then jump to the next. They consume more information, but never stay with one process long enough to see results.
      </p>
      <p>
        They keep changing direction. They overthink simple actions. They start again and again instead of repeating what already works.
      </p>
      <p>
        The problem is never effort. The problem is <strong style="color:#fff;">no system. No structure. No clear next step.</strong>
      </p>
    </div>

    <!-- Left: eagle #2 — strategic / focused -->
    <div class="et-split-img seam-right">
      <img src="app-assets/img/photos/eagle6b.png" alt="Focus">
    </div>

  </div>
</section>


<!-- ═══════════════════════════════════════════════════════════
     SECTION 5 — SOLUTION
════════════════════════════════════════════════════════════ -->
<section>
  <div class="card" style="margin-bottom:0;padding-bottom:2.5rem;">
    <div class="et-section-header">
      <span class="et-section-eyebrow">The Solution</span>
      <h3>That Is Why The Eagle Team Exists</h3>
      <p>We built this system to give you what most people never have — clear structure, simple actions and a path you can actually follow.</p>
    </div>
    <div class="et-aspiration-grid et-grid-open" style="margin:0 2.5rem 0;">
      <div class="et-aspiration-card">
        <i class="ft-list"></i>
        <div><h5>Clear Step-By-Step Plan</h5><p>No guessing. No overwhelm. You know exactly what to do next at every stage.</p></div>
      </div>
      <div class="et-aspiration-card">
        <i class="ft-zap"></i>
        <div><h5>Proven Marketing System</h5><p>A ready-to-use funnel and tools that help you stay focused on the actions that matter.</p></div>
      </div>
      <div class="et-aspiration-card">
        <i class="ft-repeat"></i>
        <div><h5>Built For Consistency</h5><p>The goal is not constant change. The goal is to follow the right process long enough to get results.</p></div>
      </div>
    </div>
  </div>
</section>


<!-- ═══════════════════════════════════════════════════════════
     SECTION 6 — FINAL CTA: 50/50 split, text left, eagle right
════════════════════════════════════════════════════════════ -->
<section style="margin-bottom:2rem;">
  <div class="et-split et-cta" style="margin-bottom:0;">

    <!-- Left: text + CTA -->
    <div class="et-split-text">
      <div class="et-cta-eyebrow">Your Next Move</div>
      <h2 class="et-cta-title">Your Next Step<br>Starts Now.</h2>
      <p class="et-cta-body">
        With the Eagle Team system, you do not have to guess or start over again.<br><br>
        You now have a clear direction, a step-by-step system and the right next move.<br><br>
        On the next page, you will see exactly what to do next.
      </p>
      <a href="start.php" class="et-btn">See The Next Step &rarr;</a>
    </div>

    <!-- Right: eagle #3 — dynamic / momentum -->
    <div class="et-split-img seam-left">
      <img src="app-assets/img/photos/eagle7b.jpg" alt="Move forward">
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
<script src="app-assets/js/core/app-menu.js"></script>
<script src="app-assets/js/core/app.js"></script>
<script src="app-assets/js/notification-sidebar.js"></script>
<script src="app-assets/js/customizer.js"></script>
<script src="app-assets/js/scroll-top.js"></script>
<script src="assets/js/scripts.js"></script>
</body>
</html>
