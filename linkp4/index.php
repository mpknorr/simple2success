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
<title>Simple2Success – Eagle Elite Access | Join Free</title>
<meta name="description" content="Join the Simple2Success Eagle Team — a step-by-step system with tools and guidance for building additional income. 100% free to start.">
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
      Eagle Elite Access — Free Registration
    </div>

    <h1>
      Stop Guessing.<br>
      <span class="highlight">Start With a Plan.</span>
    </h1>

    <p class="hero-sub">
      Join the Simple2Success Eagle Team — a step-by-step system with tools, guidance<br>
      and a clear next action.
    </p>

    <p class="hero-proof">
      ✦ Step-by-Step Guidance &nbsp;·&nbsp; Team Support &nbsp;·&nbsp; 100% FREE to Start
    </p>

    <p style="margin:10px auto 0;max-width:640px;font-size:15px;font-weight:600;color:#cb2ebc;">🦅 Our mission: supporting 1,000 people and families in building additional income, with a goal of $1,000+ per month. Results are not guaranteed.</p>

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
      <input type="text" name="name" placeholder="Your First Name" autocomplete="given-name">
      <input type="email" name="email" placeholder="Your Best Email" required autocomplete="email">
      <button type="submit" class="btn-primary">
        Explore the Free System
        <span class="btn-arrow">→</span>
      </button>
    </form>
<p style="font-size:13px;line-height:1.6;opacity:.85;">Your Simple2Success account is free. Partner registration and products have separate costs; see the terms during partner registration. You decide before confirming. You will receive access information and follow-up guidance by email; you can unsubscribe from marketing at any time.</p>
    <?php endif; ?>

    <div class="trust-row">
      <div class="trust-item"><span class="trust-icon">✓</span> 100% Free Registration</div>
      <div class="trust-item"><span class="trust-icon">✓</span> No Credit Card Required</div>
      <div class="trust-item"><span class="trust-icon">✓</span> Instant Access</div>
    </div>
  </div>
</section>

<!-- ═══════════════════════════════════════════════════════════
     SOCIAL PROOF BAR
════════════════════════════════════════════════════════════ -->
<!-- ═══════════════════════════════════════════════════════════
     PROBLEM SECTION
════════════════════════════════════════════════════════════ -->
<div class="section">
  <span class="section-label">The Real Problem</span>
  <h2 class="section-title">Why Most People <span class="hl">Never Break Through</span></h2>
  <p class="section-desc">
    A clear plan and support can help you turn learning into action.
    Your results also depend on customers, skills and costs.
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
      <p>Trying to build income without a team, without support, and without marketing tools.</p>
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
  <h2 class="section-title">Everything You Need to <span class="hl">Build Real Income</span></h2>
  <p class="section-desc">
    The Eagle Team system gives you structure, practical tools, and team support
    to help you learn and take action.
  </p>

  <div class="benefits-grid">
    <div class="benefit-card">
      <span class="benefit-num">01 — Clarity</span>
      <h3>A Step-by-Step Roadmap</h3>
      <p>Understand the next action, get help when needed, and review your progress.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">02 — Leverage</span>
      <h3>Done-For-You Marketing System</h3>
      <p>Professional funnels, capture pages, and email sequences — all built and ready to deploy with your link.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">03 — Income</span>
      <h3>Multiple Revenue Streams</h3>
      <p>Training bonuses, first-line bonuses, deep bonuses, and lifestyle rewards — all unlocked step by step as your team grows.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">04 — Community</span>
      <h3>A Team That Has Your Back</h3>
      <p>Join a global network of motivated partners who share strategies, celebrate wins, and keep each other accountable.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">05 — Freedom</span>
      <h3>Work From Anywhere</h3>
      <p>Your own schedule. Your own pace. Build your business from your laptop — whether you're at home or on the road.</p>
    </div>
    <div class="benefit-card">
      <span class="benefit-num">06 — Momentum</span>
      <h3>Duplication That Scales</h3>
      <p>When you follow the system, your team duplicates it. Review the results and costs together before repeating an approach.</p>
    </div>
  </div>
</div>

<hr class="section-divider">

