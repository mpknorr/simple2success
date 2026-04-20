<?php
// require_once "../routeprocess.php";
// Start the PHP session
if(!isset($_SESSION)){
    session_start();
}

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

$getuserdetails = mysqli_query($link, "SELECT * FROM users WHERE leadid = $userid");
foreach($getuserdetails as $userData){
    $name = $userData["name"];
    $username = $userData["username"];
    $useremail = $userData["email"];
    $paidstatus = $userData["paidstatus"];
    $profile_pic = $userData["profile_pic"];
}

// Now you can use the $userid variable in your code
// ...
?>
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
    <title>Dashboard</title>
    
    <link rel="apple-touch-icon" sizes="180x180" href="app-assets/img/ico/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="app-assets/img/ico/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="app-assets/img/ico/favicon-16x16.png">
    <link rel="manifest" href="app-assets/img/ico/site.webmanifest">
    <link rel="mask-icon" href="app-assets/img/ico/safari-pinned-tab.svg" color="#5bbad5">
    <link rel="shortcut icon" href="app-assets/img/ico/favicon.ico">
    <meta name="msapplication-TileColor" content="#9f00a7">
    <meta name="msapplication-config" content="app-assets/img/ico/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">

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
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/chartist.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/katex.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/monokai-sublime.min.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/quill.snow.css">
    <link rel="stylesheet" type="text/css" href="app-assets/vendors/css/quill.bubble.css">
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
    <link rel="stylesheet" type="text/css" href="app-assets/css/pages/dashboard1.css">
    <!-- END Page Level CSS-->
    <!-- BEGIN: Custom CSS-->
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <!-- END: Custom CSS-->
</head>