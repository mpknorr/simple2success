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