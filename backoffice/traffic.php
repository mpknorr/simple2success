<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Start the PHP session


// Check if the 'userid' session variable is not set or empty
if (!isset($_SESSION['userid']) || empty($_SESSION['userid'])) {
    // Redirect to the login page
    header('Location: login.php');
    exit(); // Make sure to exit to prevent further execution
}

// Include your 'conn.php' file
include '../includes/conn.php';

// Retrieve the 'userid' from the session
$userid = $_SESSION['userid'];

// Now you can use the $userid variable in your code
// ...
?>
<!DOCTYPE html>
<html class="loading" lang="en">

<?php
require_once "parts/head.php";
?>

<!-- BEGIN : Body-->

<body class="vertical-layout vertical-menu 2-columns  navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">

<?php
require_once "parts/navbar.php";
?>

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
      <div class="content-wrapper">
         <!--Start Traffic content -->
         <div class="col-lg-12">
         <?php
            if(empty($username)){
                echo "<p>You should complete STEP 1 & STEP 2 from <a href='" . $baseurl . "/backoffice/start.php'>here</a>.</p>";
            }else{
            ?>
            <!--  -->
            <div class="card">
               <div class="card-header">
                  <h4 class="card-title m-0 p-0">Traffic Resources</h4>
               </div>
               <div class="card-content">
                  <div class="card-body">
                     As part of your membership we are offering traffic on tap provided by various traffic agencies and list owners that we have relationships with. We will expand the list of available sources as time goes on and as we test different ways of generating leads. While we have personally used all traffic vendors listed here, in no way are we to be held responsible for the success or failure of your campaign nor can you expect to get a 'do over' or a refund if things did not work to your expectations. Start small and scale up is usually the way to go. <br><br>
                     A word of advice, do not come into this with a lottery ticket type mentality.<br><br>
                     The ADVERTISING is only doing part of the work, which is to generate leads.<br><br>
                     What YOU DO with those leads is the key to building huge downlines...<br><br>
                     Traffic packages, prices and availability are subject to change at any time. Lock in your order now while it's available.<br><Br>
                     As we are big believers in transparency, you should know all of the below links are affiliate links that pay us a commission if you purchase something from them. Please do not share this list outside of our team.
                  </div>
               </div>
            </div>
            <!-- Horizontal cards start -->
            <section id="horizontal-examples">
               <div class="row">
                  <div class="col-12">
                     <div class="content-header">Our Recommended Traffic Vendor is Traffic4Me:</div>
                  </div>
               </div>
               <div class="row match-height">
                  <div class="col-xl-12 col-lg-12">
                     <div class="card overflow-hidden">
                        <div class="row">
                           <div class="col-sm-6 col-12">
                              <div class="card-img">
                                 <div class="embed-responsive embed-responsive-16by9">
                                    <iframe width="560" height="315" src="https://www.youtube.com/embed/_QQTSUaYigk?si=UZPhxB5Zrce9iglS" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                 </div>
                              </div>
                           </div>
                           <div class="col-sm-6 col-12 d-flex align-items-center">
                              <div class="card-body">
                                 <div class="align-self-center">
                                    <div class="px-3">
                                       <h4 class="card-title mb-3">Buying Advertising on Traffic4Me</h4>
                                       <p class="card-text">T4M has a variety of different traffic streams you can purchase from. Our favorites are the 'Premium Email Traffic' and 'Top 5 Email Traffic'. Stay away from the SMS ads for now, we have not had any success with them.<br><br>
                                          When purchasing traffic from here, in the campaign details, pick the option for: 'Let the publisher choose swipe' then just provide your capture page link.
                                       </p>
                                       <a href="https://www.trafficforme.net/MK35" type="button" class="btn btn-primary" target="_blank">Click here to set up an account and begin purchasing traffic from Traffic4Me</a>
               </div>

                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </section>
            <section id="horizontal-examples">
               <div class="row">
                  <div class="col-12">
                     <div class="content-header">Our Second Recommended Traffic Vendor is Udimi:</div>
                  </div>
               </div>
               <div class="row match-height">
                  <div class="col-xl-12 col-lg-12">
                     <div class="card overflow-hidden">
                        <div class="row">
                           <div class="col-sm-6 col-12">
                              <div class="card-img">
                                 <div style="padding:56.25% 0 0 0;position:relative;"><iframe src="https://player.vimeo.com/video/741467747?h=45ba97bec4&title=0&byline=0&portrait=0" style="position:absolute;top:0;left:0;width:100%;height:100%;" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe></div>
                                 <script src="https://player.vimeo.com/api/player.js"></script>
                              </div>
                           </div>
                           <div class="col-sm-6 col-12 d-flex align-items-center">
                              <div class="card-body">
                                 <div class="align-self-center">
                                    <div class="px-3">
                                       <h4 class="card-title mb-3">Buying Advertising on UDIMI</h4>
                                       <p class="card-text">A word of caution, please be careful when choosing a click supplier to buy from. It is too easy to 'fake' clicks and if you are buying from new or untrustworthy sellers it is entirely possible that they are scamming you with fake clicks or optins. We can not vouch for every seller on udimi as there are new people joining constantly who may or may not be legitimate clicks suppliers with fresh lists of interested people.</p>
                                       <a href="https://udimi.com/a/w2zch/" type="button" class="btn btn-primary" target="_blank">Click here to sign up on UDIMI</a>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </section>
            <section id="horizontal-examples">
               <div class="row">
                  <div class="col-12">
                     <div class="content-header">Traffic Packages from Traffic Authority</div>
                  </div>
               </div>
               <div class="row match-height">
                  <div class="col-xl-12 col-lg-12">
                     <div class="card overflow-hidden">
                        <div class="row">
                           <div class="col-sm-6 col-12">
                              <div class="card-img">
                                 <div class="embed-responsive embed-responsive-16by9">
                                    <iframe width="560" height="315" src="https://www.youtube.com/embed/QxA4wzU5G6Q?si=2m9PINeGGxIvHBXF" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                 </div>
                              </div>
                           </div>
                           <div class="col-sm-6 col-12 d-flex align-items-center">
                              <div class="card-body">
                                 <div class="align-self-center">
                                    <div class="px-3">
                                       <h4 class="card-title mb-3">Buying Advertising on Traffic Authority:</h4>
                                       <p class="card-text">Welcome to the Traffic Authority review. Here I'm going to cover TrafficAuthority.com thoroughly so you can make an informed decision as to whether you should get involved with this opportunity or not.
                                          I hope you enjoy my non-biased Traffic Authority review and my hopes is that by the end of it you know the answer to is Traffic Authority a scam or legit.
                                          I've been doing reviews on make money online programs and opportunities for some time now. I hope this video on TrafficAuthority.com inspires you and helps you get clear on how to achieve your income goals. :)
                                          Traffic Authority Review l Are The Packages Worth Buying? (TRUTH REVEALED)
                                       </p>
                                       <button type="button" class="btn btn-primary">Order traffic packages here.</button>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </section>
            <section id="horizontal-examples">
               <div class="row">
                  <div class="col-12">
                     <div class="content-header">100 Leads A Day From MyLeadGenSecret</div>
                  </div>
               </div>
               <div class="row match-height">
                  <div class="col-xl-12 col-lg-12">
                     <div class="card overflow-hidden">
                        <div class="row">
                           <div class="col-sm-6 col-12">
                              <div class="card-img">
                                 <img class="img-fluid" src="app-assets/img/photos/mlgleads.jpeg" alt="Card image cap">
                              </div>
                           </div>
                           <div class="col-sm-6 col-12 d-flex align-items-center">
                              <div class="card-body">
                                 <div class="align-self-center">
                                    <div class="px-3">
                                       <h4 class="card-title mb-3">Here's what you get with
                                          MyLeadGenSecret...
                                       </h4>
                                       <p class="card-text">
                                       <ul>
                                          <li>100 drip fed "Business Opportunity" email subscribers into your contact list EVERY 24-Hours...</li>
                                          <li>Done-for-you emailing platform (CAN-SPAM Compliant)...</li>
                                          <li>Earn $5/Month "RESIDUAL INCOME" per referral...</li>
                                          <li>Earn $100 BONUSES for EVERY 5 customers you refer...</li>
                                          <li>Pays by Check, PayPal or Bitcoin...</li>
                                          <li>PLUS...</li>
                                       </ul>
                                       </p>
                                       <button type="button" class="btn btn-primary">Mail Your Offer to 100-200 New Leads Every Day</button>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </section>
            <!--  -->
            <?php }?>
         </div>
      </div>
      <!-- // Horizontal cards end -->
      <!--- End Traffic Content ////////////////////////////////////////-->
      <!-- END : End Main Content-->
   </div>
</div>
</div>


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
    <script src="app-assets/vendors/js/chartist.min.js"></script>
    <!-- END PAGE VENDOR JS-->
    <!-- BEGIN APEX JS-->
    <script src="app-assets/js/core/app-menu.js"></script>
    <script src="app-assets/js/core/app.js"></script>
    <script src="app-assets/js/notification-sidebar.js"></script>
    <script src="app-assets/js/customizer.js"></script>
    <script src="app-assets/js/scroll-top.js"></script>
    <!-- END APEX JS-->
    <!-- BEGIN PAGE LEVEL JS-->
    <script src="app-assets/js/dashboard1.js"></script>
    <!-- END PAGE LEVEL JS-->
    <!-- BEGIN: Custom CSS-->
    <script src="assets/js/scripts.js"></script>
    <!-- END: Custom CSS-->
</body>
<!-- END : Body-->

</html>