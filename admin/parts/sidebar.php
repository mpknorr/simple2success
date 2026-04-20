<div class="app-sidebar menu-fixed" data-background-color="black" data-image="../backoffice/app-assets/img/sidebar-bg/01.jpg" data-scroll-to-active="true">
            <div class="sidebar-header">
                <div class="logo clearfix">
                    <a class="logo-text float-left" href="<?= $baseurl ?>/backoffice/index.php">
                        <div align="center"><img align="center" src="../backoffice/app-assets/img/logos/eagle5b.jpg" class="img-fluid"/></div>
                    </a>
                    <a class="nav-toggle d-none d-lg-none d-xl-block" id="sidebarToggle" href="javascript:;"><i class="toggle-icon ft-toggle-right" data-toggle="expanded"></i></a>
                    <a class="nav-close d-block d-lg-block d-xl-none" id="sidebarClose" href="javascript:;"><i class="ft-x"></i></a>
                </div>
            </div>

            <div class="sidebar-content main-menu-content">
                <div class="nav-container">
                    <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/index.php"><i class="ft-home"></i><span class="menu-title">Dashboard</span></a></li>
                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/start.php"><i class="ft-dollar-sign"></i><span class="menu-title">Your Success Steps</span></a></li>
                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/links.php"><i class="ft-link"></i><span class="menu-title">Your Links</span></a></li>
                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/leads.php"><i class="ft-users"></i><span class="menu-title">Your Leads</span></a></li>
                        <?php if(!empty($username)){ ?>
                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/subscription.php"><i class="ft-star"></i><span class="menu-title">Subscription</span></a></li>
                        <?php } ?>
                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/leaderboard.php"><i class="ft-award"></i><span class="menu-title">Leaderboard</span></a></li>
                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/swipe.php"><i class="ft-edit-2"></i><span class="menu-title">Swipe Copy</span></a></li>
                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/traffic.php"><i class="ft-cpu"></i><span class="menu-title">Traffic Sources</span></a></li>
                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/support.php"><i class="ft-help-circle"></i><span class="menu-title">Support</span></a></li>

                        <li class="nav-item has-sub open"><a href="javascript:;"><i class="ft-shield"></i><span class="menu-title">Admin</span></a>
                            <ul class="menu-content">
                                <li><a href="<?= $baseurl ?>/admin/admin-settings.php"><i class="ft-settings"></i><span class="menu-title">Einstellungen</span></a></li>
                                <li><a href="<?= $baseurl ?>/admin/admin-leads.php"><i class="ft-users"></i><span class="menu-title">Alle User</span></a></li>
                                <li><a href="<?= $baseurl ?>/admin/admin-stats.php"><i class="ft-bar-chart-2"></i><span class="menu-title">Statistiken</span></a></li>
                                <li><a href="<?= $baseurl ?>/admin/admin-templates.php"><i class="ft-file-text"></i><span class="menu-title">E-Mail Templates</span></a></li>
                                <li><a href="<?= $baseurl ?>/admin/admin-support.php"><i class="ft-inbox"></i><span class="menu-title">Support Tickets</span></a></li>
                                <li><a href="<?= $baseurl ?>/admin/admin-rotator.php"><i class="ft-refresh-cw"></i><span class="menu-title">Rotator</span></a></li>
                                <li><a href="<?= $baseurl ?>/admin/admin-newsletter.php"><i class="ft-send"></i><span class="menu-title">Newsletter</span></a></li>
                                <li><a href="<?= $baseurl ?>/admin/admin-followup.php"><i class="ft-clock"></i><span class="menu-title">Follow-Up Sequenzen</span></a></li>
                                <li><a href="<?= $baseurl ?>/admin/admin-legal.php"><i class="ft-file-text"></i><span class="menu-title">Legal Pages</span></a></li>
                            </ul>
                        </li>

                        <li class="nav-item"><a href="<?= $baseurl ?>/backoffice/logout.php"><i class="ft-log-out"></i><span class="menu-title">Logout</span></a></li>
                    </ul>
                </div>
            </div>

            <div class="sidebar-background"></div>
        </div>
