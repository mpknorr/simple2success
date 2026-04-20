<nav class="navbar navbar-expand-lg navbar-light header-navbar navbar-static">
        <div class="container-fluid navbar-wrapper">
            <div class="navbar-header d-flex">
                <div class="navbar-toggle menu-toggle d-xl-none d-block float-left align-items-center justify-content-center" data-toggle="collapse"><i class="ft-menu font-medium-3"></i></div>
                <ul class="navbar-nav">
                    <li class="nav-item mr-2 d-none d-lg-block"><a class="nav-link apptogglefullscreen" id="navbar-fullscreen" href="javascript:;"><i class="ft-maximize font-medium-3"></i></a></li>
                </ul>
            </div>
            <div class="navbar-container">
                <div class="collapse navbar-collapse d-block" id="navbarSupportedContent">
                    <ul class="navbar-nav">
                        <li class="i18n-dropdown dropdown nav-item mr-2"><a class="nav-link d-flex align-items-center dropdown-toggle dropdown-language" id="dropdown-flag" href="javascript:;" data-toggle="dropdown"><img class="langimg selected-flag" src="app-assets/img/flags/us.png" alt="flag"><span class="selected-language d-md-flex d-none">English</span></a>
                            <div class="dropdown-menu dropdown-menu-right text-left" aria-labelledby="dropdown-flag"><a class="dropdown-item" href="javascript:;" data-language="en"><img class="langimg mr-2" src="app-assets/img/flags/us.png" alt="flag"><span class="font-small-3">English</span></a><!--<a class="dropdown-item" href="javascript:;" data-language="es"><img class="langimg mr-2" src="app-assets/img/flags/es.png" alt="flag"><span class="font-small-3">Spanish</span></a><a class="dropdown-item" href="javascript:;" data-language="pt"><img class="langimg mr-2" src="app-assets/img/flags/pt.png" alt="flag"><span class="font-small-3">Portuguese</span></a><a class="dropdown-item" href="javascript:;" data-language="de"><img class="langimg mr-2" src="app-assets/img/flags/de.png" alt="flag"><span class="font-small-3">German</span></a>-->
                            </div> 
                        </li>

                        <?php
                        // Notifications bell
                        $notifUserId = $_SESSION['userid'] ?? 0;
                        $notifList = [];
                        $notifCount = 0;
                        if ($notifUserId) {
                            // Create table if not exists (safe on every load)
                            mysqli_query($link, "CREATE TABLE IF NOT EXISTS notifications (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                recipient_id INT NOT NULL,
                                lead_id INT NOT NULL,
                                lead_name VARCHAR(255),
                                lead_profile_pic VARCHAR(255),
                                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                is_read TINYINT(1) DEFAULT 0
                            )");
                            $notifRes = mysqli_query($link, "SELECT * FROM notifications WHERE recipient_id = $notifUserId AND is_read = 0 ORDER BY created_at DESC LIMIT 10");
                            if ($notifRes) {
                                while ($row = mysqli_fetch_assoc($notifRes)) {
                                    $notifList[] = $row;
                                }
                                $notifCount = count($notifList);
                            }
                        }
                        function timeAgo($datetime) {
                            $diff = time() - strtotime($datetime);
                            if ($diff < 60) return $diff . 's ago';
                            if ($diff < 3600) return floor($diff/60) . 'm ago';
                            if ($diff < 86400) return floor($diff/3600) . 'h ago';
                            return floor($diff/86400) . ' days ago';
                        }
                        ?>
                        <li class="dropdown nav-item mx-2">
                            <a class="nav-link dropdown-toggle dropdown-notification px-2 mt-2" id="dropdownBasic1" href="javascript:;" data-toggle="dropdown">
                                <i class="ft-bell font-medium-3"></i>
                                <?php if ($notifCount > 0): ?>
                                <span class="notification badge badge-pill badge-danger"><?= $notifCount ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="notification-dropdown dropdown-menu dropdown-menu-media dropdown-menu-right m-0 overflow-hidden">
                                <li class="dropdown-menu-header">
                                    <div class="dropdown-header d-flex justify-content-between m-0 px-3 py-2 white bg-primary">
                                        <div class="d-flex">
                                            <i class="ft-bell font-medium-3 d-flex align-items-center mr-2"></i>
                                            <span class="noti-title"><?= $notifCount > 0 ? $notifCount . ' New Notification' . ($notifCount > 1 ? 's' : '') : 'Notifications' ?></span>
                                        </div>
                                        <?php if ($notifCount > 0): ?>
                                        <span class="text-bold-400 cursor-pointer" id="markAllRead" style="cursor:pointer;">Mark all as read</span>
                                        <?php endif; ?>
                                    </div>
                                </li>
                                <li class="scrollable-container" style="max-height:320px;overflow-y:auto;">
                                    <?php if (empty($notifList)): ?>
                                    <div class="px-3 py-3 text-muted" style="font-size:13px;">No new notifications</div>
                                    <?php else: ?>
                                    <div class="px-3 py-1" style="font-size:11px;color:#aaa;border-bottom:1px solid #333;">New Members (Last 10)</div>
                                    <?php foreach ($notifList as $n): ?>
                                    <a class="d-flex justify-content-between read-notification px-3 py-2" href="<?= $baseurl ?>/backoffice/leads-view.php?id=<?= (int)$n['lead_id'] ?>" style="border-bottom:1px solid #333;text-decoration:none;">
                                        <div class="media d-flex align-items-center">
                                            <div class="media-left mr-3">
                                                <img class="avatar rounded-circle" src="app-assets/img/portrait/small/<?= htmlspecialchars($n['lead_profile_pic'] ?: 'user_default.png') ?>" alt="avatar" height="40" width="40" style="object-fit:cover;">
                                            </div>
                                            <div class="media-body">
                                                <h6 class="m-0" style="font-size:13px;">
                                                    <span><?= htmlspecialchars($n['lead_name']) ?></span>
                                                    <small class="grey lighten-1 font-italic float-right" style="color:#888;"><?= timeAgo($n['created_at']) ?></small>
                                                </h6>
                                                <small class="noti-text" style="color:#aaa;">Completed Step 2</small>
                                            </div>
                                        </div>
                                    </a>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </li>
                                <?php if ($notifCount > 0): ?>
                                <li class="dropdown-menu-footer">
                                    <div class="noti-footer text-center cursor-pointer primary border-top text-bold-400 py-1" id="markAllRead2" style="cursor:pointer;">Mark all as read</div>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <script>
                        (function(){
                            function markRead() {
                                fetch('<?= $baseurl ?>/includes/markNotificationsRead.php', {method:'POST'})
                                    .then(function(){ location.reload(); });
                            }
                            document.addEventListener('DOMContentLoaded', function(){
                                var b1 = document.getElementById('markAllRead');
                                var b2 = document.getElementById('markAllRead2');
                                if(b1) b1.addEventListener('click', markRead);
                                if(b2) b2.addEventListener('click', markRead);
                            });
                        })();
                        </script>

                        <li class="dropdown nav-item mr-1"><a class="nav-link dropdown-toggle user-dropdown d-flex align-items-end" id="dropdownBasic2" href="javascript:;" data-toggle="dropdown">
                                <div class="user d-md-flex d-none mr-2"><span class="text-right"><?= empty($name) ? $useremail : $name?></span><span class="text-right text-muted font-small-3"><?= $paidstatus ?> Member</span></div><img class="avatar" src="app-assets/img/portrait/small/<?= $profile_pic?>" alt="avatar" height="35" width="35">
                            </a>
                            <div class="dropdown-menu text-left dropdown-menu-right m-0 pb-0" aria-labelledby="dropdownBasic2">
                                <a class="dropdown-item" href="user-profile.php">
                                    <div class="d-flex align-items-center"><i class="ft-edit mr-2"></i><span>Edit Profile</span></div>
                                </a>

                                <div class="dropdown-divider"></div><a class="dropdown-item" href="logout.php">
                                    <div class="d-flex align-items-center"><i class="ft-power mr-2"></i><span>Logout</span></div>
                                </a>
                            </div>
                        </li>
                       <!-- <li class="nav-item d-none d-lg-block mr-2 mt-1"><a class="nav-link notification-sidebar-toggle" href="javascript:;"><i class="ft-align-right font-medium-3"></i></a></li>-->
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <!-- Navbar (Header) Ends-->