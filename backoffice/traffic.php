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
          <!--  <section id="horizontal-examples">
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
                                 <div style="padding:56.25% 0 0 0;position:relative;">
                                    <iframe id="vimeo-traffic-1"
                                            src="https://player.vimeo.com/video/1185176396?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479&amp;title=0&amp;byline=0&amp;portrait=0"
                                            frameborder="0"
                                            allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share"
                                            referrerpolicy="strict-origin-when-cross-origin"
                                            style="position:absolute;top:0;left:0;width:100%;height:100%;"
                                            title="How to Buy Advertising on Traffic4Me"></iframe>


                                    </div>
                                 <script src="https://player.vimeo.com/api/player.js"></script>
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
            </section>-->
            <section id="horizontal-examples">
               <div class="row">
                  <div class="col-12">
                     <div class="content-header">Our Recommended Traffic Vendor is Udimi:</div>
                  </div>
               </div>
               <div class="row match-height">
                  <div class="col-xl-12 col-lg-12">
                     <div class="card overflow-hidden">
                        <div class="row">
                           <div class="col-sm-6 col-12">
                              <div class="card-img">
                                 <div style="padding:56.25% 0 0 0;position:relative;">
                                    <iframe id="vimeo-traffic-2"
                                            src="https://player.vimeo.com/video/1185173699?badge=0&amp;autopause=0&amp;player_id=0&amp;app_id=58479&amp;title=0&amp;byline=0&amp;portrait=0"
                                            frameborder="0"
                                            allow="autoplay; fullscreen; picture-in-picture; clipboard-write; encrypted-media; web-share"
                                            referrerpolicy="strict-origin-when-cross-origin"
                                            style="position:absolute;top:0;left:0;width:100%;height:100%;"
                                            title="How to Buy a Solo Ad on Udimi"></iframe>
                                 </div>
                                 <script src="https://player.vimeo.com/api/player.js"></script>
                              </div>
                           </div>
                           <div class="col-sm-6 col-12 d-flex align-items-center">
                              <div class="card-body">
                                 <div class="align-self-center">
                                    <div class="px-3">
                                       <h4 class="card-title mb-3">Serious Marketers Buy Solo Ads on Udimi</h4>
                                       <p class="card-text">Buying traffic should not feel like a gamble.<br>
<br>
With Udimi, you can see who you are buying from before you spend a dollar. Seller ratings, order history, and performance data help you make a smarter decision with less risk.<br>
<br>
That means you are not guessing.<br>
You are choosing based on proof.<br>
<br>
Udimi also gives buyers an extra layer of protection by keeping orders and payments inside the platform. This creates more security, more transparency, and more peace of mind.<br>
<br>
For advertisers, that matters.<br>

Because when you are investing in traffic, you want three things:<br>
quality, safety, and results.<br>
<br>
Udimi makes that easier by giving you access to experienced solo ad sellers across multiple niches, while helping you avoid the uncertainty that comes with random traffic sources.<br>
<br>
If you want a simpler and more trusted way to buy solo ads, Udimi is one of the first places marketers look.</p>
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
                                    <iframe id="yt-traffic-1" width="560" height="315" src="https://www.youtube.com/embed/QxA4wzU5G6Q?si=2m9PINeGGxIvHBXF&enablejsapi=1" title="Traffic Authority Review" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
                                 </div>
                              </div>
                           </div>
                           <div class="col-sm-6 col-12 d-flex align-items-center">
                              <div class="card-body">
                                 <div class="align-self-center">
                                    <div class="px-3">
                                       <h4 class="card-title mb-3">Buying Advertising on Traffic Authority:</h4>
                                       <p class="card-text">Traffic Authority provides premium traffic, tools, and training for online businesses and has been serving marketers for over 18 years.<br>
<br>
Their traffic is focused exclusively on USA and Canada, helping advertisers avoid wasted spend on untargeted international clicks. With delivery usually starting within 7 to 14 days, plus the ability to change your link or pause and restart orders, you get both flexibility and control.<br>
<br>
Traffic Authority also tests providers rigorously, monitors performance daily, and uses exclusive wholesale deals plus in-house traffic generation to maintain strong traffic quality at competitive pricing.<br>
<br>
If you want traffic that is built for better targeting, better control, and better business results, Traffic Authority offers a strong solution.<br>
                                       </p>
                                      <a href="https://r1.trafficauthority.net/cpcb40 " type="button" class="btn btn-primary" target="_blank">Order traffic packages here.</a>
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
                                       <a href="https://www.myleadgensecret.com/sizzle/?rid=2530" type="button" class="btn btn-primary" target="_blank">Mail Your Offer to 100-200 New Leads Every Day</a>
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
<script src="https://www.youtube.com/iframe_api"></script>
<script>
(function() {
  var trackUrl = '../includes/track-video.php';
  var page     = window.location.pathname;

  function sendVideoPlay(title) {
    fetch(trackUrl, {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'video=' + encodeURIComponent(title) + '&page=' + encodeURIComponent(page)
    });
  }

  // Vimeo
  document.querySelectorAll('iframe[src*="vimeo"]').forEach(function(iframe) {
    var player = new Vimeo.Player(iframe);
    var played = false;
    player.on('play', function() {
      if (played) return;
      played = true;
      sendVideoPlay(iframe.title || iframe.id);
    });
  });

  // YouTube
  var ytPlayed = {};
  window.onYouTubeIframeAPIReady = function() {
    document.querySelectorAll('iframe[src*="youtube"]').forEach(function(iframe) {
      new YT.Player(iframe.id, {
        events: {
          onStateChange: function(e) {
            if (e.data === YT.PlayerState.PLAYING && !ytPlayed[iframe.id]) {
              ytPlayed[iframe.id] = true;
              sendVideoPlay(iframe.title || iframe.id);
            }
          }
        }
      });
    });
  };
})();
</script>
</body>
<!-- END : Body-->

</html>