<!-- ═══════════════════════════════════════════════════════════
     HOW IT WORKS
════════════════════════════════════════════════════════════ -->
<div class="section">
  <span class="section-label">How It Works</span>
  <h2 class="section-title">A Clear Route to <span class="hl">Your First Actions</span></h2>
  <p class="section-desc">
    The system is designed to be simple enough for anyone to follow —
    with opportunities to ask questions before making a decision.
  </p>

  <div class="steps-wrapper">
    <div class="steps-line"></div>

    <div class="step-item">
      <div class="step-dot">1</div>
      <div class="step-body">
        <h3>Register for Free &amp; Claim Your Position</h3>
        <p>Create your free Simple2Success account and explore the process. Review what is included and which later choices involve costs.</p>
      </div>
    </div>

    <div class="step-item">
      <div class="step-dot">2</div>
      <div class="step-body">
        <h3>Complete Step 2 — Activate Your Partnership</h3>
        <p>Review local registration fees and product-package options, create your partner account, then enter your Partner ID. Saving your Partner ID completes your setup. Earning commissions requires qualifying activity under the partner terms.</p>
      </div>
    </div>

    <div class="step-item">
      <div class="step-dot">3</div>
      <div class="step-body">
        <h3>Learn, Take Action &amp; Review</h3>
        <p>Learn how to support customers and use your tools. If you choose paid advertising, set a budget, measure results and review costs before spending more.</p>
      </div>
    </div>
  </div>
</div>

<hr class="section-divider">

<div class="section">
  <h2 class="section-title">Understand Your Next Step</h2>
  <p>Explore the tools, ask questions and review the costs before choosing your next action. Consistent learning and customer support matter; income is not automatic.</p>
</div>

<!-- ═══════════════════════════════════════════════════════════
     FINAL CTA
════════════════════════════════════════════════════════════ -->
<div class="section" style="padding-bottom:80px;">
  <div class="cta-section">
    <h2>
      Your Next Step Starts
      <span style="background:linear-gradient(135deg,#a855f7,#e879f9);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;">Right Now.</span>
    </h2>
    <p>
      Explore the system for free, understand the process,
      and choose your next action with the Eagle Team.
    </p>

    <?php if ($show_form): ?>
    <form method="POST" action="<?= $baseurl ?>/includes/postlead.php" class="cta-form">
      <input type="hidden" name="a" value="1">
      <input type="hidden" name="tr" value="">
      <input type="hidden" name="page" value="linkp4">
      <input type="hidden" name="lang" value="en">
      <input type="hidden" name="referer" value="<?= $referer ?>">
      <input type="hidden" name="source" value="<?= $source ?>">
      <input type="text" name="name" placeholder="First Name">
      <input type="email" name="email" placeholder="Best Email" required>
      <button type="submit" class="btn-cta">Get Free Access →</button>
    </form>
<p style="font-size:13px;line-height:1.6;opacity:.85;">Your Simple2Success account is free. Partner registration and products have separate costs; see the terms during partner registration. You decide before confirming. You will receive access information and follow-up guidance by email; you can unsubscribe from marketing at any time.</p>
    <?php else: ?>
    <div style="background:rgba(0,207,232,.08);border:1px solid rgba(0,207,232,.3);border-radius:8px;padding:16px 20px;margin:12px 0 16px;text-align:center;">
      <p style="margin:0 0 10px;font-size:15px;"><?= htmlspecialchars($s2s_lang['err_eae'][$_pg_lang]) ?></p>
      <a href="<?= rtrim($baseurl,'/') ?>/backoffice/login.php" style="display:inline-block;background:#cb2ebc;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;font-weight:700;font-size:14px;"><?= htmlspecialchars($s2s_lang['login_here'][$_pg_lang]) ?></a>
    </div>
    <?php endif; ?>

    <div class="trust-row" style="margin-top:16px;">
      <div class="trust-item"><span class="trust-icon">✓</span> 100% Free</div>
      <div class="trust-item"><span class="trust-icon">✓</span> No Spam</div>
      <div class="trust-item"><span class="trust-icon">✓</span> Cancel Anytime</div>
    </div>

    <p class="cta-disclaimer">
      By registering you agree to our
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
