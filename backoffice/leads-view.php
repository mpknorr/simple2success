<?php
// Start the PHP session
session_start();

// Check if the 'userid' session variable is not set or empty
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    // Redirect to the login page
    header('Location: login.php');
    exit(); // Make sure to exit to prevent further execution
}

// Include your 'conn.php' file
include '../includes/conn.php';
$leadid = $_GET['id'];
$sql = "SELECT * FROM users WHERE leadid = $leadid";
$userDatas = mysqli_query($link, $sql);
foreach($userDatas as $user){
    $lead_id = $user['leadid'];
    $lead_ip = $user['user_ip'];
    $lead_name = $user['name'];
    $lead_email = $user['email'];
    $lead_registered = date('Y/m/d', strtotime($user['timestamp']));
    $lead_username = $user['username'];
    $lead_paidstatus = $user['paidstatus'];
    $lead_profile_pic = $user['profile_pic'];
    $lead_signuproot = $user['signuproot'];
    $lead_timestamp    = $user['timestamp'];
    $lead_registered   = $user['registered_at'] ?? $user['timestamp'];
    $lead_step1_at     = $user['step1_at']     ?? null;
    $lead_step1_ip     = $user['step1_ip']     ?? null;
    $lead_step2_at     = $user['step2_at']     ?? null;
    $lead_step2_ip     = $user['step2_ip']     ?? null;
    $lead_page         = $user['page']             ?? '';
    $lead_source       = $user['source']           ?? '';
    $lead_utm_source   = $user['utm_source']       ?? '';
    $lead_utm_medium   = $user['utm_medium']       ?? '';
    $lead_utm_campaign = $user['utm_campaign']     ?? '';
    $lead_tr           = $user['tr']               ?? '';
    $lead_lang             = $user['lang']             ?? '';
    $lead_country_detected = $user['country_detected'] ?? '';
}
// ── Lead activity events ─────────────────────────────────────────────────────
$lead_events_data = mysqli_query($link, "SELECT * FROM lead_events WHERE lead_id = $lead_id ORDER BY created_at ASC");
$lead_events = [];
if ($lead_events_data) {
    while ($ev = mysqli_fetch_assoc($lead_events_data)) {
        $lead_events[] = $ev;
    }
}

