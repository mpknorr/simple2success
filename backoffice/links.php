<?php
if (session_status() === PHP_SESSION_NONE) session_start();

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
<!-- BEGIN : Head-->

<?php
require_once "parts/head.php";
?>

<!-- BEGIN : Body-->

<style>
/* Link URL input — darker text, icon flush to the right of the box */
.s2s-link-group {
  display: flex;
  align-items: stretch;
  width: 100%;
  margin-bottom: .5rem;
}
.s2s-link-group .form-control {
  flex: 1;
  border-radius: .25em 0 0 .25em !important;
  color: #222 !important;
  background: #ffffff !important;
  font-size: var(--s2s-size-body) !important;
  border: 1px solid #ced4da !important;
  border-right: none !important;
  min-width: 0;
  -webkit-text-fill-color: #222 !important;
}
.s2s-link-group .s2s-link-open {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 .75rem;
  background: #f0f0f0;
  border: 1px solid #ced4da;
  border-left: none;
  border-radius: 0 .25em .25em 0;
  color: #555;
  text-decoration: none;
  transition: background .15s, color .15s;
  flex-shrink: 0;
}
.s2s-link-group .s2s-link-open:hover {
  background: #b700e0;
  color: #fff;
  border-color: #b700e0;
}
</style>
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
   <!-- COntent wrapper  -->
   <div class="content-wrapper">
      <!--Basic cards Starts-->
      <div class="col-lg-12">
         <div class="card">
            <?php
            if(empty($username)){
                echo "<p>You should complete STEP 1 & STEP 2 from <a href='" . $baseurl . "/backoffice/start.php'>here</a>.</p>";
            }else{
            ?>
            <!--  -->
            <div class="card-header">
               <h4 class="card-title m-0 p-0">Your Links</h4>
            </div>
            <div class="card-content">
               <div class="card-body">
                  <div class="row">
                     <div class="col-lg-12">
                        <h2 align="text-left">Premium Pages</h2>
                        <br>
                        <!--- Premium Pages Cards /////////////////////////////////////////// -->
                        <!-- Content types section start -->
                        <?php
                        $shareurlp1 = $baseurl . '/go/' . $userid . '/linkp1/';
                        $shareurlp3 = $baseurl . '/go/' . $userid . '/linkp3/';
                        $shareurlp4 = $baseurl . '/go/' . $userid . '/linkp4/';
                        ?>
                        <section id="content-types-premium">
                           <div class="row match-height">

                              <div class="col-lg-4 col-md-6 col-sm-12">
                                 <div class="card">
                                    <div class="card-content">
                                       <img class="card-img-top img-fluid" src="<?= $baseurl ?>/linkp1/images/linkp1preview.jpg.jpg" style="width:100%;object-fit:cover;object-position:top;max-height:300px;cursor:pointer;" onclick="window.open('<?= $shareurlp1 ?>','_blank');" title="Klicken für Vorschau">
                                       <div class="card-body" style="background-color: white; min-height: 280px;">
                                          <h4 class="card-title" style="color: black;">Premium Page 1</h4>
                                          <p class="card-text" style="color: black;">Your Link:</p>
                                          <div class="s2s-link-group">
                                            <input type="text" class="form-control" placeholder="" value="<?= $shareurlp1 ?>" readonly>
                                            <a href="<?= $shareurlp1 ?>" target="_blank" class="s2s-link-open" title="Link öffnen"><i class="fa fa-external-link" aria-hidden="true"></i></a>
                                          </div>
                                          <p class="card-text" style="color: black;">
                                             You can track also source traffic in the link, such as: <?= $shareurlp1 ?>?source=XYZ
                                          </p>
                                          <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                             <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareurlp1) ?>" target="_blank" class="btn btn-sm" style="background:#1877F2;color:white;"><i class="fa fa-facebook"></i> Facebook</a>
                                             <a href="https://wa.me/?text=<?= urlencode('Schau dir das an: ' . $shareurlp1) ?>" target="_blank" class="btn btn-sm" style="background:#25D366;color:white;"><i class="fa fa-whatsapp"></i> WhatsApp</a>
                                             <a href="https://t.me/share/url?url=<?= urlencode($shareurlp1) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#0088cc;color:white;"><i class="fa fa-telegram"></i> Telegram</a>
                                             <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareurlp1) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#000;color:white;"><i class="fa fa-twitter"></i> X</a>
                                             <a href="mailto:?subject=Simple2Success&body=<?= urlencode('Schau dir das an: ' . $shareurlp1) ?>" class="btn btn-sm" style="background:#555;color:white;"><i class="fa fa-envelope"></i> E-Mail</a>
                                             <span style="display:block;width:100%;margin-top:8px;"></span>
                                             <button onclick="copyLink('<?= $shareurlp1 ?>', this)" class="btn btn-lg s2s-btn-brand"><i class="fa fa-copy"></i> Link kopieren</button>
                                             <button onclick="window.open('<?= $shareurlp1 ?>','_blank')" class="btn btn-lg s2s-btn-neutral"><i class="fa fa-eye"></i> Preview</button>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>

                              <div class="col-lg-4 col-md-6 col-sm-12">
                                 <div class="card">
                                    <div class="card-content">
                                       <img class="card-img-top img-fluid" src="<?= $baseurl ?>/linkp3/images/linkp3preview.jpg.jpg" style="width:100%;object-fit:cover;object-position:top;max-height:300px;cursor:pointer;" onclick="window.open('<?= $shareurlp4 ?>','_blank');" title="Klicken für Vorschau">
                                       <div class="card-body" style="background-color: white; min-height: 280px;">
                                          <h4 class="card-title" style="color: black;">Premium Page 4</h4>
                                          <p class="card-text" style="color: black;">Your Link:</p>
                                          <div class="s2s-link-group">
                                            <input type="text" class="form-control" placeholder="" value="<?= $shareurlp4 ?>" readonly>
                                            <a href="<?= $shareurlp4 ?>" target="_blank" class="s2s-link-open" title="Link öffnen"><i class="fa fa-external-link" aria-hidden="true"></i></a>
                                          </div>
                                          <p class="card-text" style="color: black;">
                                             You can track also source traffic in the link, such as: <?= $shareurlp4 ?>?source=XYZ
                                          </p>
                                          <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                             <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareurlp4) ?>" target="_blank" class="btn btn-sm" style="background:#1877F2;color:white;"><i class="fa fa-facebook"></i> Facebook</a>
                                             <a href="https://wa.me/?text=<?= urlencode('Schau dir das an: ' . $shareurlp4) ?>" target="_blank" class="btn btn-sm" style="background:#25D366;color:white;"><i class="fa fa-whatsapp"></i> WhatsApp</a>
                                             <a href="https://t.me/share/url?url=<?= urlencode($shareurlp4) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#0088cc;color:white;"><i class="fa fa-telegram"></i> Telegram</a>
                                             <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareurlp4) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#000;color:white;"><i class="fa fa-twitter"></i> X</a>
                                             <a href="mailto:?subject=Simple2Success&body=<?= urlencode('Schau dir das an: ' . $shareurlp4) ?>" class="btn btn-sm" style="background:#555;color:white;"><i class="fa fa-envelope"></i> E-Mail</a>
                                             <span style="display:block;width:100%;margin-top:8px;"></span>
                                             <button onclick="copyLink('<?= $shareurlp4 ?>', this)" class="btn btn-lg s2s-btn-brand"><i class="fa fa-copy"></i> Link kopieren</button>
                                             <button onclick="window.open('<?= $shareurlp4 ?>','_blank')" class="btn btn-lg s2s-btn-neutral"><i class="fa fa-eye"></i> Preview</button>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>

                              <div class="col-lg-4 col-md-6 col-sm-12">
                                 <div class="card">
                                    <div class="card-content">
                                       <img class="card-img-top img-fluid" src="<?= $baseurl ?>/linkp3/images/linkp3preview.jpg.jpg" style="width:100%;object-fit:cover;object-position:top;max-height:300px;cursor:pointer;" onclick="window.open('<?= $shareurlp3 ?>','_blank');" title="Klicken für Vorschau">
                                       <div class="card-body" style="background-color: white; min-height: 280px;">
                                          <h4 class="card-title" style="color: black;">Premium Page 3</h4>
                                          <p class="card-text" style="color: black;">Your Link:</p>
                                          <div class="s2s-link-group">
                                            <input type="text" class="form-control" placeholder="" value="<?= $shareurlp3 ?>" readonly>
                                            <a href="<?= $shareurlp3 ?>" target="_blank" class="s2s-link-open" title="Link öffnen"><i class="fa fa-external-link" aria-hidden="true"></i></a>
                                          </div>
                                          <p class="card-text" style="color: black;">
                                             You can track also source traffic in the link, such as: <?= $shareurlp3 ?>?source=XYZ
                                          </p>
                                          <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                             <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareurlp3) ?>" target="_blank" class="btn btn-sm" style="background:#1877F2;color:white;"><i class="fa fa-facebook"></i> Facebook</a>
                                             <a href="https://wa.me/?text=<?= urlencode('Schau dir das an: ' . $shareurlp3) ?>" target="_blank" class="btn btn-sm" style="background:#25D366;color:white;"><i class="fa fa-whatsapp"></i> WhatsApp</a>
                                             <a href="https://t.me/share/url?url=<?= urlencode($shareurlp3) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#0088cc;color:white;"><i class="fa fa-telegram"></i> Telegram</a>
                                             <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareurlp3) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#000;color:white;"><i class="fa fa-twitter"></i> X</a>
                                             <a href="mailto:?subject=Simple2Success&body=<?= urlencode('Schau dir das an: ' . $shareurlp3) ?>" class="btn btn-sm" style="background:#555;color:white;"><i class="fa fa-envelope"></i> E-Mail</a>
                                             <span style="display:block;width:100%;margin-top:8px;"></span>
                                             <button onclick="copyLink('<?= $shareurlp3 ?>', this)" class="btn btn-lg s2s-btn-brand"><i class="fa fa-copy"></i> Link kopieren</button>
                                             <button onclick="window.open('<?= $shareurlp3 ?>','_blank')" class="btn btn-lg s2s-btn-neutral"><i class="fa fa-eye"></i> Preview</button>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                              </div>

                           </div>
                        </section>
                        <!-- Content types section end -->
                        <br><br>
                        <h2 align="text-left">Capture Pages</h2><br>
                        <section id="content-types">
                           <div class="row match-height">
                              
                               <div class="col-lg-4 col-md-6 col-sm-12">
                                   <div class="card">
                                       <div class="card-content">
                                           <img class="card-img-top img-fluid" src="<?= $baseurl ?>/link1/eagle1bpreview.jpg" style="width:100%;object-fit:cover;object-position:top;max-height:300px;cursor:pointer;" onclick="window.open('<?= $baseurl ?>/go/<?= $userid ?>/link1/','_blank');" title="Klicken für Vorschau">
                                           <div class="card-body" style="background-color: white; min-height: 280px;">
                                               <h4 class="card-title" style="color: black;">Capture Page 1</h4>
                                               <p class="card-text" style="color: black;">Your Link:</p>
                                               <div class="s2s-link-group">
                                                 <input type="text" class="form-control" placeholder="" value="<?php echo $baseurl; ?>/go/<?= $userid?>/link1/" readonly>
                                                 <a href="<?php echo $baseurl; ?>/go/<?= $userid?>/link1/" target="_blank" class="s2s-link-open" title="Link öffnen"><i class="fa fa-external-link" aria-hidden="true"></i></a>
                                               </div>
                           
                                               <?php $shareurl1 = $baseurl . '/go/' . $userid . '/link1/'; ?>
                                               <p class="card-text" style="color: black;">
                                                   You can track also source traffic in the link, such as: <?= $shareurl1 ?>?source=XYZ
                                               </p>
                                               <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                                   <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareurl1) ?>" target="_blank" class="btn btn-sm" style="background:#1877F2;color:white;"><i class="fa fa-facebook"></i> Facebook</a>
                                                   <a href="https://wa.me/?text=<?= urlencode('Schau dir das an: ' . $shareurl1) ?>" target="_blank" class="btn btn-sm" style="background:#25D366;color:white;"><i class="fa fa-whatsapp"></i> WhatsApp</a>
                                                   <a href="https://t.me/share/url?url=<?= urlencode($shareurl1) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#0088cc;color:white;"><i class="fa fa-telegram"></i> Telegram</a>
                                                   <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareurl1) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#000;color:white;"><i class="fa fa-twitter"></i> X</a>
                                                   <a href="mailto:?subject=Simple2Success&body=<?= urlencode('Schau dir das an: ' . $shareurl1) ?>" class="btn btn-sm" style="background:#555;color:white;"><i class="fa fa-envelope"></i> E-Mail</a>
                                                   <span style="display:block;width:100%;margin-top:8px;"></span>
                                                   <button onclick="copyLink('<?= $shareurl1 ?>', this)" class="btn btn-lg s2s-btn-brand"><i class="fa fa-copy"></i> Link kopieren</button>
                                                   <button onclick="window.open('<?= $shareurl1 ?>','_blank')" class="btn btn-lg s2s-btn-neutral"><i class="fa fa-eye"></i> Preview</button>
                                               </div>
                           
                                           </div>
                                       </div>
                                   </div>
                               </div>
                           
                               <div class="col-lg-4 col-md-6 col-sm-12">
                                   <div class="card">
                                       <div class="card-content">
                                           <img class="card-img-top img-fluid" src="<?= $baseurl ?>/link2/eagle3preview.jpg" style="width:100%;object-fit:cover;object-position:top;max-height:300px;cursor:pointer;" onclick="window.open('<?= $baseurl ?>/go/<?= $userid ?>/link2/','_blank');" title="Klicken für Vorschau">
                                           <div class="card-body" style="background-color: white; min-height: 280px;">
                                               <h4 class="card-title" style="color: black;">Capture Page 2</h4>
                                               <p class="card-text" style="color: black;">Your Link:</p>
                           
                           
                                               <div class="s2s-link-group">
                                                 <input type="text" class="form-control" placeholder="" value="<?php echo $baseurl; ?>/go/<?= $userid?>/link2/" readonly>
                                                 <a href="<?php echo $baseurl; ?>/go/<?= $userid?>/link2/" target="_blank" class="s2s-link-open" title="Link öffnen"><i class="fa fa-external-link" aria-hidden="true"></i></a>
                                               </div>
                           
                                               <?php $shareurl2 = $baseurl . '/go/' . $userid . '/link2/'; ?>
                                               <p class="card-text" style="color: black;">
                                                   You can track also source traffic in the link, such as: <?= $shareurl2 ?>?source=XYZ
                                               </p>
                                               <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                                   <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareurl2) ?>" target="_blank" class="btn btn-sm" style="background:#1877F2;color:white;"><i class="fa fa-facebook"></i> Facebook</a>
                                                   <a href="https://wa.me/?text=<?= urlencode('Schau dir das an: ' . $shareurl2) ?>" target="_blank" class="btn btn-sm" style="background:#25D366;color:white;"><i class="fa fa-whatsapp"></i> WhatsApp</a>
                                                   <a href="https://t.me/share/url?url=<?= urlencode($shareurl2) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#0088cc;color:white;"><i class="fa fa-telegram"></i> Telegram</a>
                                                   <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareurl2) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#000;color:white;"><i class="fa fa-twitter"></i> X</a>
                                                   <a href="mailto:?subject=Simple2Success&body=<?= urlencode('Schau dir das an: ' . $shareurl2) ?>" class="btn btn-sm" style="background:#555;color:white;"><i class="fa fa-envelope"></i> E-Mail</a>
                                                   <span style="display:block;width:100%;margin-top:8px;"></span>
                                                   <button onclick="copyLink('<?= $shareurl2 ?>', this)" class="btn btn-lg s2s-btn-brand"><i class="fa fa-copy"></i> Link kopieren</button>
                                                   <button onclick="window.open('<?= $shareurl2 ?>','_blank')" class="btn btn-lg s2s-btn-neutral"><i class="fa fa-eye"></i> Preview</button>
                                               </div>
                           
                                           </div>
                                       </div>
                                   </div>
                               </div>
                           
                           
                               <div class="col-lg-4 col-md-6 col-sm-12">
                                   <div class="card">
                                       <div class="card-content">
                                           <img class="card-img-top img-fluid" src="<?= $baseurl ?>/link3/eagle1bpreview.jpg" style="width:100%;object-fit:cover;object-position:top;max-height:300px;cursor:pointer;" onclick="window.open('<?= $baseurl ?>/go/<?= $userid ?>/link3/','_blank');" title="Klicken für Vorschau">
                                           <div class="card-body" style="background-color: white; min-height: 280px;">
                                               <h4 class="card-title" style="color: black;">Capture Page 3</h4>
                                               <p class="card-text" style="color: black;">Your Link:</p>
                           
                           
                                               <div class="s2s-link-group">
                                                 <input type="text" class="form-control" placeholder="" value="<?php echo $baseurl; ?>/go/<?= $userid?>/link3/" readonly>
                                                 <a href="<?php echo $baseurl; ?>/go/<?= $userid?>/link3/" target="_blank" class="s2s-link-open" title="Link öffnen"><i class="fa fa-external-link" aria-hidden="true"></i></a>
                                               </div>
                           
                                               <?php $shareurl3 = $baseurl . '/go/' . $userid . '/link3/'; ?>
                                               <p class="card-text" style="color: black;">
                                                   You can track also source traffic in the link, such as: <?= $shareurl3 ?>?source=XYZ
                                               </p>
                                               <div class="mt-2" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                                   <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareurl3) ?>" target="_blank" class="btn btn-sm" style="background:#1877F2;color:white;"><i class="fa fa-facebook"></i> Facebook</a>
                                                   <a href="https://wa.me/?text=<?= urlencode('Schau dir das an: ' . $shareurl3) ?>" target="_blank" class="btn btn-sm" style="background:#25D366;color:white;"><i class="fa fa-whatsapp"></i> WhatsApp</a>
                                                   <a href="https://t.me/share/url?url=<?= urlencode($shareurl3) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#0088cc;color:white;"><i class="fa fa-telegram"></i> Telegram</a>
                                                   <a href="https://twitter.com/intent/tweet?url=<?= urlencode($shareurl3) ?>&text=<?= urlencode('Schau dir das an!') ?>" target="_blank" class="btn btn-sm" style="background:#000;color:white;"><i class="fa fa-twitter"></i> X</a>
                                                   <a href="mailto:?subject=Simple2Success&body=<?= urlencode('Schau dir das an: ' . $shareurl3) ?>" class="btn btn-sm" style="background:#555;color:white;"><i class="fa fa-envelope"></i> E-Mail</a>
                                                   <span style="display:block;width:100%;margin-top:8px;"></span>
                                                   <button onclick="copyLink('<?= $shareurl3 ?>', this)" class="btn btn-lg s2s-btn-brand"><i class="fa fa-copy"></i> Link kopieren</button>
                                                   <button onclick="window.open('<?= $shareurl3 ?>','_blank')" class="btn btn-lg s2s-btn-neutral"><i class="fa fa-eye"></i> Preview</button>
                                               </div>
                           
                                           </div>
                                       </div>
                                   </div>
                               </div>
                           
                           
                           </div>
                           
                           </section>
                     </div>
                  </div>
               </div>
            </div>
            <!--  -->
            <?php }?>
         </div>
         <!--- End Neue Cards ////////////////////////////////////////-->
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


    <!-- Toast Notification -->
    <div id="copyToast" style="display:none;position:fixed;bottom:30px;right:30px;background:#333;color:white;padding:12px 20px;border-radius:8px;z-index:9999;font-size:var(--s2s-size-body);">
        <i class="fa fa-check" style="color:#25D366;margin-right:8px;"></i> Link kopiert!
        <br><small style="color:#aaa;">Auf Instagram: Link in Bio oder Story einfügen</small>
    </div>

    <script>
    function copyLink(url, btn) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                showCopyToast();
            });
        } else {
            var el = document.createElement('textarea');
            el.value = url;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            showCopyToast();
        }
    }
    function showCopyToast() {
        var toast = document.getElementById('copyToast');
        toast.style.display = 'block';
        setTimeout(function(){ toast.style.display = 'none'; }, 3000);
    }
    </script>

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