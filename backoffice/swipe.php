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

<body class="vertical-layout vertical-menu 2-columns navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">

  <?php require_once "parts/navbar.php"; ?>

  <div class="wrapper">
    <?php require_once "parts/sidebar.php"; ?>

    <div class="main-panel">
      <div class="main-content">
        <div class="content-wrapper">

          <section id="sizing">
            <div class="row match-height">
              <div class="content-header">Swipe Copy</div>
            </div>
            <div class="row">

<?php
if (empty($username)) {
    echo "<p>You should complete STEP 1 &amp; STEP 2 from <a href='" . $baseurl . "/backoffice/start.php'>here</a>.</p>";
} else {

$link = (isset($_SERVER['HTTPS']) === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/go/' . $userid . '/link1/';

$ads = [

  // ── AD 1 ──────────────────────────────────────────────────────────────────
  1 => [
    'subject' => 'This Free System Helped Me Build a Second Income — Here\'s How',
    'text'    => <<<EOT
Hey [First Name],

I want to share something with you that changed the way I think about building income online.

A few months ago I came across a step-by-step system that is completely free to join and designed for people who want to build a real, recurring income — without needing a product, a website, or any prior experience.

What makes it different from everything else out there:

- The system does the explaining for you — no selling required
- You get your own personal tracking link the moment you sign up
- Every person you refer who follows the steps creates income for you

Thousands of people in over 40 countries are already using this exact system. And because it's free to start, there's genuinely no risk in taking a look.

Click the link below to see the full overview and claim your free account:

$link

Talk soon,
[Your Name]
EOT,
  ],

  // ── AD 2 ──────────────────────────────────────────────────────────────────
  2 => [
    'subject' => 'The Simplest Way I\'ve Found to Build Recurring Online Income',
    'text'    => <<<EOT
Hey [First Name],

If you've been looking for a way to generate income online that actually makes sense — this is worth 2 minutes of your time.

It's a free team system with a clear, numbered path. You follow the steps, you share your link, and the system works for you around the clock.

Here's what I like most about it:

✓ Free to join — no upfront cost
✓ Works in 40+ countries
✓ The system explains itself — you don't need to pitch anything
✓ Real commissions, not just points or tokens

I've seen people get their first results within their first week simply by sharing their personal link with the right audience.

See it for yourself here:

$link

[Your Name]
EOT,
  ],

  // ── AD 3 ──────────────────────────────────────────────────────────────────
  3 => [
    'subject' => 'How Ordinary People Are Building Income With a Free Online System',
    'text'    => <<<EOT
Hey [First Name],

Most people who try to build income online fail for one simple reason: they don't have a system. They jump from one idea to the next, spend money on courses, and never see consistent results.

The system I want to show you today solves exactly that problem.

It's a structured, step-by-step path that walks you through everything — from getting your free account to generating your first commissions. No guesswork. No complicated tech. No upfront investment.

What you get when you sign up for free:

- Your own personal referral link (ready to use immediately)
- A proven follow-up system that works for you automatically
- Access to a community of active members in 40+ countries
- Step-by-step guidance at every stage

The people succeeding with this aren't special. They just followed the steps.

Ready to see what it looks like?

$link

[Your Name]
EOT,
  ],

  // ── AD 4 ──────────────────────────────────────────────────────────────────
  4 => [
    'subject' => '3 Reasons This Free System Works When Others Don\'t',
    'text'    => <<<EOT
Hey [First Name],

I've looked at a lot of online income systems over the years. Most of them have one thing in common: they make you do all the work while the company keeps most of the money.

This one is different — and here are three reasons why:

1. It's genuinely free to start.
   No trial, no credit card, no "free for 7 days then $97/month." Your account is free. Period.

2. The system sells itself.
   When someone clicks your link, they land on a professionally built page that explains everything. You don't need to be a salesperson. You just need to share the link.

3. The income compounds over time.
   As your team grows, so does your income — even when you're not actively working. That's the power of a system built on duplication.

If you're serious about building a second income stream this year, this is the most straightforward path I've found.

Take a look here — it takes less than 2 minutes to get your free account:

$link

[Your Name]
EOT,
  ],

  // ── AD 5 ──────────────────────────────────────────────────────────────────
  5 => [
    'subject' => 'Free Account. Real Commissions. Here\'s the Link.',
    'text'    => <<<EOT
Hey [First Name],

Short and to the point today.

I'm part of a team that uses a free online system to generate recurring income. The system is structured, proven, and works in over 40 countries.

You don't need to:
- Create a product
- Build a website
- Run paid ads
- Know anything about online marketing

You do need to:
- Sign up for free (takes 2 minutes)
- Follow the numbered steps
- Share your personal link

That's the entire model.

The people on our team who are seeing the best results are the ones who simply stayed consistent and kept sharing their link.

If that sounds like something you can do, here's where to start:

$link

[Your Name]
EOT,
  ],

  // ── AD 6 ──────────────────────────────────────────────────────────────────
  6 => [
    'subject' => 'Why Most People Never Build a Second Income (And How to Fix It)',
    'text'    => <<<EOT
Hey [First Name],

Here's the honest truth about building income online:

Most people fail not because they lack effort — but because they lack a system. They try random things, get inconsistent results, and eventually give up.

The system I want to show you today was built specifically to solve that problem.

It gives you:

→ A clear path from Step 1 to Step 5 — no guessing what to do next
→ A free account with your own personal tracking link
→ Automated follow-up that works while you sleep
→ A team structure that pays you when others follow the same steps

The best part? You can get started today without spending a single dollar.

Over 10,000 people across 40+ countries have already joined. The ones who are winning are the ones who started.

Here's your link to get started for free:

$link

[Your Name]
EOT,
  ],

  // ── AD 7 ──────────────────────────────────────────────────────────────────
  7 => [
    'subject' => 'This Is the Closest Thing to a "Done-For-You" Income System I\'ve Found',
    'text'    => <<<EOT
Hey [First Name],

I want to be upfront with you: I'm not going to promise you'll get rich overnight. That's not how this works.

What I can tell you is this:

The system I'm sharing with you today is the most complete, done-for-you income framework I've come across. Here's what "done for you" actually means in this case:

✓ The landing pages are built — you just share your link
✓ The follow-up emails go out automatically — you don't write them
✓ The presentation explains the opportunity — you don't need to pitch
✓ The compensation structure is already set up — you just need to activate it

Your job is simple: get your free account, follow the steps, and share your link consistently.

The system does the rest.

If you've been looking for a way to build income online without building everything from scratch, this is it.

Claim your free account here:

$link

[Your Name]
EOT,
  ],

  // ── AD 8 ──────────────────────────────────────────────────────────────────
  8 => [
    'subject' => 'How to Build a Residual Income Stream Starting Today (For Free)',
    'text'    => <<<EOT
Hey [First Name],

Residual income — income that keeps coming in after the initial work is done — is the goal for most people who want financial freedom.

The challenge is that most paths to residual income require significant upfront investment, technical skills, or years of effort before you see results.

The system I'm part of is different.

It's designed so that every person you refer who follows the steps creates ongoing income for you. And because the system is free to join, the barrier to getting started is as low as it gets.

Here's how the income builds:

- You share your link → someone signs up for free
- They follow the steps → they become an active member
- Their activity → generates income for you
- They share their link → the cycle repeats

This is how residual income actually works in practice — and this system makes it accessible to anyone.

Get your free account and see the full picture here:

$link

[Your Name]
EOT,
  ],

  // ── AD 9 ──────────────────────────────────────────────────────────────────
  9 => [
    'subject' => 'The Step-by-Step System That Turns Leads Into Recurring Income',
    'text'    => <<<EOT
Hey [First Name],

Generating leads is only half the battle. The other half is converting those leads into income — and doing it consistently.

That's exactly what this system is designed to do.

When someone clicks your link and signs up, they don't just become a lead. They enter a structured onboarding sequence that:

1. Explains the opportunity clearly
2. Walks them through the activation steps
3. Encourages them to share their own link

Every person who completes the steps becomes part of your active team — and that's where the income comes from.

You don't need to manage this manually. The system handles the follow-up, the explanations, and the step-by-step guidance automatically.

Your only job: keep sending people to your link.

If you're running solo ads or any form of email traffic, this system is built to convert that traffic into results.

See how it works and get your free account here:

$link

[Your Name]
EOT,
  ],

  // ── AD 10 ─────────────────────────────────────────────────────────────────
  10 => [
    'subject' => 'Solo Ads + This System = A Repeatable Income Formula',
    'text'    => <<<EOT
Hey [First Name],

If you're using solo ads to build your list, you already know the challenge: getting clicks is easy, but turning those clicks into consistent income is hard.

The system I want to share with you today was built specifically for solo ad traffic.

Here's why it works:

→ The capture page is optimized for cold traffic — fast, clear, and compelling
→ The follow-up sequence runs automatically — no manual follow-up needed
→ The offer is free to join — which dramatically increases opt-in rates
→ The step-by-step system converts leads into active team members

The result is a repeatable formula:
Traffic → Free opt-in → Automated follow-up → Active members → Recurring income

Thousands of members across 40+ countries are using this exact formula right now.

If you want to see how it works and get your own free account, click the link below:

$link

[Your Name]
EOT,
  ],

  // ── AD 11 ─────────────────────────────────────────────────────────────────
  11 => [
    'subject' => 'What Happens After Someone Clicks Your Link (The Full Picture)',
    'text'    => <<<EOT
Hey [First Name],

A lot of people ask me: "What exactly happens after someone clicks my link?"

Great question. Here's the full picture:

Step 1 — They land on a professionally designed capture page and enter their name and email.

Step 2 — They're taken to the member dashboard where they see the full system overview, including the income opportunity and how the steps work.

Step 3 — They're guided to complete their account activation, which is also free.

Step 4 — They receive automated follow-up emails that keep them engaged and moving through the steps.

Step 5 — Once active, they start sharing their own link — and the cycle continues.

You don't manage any of this manually. The system runs the entire sequence for you.

All you need to do is keep driving traffic to your link.

Want to see it from the inside? Get your free account here:

$link

[Your Name]
EOT,
  ],

  // ── AD 12 ─────────────────────────────────────────────────────────────────
  12 => [
    'subject' => 'The Income System That Works While You\'re Offline',
    'text'    => <<<EOT
Hey [First Name],

What if your income didn't depend on you being online?

That's the idea behind the system I want to share with you today.

Once you've set it up — which takes less than a day — the system runs on autopilot:

✓ Your capture page collects leads 24/7
✓ The automated email sequence follows up for you
✓ The step-by-step onboarding converts leads into active members
✓ Active members generate income for you — even while you sleep

This isn't theory. It's the exact model that thousands of members across 40+ countries are using right now to build a consistent second income.

And the best part: getting started is completely free.

No monthly fees. No product to buy. No complicated setup. Just a clear system, your personal link, and the traffic you send to it.

Ready to see it for yourself?

$link

[Your Name]
EOT,
  ],

];

foreach ($ads as $n => $ad) {
    $subj = htmlspecialchars($ad['subject'], ENT_QUOTES);
    $text = htmlspecialchars($ad['text'], ENT_QUOTES);
    echo <<<HTML

<!-- ////////////////////////////// AD {$n} ////////////////////////////////////////-->
<div class="col-md-6">
  <div class="card">
    <div class="card-header">
      <h4 class="card-title">Ad {$n}</h4>
    </div>
    <div class="card-content">
      <div class="card-body">
        <form class="form">
          <div class="form-body">
            <div class="form-group">
              <p>Subject:<br>
              <input id="subjectInput{$n}" type="text" class="form-control"
                value="{$subj}" style="width:100%; text-align:left;">
              <p>Text:<br>
              <textarea id="textInput{$n}" rows="23" class="form-control"
                name="comment" cols="60" style="width:100%;">{$text}</textarea>
            </div>
          </div>
          <div>
            <button id="copySubjectButton{$n}" type="button" class="btn btn-primary mr-1">
              <i class="ft-check mr-2"></i>Copy Subject
            </button>
            <button id="copyTextButton{$n}" type="button" class="btn btn-primary mr-1">
              <i class="ft-check mr-2"></i>Copy Text
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
HTML;
}

} // end if username
?>

            </div><!-- .row -->
          </section>

        </div><!-- .content-wrapper -->
      </div><!-- .main-content -->
    </div><!-- .main-panel -->
  </div><!-- .wrapper -->

<!-- ////////////// Copy Buttons Script ////////////////////////////-->
<script>
(function() {
  function setupCopy(n) {
    var subBtn = document.getElementById('copySubjectButton' + n);
    var txtBtn = document.getElementById('copyTextButton' + n);
    if (!subBtn || !txtBtn) return;

    subBtn.addEventListener('click', function() {
      var el = document.getElementById('subjectInput' + n);
      el.select();
      try {
        document.execCommand('copy');
        alert('Subject was copied!');
      } catch(e) {
        alert('Please copy manually.');
      }
    });

    txtBtn.addEventListener('click', function() {
      var el = document.getElementById('textInput' + n);
      el.select();
      try {
        document.execCommand('copy');
        alert('Text was copied!');
      } catch(e) {
        alert('Please copy manually.');
      }
    });
  }

  for (var i = 1; i <= 12; i++) { setupCopy(i); }
})();
</script>

<?php require_once "parts/footer.php"; ?>
<button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>

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