// ── Follow-up delivery history ───────────────────────────────────────────────
$fup_history = [];
$fup_res = mysqli_query($link,
    "SELECT fl.sent_at, fl.status, fl.delivered_at, fl.opened_at, fl.bounced_at,
            fl.spam_at, fl.failed_at, fl.bounce_type, fl.brevo_message_id,
            fs.subject, fs.day_offset, fs.target AS sequence_type
     FROM followup_log fl
     JOIN followup_sequences fs ON fs.id = fl.sequence_id
     WHERE fl.user_id = $lead_id
     ORDER BY fl.sent_at DESC
     LIMIT 50");
if ($fup_res) {
    while ($row = mysqli_fetch_assoc($fup_res)) {
        $fup_history[] = $row;
    }
}

// Derive email health from history (spam/hard bounce = worst, then soft, engaged, delivered)
$emailHealth = 'unknown';
foreach ($fup_history as $row) {
    if ($row['status'] === 'spam') { $emailHealth = 'spam'; break; }
    if ($row['status'] === 'bounced' && $row['bounce_type'] === 'hard') { $emailHealth = 'hard_bounce'; break; }
    if ($row['status'] === 'bounced' && $emailHealth !== 'hard_bounce') { $emailHealth = 'soft_bounce'; }
    elseif ($emailHealth === 'unknown' && in_array($row['status'], ['opened','clicked'])) { $emailHealth = 'engaged'; }
    elseif ($emailHealth === 'unknown' && $row['status'] === 'delivered') { $emailHealth = 'delivered'; }
    elseif ($emailHealth === 'unknown' && $row['status'] !== '') { $emailHealth = 'sent'; }
}

$lead_social_data = mysqli_query($link, "SELECT * FROM user_socialmedia WHERE user_id = $lead_id");
if(mysqli_num_rows($lead_social_data) > 0){
    foreach($lead_social_data as $lsd){
        $lead_fblink = $lsd["fb_link"];
        $lead_instalink = $lsd["insta_link"];
        $lead_tglink = $lsd["tg_link"];
        $lead_twitterlink = $lsd["twitter_link"];
        $lead_country = $lsd["country"];
        $lead_language = $lsd["language"];
        $lead_phone = $lsd["phone"];
        $lead_whatsapp = $lsd["whatsapp"];
    }
}
?>
<!DOCTYPE html>
<html class="loading" lang="en">
<!-- BEGIN : Head-->

<?php
require_once "parts/head.php";
?>
<!-- END : Head-->

<!-- BEGIN : Body-->

<body class="vertical-layout vertical-menu 2-columns  navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">

    <?php
    require_once "parts/navbar.php";
    ?>
    <!-- Navbar (Header) Ends-->

    <!-- ////////////////////////////////////////////////////////////////////////////-->
    <div class="wrapper">


        <!-- main menu-->
        <!--.main-menu(class="#{menuColor} #{menuOpenType}", class=(menuShadow == true ? 'menu-shadow' : ''))-->
        <?php
        require_once "parts/sidebar.php";
        ?>






   <div class="main-panel">
            <!-- BEGIN : Main Content-->
            <div class="main-content">
                <div class="content-overlay"></div>
                <div class="content-wrapper">
                    <section class="users-view">
                        <!-- Media object starts -->
                        <div class="row">
                            <div class="col-12 col-sm-7">
                                <div class="media d-flex align-items-center">
                                    <a href="javascript:;">
                                        <img src="app-assets/img/portrait/small/<?= $lead_profile_pic?>" alt="user view avatar" class="users-avatar-shadow rounded" height="64" width="64">
                                    </a>
                                    <div class="media-body ml-3">
                                        <h4>
                                            <span><?= empty($lead_name) ? $lead_email : $lead_name?></span>
                                            <span class="text-muted font-medium-1">
                                            </span>
                                        </h4>
                                        <span>ID:</span>
                                        <span><?= $lead_id?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-5 d-flex justify-content-end align-items-center">
                                
                                <a href="leads.php" class="btn btn-sm btn-primary px-3 py-1">Back to Lead List</a>
                            </div>
                        </div>
                        <!-- Media object ends -->

                        <div class="row">
                            <div class="col-12">
                                <!-- Card data starts -->
                                <div class="card">
                                    <div class="card-content">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 col-xl-4">
                                                    <table class="table table-borderless">
                                                        <tbody>
                                                            <tr>
                                                                <td>Registered:</td>
                                                                <td><?= htmlspecialchars(date('Y/m/d', strtotime($lead_registered))) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Latest Activity:</td>
                                                                <td class="users-view-latest-activity"><?= $lead_timestamp?></td>
                                                            </tr>
                                                            
                                                            <tr>
                                                                <td>Language:</td>
                                                                <td>
                                                                <?php if (!empty($lead_lang)): ?>
                                                                    <?php
                                                                    $langLabels = [
                                                                        'en' => '🇬🇧 English',
                                                                        'de' => '🇩🇪 Deutsch',
                                                                        'fr' => '🇫🇷 Français',
                                                                        'es' => '🇪🇸 Español',
                                                                        'it' => '🇮🇹 Italiano',
                                                                        'nl' => '🇳🇱 Nederlands',
                                                                        'pt' => '🇵🇹 Português',
                                                                        'pl' => '🇵🇱 Polski',
                                                                        'ru' => '🇷🇺 Русский',
                                                                        'tr' => '🇹🇷 Türkçe',
                                                                        'ar' => '🇸🇦 العربية',
                                                                        'zh' => '🇨🇳 中文',
                                                                        'ja' => '🇯🇵 日本語',
                                                                        'ko' => '🇰🇷 한국어',
                                                                    ];
                                                                    echo $langLabels[$lead_lang] ?? htmlspecialchars(strtoupper($lead_lang));
                                                                    ?>
                                                                    <small style="opacity:.5;"> (auto-detected)</small>
                                                                <?php else: ?>
                                                                    <span style="opacity:.4;">—</span>
                                                                <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>PM Partner ID:</td>
                                                                <td><?= empty($lead_username) ? "-" : htmlspecialchars($lead_username) ?></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Member status:</td>
                                                                <td><span class="badge bg-light-success"><?= $lead_paidstatus?></span></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="col-12 col-xl-8 users-module">
                                                    <div class="table-responsive">
                                                        <table class="table mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Timestamp</th>
                                                                    <th>Step</th>
                                                                    <th>IP Adress</th>
                                                                    
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($lead_timestamp) ?></td>
                                                                    <td>
                                                                        Signed up (Step 0)
                                                                        <?php if (!empty($lead_page)): ?>
                                                                            <br><small style="opacity:.65;">
                                                                                Page: <strong><?= htmlspecialchars($lead_page) ?></strong>
                                                                                <?= !empty($lead_source)       ? ' &mdash; Source: <strong>'       . htmlspecialchars($lead_source)       . '</strong>' : '' ?>
                                                                                <?= !empty($lead_tr)           ? ' &mdash; Ref: <strong>'           . htmlspecialchars($lead_tr)           . '</strong>' : '' ?>
                                                                                <?= !empty($lead_utm_source)   ? ' &mdash; utm_source: <strong>'    . htmlspecialchars($lead_utm_source)   . '</strong>' : '' ?>
                                                                                <?= !empty($lead_utm_medium)   ? ' / utm_medium: <strong>'          . htmlspecialchars($lead_utm_medium)   . '</strong>' : '' ?>
                                                                                <?= !empty($lead_utm_campaign) ? ' / utm_campaign: <strong>'        . htmlspecialchars($lead_utm_campaign) . '</strong>' : '' ?>
                                                                            </small>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($lead_ip) ?></td>
                                                                </tr>
                                                                <?php if (!empty($lead_step1_at)): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($lead_step1_at) ?></td>
                                                                    <td>Clicked Step 1 Link</td>
                                                                    <td><?= htmlspecialchars($lead_step1_ip ?: $lead_ip) ?></td>
                                                                </tr>
                                                                <?php elseif (!empty($lead_signuproot)): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($lead_signuproot) ?></td>
                                                                    <td>Clicked Step 1 Link <small style="color:#888;">(client time)</small></td>
                                                                    <td><?= htmlspecialchars($lead_ip) ?></td>
                                                                </tr>
                                                                <?php endif; ?>
                                                                <?php if (!empty($lead_username)): ?>
                                                                <tr>
                                                                    <td><?= !empty($lead_step2_at) ? htmlspecialchars($lead_step2_at) : '—' ?></td>
                                                                    <td>Step 2 Complete — PM Partner ID saved</td>
                                                                    <td><?= !empty($lead_step2_ip) ? htmlspecialchars($lead_step2_ip) : '—' ?></td>
                                                                </tr>
                                                                <?php endif; ?>
                                                                <?php foreach ($lead_events as $ev):
                                                                    $evType = $ev['event_type'];
                                                                    if ($evType === 'signup_attempt') {
                                                                        $evLabel = '🔁 Re-signup attempt on landing page';
                                                                        $evColor = 'rgba(255,193,7,.7)';
                                                                    } elseif ($evType === 'login') {
                                                                        $evLabel = '🔓 Logged into backoffice';
                                                                        $evColor = 'rgba(40,199,111,.7)';
                                                                    } elseif ($evType === 'login_failed') {
                                                                        $evLabel = '⚠️ Failed login attempt';
                                                                        $evColor = 'rgba(234,84,85,.7)';
                                                                    } elseif ($evType === 'email_sent') {
                                                                        $evLabel = '📧 E-Mail gesendet';
                                                                        $evColor = 'rgba(255,255,255,.5)';
                                                                    } elseif ($evType === 'email_hard_bounce') {
                                                                        $evLabel = '⛔ Hard Bounce — E-Mail-Adresse ungültig';
                                                                        $evColor = 'rgba(234,84,85,.9)';
                                                                    } elseif ($evType === 'email_spam') {
                                                                        $evLabel = '🚫 Spam-Beschwerde';
                                                                        $evColor = 'rgba(234,84,85,.9)';
                                                                    } elseif ($evType === 'step1_button_click') {
                                                                        $evLabel = '👆 Step-1-Button geklickt';
                                                                        $evColor = 'rgba(0,207,232,.8)';
                                                                    } else {
                                                                        $evLabel = htmlspecialchars($evType);
                                                                        $evColor = 'rgba(255,255,255,.4)';
                                                                    }
                                                                ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars($ev['created_at']) ?></td>
                                                                    <td>
                                                                        <span style="color:<?= $evColor ?>;"><?= $evLabel ?></span>
                                                                        <?php if (!empty($ev['page'])): ?>
                                                                            <br><small style="opacity:.6;">
                                                                                Page: <strong><?= htmlspecialchars($ev['page']) ?></strong>
                                                                                <?= !empty($ev['source'])       ? ' &mdash; Source: <strong>'       . htmlspecialchars($ev['source'])       . '</strong>' : '' ?>
                                                                                <?= !empty($ev['utm_source'])   ? ' &mdash; utm_source: <strong>'   . htmlspecialchars($ev['utm_source'])   . '</strong>' : '' ?>
                                                                                <?= !empty($ev['utm_medium'])   ? ' / utm_medium: <strong>'         . htmlspecialchars($ev['utm_medium'])   . '</strong>' : '' ?>
                                                                                <?= !empty($ev['utm_campaign']) ? ' / utm_campaign: <strong>'       . htmlspecialchars($ev['utm_campaign']) . '</strong>' : '' ?>
                                                                            </small>
                                                                        <?php endif; ?>
                                                                        <?php if (!empty($ev['meta'])): ?>
                                                                            <br><small style="opacity:.5;"><?= htmlspecialchars($ev['meta']) ?></small>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?= htmlspecialchars($ev['ip'] ?? '—') ?></td>
                                                                </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Card data ends -->
                            </div>

                            <!-- ── E-Mail Delivery & Follow-up History ────────────── -->
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header py-2 d-flex align-items-center gap-2" style="border-bottom:1px solid rgba(255,255,255,.07);">
                                        <h5 class="card-title m-0" style="font-size:.95rem;"><i class="ft-mail mr-1" style="color:#cb2ebc;"></i> E-Mail Delivery &amp; Follow-up-Historie</h5>
                                        <?php
                                        $healthCfg = [
                                            'spam'        => ['🚫 Spam-Beschwerde',    '#ea5455', '#ea545533'],
                                            'hard_bounce' => ['⛔ Hard Bounce',         '#ea5455', '#ea545533'],
                                            'soft_bounce' => ['⚠️ Soft Bounce',         '#ff9800', '#ff980033'],
                                            'engaged'     => ['👁 Geöffnet/Geklickt',  '#00cfe8', '#00cfe833'],
                                            'delivered'   => ['✅ Zugestellt',          '#28c76f', '#28c76f33'],
                                            'sent'        => ['→ Gesendet',            'rgba(255,255,255,.5)', 'rgba(255,255,255,.06)'],
                                            'unknown'     => ['ℹ️ Noch kein Status',    'rgba(255,255,255,.3)', 'rgba(255,255,255,.04)'],
                                        ];
                                        [$hlabel, $hcolor, $hbg] = $healthCfg[$emailHealth] ?? $healthCfg['unknown'];
                                        echo '<span style="background:' . $hbg . ';color:' . $hcolor . ';border-radius:4px;padding:3px 10px;font-size:.8rem;font-weight:600;">' . $hlabel . '</span>';
                                        ?>
                                    </div>
                                    <?php
                                    if ($emailHealth === 'hard_bounce'): ?>
                                    <div style="background:#ea545518;border-left:3px solid #ea5455;padding:8px 16px;font-size:.85rem;color:#ea5455;">
                                        ⛔ Diese E-Mail-Adresse hat einen Hard Bounce erzeugt. Weitere Follow-ups werden wahrscheinlich nicht zugestellt.
                                    </div>
                                    <?php elseif ($emailHealth === 'spam'): ?>
                                    <div style="background:#ea545518;border-left:3px solid #ea5455;padding:8px 16px;font-size:.85rem;color:#ea5455;">
                                        🚫 Diese E-Mail-Adresse hat eine Spam-Beschwerde ausgelöst. Bitte nicht erneut kontaktieren.
                                    </div>
                                    <?php endif; ?>
                                    <div class="card-body p-0">
                                    <?php if (empty($fup_history)): ?>
                                        <p class="text-muted px-3 py-2 mb-0" style="font-size:.85rem;">ℹ️ Noch keine Follow-up-E-Mails an diesen Lead gesendet.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                        <table class="table table-sm mb-0" style="font-size:.82rem;">
                                            <thead>
                                                <tr style="font-size:.72rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.04em;">
                                                    <th style="padding:.4rem .75rem;">Gesendet</th>
                                                    <th style="padding:.4rem .75rem;">Template</th>
                                                    <th style="padding:.4rem .75rem;">Sequenz</th>
                                                    <th style="padding:.4rem .75rem;">Tag</th>
                                                    <th style="padding:.4rem .75rem;">Status</th>
                                                    <th style="padding:.4rem .75rem;">Zugestellt</th>
                                                    <th style="padding:.4rem .75rem;">Geöffnet</th>
                                                    <th style="padding:.4rem .75rem;">Bounce/Spam</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($fup_history as $fh):
                                                $fhStatus = $fh['status'] ?? 'sent';
                                                $statusBadge = [
                                                    'sent'      => ['Gesendet',       'rgba(255,255,255,.5)', 'rgba(255,255,255,.06)'],
                                                    'delivered' => ['Zugestellt',      '#28c76f', '#28c76f22'],
                                                    'opened'    => ['Geöffnet',        '#00cfe8', '#00cfe822'],
                                                    'clicked'   => ['Geklickt',        '#00cfe8', '#00cfe822'],
                                                    'bounced'   => ['Bounce',          '#ff9800', '#ff980022'],
                                                    'spam'      => ['Spam',            '#ea5455', '#ea545522'],
                                                    'failed'    => ['Fehlgeschlagen',  'rgba(255,255,255,.3)', 'rgba(255,255,255,.04)'],
                                                ];
                                                [$stLabel, $stColor, $stBg] = $statusBadge[$fhStatus] ?? ['—', 'rgba(255,255,255,.3)', 'transparent'];
                                                $fhSeq = $fh['sequence_type'] === 'member' ? 'Member' : 'Lead';
                                            ?>
                                            <tr style="border-top:1px solid rgba(255,255,255,.04);">
                                                <td style="padding:.4rem .75rem;white-space:nowrap;color:rgba(255,255,255,.45);"><?= htmlspecialchars(substr($fh['sent_at'], 0, 16)) ?></td>
                                                <td style="padding:.4rem .75rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($fh['subject']) ?>"><?= htmlspecialchars($fh['subject']) ?></td>
                                                <td style="padding:.4rem .75rem;"><?= $fhSeq ?></td>
                                                <td style="padding:.4rem .75rem;text-align:center;"><span class="badge badge-secondary" style="font-size:.7rem;">Tag <?= (int)$fh['day_offset'] ?></span></td>
                                                <td style="padding:.4rem .75rem;"><span style="background:<?= $stBg ?>;color:<?= $stColor ?>;border-radius:3px;padding:1px 7px;font-size:.72rem;white-space:nowrap;"><?= $stLabel ?><?php
                                                    if ($fhStatus === 'bounced' && !empty($fh['bounce_type'])) {
                                                        echo ' <span style="font-size:.65rem;opacity:.7;">(' . ($fh['bounce_type'] === 'hard' ? 'Hard' : 'Soft') . ')</span>';
                                                    }
                                                ?></span></td>
                                                <td style="padding:.4rem .75rem;color:rgba(255,255,255,.35);white-space:nowrap;"><?= !empty($fh['delivered_at']) ? htmlspecialchars(substr($fh['delivered_at'],0,16)) : '<span style="opacity:.25;">—</span>' ?></td>
                                                <td style="padding:.4rem .75rem;color:rgba(255,255,255,.35);white-space:nowrap;"><?= !empty($fh['opened_at']) ? htmlspecialchars(substr($fh['opened_at'],0,16)) : '<span style="opacity:.25;">—</span>' ?></td>
                                                <td style="padding:.4rem .75rem;white-space:nowrap;"><?php
                                                    if (!empty($fh['spam_at'])) {
                                                        echo '<span style="color:#ea5455;font-size:.75rem;">🚫 ' . htmlspecialchars(substr($fh['spam_at'],0,16)) . '</span>';
                                                    } elseif (!empty($fh['bounced_at'])) {
                                                        $btLabel = $fh['bounce_type'] === 'hard' ? '⛔' : '⚠️';
                                                        echo '<span style="color:#ff9800;font-size:.75rem;">' . $btLabel . ' ' . htmlspecialchars(substr($fh['bounced_at'],0,16)) . '</span>';
                                                    } else {
                                                        echo '<span style="opacity:.25;">—</span>';
                                                    }
                                                ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                        </div>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <!-- ── /E-Mail Delivery ───────────────────────────────── -->

                            <div class="col-12">
                                </div>
                            <div class="col-12">
                                <!-- User detail starts -->
                                <div class="card">
                                    <div class="card-content">
                                        <div class="card-body">
                                            <div class="row bg-primary bg-lighten-4 rounded mb-3 mx-1 text-center text-lg-left">
                                                
                                            </div>

                                            <div class="col-12 col-xl-4">
                                                

                                                <h5 class="mb-2 text-bold-500"><i class="ft-link mr-2"></i>Social Profiles</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-borderless">
                                                        <tbody>
                                                            <tr>
                                                                <td>Facebook:</td>
                                                                <td><a href="<?= $lead_fblink ?? ""?>"><?= $lead_fblink ?? ""?></a></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Instagram:</td>
                                                                <td><a href="<?= $lead_instalink ?? ""?>"><?= $lead_instalink ?? ""?></a></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Telegram:</td>
                                                                <td><a href="<?= $lead_instalink ?? ""?>"><?= $lead_tglink ?? ""?></a></td>
                                                            </tr>
                                                            <tr>
                                                                <td>Twitter (X):</td>
                                                                <td><a href="<?= $lead_twitterlink ?? ""?>"><?= $lead_twitterlink ?? ""?></a></td>
                                                            </tr>


                                                        </tbody>
                                                    </table>
                                                </div>

                                                <h5 class="mb-2 text-bold-500"><i class="ft-info mr-2"></i> Personal Info</h5>
                                                <div class="table-responsive">
                                                    <table class="table table-borderless m-0">
                                                        <tbody>
                                                            
                                                            <tr>
                                                                <td>Country:</td>
                                                                <td>
                                                                <?php
                                                                // Convert ISO code to flag emoji
                                                                function isoToFlag(string $iso): string {
                                                                    if (strlen($iso) !== 2) return '';
                                                                    $iso = strtoupper($iso);
                                                                    return mb_chr(ord($iso[0]) - 65 + 0x1F1E6) . mb_chr(ord($iso[1]) - 65 + 0x1F1E6);
                                                                }
                                                                if (!empty($lead_country_detected)):
                                                                    echo isoToFlag($lead_country_detected) . ' ' . htmlspecialchars($lead_country_detected);
                                                                    echo '<small style="opacity:.5;"> (auto-detected)</small>';
                                                                elseif (!empty($lead_country ?? '')):
                                                                    echo htmlspecialchars($lead_country);
                                                                else:
                                                                    echo '<span style="opacity:.4;">—</span>';
                                                                endif;
                                                                ?>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>Contact:</td>
                                                                <td><?= $lead_phone ?? ""?></td>
                                                            </tr>
                                                              <tr>
                                                                <td>WhatsApp:</td>
                                                                <td><?= $lead_whatsapp ?? ""?></td>
                                                            </tr>
                                                            <tr>
                                                                <td>E-mail:</td>
                                                                <td><?= $lead_email ?? ""?></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- User detail ends -->
                            </div>
                        </div>
                    </section>
                </div>
            </div>
            <!-- END : End Main Content-->




            <!-- BEGIN : Footer-->
         
          <?php
    require_once "parts/footer.php";
    ?>
    
            <!-- End : Footer-->
            <!-- Scroll to top button -->
            <button class="btn btn-primary scroll-top" type="button"><i class="ft-arrow-up"></i></button>

        </div>
    </div>
    <!-- ////////////////////////////////////////////////////////////////////////////-->

    
    <!-- START Notification Sidebar -->
    <!-- END Notification Sidebar-->


    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>
    <!-- BEGIN VENDOR JS-->
    <script src="app-assets/vendors/js/vendors.min.js"></script>
    <script src="app-assets/vendors/js/switchery.min.js"></script>
    <!-- BEGIN VENDOR JS-->
    <!-- BEGIN PAGE VENDOR JS-->
    <!-- END PAGE VENDOR JS-->
    <!-- BEGIN APEX JS-->
    <script src="app-assets/js/core/app-menu.js"></script>
    <script src="app-assets/js/core/app.js"></script>
    <script src="app-assets/js/notification-sidebar.js"></script>
    <script src="app-assets/js/customizer.js"></script>
    <script src="app-assets/js/scroll-top.js"></script>
    <!-- END APEX JS-->
    <!-- BEGIN PAGE LEVEL JS-->
    <script src="app-assets/js/page-users.js"></script>
    <!-- END PAGE LEVEL JS-->
    <!-- BEGIN: Custom CSS-->
    <script src="assets/js/scripts.js"></script>
    <!-- END: Custom CSS-->
</body>
<!-- END : Body-->

</html>