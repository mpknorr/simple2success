<style>
/* Make sidebar a flex column so content fills remaining height after header */
.app-sidebar {
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
}
.app-sidebar .sidebar-header {
    flex-shrink: 0;
}
.app-sidebar .sidebar-content.main-menu-content {
    flex: 1 1 0 !important;
    height: auto !important;
    max-height: none !important;
    overflow-y: scroll !important;
}
.app-sidebar .sidebar-content.main-menu-content::-webkit-scrollbar { width: 4px; }
.app-sidebar .sidebar-content.main-menu-content::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }
.app-sidebar .sidebar-content.main-menu-content::-webkit-scrollbar-track { background: transparent; }
</style>
<div class="app-sidebar menu-fixed" data-background-color="black" data-image="app-assets/img/sidebar-bg/01.jpg" data-scroll-to-active="true">
            <!-- main menu header-->
            



            <!-- Sidebar Header starts-->
            <div class="sidebar-header">
                <div class="logo clearfix"><a class="logo-text float-left" href="index.php">
                        <div align="center"><img  align="center" src="app-assets/img/logos/eagle5b.jpg" class="img-fluid"/></div>

                    </a><a class="nav-toggle d-none d-lg-none d-xl-block" id="sidebarToggle" href="javascript:;"><i class="toggle-icon ft-toggle-right" data-toggle="expanded"></i></a><a class="nav-close d-block d-lg-block d-xl-none" id="sidebarClose" href="javascript:;"><i class="ft-x"></i></a></div>
            </div>
            <!-- Sidebar Header Ends-->
            <!-- / main menu header-->
            



            <!-- main menu content-->
            <div class="sidebar-content main-menu-content">
                <div class="nav-container">
                    <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
                        <li class="nav-item"><a href="index.php"><i class="ft-home"></i><span class="menu-title" data-i18n="Dashboard">Dashboard</span></a>
                        </li>

                        <li class="nav-item"><a href="start.php"><i class="ft-dollar-sign"></i><span class="menu-title" data-i18n="Your Success Steps">Your Success Steps</span></a>
                        </li>

                        <li class=" nav-item"><a href="links.php"><i class="ft-link"></i><span class="menu-title" data-i18n="Your Links">Your Links</span></a>
                        </li>
                        
                        <li class=" nav-item"><a href="leads.php"><i class="ft-users"></i><span class="menu-title" data-i18n="Your Leads">Your Leads</span></a>
                        </li>

                        <li class=" nav-item"><a href="leaderboard.php"><i class="ft-award"></i><span class="menu-title" data-i18n="Leaderboard">Leaderboard</span></a>
                        </li>
                        
                        <li class=" nav-item"><a href="swipe.php"><i class="ft-edit-2"></i><span class="menu-title" data-i18n="Swipe Copy">Swipe Copy</span></a>
                        </li>
                        
                        <li class=" nav-item"><a href="traffic.php"><i class="ft-cpu"></i><span class="menu-title" data-i18n="Traffic Sources">Traffic Sources</span></a>
                        </li>
                        <?php
                        if(!empty($username)){
                        ?>
                        <li class="nav-item"><a href="subscription.php"><i class="ft-help-circle"></i><span class="menu-title" data-i18n="Subscription">Subscription</span></a>    
                        </li>
                        <?php }?>

                        <li class="nav-item"><a href="support.php"><i class="ft-help-circle"></i><span class="menu-title" data-i18n="Subscription">Support</span></a>    
                        </li>

                        <?php if(!empty($_SESSION["is_admin"])){ ?>
                        <li class="nav-item has-sub"><a href="javascript:;"><i class="ft-shield"></i><span class="menu-title">Admin</span></a>
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
                        <?php } ?>

                        <li class="nav-item"><a href="user-profile.php"><i class="ft-user"></i><span class="menu-title">My Profile</span></a>
                        </li>

                        <li class="nav-item"><a href="logout.php"><i class="ft-log-out"></i><span class="menu-title" data-i18n="Logout">Logout</span></a>
                        </li>
                        
                    </ul>
                </div>
            </div>

            <!-- main menu content-->
            

            <div class="sidebar-background"></div>
            <!-- main menu footer-->
            <!-- include includes/menu-footer-->
            <!-- main menu footer-->
            <!-- / main menu-->
        </div>
