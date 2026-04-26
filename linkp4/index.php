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
<meta name="description" content="Join the Simple2Success Eagle Team — a proven, step-by-step system that turns daily actions into lasting income. 100% free to start.">
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
      <span class="highlight">Start Earning.</span>
    </h1>

    <p class="hero-sub">
      Join the Simple2Success Eagle Team — a proven, step-by-step system<br>
      that turns daily actions into lasting income.
    </p>

    <p class="hero-proof">
      ✦ 10,000+ Members in 40+ Countries &nbsp;·&nbsp; 100% FREE to Start
    </p>

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
        Claim My Free Position Now
        <span class="btn-arrow">→</span>
      </button>
    </form>
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
<div class="proof-bar">
  <div class="proof-stat">
    <span class="num">10,000+</span>
    <span class="label">Active Members</span>
  </div>
  <div class="proof-divider"></div>
  <div class="proof-stat">
    <span class="num">40+</span>
    <span class="label">Countries</span>
  </div>
  <div class="proof-divider"></div>
  <div class="proof-stat">
    <span class="num">1B+</span>
    <span class="label">Products Delivered Worldwide</span>
  </div>
  <div class="proof-divider"></div>
  <div class="proof-stat">
    <span class="num">30+</span>
    <span class="label">Years of Industry Experience</span>
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
      <p>Trying to build income without a team, without support, and without proven marketing tools.</p>
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
    The Eagle Team system gives you structure, proven tools, and a team
    that handles the marketing — so you can focus on what matters.
  </p>

  <div class="benefits-grid">
    <div class="benefit-card">
      <span class="benefit-num">01 — Clarity</span>
      <h3>A Step-by-Step Roadmap</h3>
      <p>No guessing. No overwhelm. You know exactly what to do at every stage — from Day 1 to full-time income.</p>
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
      <p>When you follow the system, your team duplicates it. That's how small daily actions compound into exponential growth.</p>
    </div>
  </div>
</div>

<hr class="section-divider">

<!-- ═══════════════════════════════════════════════════════════
     HOW IT WORKS
════════════════════════════════════════════════════════════ -->
<div class="section">
  <span class="section-label">How It Works</span>
  <h2 class="section-title">3 Simple Steps to <span class="hl">Your First Income</span></h2>
  <p class="section-desc">
    The system is designed to be simple enough for anyone to follow —
    and powerful enough to create real, lasting results.
  </p>

  <div class="steps-wrapper">
    <div class="steps-line"></div>

    <div class="step-item">
      <div class="step-dot">1</div>
      <div class="step-body">
        <h3>Register for Free &amp; Claim Your Position</h3>
        <p>Create your free Simple2Success account and secure your spot in the Eagle Team network. No credit card. No risk. Takes less than 2 minutes.</p>
      </div>
    </div>

    <div class="step-item">
      <div class="step-dot">2</div>
      <div class="step-body">
        <h3>Complete Step 2 — Activate Your Partnership</h3>
        <p>Create your free partner account and enter your Partner ID. This activates your marketing system and unlocks all income streams.</p>
      </div>
    </div>

    <div class="step-item">
      <div class="step-dot">3</div>
      <div class="step-body">
        <h3>Order Traffic &amp; Let the System Work</h3>
        <p>Use our trusted solo ad traffic sources to send leads into your funnel. The system handles follow-up, conversion, and duplication automatically.</p>
      </div>
    </div>
  </div>
</div>

<hr class="section-divider">

<!-- ═══════════════════════════════════════════════════════════
     TESTIMONIALS
════════════════════════════════════════════════════════════ -->
<div class="section">
  <span class="section-label">Real Results</span>
  <h2 class="section-title">What Eagle Team Members <span class="hl">Are Saying</span></h2>

  <div class="testimonials-grid">
    <div class="testimonial-card">
      <div class="stars">★★★★★</div>
      <p class="testimonial-text">"I had tried three other systems before this. Simple2Success was the first one where I actually knew what to do every day. Within 60 days I had my first team members and my first commissions."</p>
      <div class="testimonial-author">
        <div class="author-avatar">MK</div>
        <div>
          <div class="author-name">Michael K.</div>
          <div class="author-location">Germany · 3 months in</div>
        </div>
      </div>
    </div>

    <div class="testimonial-card">
      <div class="stars">★★★★★</div>
      <p class="testimonial-text">"The done-for-you marketing system is incredible. I just share my link, the funnel does the work, and I focus on following up with people who are already interested."</p>
      <div class="testimonial-author">
        <div class="author-avatar">SL</div>
        <div>
          <div class="author-name">Sandra L.</div>
          <div class="author-location">Austria · 5 months in</div>
        </div>
      </div>
    </div>

    <div class="testimonial-card">
      <div class="stars">★★★★★</div>
      <p class="testimonial-text">"What I love most is the community. Everyone is helping each other. The leaderboard keeps me motivated and the step-by-step system removes all the guesswork."</p>
      <div class="testimonial-author">
        <div class="author-avatar">TM</div>
        <div>
          <div class="author-name">Thomas M.</div>
          <div class="author-location">Switzerland · 2 months in</div>
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
    <div class="urgency-icon">⚡</div>
    <div class="urgency-text">
      <strong>Limited Spots Available This Month</strong>
      <span>To maintain team quality and support levels, we limit new registrations each month. Once the spots are filled, the next opening is next month.</span>
    </div>
    <div class="spots-counter">
      <span class="spots-num">17</span>
      <span class="spots-label">Spots Left</span>
    </div>
  </div>
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
      Register for free, follow the system, and join thousands of people
      who are already building their income with the Eagle Team.
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
    <a href="<?= $baseurl ?>/impress.php">Impressum</a>
    <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy">Privacy Policy</a>
    <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use">Terms of Use</a>
    <?php endif; ?>
  </div>
  <p class="footer-copy">&copy; <?= date('Y') ?> Simple2Success. All rights reserved.</p>
  <p class="footer-disclaimer"><?= $disclaimerText ?></p>
</footer>

</body>
</html>
