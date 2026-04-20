<?php
require_once "../includes/conn.php";
if(!isset($_SESSION)){
    session_start();
}
$tmpUserID = $_SESSION["userid"];
if(isset($_POST["save_profile_data"])){
    $tmpUserName  = mysqli_real_escape_string($link, $_POST["name"]  ?? '');
    $tmpUserEmail = mysqli_real_escape_string($link, $_POST["email"] ?? '');
    // Language is stored in users.lang (ISO code) — single source of truth
    $tmpLang = mysqli_real_escape_string($link, detectLanguage($_POST["language"] ?? ''));
    mysqli_query($link, "UPDATE users SET name='$tmpUserName', email='$tmpUserEmail', lang='$tmpLang' WHERE leadid=$tmpUserID");

    $tmFbLink      = mysqli_real_escape_string($link, $_POST["fb_profile"]   ?? '');
    $tmpInstaLink  = mysqli_real_escape_string($link, $_POST["insta_profile"] ?? '');
    $tmpTgLink     = mysqli_real_escape_string($link, $_POST["tg_username"]   ?? '');
    $tmpTwitterLink= mysqli_real_escape_string($link, $_POST["twitter_link"]  ?? '');
    $tmpCountry    = mysqli_real_escape_string($link, $_POST["country"]       ?? '');
    $tmpPhone      = mysqli_real_escape_string($link, $_POST["phone"]         ?? '');
    $tmpWhatsapp   = mysqli_real_escape_string($link, $_POST["whatsapp"]      ?? '');
    $tmpChkSocial  = mysqli_query($link, "SELECT id FROM user_socialmedia WHERE user_id=$tmpUserID");
    if(mysqli_num_rows($tmpChkSocial) > 0){
        mysqli_query($link, "UPDATE user_socialmedia SET fb_link='$tmFbLink', insta_link='$tmpInstaLink', tg_link='$tmpTgLink', twitter_link='$tmpTwitterLink', country='$tmpCountry', phone='$tmpPhone', whatsapp='$tmpWhatsapp' WHERE user_id=$tmpUserID");
    }else{
        mysqli_query($link, "INSERT INTO user_socialmedia (user_id, fb_link, insta_link, tg_link, twitter_link, country, phone, whatsapp)
                VALUES ($tmpUserID, '$tmFbLink', '$tmpInstaLink', '$tmpTgLink', '$tmpTwitterLink', '$tmpCountry', '$tmpPhone', '$tmpWhatsapp')");
    }
    header('Location: ' . $_SERVER["HTTP_REFERER"]);
    die();
}
if(isset($_FILES["profile_pic_input"])){
    $profile_pic_input = $_FILES["profile_pic_input"];
    $uploadDir = 'app-assets/img/portrait/small/';
    $uploadFileName = basename($_FILES['profile_pic_input']['name']);
    $uploadFile = $uploadDir . $uploadFileName;
    move_uploaded_file($_FILES['profile_pic_input']['tmp_name'], $uploadFile);
    mysqli_query($link, "UPDATE users SET profile_pic = '$uploadFileName' WHERE users.leadid = $tmpUserID");
    die();
}
if(isset($_POST["reset_profile_pic"])){
    mysqli_query($link, "UPDATE users SET profile_pic = 'user_default.png' WHERE users.leadid = $tmpUserID");
    die();
}
if(isset($_POST["delete_account"]) && $_POST["delete_confirm"] === "delete"){
    mysqli_query($link, "DELETE FROM user_socialmedia WHERE user_id = $tmpUserID");
    mysqli_query($link, "DELETE FROM users WHERE leadid = $tmpUserID");
    session_destroy();
    header('Location: ' . $baseurl . '/backoffice/login.php?deleted=1');
    die();
}
// Language + country_detected from users — single source of truth
$user_row = mysqli_fetch_assoc(mysqli_query($link, "SELECT lang, country_detected FROM users WHERE leadid=$tmpUserID"));
$language_data    = $user_row['lang']             ?? 'en';
$country_detected = $user_row['country_detected'] ?? '';

$user_socials = mysqli_query($link, "SELECT * FROM user_socialmedia WHERE user_id = $tmpUserID");
if($user_socials){
    if(mysqli_num_rows($user_socials) > 0){
        foreach($user_socials as $usr_social){
            $fb_data       = $usr_social["fb_link"];
            $insta_data    = $usr_social["insta_link"];
            $tg_data       = $usr_social["tg_link"];
            $twitter_data  = $usr_social["twitter_link"];
            // Use manual value if set, otherwise fall back to auto-detected country
            $country_data  = !empty($usr_social["country"]) ? $usr_social["country"] : $country_detected;
            $phone_data    = $usr_social["phone"];
            $whatsapp_data = $usr_social["whatsapp"];
        }
    }
}
?>

<?php
require_once "parts/head.php";
?>
<!-- BEGIN : Body-->

<body class="vertical-layout vertical-menu 2-columns  navbar-static layout-dark" data-menu="vertical-menu" data-col="2-columns">

    <?php
    require_once "parts/navbar.php";
    ?>
    <!-- Navbar (Header) Ends-->

    <!-- ////////////////////////////////////////////////////////////////////////////-->
    <div class="wrapper">


        <!-- main menu-->
        <?php require_once "parts/sidebar.php"; ?>
        <!-- / main menu-->



        <div class="main-panel">
            <!-- BEGIN : Main Content-->
            <div class="main-content">
                <div class="content-overlay"></div>
                <div class="content-wrapper">
                    <section class="users-edit">
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-content">
                                        <div class="card-body">
                                            <!-- Nav-tabs -->
                                            <ul class="nav nav-tabs" role="tablist">
                                                <li class="nav-item">
                                                    <a href="#account" role="tab" id="account-tab" class="nav-link d-flex align-items-center active" data-toggle="tab" aria-controls="account" aria-selected="true">
                                                        <i class="ft-user mr-1"></i>
                                                        <span class="d-none d-sm-block">Account</span>
                                                    </a>
                                                </li>
                                             <!--   <li class="nav-item">
                                                    <a href="#information" role="tab" id="information-tab" class="nav-link d-flex align-items-center" data-toggle="tab" aria-controls="information" aria-selected="false">
                                                        <i class="ft-info mr-1"></i>
                                                        <span class="d-none d-sm-block">Information</span>
                                                    </a>
                                                </li> -->
                                            </ul>
                                            <div class="tab-content">
                                                <!-- Account content starts -->
                                                <div class="tab-pane fade mt-2 show active" id="account" role="tabpanel" aria-labelledby="account-tab">
                                                    <!-- Media object starts -->
                                                    <div class="media">
                                                        <img id="user_profile_pic" src="app-assets/img/portrait/small/<?= $profile_pic?>" alt="user edit avatar" class="users-avatar-shadow avatar mr-3 rounded-circle" height="64" width="64">
                                                        <div class="media-body">
                                                            <h4><?= empty($name) ? $useremail : $name?></h4>
                                                            <div class="d-flex flex-sm-row flex-column justify-content-start px-0 mb-sm-2">
                                                                <form id="imageUploadForm" method="POST">
                                                                    <input type="file" name="profile_pic_input" id="profile_pic_input" style="display:none">
                                                                    <label for="profile_pic_input"><div class="btn btn-sm btn-primary mb-1 mb-sm-0">Change</div></label>
                                                                    <div class="btn btn-sm bg-light-secondary ml-sm-2" id="reset_profile_pic">Reset</div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- Media object ends -->

                                                    <!-- Account form starts -->
                                                    <form novalidate method="post">
                                                        <div class="row">
                                                            <div class="col-12 col-md-6">
                                                                <div class="form-group">
                                                                    <div class="controls">
                                                                        <label for="users-edit-username">USER ID</label>
                                                                        <input type="text" id="users-edit-username" class="form-control" placeholder="userid" value="<?= $userid?>" aria-invalid="false" disabled style="background: #464646">
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <div class="controls">
                                                                        <label for="users-edit-name">Name</label>
                                                                        <input type="text" name="name" required id="users-edit-name" class="form-control" placeholder="Name" value="<?= empty($name) ? "-" : $name?>" aria-invalid="false">
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                   
                                                                </div>
                                                            </div>

                                                            <div class="col-12 col-md-6">
                                                                <div class="form-group">
                                                                    <div class="controls">
                                                                       <label for="users-edit-email">E-mail</label>
                                                                        <input type="email" name="email" required id="users-edit-email" class="form-control" placeholder="Email" value="<?= $useremail?>" aria-invalid="false">
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                   
                                                                </div>
                                                            </div>







                                                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                                                <br><br> 
                                                                <h4 class="mb-3"><i class="ft-link mr-2"></i>Social Profiles</h4>
                                                                <div class="form-group">
                                                                    <label for="users-edit-facebook">Facebook Profile link</label>
                                                                    <input name="fb_profile" type="text" id="users-edit-facebook" class="form-control" value="<?= $fb_data?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="users-edit-instagram">Instagram Profile</label>
                                                                    <input name="insta_profile" type="text" id="users-edit-instagram" class="form-control" value="<?= $insta_data?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="users-edit-telegram">Telegram Username</label>
                                                                    <input name="tg_username" type="text" id="users-edit-telegram" class="form-control" value="<?= $tg_data?>">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="users-edit-linkedin">Twitter</label>
                                                                   <input name="twitter_link" type="text" id="users-edit-twitter" class="form-control" value="<?= $twitter_data?>">
                                                                </div>
                                                                
                                                            </div>

                                                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                                                <br><br> 
                                                                <h4 class="mb-3"><i class="ft-user mr-2"></i>Personal Info</h4>
                                                                
                                                                <div class="form-group">
                                                                    <div class="controls">
                                                                        <label for="users-edit-country">Country</label>
                                                                        <?php $countries = ['Afghanistan','Albania','Algeria','Andorra','Angola','Antigua & Deps','Argentina','Armenia','Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Barbados','Belarus','Belgium','Belize','Benin','Bhutan','Bolivia','Bosnia Herzegovina','Botswana','Brazil','Brunei','Bulgaria','Burkina','Burundi','Cambodia','Cameroon','Canada','Cape Verde','Central African Rep','Chad','Chile','China','Colombia','Comoros','Congo','Congo {Democratic Rep}','Costa Rica','Croatia','Cuba','Cyprus','Czech Republic','Denmark','Djibouti','Dominica','Dominican Republic','East Timor','Ecuador','Egypt','El Salvador','Equatorial Guinea','Eritrea','Estonia','Ethiopia','Fiji','Finland','France','Gabon','Gambia','Georgia','Germany','Ghana','Greece','Grenada','Guatemala','Guinea','Guinea-Bissau','Guyana','Haiti','Honduras','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland {Republic}','Israel','Italy','Ivory Coast','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kiribati','Korea North','Korea South','Kosovo','Kuwait','Kyrgyzstan','Laos','Latvia','Lebanon','Lesotho','Liberia','Libya','Liechtenstein','Lithuania','Luxembourg','Macedonia','Madagascar','Malawi','Malaysia','Maldives','Mali','Malta','Marshall Islands','Mauritania','Mauritius','Mexico','Micronesia','Moldova','Monaco','Mongolia','Montenegro','Morocco','Mozambique','Myanmar, {Burma}','Namibia','Nauru','Nepal','Netherlands','New Zealand','Nicaragua','Niger','Nigeria','Norway','Oman','Pakistan','Palau','Panama','Papua New Guinea','Paraguay','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russian Federation','Rwanda','St Kitts & Nevis','St Lucia','Saint Vincent & the Grenadines','Samoa','San Marino','Sao Tome & Principe','Saudi Arabia','Senegal','Serbia','Seychelles','Sierra Leone','Singapore','Slovakia','Slovenia','Solomon Islands','Somalia','South Africa','South Sudan','Spain','Sri Lanka','Sudan','Suriname','Swaziland','Sweden','Switzerland','Syria','Taiwan','Tajikistan','Tanzania','Thailand','Togo','Tonga','Trinidad & Tobago','Tunisia','Turkey','Turkmenistan','Tuvalu','Uganda','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan','Vanuatu','Vatican City','Venezuela','Vietnam','Yemen','Zambia','Zimbabwe']; ?>
                                                                        <select name="country" id="users-edit-country" class="form-control">
                                                                            <option value="">Select Country</option>
                                                                            <?php foreach ($countries as $c): ?><option value="<?= htmlspecialchars($c) ?>"<?= $country_data == $c ? ' selected="selected"' : '' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="users-edit-languages">Language</label>
                                                                    <select name="language" id="users-edit-languages" class="form-control">
                                                                        <?php
                                                                        $langOptions = [
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
                                                                        ];
                                                                        foreach ($langOptions as $code => $label):
                                                                        ?>
                                                                        <option value="<?= $code ?>"<?= $language_data === $code ? ' selected' : '' ?>><?= $label ?></option>
                                                                        <?php endforeach; ?>
                                                                    </select>
                                                                </div>
                                                                <div class="form-group">
                                                                    <div class="controls">
                                                                        <label for="users-edit-phone">Phone</label>
                                                                        <input name="phone" type="tel" id="users-edit-phone" placeholder="e.g. 151 40438186" value="<?= htmlspecialchars($phone_data ?? '') ?>">
                                                                        <small id="phone-format-hint" class="text-muted" style="font-size:11px;"></small>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <div class="controls">
                                                                        <label for="users-edit-whatsapp">WhatsApp</label>
                                                                        <input name="whatsapp" type="tel" id="users-edit-whatsapp" placeholder="e.g. 151 40438186" value="<?= htmlspecialchars($whatsapp_data ?? '') ?>">
                                                                        <small id="whatsapp-format-hint" class="text-muted" style="font-size:11px;"></small>
                                                                    </div>
                                                                </div>
                                                            </div>





                                                            <div class="col-12 d-flex flex-sm-row flex-column justify-content-end mt-3 mt-sm-2">
                                                                <button type="submit" name="save_profile_data" class="btn btn-primary mb-2 mb-sm-0 mr-sm-2">Save Changes</button>
                                                                <button type="reset" class="btn btn-secondary">Cancel</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                    <!-- Account form ends -->

                                                    <!-- Delete Account -->
                                                    <div class="mt-4 pt-3" style="border-top:1px solid #444;">
                                                        <h5 style="color:#dc3545;"><i class="ft-trash-2 mr-1"></i> Danger Zone</h5>
                                                        <p class="text-muted" style="font-size:13px;">Once you delete your account, all your data will be permanently removed. This action cannot be undone.</p>
                                                        <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteAccountModal">
                                                            <i class="ft-trash-2 mr-1"></i> Delete my Profile
                                                        </button>
                                                    </div>

                                                    <!-- Delete Account Modal -->
                                                    <div class="modal fade" id="deleteAccountModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                                            <div class="modal-content" style="background:#1a1a2e;border:1px solid #dc3545;">
                                                                <div class="modal-header" style="border-bottom:1px solid #dc3545;">
                                                                    <h5 class="modal-title" id="deleteModalLabel" style="color:#dc3545;"><i class="ft-alert-triangle mr-1"></i> Delete Account</h5>
                                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span style="color:#ccc;" aria-hidden="true">&times;</span></button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <p style="color:#ccc;">This will permanently delete your account and <strong>all associated data</strong>. This cannot be undone.</p>
                                                                        <p style="color:#ccc;">To confirm, please type <strong style="color:#dc3545;">delete</strong> below:</p>
                                                                        <input type="text" id="deleteConfirmInput" name="delete_confirm" class="form-control" placeholder='Type "delete" to confirm' autocomplete="off">
                                                                        <div id="deleteConfirmError" class="text-danger mt-1" style="display:none;font-size:12px;">Please type exactly "delete" to confirm.</div>
                                                                    </div>
                                                                    <div class="modal-footer" style="border-top:1px solid #444;">
                                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="delete_account" id="deleteConfirmBtn" class="btn btn-danger" disabled>
                                                                            <i class="ft-trash-2 mr-1"></i> Delete my Account permanently
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- Account content ends -->

                                                <!-- Information content starts -->
                                            <!--    <div class="tab-pane fade mt-2" id="information" role="tabpanel" aria-labelledby="information-tab"> -->
                                                    <!-- Information form starts 
                                                    <form novalidate>
                                                        <div class="row">
                                                            <div class="col-12 col-md-6 mb-3 mb-md-0">
                                                                <h4 class="mb-3"><i class="ft-link mr-2"></i>Social Links</h4>
                                                                <div class="form-group">
                                                                    <label for="users-edit-twitter">Twitter</label>
                                                                    <input type="text" id="users-edit-twitter" class="form-control" value="https://www.twitter.com/">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="users-edit-facebook">Facebook</label>
                                                                    <input type="text" id="users-edit-facebook" class="form-control" value="https://www.facebook.com/">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="users-edit-google">Google+</label>
                                                                    <input type="text" id="users-edit-google" class="form-control">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="users-edit-linkedin">LinkedIn</label>
                                                                    <input type="text" id="users-edit-linkedin" class="form-control">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="users-edit-instagram">Instagram</label>
                                                                    <input type="text" id="users-edit-instagram" class="form-control" value="https://www.instagram.com/">
                                                                </div>
                                                            </div>

                                                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                                                <h4 class="mb-3"><i class="ft-user mr-2"></i>Personal Info</h4>
                                                                <div class="form-group">
                                                                    <div class="controls position-relative">
                                                                        <label for="users-edit-bday">Birth date</label>
                                                                        <input type="text" id="users-edit-bday" class="form-control birthdate-picker" required placeholder="Birth date">
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <div class="controls">
                                                                        <label for="users-edit-country">Country</label>
                                                                        <select id="users-edit-country" class="form-control" required>
                                                                            <option value="">Select Country</option>
                                                                            <?php foreach ($countries as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <div class="controls">
                                                                        <label for="users-edit-phone">Phone</label>
                                                                        <input type="text" id="users-edit-phone" class="form-control" placeholder="Phone Number" required value="(+656) 254 2568">
                                                                    </div>
                                                                </div>
                                                                <div class="form-group">
                                                                    <div class="controls">
                                                                        <label for="users-edit-address">Address</label>
                                                                        <input type="text" id="users-edit-address" class="form-control" placeholder="Address" required>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            

                                                            <div class="col-12 d-flex justify-content-end flex-sm-row flex-column mt-3 mt-sm-0">
                                                                <button type="submit" class="btn btn-primary mb-2 mb-sm-0 mr-sm-2">Save Changes</button>
                                                                <button type="reset" class="btn btn-secondary">Cancel</button>
                                                            </div>
                                                        </div>
                                                    </form> -->
                                                    <!-- Information form ends -->
                                                </div>
                                                <!-- Information content ends -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

    <!-- START Notification Sidebar-->
    <!-- END Notification Sidebar-->

    <div class="sidenav-overlay"></div>
    <div class="drag-target"></div>
    <!-- BEGIN VENDOR JS-->
    <script src="app-assets/vendors/js/vendors.min.js"></script>
    <script src="app-assets/vendors/js/switchery.min.js"></script>
    <!-- BEGIN VENDOR JS-->
    <!-- BEGIN PAGE VENDOR JS-->
    <script src="app-assets/vendors/js/select2.full.min.js"></script>
    <script src="app-assets/vendors/js/jqBootstrapValidation.js"></script>
    <script src="app-assets/vendors/js/pickadate/picker.js"></script>
    <script src="app-assets/vendors/js/pickadate/picker.date.js"></script>
    <script src="app-assets/vendors/js/pickadate/picker.time.js"></script>
    <script src="app-assets/vendors/js/pickadate/legacy.js"></script>
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
    <script src="app-assets/js/datetime-picker.js"></script>
    <script src="app-assets/js/select2.js"></script>
    <script src="app-assets/js/form-validation.js"></script>
    <!-- END PAGE LEVEL JS-->
    <!-- BEGIN: Custom CSS-->
    <script src="assets/js/scripts.js"></script>
    <!-- END: Custom CSS-->
    <!-- intl-tel-input -->
    <link rel="stylesheet" href="app-assets/vendors/css/intlTelInput.css">
    <script src="app-assets/vendors/js/intlTelInput.min.js"></script>
    <style>
    /* intl-tel-input — match dark theme .form-control exactly, don't override padding (set by JS) */
    .iti { width: 100% !important; display: block !important; }
    .iti input[type=tel] {
        width: 100% !important;
        background-color: #10163a !important;
        color: #c2c6dc !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        border-radius: 5px !important;
        font-size: 1rem !important;
        height: calc(2.25rem + 2px) !important;
        /* NO padding override — intl-tel-input sets padding-left dynamically via JS */
    }
    .iti input[type=tel]:focus {
        background-color: #10163a !important;
        color: #c2c6dc !important;
        border-color: #cb2ebc !important;
        box-shadow: 0 0 0 0.2rem rgba(203,46,188,0.25) !important;
        outline: 0 !important;
    }
    .iti__flag-container {
        border-right: 1px solid rgba(255,255,255,0.1);
    }
    .iti__selected-flag {
        background-color: #10163a !important;
        border-radius: 5px 0 0 5px;
    }
    .iti__selected-flag:hover,
    .iti__selected-flag:focus {
        background-color: #1a2550 !important;
    }
    .iti__arrow { border-top-color: #c2c6dc; }
    .iti__arrow--up { border-bottom-color: #c2c6dc; }
    .iti__country-list {
        background-color: #10163a !important;
        border: 1px solid rgba(255,255,255,0.15) !important;
        color: #c2c6dc !important;
        max-height: 220px;
        z-index: 9999;
    }
    .iti__country-list .iti__country:hover,
    .iti__country-list .iti__country.iti__highlight {
        background-color: #1a2550 !important;
    }
    .iti__country-list .iti__country-name { color: #c2c6dc; }
    .iti__dial-code { color: #888 !important; }
    .iti__selected-dial-code { color: #c2c6dc !important; margin-left: 4px; font-size: 13px; }
    .iti__divider { border-bottom: 1px solid rgba(255,255,255,0.1) !important; }
    .iti__search-input {
        background-color: #1a2550 !important;
        color: #c2c6dc !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        border-radius: 4px;
        padding: 6px 10px;
        width: 100%;
    }
    .iti__search-input::placeholder { color: #666; }
    </style>
    <!-- CUSTOM JQUERY -->
    <script>
        $('#profile_pic_input').change(function() {
        var tmProfilePic = this.files[0];
        if (tmProfilePic) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $("#user_profile_pic").attr("src", e.target.result);
            };

            // Read the selected file as a data URL
            reader.readAsDataURL(tmProfilePic);
        }
        // 
        var formData = new FormData($('#imageUploadForm')[0]);
                $.ajax({
                    url: window.location.href, // Send the request to the same page URL
                    type: 'POST',
                    processData: false,
                    contentType: false,
                    data: formData,
                    success: function(response) {
                        // Handle the server's response
                        console.log(response);
                    }
                });
        // 
        });
        $("#reset_profile_pic").on("click", function(){
            $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    data: {reset_profile_pic: true},
                    success: function(response) {
                        location.reload();
                    }
            });
        });

        // intl-tel-input setup
        var geoCountry = "de";
        fetch("https://ipapi.co/json/")
            .then(function(res){ return res.json(); })
            .then(function(data){ geoCountry = data.country_code || "de"; initTelInputs(); })
            .catch(function(){ initTelInputs(); });

        function formatHint(iti, hintEl) {
            if (!hintEl) return;
            var data = iti.getSelectedCountryData();
            var dialCode = data.dialCode || "";
            var name = data.name ? data.name.replace(/\s*\(.*\)/, '') : "";
            // Show: enter number WITHOUT leading 0 — e.g. +49 → 151 40438186 (not 0151...)
            if (dialCode) {
                hintEl.innerHTML = '<i class="ft-info" style="font-size:10px;"></i> ' + name + ': Enter without leading 0 — e.g. <strong>+' + dialCode + ' 151 40438186</strong>';
            } else {
                hintEl.textContent = '';
            }
        }

        function initTelInputs() {
            var phoneInput = document.querySelector("#users-edit-phone");
            var whatsappInput = document.querySelector("#users-edit-whatsapp");
            var phoneHint = document.getElementById("phone-format-hint");
            var waHint = document.getElementById("whatsapp-format-hint");

            var itiOpts = {
                utilsScript: "app-assets/vendors/js/intlTelInputUtils.js",
                separateDialCode: true,
                preferredCountries: ["de", "at", "ch", "us", "gb"],
                initialCountry: geoCountry
            };

            if (phoneInput) {
                var itiPhone = window.intlTelInput(phoneInput, itiOpts);
                formatHint(itiPhone, phoneHint);
                phoneInput.addEventListener("countrychange", function() { formatHint(itiPhone, phoneHint); });
                phoneInput.closest("form") && phoneInput.closest("form").addEventListener("submit", function() {
                    if (itiPhone.isValidNumber()) phoneInput.value = itiPhone.getNumber();
                });
            }
            if (whatsappInput) {
                var itiWa = window.intlTelInput(whatsappInput, itiOpts);
                formatHint(itiWa, waHint);
                whatsappInput.addEventListener("countrychange", function() { formatHint(itiWa, waHint); });
                whatsappInput.closest("form") && whatsappInput.closest("form").addEventListener("submit", function() {
                    if (itiWa.isValidNumber()) whatsappInput.value = itiWa.getNumber();
                });
            }
        }

        // Delete Account Modal — enable button only when "delete" is typed
        document.getElementById("deleteConfirmInput") && document.getElementById("deleteConfirmInput").addEventListener("input", function() {
            var val = this.value.trim();
            var btn = document.getElementById("deleteConfirmBtn");
            var err = document.getElementById("deleteConfirmError");
            if (val === "delete") {
                btn.disabled = false;
                err.style.display = "none";
            } else {
                btn.disabled = true;
                if (val.length > 0) err.style.display = "block";
                else err.style.display = "none";
            }
        });
    </script>
    <!-- CUSTOM JQUERY -->
</body>
<!-- END : Body-->

</html>