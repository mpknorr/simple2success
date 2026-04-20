<!DOCTYPE html>
<html class="loading" lang="en">
<!-- BEGIN : Head-->

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="description" content="Unlock your path to success with Simple2Success, where ambitious entrepreneurs like you spread their wings and soar. Join a community dedicated to personal and financial growth, guided by the wisdom of the eagle. Our platform is your gateway to unlimited opportunities, tailored to help you achieve your dreams in the dynamic world of online business">
    <meta name="keywords" content="Simple2Success, online business success, entrepreneurial community, eagle wisdom, financial growth, personal development">
    <meta name="author" content="SIMPLE2SUCCESS">
    <title>Login Page</title>
    <link rel="shortcut icon" type="image/x-icon" href="app-assets/img/ico/favicon.ico">
    <link rel="shortcut icon" type="image/png" href="app-assets/img/ico/favicon-32.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link href="app-assets/css/fonts/font-style.css" rel="stylesheet">
    <!-- BEGIN VENDOR CSS-->
    <!-- font icons-->
    <link rel="stylesheet" type="text/css" href="app-assets/fonts/feather/style.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/fonts/simple-line-icons/style.css">
    <link rel="stylesheet" type="text/css" href="app-assets/fonts/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/perfect-scrollbar.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/prism.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/switchery.min.css">
    <!-- END VENDOR CSS-->
    <!-- BEGIN APEX CSS-->
    <link rel="stylesheet" type="text/css" href="app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/components.css">
    <link rel="stylesheet" type="text/css" href="app-assets/css/themes/layout-dark.css">
    <link rel="stylesheet" href="app-assets/css/plugins/switchery.css">
    <!-- END APEX CSS-->
    <!-- BEGIN Page Level CSS-->
    <link rel="stylesheet" href="app-assets/css/pages/authentication.css">
    <!-- END Page Level CSS-->
    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <!-- END: Custom CSS-->


   <script>
        function displayEmailNotFoundError() {
            var emailField = document.getElementById("email");
            emailField.setCustomValidity("Email not found in the database.");
        }

        function clearEmailFieldError() {
            var emailField = document.getElementById("email");
            emailField.setCustomValidity("");
        }

        function validateForm() {
            var emailField = document.getElementById("email");
            var emailValue = emailField.value.trim();

            if (emailValue === "") {
                alert("Please enter your email address.");
                emailField.focus();
                return false;
            }

            // Hier können Sie weitere Validierungen durchführen, z. B. für das Passwort.

            return true; // Das Formular wird nur abgesendet, wenn die Validierung erfolgreich ist.
        }
    </script>

</head>
<!-- END : Head-->



<!-- BEGIN : Body-->

<body class="vertical-layout vertical-menu 1-column auth-page navbar-static layout-dark blank-page" data-menu="vertical-menu" data-col="1-column">
    <!-- ////////////////////////////////////////////////////////////////////////////-->
    <div class="wrapper">
        <div class="main-panel">


            <!-- BEGIN : Main Content-->
            <div class="main-content">
                <div class="content-overlay"></div>
                <div class="content-wrapper">
                    <!--Login Page Starts-->
                    <section id="login" class="auth-height">
                        <div class="row full-height-vh m-0">
                            <div class="col-12 d-flex align-items-center justify-content-center">
                                <div class="card overflow-hidden">
                                    <div class="card-content">
                                        <div class="card-body auth-img">
                                            <div class="row m-0">
                                                <div class="col-lg-6 d-none d-lg-flex justify-content-center align-items-center auth-img-bg p-3">
                                                    <img src="app-assets/img/gallery/login.png" alt="" class="img-fluid" width="300" height="230">
                                                </div>
                                                <div class="col-lg-6 col-12 px-4 py-3">
                                                    <h2 class="mb-2 card-title">Dashboard Login</h2>
                                                    <p>Please enter your e-mail address</p>
                                                    <form id="login-form" action="login-validation.php" method="post">
                                                        <input type="email" id="email" name="email" class="form-control mb-3" placeholder="E-mail" <?php if(isset($_GET['error']) && $_GET['error'] == 'email_not_found') echo 'placeholder="Email not found in the database"'; ?>>

                                                        <input type="password" id="password" name="password" class="form-control mb-2" placeholder="Password">
                                                        <div class="d-sm-flex justify-content-between mb-3 font-small-2">
                                                            <div class="remember-me mb-2 mb-sm-0">
                                                                <div class="checkbox auth-checkbox">
                                                                    <input type="checkbox" id="auth-login">
                                                                    <label for="auth-ligin"><span>Remember Me</span></label>
                                                                </div>
                                                            </div>
                                                            <a href="forgot-password.php">Forgot Password?</a>
                                                        </div>
                                                        <div class="d-flex justify-content-between flex-sm-row flex-column">
                                                            <button type="submit" class="btn btn-primary">Login</button>
                                                        </div>
                                                    </form>
                                                    <?php
                                                    // Überprüfen Sie, ob ein Fehler-GET-Parameter vorhanden ist
                                                    if(isset($_GET['error'])) {
                                                        // Zeigen Sie die entsprechende Fehlermeldung basierend auf dem GET-Parameter an
                                                        if($_GET['error'] == 'email_not_found') {
                                                            echo '<div class="alert alert-danger mt-2">Email not found in the database.</div>';
                                                        } elseif($_GET['error'] == 'password_incorrect') {
                                                            echo '<div class="alert alert-danger mt-2">Incorrect password.</div>';
                                                        } elseif($_GET['error'] == 'database_error') {
                                                            echo '<div class="alert alert-danger mt-2">Database query error.</div>';
                                                        }
                                                    }
                                                    if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
                                                        echo '<div class="alert alert-success mt-2">Password reset successful. Please log in with your new password.</div>';
                                                    }
                                                    ?>
                                                </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!--Login Page Ends-->
                </div>
            </div>
            <!-- END : End Main Content-->


        </div>
    </div>
    <!-- ////////////////////////////////////////////////////////////////////////////-->

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
    <!-- END PAGE LEVEL JS-->
    <!-- BEGIN: Custom CSS-->
    <script src="assets/js/scripts.js"></script>
    <!-- END: Custom CSS-->
</body>
<!-- END : Body-->

</html>