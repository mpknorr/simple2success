<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}
include '../includes/conn.php';
$userid = $_SESSION['userid'];
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<?php require_once "parts/head.php"; ?>
<style>
/* ── Subscription Coming Soon — local styles ─────────────────── */
.sub-page {
    min-height: calc(100vh - 120px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 3rem 1.5rem;
}

.sub-hero {
    max-width: 720px;
    width: 100%;
    text-align: center;
}

/* Badge */
.sub-badge {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    background: rgba(183,0,224,.12);
    border: 1px solid rgba(183,0,224,.35);
    border-radius: 100px;
    padding: .35rem 1rem;
    font-size: var(--s2s-size-eyebrow);
    font-weight: 700;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--s2s-brand);
    margin-bottom: 1.75rem;
}
.sub-badge i { font-size: .85rem; }

/* Headline */
.sub-title {
    font-size: 2.4rem;
    font-weight: 800;
    color: var(--s2s-text-100);
    line-height: 1.15;
    margin-bottom: 1.1rem;
}
.sub-title span { color: var(--s2s-brand); }

.sub-sub {
    font-size: var(--s2s-size-body-lg);
    color: var(--s2s-text-50);
    line-height: var(--s2s-lh-body);
    max-width: 520px;
    margin: 0 auto 2.5rem;
}

/* Feature cards */
.sub-features {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 2.5rem;
}
.sub-feat {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: var(--s2s-radius);
    padding: 1.4rem 1.2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: border-color .2s, background .2s;
}
.sub-feat:hover {
    border-color: rgba(183,0,224,.25);
    background: rgba(183,0,224,.05);
}
.sub-feat-icon {
    font-size: 1.6rem;
    color: var(--s2s-brand);
    margin-bottom: .75rem;
    display: block;
    opacity: .85;
}
.sub-feat h6 {
    font-size: var(--s2s-size-body);
    font-weight: 700;
    color: var(--s2s-text-100);
    margin: 0 0 .35rem;
}
.sub-feat p {
    font-size: var(--s2s-size-body-sm);
    color: var(--s2s-text-42);
    margin: 0;
    line-height: var(--s2s-lh-body);
}
/* "soon" label on each card */
.sub-feat-soon {
    position: absolute;
    top: .55rem;
    right: .55rem;
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .1em;
    text-transform: uppercase;
    color: var(--s2s-brand);
    opacity: .7;
}

/* Divider */
.sub-divider {
    border: none;
    border-top: 1px solid rgba(255,255,255,.07);
    margin: 2rem 0;
}

/* Notify box */
.sub-notify {
    background: linear-gradient(135deg, #10082a 0%, #1a1040 100%);
    border: 1px solid rgba(183,0,224,.25);
    border-radius: var(--s2s-radius);
    padding: 2rem 2rem;
}
.sub-notify h5 {
    font-size: var(--s2s-size-h4);
    font-weight: 700;
    color: var(--s2s-text-100);
    margin-bottom: .4rem;
}
.sub-notify p {
    font-size: var(--s2s-size-body-sm);
    color: var(--s2s-text-42);
    margin-bottom: 0;
}
.sub-notify .sub-notify-meta {
    margin-top: .85rem;
    font-size: var(--s2s-size-small);
    color: var(--s2s-text-30);
}

@media (max-width: 767px) {
    .sub-title { font-size: 1.7rem; }
    .sub-features { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 480px) {
    .sub-features { grid-template-columns: 1fr; }
}
</style>
<body class="vertical-layout vertical-menu 2-columns navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">
<?php require_once "parts/navbar.php"; ?>

<div class="wrapper">
    <?php require_once "parts/sidebar.php"; ?>

    <div class="main-panel">
        <div class="main-content">
            <div class="content-overlay"></div>
            <div class="content-wrapper">

                <div class="sub-page">
                    <div class="sub-hero">

                        <!-- Badge -->
                        <div class="sub-badge">
                            <i class="ft-zap"></i> Eagle Team Premium
                        </div>

                        <!-- Headline -->
                        <h1 class="sub-title">
                            Something <span>Powerful</span><br>Is Coming.
                        </h1>
                        <p class="sub-sub">
                            We are building a Premium upgrade that gives you unfair advantages —
                            more reach, more automation and more tools to grow your team faster.
                        </p>

                        <!-- Planned feature cards -->
                        <div class="sub-features">

                            <div class="sub-feat">
                                <span class="sub-feat-soon">Soon</span>
                                <span class="sub-feat-icon"><i class="ft-monitor"></i></span>
                                <h6>Premium Capture Pages</h6>
                                <p>High-converting landing pages with your own branding — ready to use, no tech skills needed.</p>
                            </div>

                            <div class="sub-feat">
                                <span class="sub-feat-soon">Soon</span>
                                <span class="sub-feat-icon"><i class="ft-refresh-cw"></i></span>
                                <h6>Personal Lead Rotator</h6>
                                <p>Automatically distribute incoming leads across your own team members — hands-free.</p>
                            </div>

                            <div class="sub-feat">
                                <span class="sub-feat-soon">Soon</span>
                                <span class="sub-feat-icon"><i class="ft-send"></i></span>
                                <h6>Follow-Up Automation</h6>
                                <p>Automated email sequences that follow up with your leads on your behalf.</p>
                            </div>

                            <div class="sub-feat">
                                <span class="sub-feat-soon">Soon</span>
                                <span class="sub-feat-icon"><i class="ft-bar-chart-2"></i></span>
                                <h6>Advanced Analytics</h6>
                                <p>Deeper stats on your team's conversion rates, traffic sources and step progress.</p>
                            </div>

                            <div class="sub-feat">
                                <span class="sub-feat-soon">Soon</span>
                                <span class="sub-feat-icon"><i class="ft-link"></i></span>
                                <h6>Custom Link Tracking</h6>
                                <p>Create trackable short links for all your campaigns and see exactly what works.</p>
                            </div>

                            <div class="sub-feat">
                                <span class="sub-feat-soon">Soon</span>
                                <span class="sub-feat-icon"><i class="ft-star"></i></span>
                                <h6>More Features TBA</h6>
                                <p>We are collecting ideas. If you have a feature request, reach out to us via Support.</p>
                            </div>

                        </div>

                        <hr class="sub-divider">

                        <!-- Notify box -->
                        <div class="sub-notify">
                            <h5>Be the first to know when it launches.</h5>
                            <p>Premium is not available yet. We will notify all members as soon as it is ready.</p>
                            <p class="sub-notify-meta">
                                <i class="ft-info" style="margin-right:.3rem;"></i>
                                Questions or ideas? Use the
                                <a href="support.php" style="color:var(--s2s-brand);">Support page</a>
                                to reach out.
                            </p>
                        </div>

                    </div>
                </div>

            </div>
        </div>

        <?php require_once "parts/footer.php"; ?>
        <button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>
    </div>
</div>

<div class="sidenav-overlay"></div>
<div class="drag-target"></div>
<script src="app-assets/vendors/js/vendors.min.js"></script>
<script src="app-assets/js/core/app-menu.js"></script>
<script src="app-assets/js/core/app.js"></script>
<script src="app-assets/js/notification-sidebar.js"></script>
<script src="app-assets/js/scroll-top.js"></script>
<script src="assets/js/scripts.js"></script>
</body>
</html>
