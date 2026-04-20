<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';


// Function to send mail
function sendNewMail($to, $subject, $msg){
    $mail = new PHPMailer(true);
    try {
    //Server settings
    //$mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
    $mail->isSMTP();                                            //Send using SMTP
    $mail->CharSet = 'UTF-8';
    $mail->Host       = 'smtp-relay.brevo.com';                     //Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
    $mail->Username   = 'mp.knorr1@gmail.com';                     //SMTP username
    $mail->Password   = '7RmCKzX3hJcMn8vU';                               //SMTP password
    // $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
    $mail->Port       = 587;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

    //Recipients
    $mail->setFrom("info@power-together.team");
    $mail->addAddress($to);     //Add a recipient
    //Content
    //$mail->isHTML(true);                                  //Set email format to HTML
    $mail->Subject = $subject;
    $mail->Body    = $msg;

    $mail->send();
    // return 'Message has been sent';
    } catch (Exception $e) {
        // return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
// Function to send mail

// 
 // Function to generate mail template
function getMsg($user_email){
    return '<img src="https://www.simple2success.com/backoffice/app-assets/img/banner/newleademailheader.jpg" style="max-width:100%"><h1>CONGRATULATIONS! You Have a New Personal Customer!</h1>
    <p><strong>Hey there,</strong><br>
    <em>"Success is not something that just happens." - George Halas</em><br>
    We have great news! <strong>Dedication and support have paid off </strong> - a <strong>new personal customer</strong> has been added!<br>
    Here is the info for the new customer:</p>
   '. $user_email .'
    <br><p>This is proof of excellent work in attracting leads and bringing them to completion. This success belongs to all of us.<br>
    <strong>But now, it\'s about staying committed and continuing</strong>. Remember that the path to success consists of multiple steps. <strong>It\'s time to repeat Step 3</strong> to achieve even more success.<br>
    <strong> Don\'t forget! </strong> <br>To ensure commissions in the future, it\'s crucial to <strong>activate RPS (ROOT Prime Subscription)</strong>. ROOT Prime Subscription (RPS) is a loyalty membership program designed to provide special rewards and services to customers and ambassadors who receive their selected products automatically shipped every 30 days. ROOT Prime membership is activated upon the first ROOT Prime Subscription (RPS) order.
    We are confident that with determination and commitment, even greater success is ahead. If you need more information or assistance, please feel free to reach out.<br>
    Congratulations once again on this fantastic achievement!
    Your Eagle Team</p>';
}



function sendMailtoUser($link, $root){
    $getUserReferrerEmail = mysqli_query($link, "SELECT u.email AS user_email, r.email AS referer_email FROM users u LEFT JOIN users r ON u.referer = r.leadid WHERE u.username = '$root'");
    foreach($getUserReferrerEmail as $user){
        $user_email = $user["user_email"];
        $referer_email = $user["referer_email"];
    }
    $msg = getMsg($user_email);
    sendNewMail($referer_email, "CONGRATULATION You have a New Personal Customer!", $msg);
}