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
require_once __DIR__ . '/../includes/lang.php';
$referer   = isset($referer) && is_numeric($referer) ? (int)$referer : 0;
$_eae      = isset($_GET['err']) && $_GET['err'] === 'eae';
$show_form = !$_eae;
$_pg_lang  = isset($_GET['lang']) && isset($s2s_lang['err_eae'][$_GET['lang']]) ? $_GET['lang'] : 'en';
$source    = htmlspecialchars($_GET['source'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php require_once __DIR__ . '/../includes/head-tracking.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Simple2Success – Mission 1000 Families | Free Access</title>
<meta name="description" content="See the Simple2Success Eagle Team system: a clear activation path, practical tools and one next action. Free to explore; results are not guaranteed.">
<link rel="shortcut icon" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon.ico">
<link rel="stylesheet" href="<?= $baseurl ?>/linkp4/fonts/fonts.css">
<link rel="stylesheet" href="<?= $baseurl ?>/linkp4/css/style.css">
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════════ -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-grid"></div>

  <div class="hero-content">
    <div class="badge-top">
      <span class="badge-dot"></span>
      Mission 1000 Families — Free Simple2Success Access
    </div>

    <h1>
      Stop Switching.<br>
      <span class="highlight">Start Following One Clear Plan.</span>
    </h1>

    <p class="hero-sub">
      See a step-by-step system built to reduce uncertainty,<br>
      strengthen your skills and show one practical next action.
    </p>

    <p class="hero-proof">
      ✦ Clear onboarding &nbsp;·&nbsp; Human support &nbsp;·&nbsp; 100% free access
    </p>

    <p style="margin:10px auto 0;max-width:700px;font-size:15px;font-weight:600;color:#cb2ebc;">🦅 Our mission: support 1,000 families as they build skills, consistency and a path toward at least $1,000 in additional monthly income.</p>

    <?php if ($_eae): ?>
      <div style="background:rgba(0,207,232,.08);border:1px solid rgba(0,207,232,.3);border-radius:8px;padding:16px 20px;margin:12px 0 16px;text-align:center;">
        <p style="margin:0 0 10px;font-size:15px;"><?= htmlspecialchars($s2s_lang['err_eae'][$_pg_lang]) ?></p>
        <a href="<?= rtrim($baseurl,'/') ?>/backoffice/login.php" style="display:inline-block;background:#cb2ebc;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:700;font-size:14px;"><?= htmlspecialchars($s2s_lang['login_here'][$_pg_lang]) ?></a>
      </div>
    <?php endif; ?>
    <?php if ($show_form): ?>
    <form method="POST" action="<?= $baseurl ?>/includes/postlead.php" class="hero-form">
      <input type="hidden" name="a" value="1">
      <input type="hidden" name="tr" value="">
      <input type="hidden" name="page" value="linkp4">
      <input type="hidden" name="lang" value="en">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <input type="hidden" name="source" value="<?= $source ?>">
      <input type="hidden" name="utm_source" value="<?= htmlspecialchars($_GET['utm_source'] ?? '') ?>">
      <input type="hidden" name="utm_medium" value="<?= htmlspecialchars($_GET['utm_medium'] ?? '') ?>">
      <input type="hidden" name="utm_campaign" value="<?= htmlspecialchars($_GET['utm_campaign'] ?? '') ?>">
      <input type="hidden" name="email_notice_version" value="mission-v2">
      <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">
      <input type="text" name="name" placeholder="Your First Name" maxlength="100" required autocomplete="given-name">
      <input type="email" name="email" placeholder="Your Best Email" required autocomplete="email">
      <button type="submit" class="btn-primary">
        Show Me the Step-by-Step System
        <span class="btn-arrow">→</span>
      </button>
    </form>
    <?php endif; ?>

    <div class="trust-row">
      <div class="trust-item"><span class="trust-icon">✓</span> 100% Free Access</div>
      <div class="trust-item"><span class="trust-icon">✓</span> No Credit Card Required</div>
      <div class="trust-item"><span class="trust-icon">✓</span> Unsubscribe Anytime</div>
    </div>
    <p style="margin:12px auto 0;max-width:680px;font-size:11px;line-height:1.5;color:rgba(255,255,255,.42);">Includes account access and action-focused follow-up emails. $1,000+ is a mission target, not an earnings guarantee; results vary.</p>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     SOCIAL PROOF BAR
════════════════════════════════════════════════════════════ -->
<div class="proof-bar">
  <div class="proof-stat">
    <span class="num">1,000</span>
    <span class="label">Family Mission</span>
  </div>
  <div class="proof-divider"></div>
  <div class="proof-stat">
    <span class="num">3</span>
    <span class="label">Activation Steps</span>
  </div>
  <div class="proof-divider"></div>
  <div class="proof-stat">
    <span class="num">1</span>
    <span class="label">Clear Next Action</span>
  </div>
  <div class="proof-divider"></div>
  <div class="proof-stat">
    <span class="num">0</span>
    <span class="label">Income Guarantees</span>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     PROBLEM SECTION
════════════════════════════════════════════════════════════ -->
<div class="section">
  <span class="section-label">The Real Problem</span>
  <h2 class="section-title">Why Most People <span class="hl">Never Break Through</span></h2>
  <p class="section-desc">
    It's not a lack of effort. It's a lack of the right system, the right team,
    and a clear next step to follow every single day.
  </p>

  <div class="problem-grid">
    <div class="problem-card">
      <span class="icon">🔄</span>
      <h3>Constant Direction Changes</h3>
      <p>Jumping from one opportunity to the next without ever staying long enough to see results.</p>
    </div>
    <div class="problem-card">
      <span class="icon">🧩</span>
      <h3>No Clear System</h3>
      <p>Consuming endless content but never having a structured, repeatable process to follow.</p>
    </div>
    <div class="problem-card">
      <span class="icon">🏝️</span>
      <h3>Working Alone</h3>
      <p>Trying to build a business routine without a team, without support, and without practical marketing tools.</p>
    </div>
    <div class="problem-card">
      <span class="icon">⏳</span>
      <h3>Wasting Time on Wrong Actions</h3>
      <p>Spending hours on activities that don't move the needle — because nobody showed you what actually works.</p>
    </div>
  </div>
</div>

<hr class="section-divider">

<!-- ═══════════════════════════════════════════════════════════
     SOLUTION / BENEFITS
════════════════════════════════════════════════════════════ -->
<div class="section">
  <span class="section-label">The Solution</span>
  <h2 class="section-title">A System Designed for <span class="hl">Clarity and Consistency</span></h2>
  <p class="section-desc">
    The Eagle Team system gives you structure, practical tools and support
    so you can focus on the next useful action instead of information overload.
  </p>

  <div class="benefits-grid">
    <div class="benefit-card">
      <span class="benefit-num">01 — Clarity</span>
      <h3>A Step-by-Step Roadmap</h3>
      <p>No guessing and no giant checklist. You see the step that matters now and what unlocks after it.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">02 — Leverage</span>
      <h3>Ready-to-Use Marketing Tools</h3>
      <p>Personal links, capture pages and follow-up tools are prepared so you can spend more time learning and acting.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">03 — Measurement</span>
      <h3>Progress You Can See</h3>
      <p>Track account creation, partner-registration activity and completed activations instead of relying on vague motivation.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">04 — Community</span>
      <h3>A Team That Has Your Back</h3>
      <p>Build alongside people who share practical lessons, celebrate real progress and encourage consistent action.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">05 — Flexibility</span>
      <h3>A Routine That Fits Real Life</h3>
      <p>Choose a realistic schedule and build skills around family and work commitments. Progress still requires consistent effort.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">06 — Momentum</span>
      <h3>A Process Others Can Learn</h3>
      <p>A simple process is easier to explain, repeat and improve. Small actions can compound, but outcomes always vary.</p>
    </div>
  </div>
</div>

<hr class="section-divider">

<!-- ═══════════════════════════════════════════════════════════
     HOW IT WORKS
════════════════════════════════════════════════════════════ -->
<div class="section">
  <span class="section-label">How It Works</span>
  <h2 class="section-title">3 Simple Steps to <span class="hl">Activate Your Plan</span></h2>
  <p class="section-desc">
    The process is designed to be easy to understand. Your individual outcome
    still depends on skill-building, consistent action, time and expenses.
  </p>

  <div class="steps-wrapper">
    <div class="steps-line"></div>

    <div class="step-item">
      <div class="step-dot">1</div>
      <div class="step-body">
        <h3>Create Your Free Simple2Success Account</h3>
        <p>Open Mission Control, choose your personal reason and see the single next action. No credit card is required for Simple2Success access.</p>
      </div>
    </div>

    <div class="step-item">
      <div class="step-dot">2</div>
      <div class="step-body">
        <h3>Review the Partner Registration</h3>
        <p>Open the official partner page, check the current terms and sponsor connection, and decide whether it fits you.</p>
      </div>
    </div>

    <div class="step-item">
      <div class="step-dot">3</div>
      <div class="step-body">
        <h3>Save Your Partner ID and Start Building</h3>
        <p>After confirmation, save your Partner ID. Then follow a focused 90-day routine using your links, outreach tools and measured follow-up.</p>
      </div>
    </div>
  </div>
</div>

<hr class="section-divider">

<!-- ═══════════════════════════════════════════════════════════
     TESTIMONIALS
════════════════════════════════════════════════════════════ -->
<div class="section">
  <span class="section-label">What Progress Looks Like</span>
  <h2 class="section-title">Measure Actions Before <span class="hl">Outcomes</span></h2>

  <div class="testimonials-grid">
    <div class="testimonial-card">
      <div class="stars">STEP 1</div>
      <p class="testimonial-text">Understand the partner, review the current terms and make an informed decision. A clear decision is the first measurable win.</p>
      <div class="testimonial-author">
        <div class="author-avatar">01</div>
        <div>
          <div class="author-name">Clarity</div>
          <div class="author-location">Know what happens next</div>
        </div>
      </div>
    </div>

    <div class="testimonial-card">
      <div class="stars">STEP 2</div>
      <p class="testimonial-text">Complete the account connection and save the correct Partner ID. Activation—not a page view—is the funnel milestone.</p>
      <div class="testimonial-author">
        <div class="author-avatar">02</div>
        <div>
          <div class="author-name">Activation</div>
          <div class="author-location">Finish the setup correctly</div>
        </div>
      </div>
    </div>

    <div class="testimonial-card">
      <div class="stars">NEXT 90 DAYS</div>
      <p class="testimonial-text">Use one outreach approach, track response and activation, and improve the biggest bottleneck before changing systems.</p>
      <div class="testimonial-author">
        <div class="author-avatar">90</div>
        <div>
          <div class="author-name">Consistency</div>
          <div class="author-location">Repeat, measure and improve</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     URGENCY BANNER
════════════════════════════════════════════════════════════ -->
<div style="max-width:1100px; margin:0 auto; padding:0 20px;">
  <div class="urgency-banner">
    <div class="urgency-icon">✓</div>
    <div class="urgency-text">
      <strong>Start when you are ready to take the next step</strong>
      <span>There is no artificial countdown. Free access lets you review the process first and make an informed decision.</span>
    </div>
    <div class="spots-counter">
      <span class="spots-num">0</span>
      <span class="spots-label">Fake Timers</span>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     FINAL CTA
════════════════════════════════════════════════════════════ -->
<div class="section" style="padding-bottom:80px;">
  <div class="cta-section">
    <h2>
      Your Next Step Is Ready
      <span style="background:linear-gradient(135deg,#a855f7,#e879f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">When You Are.</span>
    </h2>
    <p>
      Create your free account, see your exact next step and decide whether
      the Eagle Team process fits your goal.
    </p>

    <?php if ($show_form): ?>
    <form method="POST" action="<?= $baseurl ?>/includes/postlead.php" class="cta-form">
      <input type="hidden" name="a" value="1">
      <input type="hidden" name="tr" value="">
      <input type="hidden" name="page" value="linkp4">
      <input type="hidden" name="lang" value="en">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <input type="hidden" name="source" value="<?= $source ?>">
      <input type="hidden" name="utm_source" value="<?= htmlspecialchars($_GET['utm_source'] ?? '') ?>">
      <input type="hidden" name="utm_medium" value="<?= htmlspecialchars($_GET['utm_medium'] ?? '') ?>">
      <input type="hidden" name="utm_campaign" value="<?= htmlspecialchars($_GET['utm_campaign'] ?? '') ?>">
      <input type="hidden" name="email_notice_version" value="mission-v2">
      <input type="text" name="website" value="" tabindex="-1" autocomplete="off" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">
      <input type="text" name="name" placeholder="First Name" maxlength="100" required autocomplete="given-name">
      <input type="email" name="email" placeholder="Best Email" required>
      <button type="submit" class="btn-cta">Show My Next Step →</button>
    </form>
    <?php else: ?>
    <div style="background:rgba(0,207,232,.08);border:1px solid rgba(0,207,232,.3);border-radius:8px;padding:16px 20px;margin:12px 0 16px;text-align:center;">
      <p style="margin:0 0 10px;font-size:15px;"><?= htmlspecialchars($s2s_lang['err_eae'][$_pg_lang]) ?></p>
      <a href="<?= rtrim($baseurl,'/') ?>/backoffice/login.php" style="display:inline-block;background:#cb2ebc;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:700;font-size:14px;"><?= htmlspecialchars($s2s_lang['login_here'][$_pg_lang]) ?></a>
    </div>
    <?php endif; ?>

    <div class="trust-row" style="margin-top:16px;">
      <div class="trust-item"><span class="trust-icon">✓</span> 100% Free</div>
      <div class="trust-item"><span class="trust-icon">✓</span> Clear Expectations</div>
      <div class="trust-item"><span class="trust-icon">✓</span> Unsubscribe Anytime</div>
    </div>

    <p class="cta-disclaimer">
      Includes account access and action-focused follow-up emails. Results are not guaranteed. By registering you agree to our
      <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use" style="color:var(--purple-light);text-decoration:none;">Terms of Use</a>
      and
      <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy" style="color:var(--purple-light);text-decoration:none;">Privacy Policy</a>.
    </p>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     FOOTER
════════════════════════════════════════════════════════════ -->
<footer>
  <div class="footer-links">
    <?php
    $_fb = function_exists('getLegalFooterLinks') ? getLegalFooterLinks($link) : [];
    if (!empty($_fb) && function_exists('getLegalPageUrl')):
        foreach ($_fb as $fl):
            echo '<a href="' . htmlspecialchars(getLegalPageUrl($baseurl, $fl['slug'])) . '">' . htmlspecialchars($fl['title']) . '</a>';
        endforeach;
    else: ?>
    <a href="<?= $baseurl ?>/impress.php">Legal Notice</a>
    <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy">Privacy Policy</a>
    <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use">Terms of Use</a>
    <?php endif; ?>
  </div>
  <p class="footer-copy">&copy; <?= date('Y') ?> Simple2Success. All rights reserved.</p>
  <p class="footer-disclaimer"><?= $disclaimerText ?></p>
</footer>

</body>
</html>
