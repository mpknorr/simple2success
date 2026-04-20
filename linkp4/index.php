<?php
require_once __DIR__ . '/../includes/conn.php';
require_once __DIR__ . '/../includes/legal.php';
$disclaimerText = getLegalFooterSnippet($link, 'income-disclaimer');
$errorMsg = '';
if (isset($_GET['err']) && $_GET['err'] === 'eae') {
    $errorMsg = 'This email address is already registered. Please use a different email or log in.';
}
$source = htmlspecialchars($_GET['source'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Simple2Success – Eagle Elite Access | Join Free</title>
<meta name="description" content="Join the Simple2Success Eagle Team — a proven, step-by-step system that turns daily actions into lasting income. 100% free to start.">
<link rel="shortcut icon" href="https://www.simple2success.com/backoffice/app-assets/img/ico/favicon.ico">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

  :root {
    --purple: #9333ea;
    --purple-light: #a855f7;
    --purple-dark: #7e22ce;
    --magenta: #d946ef;
    --magenta-bright: #e879f9;
    --bg-dark: #0a0a0f;
    --bg-card: #111118;
    --bg-card2: #16161f;
    --text-white: #f8fafc;
    --text-muted: #94a3b8;
    --border: rgba(147,51,234,0.25);
    --glow: 0 0 40px rgba(147,51,234,0.35);
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Inter', sans-serif;
    background: var(--bg-dark);
    color: var(--text-white);
    overflow-x: hidden;
    line-height: 1.6;
  }

  /* ─── HERO ─────────────────────────────────────────────── */
  .hero {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 80px 20px 60px;
  }

  .hero-bg {
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 80% 60% at 50% 0%, rgba(147,51,234,0.22) 0%, transparent 70%),
                radial-gradient(ellipse 60% 40% at 80% 80%, rgba(217,70,239,0.12) 0%, transparent 60%),
                linear-gradient(180deg, #0a0a0f 0%, #0d0d18 100%);
  }

  .hero-grid {
    position: absolute; inset: 0;
    background-image:
      linear-gradient(rgba(147,51,234,0.07) 1px, transparent 1px),
      linear-gradient(90deg, rgba(147,51,234,0.07) 1px, transparent 1px);
    background-size: 60px 60px;
    mask-image: radial-gradient(ellipse 80% 80% at 50% 50%, black 30%, transparent 100%);
  }

  .hero-content {
    position: relative;
    max-width: 860px;
    text-align: center;
    z-index: 2;
  }

  .badge-top {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(147,51,234,0.15);
    border: 1px solid rgba(147,51,234,0.4);
    border-radius: 100px;
    padding: 6px 18px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--purple-light);
    margin-bottom: 28px;
  }

  .badge-dot {
    width: 7px; height: 7px;
    background: var(--magenta-bright);
    border-radius: 50%;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%,100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.4); }
  }

  .hero h1 {
    font-size: clamp(2.2rem, 5vw, 4rem);
    font-weight: 900;
    line-height: 1.1;
    margin-bottom: 10px;
    letter-spacing: -0.02em;
  }

  .hero h1 .highlight {
    background: linear-gradient(135deg, var(--purple-light), var(--magenta-bright));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .hero-sub {
    font-size: clamp(1rem, 2.5vw, 1.3rem);
    color: var(--text-muted);
    margin-bottom: 14px;
    font-weight: 400;
  }

  .hero-proof {
    font-size: 0.95rem;
    color: var(--magenta-bright);
    font-weight: 600;
    margin-bottom: 40px;
    letter-spacing: 0.02em;
  }

  .form-error {
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.35);
    border-radius: 10px;
    color: #fca5a5;
    font-size: 0.9rem;
    padding: 12px 16px;
    margin-bottom: 14px;
    text-align: center;
  }

  .hero-form {
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-width: 480px;
    margin: 0 auto 28px;
  }

  .hero-form input[type="text"],
  .hero-form input[type="email"] {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(147,51,234,0.35);
    border-radius: 12px;
    padding: 16px 20px;
    font-size: 1rem;
    color: var(--text-white);
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
    font-family: inherit;
    width: 100%;
  }

  .hero-form input::placeholder { color: rgba(148,163,184,0.6); }
  .hero-form input:focus {
    border-color: var(--purple-light);
    box-shadow: 0 0 0 3px rgba(147,51,234,0.18);
  }

  .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: linear-gradient(135deg, var(--purple), var(--magenta));
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    padding: 18px 36px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 30px rgba(147,51,234,0.4);
    width: 100%;
    font-family: inherit;
    letter-spacing: 0.01em;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 40px rgba(147,51,234,0.55);
  }

  .btn-arrow { font-size: 1.3rem; transition: transform 0.2s; }
  .btn-primary:hover .btn-arrow { transform: translateX(4px); }

  .trust-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 8px;
  }

  .trust-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.82rem;
    color: var(--text-muted);
  }

  .trust-icon { color: #22c55e; font-size: 0.9rem; }

  /* ─── SOCIAL PROOF BAR ─────────────────────────────────── */
  .proof-bar {
    background: rgba(147,51,234,0.08);
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
  }

  .proof-stat { text-align: center; }

  .proof-stat .num {
    font-size: 1.6rem;
    font-weight: 800;
    background: linear-gradient(135deg, var(--purple-light), var(--magenta-bright));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: block;
  }

  .proof-stat .label {
    font-size: 0.78rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
  }

  .proof-divider { width: 1px; height: 40px; background: var(--border); }

  /* ─── SECTION WRAPPER ──────────────────────────────────── */
  .section {
    padding: 80px 20px;
    max-width: 1100px;
    margin: 0 auto;
  }

  .section-label {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--purple-light);
    margin-bottom: 12px;
    display: block;
  }

  .section-title {
    font-size: clamp(1.6rem, 3.5vw, 2.6rem);
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 16px;
    letter-spacing: -0.02em;
  }

  .section-title .hl {
    background: linear-gradient(135deg, var(--purple-light), var(--magenta-bright));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .section-desc {
    font-size: 1rem;
    color: var(--text-muted);
    max-width: 640px;
    line-height: 1.7;
  }

  /* ─── PROBLEM ──────────────────────────────────────────── */
  .problem-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-top: 48px;
  }

  .problem-card {
    background: var(--bg-card);
    border: 1px solid rgba(239,68,68,0.2);
    border-radius: 16px;
    padding: 28px 24px;
  }

  .problem-card .icon { font-size: 1.8rem; margin-bottom: 14px; display: block; }
  .problem-card h3 { font-size: 1rem; font-weight: 700; margin-bottom: 8px; color: #fca5a5; }
  .problem-card p { font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; }

  /* ─── BENEFITS ─────────────────────────────────────────── */
  .benefits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-top: 48px;
  }

  .benefit-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 32px 28px;
    position: relative;
    overflow: hidden;
    transition: border-color 0.3s, transform 0.3s;
  }

  .benefit-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--purple), var(--magenta));
  }

  .benefit-card:hover { border-color: rgba(147,51,234,0.5); transform: translateY(-4px); }

  .benefit-num {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--purple-light);
    margin-bottom: 14px;
    display: block;
  }

  .benefit-card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 10px; }
  .benefit-card p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.65; }

  /* ─── STEPS ────────────────────────────────────────────── */
  .steps-wrapper { margin-top: 56px; position: relative; }

  .steps-line {
    position: absolute;
    left: 28px; top: 0; bottom: 0;
    width: 2px;
    background: linear-gradient(180deg, var(--purple), var(--magenta), transparent);
  }

  .step-item { display: flex; gap: 28px; margin-bottom: 40px; position: relative; }

  .step-dot {
    width: 56px; height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--purple), var(--magenta));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 800;
    flex-shrink: 0;
    box-shadow: var(--glow);
    position: relative;
    z-index: 1;
  }

  .step-body { padding-top: 10px; }
  .step-body h3 { font-size: 1.05rem; font-weight: 700; margin-bottom: 6px; }
  .step-body p { font-size: 0.9rem; color: var(--text-muted); line-height: 1.6; }

  /* ─── TESTIMONIALS ─────────────────────────────────────── */
  .testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-top: 48px;
  }

  .testimonial-card {
    background: var(--bg-card2);
    border: 1px solid var(--border);
    border-radius: 16px;
    padding: 28px 24px;
  }

  .stars { color: #fbbf24; font-size: 0.9rem; margin-bottom: 14px; letter-spacing: 2px; }

  .testimonial-text {
    font-size: 0.93rem;
    color: #cbd5e1;
    line-height: 1.7;
    margin-bottom: 20px;
    font-style: italic;
  }

  .testimonial-author { display: flex; align-items: center; gap: 12px; }

  .author-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--purple), var(--magenta));
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.9rem;
    flex-shrink: 0;
  }

  .author-name { font-weight: 600; font-size: 0.9rem; }
  .author-location { font-size: 0.78rem; color: var(--text-muted); }

  /* ─── URGENCY ──────────────────────────────────────────── */
  .urgency-banner {
    background: linear-gradient(135deg, rgba(217,70,239,0.12), rgba(147,51,234,0.12));
    border: 1px solid rgba(217,70,239,0.3);
    border-radius: 14px;
    padding: 20px 28px;
    display: flex;
    align-items: center;
    gap: 16px;
    max-width: 800px;
    margin: 48px auto 0;
  }

  .urgency-icon { font-size: 2rem; flex-shrink: 0; }

  .urgency-text strong {
    display: block;
    font-size: 1rem;
    font-weight: 700;
    color: var(--magenta-bright);
    margin-bottom: 4px;
  }

  .urgency-text span { font-size: 0.88rem; color: var(--text-muted); }

  .spots-counter { margin-left: auto; text-align: center; flex-shrink: 0; }

  .spots-num {
    font-size: 2.2rem;
    font-weight: 900;
    color: var(--magenta-bright);
    display: block;
    line-height: 1;
  }

  .spots-label {
    font-size: 0.72rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.1em;
  }

  /* ─── CTA SECTION ──────────────────────────────────────── */
  .cta-section {
    background: linear-gradient(135deg, rgba(147,51,234,0.12), rgba(217,70,239,0.08));
    border: 1px solid var(--border);
    border-radius: 24px;
    padding: 64px 40px;
    text-align: center;
    max-width: 860px;
    margin: 0 auto;
    position: relative;
    overflow: hidden;
  }

  .cta-section::before {
    content: '';
    position: absolute;
    top: -60px; left: 50%;
    transform: translateX(-50%);
    width: 300px; height: 300px;
    background: radial-gradient(circle, rgba(147,51,234,0.2), transparent 70%);
    pointer-events: none;
  }

  .cta-section h2 {
    font-size: clamp(1.6rem, 3.5vw, 2.4rem);
    font-weight: 800;
    margin-bottom: 14px;
    line-height: 1.2;
  }

  .cta-section > p {
    color: var(--text-muted);
    font-size: 1rem;
    margin-bottom: 36px;
    max-width: 520px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.7;
  }

  .cta-form {
    display: flex;
    gap: 12px;
    max-width: 480px;
    margin: 0 auto 20px;
    flex-wrap: wrap;
  }

  .cta-form input[type="text"],
  .cta-form input[type="email"] {
    flex: 1;
    min-width: 180px;
    background: rgba(255,255,255,0.07);
    border: 1px solid rgba(147,51,234,0.35);
    border-radius: 10px;
    padding: 14px 18px;
    font-size: 0.95rem;
    color: var(--text-white);
    outline: none;
    font-family: inherit;
  }

  .cta-form input:focus {
    border-color: var(--purple-light);
    box-shadow: 0 0 0 3px rgba(147,51,234,0.18);
  }

  .btn-cta {
    background: linear-gradient(135deg, var(--purple), var(--magenta));
    color: white;
    font-weight: 700;
    font-size: 1rem;
    padding: 14px 28px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-family: inherit;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 20px rgba(147,51,234,0.35);
    white-space: nowrap;
  }

  .btn-cta:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(147,51,234,0.5); }

  .cta-disclaimer { font-size: 0.78rem; color: rgba(148,163,184,0.6); margin-top: 12px; }

  /* ─── FOOTER ───────────────────────────────────────────── */
  footer {
    border-top: 1px solid var(--border);
    padding: 28px 20px;
    text-align: center;
  }

  .footer-links {
    display: flex;
    justify-content: center;
    gap: 24px;
    margin-bottom: 14px;
    flex-wrap: wrap;
  }

  .footer-links a {
    color: var(--text-muted);
    text-decoration: none;
    font-size: 0.82rem;
    transition: color 0.2s;
  }

  .footer-links a:hover { color: var(--purple-light); }
  .footer-copy { font-size: 0.78rem; color: rgba(148,163,184,0.45); }

  .footer-disclaimer {
    font-size: 0.72rem;
    color: rgba(148,163,184,0.35);
    max-width: 800px;
    margin: 14px auto 0;
    line-height: 1.6;
  }

  /* ─── DIVIDER ──────────────────────────────────────────── */
  .section-divider { border: none; border-top: 1px solid var(--border); margin: 0; }

  /* ─── RESPONSIVE ───────────────────────────────────────── */
  @media (max-width: 600px) {
    .proof-divider { display: none; }
    .urgency-banner { flex-direction: column; text-align: center; }
    .spots-counter { margin: 0 auto; }
    .steps-line { display: none; }
    .step-item { flex-direction: column; gap: 12px; }
    .cta-form { flex-direction: column; }
  }
</style>
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

    <?php if ($errorMsg): ?>
      <div class="form-error"><?= $errorMsg ?></div>
    <?php endif; ?>

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
    <span class="label">Products Sold Worldwide</span>
  </div>
  <div class="proof-divider"></div>
  <div class="proof-stat">
    <span class="num">30+</span>
    <span class="label">Years of Team Experience</span>
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
      <p>Training bonuses, first-line bonuses, deep bonuses, and lifestyle rewards — all unlocked through our partner compensation plan.</p>
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
        <p>Create your free partner account and enter your Partner ID. This activates your marketing system and unlocks the full compensation plan.</p>
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
    <a href="<?= $baseurl ?>/impress.php">Impressum</a>
    <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy">Privacy Policy</a>
    <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use">Terms of Use</a>
  </div>
  <p class="footer-copy">&copy; <?= date('Y') ?> Simple2Success. All rights reserved.</p>
  <p class="footer-disclaimer"><?= $disclaimerText ?></p>
</footer>

</body>
</html>
