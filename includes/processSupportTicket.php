<?php
session_start();

if (empty($_SESSION['userid'])) {
    header('Location: ../backoffice/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../backoffice/support.php');
    exit();
}

require_once 'conn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$leadId = (int)$_SESSION['userid'];

// Ensure table exists
mysqli_query($link, "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lead_id INT NOT NULL,
    fname VARCHAR(100),
    lname VARCHAR(100),
    email VARCHAR(255),
    phone VARCHAR(50),
    message TEXT,
    attachment VARCHAR(255),
    status ENUM('open','closed') DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Validate required fields
$fname   = trim($_POST['fname'] ?? '');
$lname   = trim($_POST['lname'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$message = trim($_POST['comment'] ?? '');

if (!$fname || !$email || !$message) {
    header('Location: ../backoffice/support.php?error=1&msg=missing_fields');
    exit();
}

// File upload
$attachmentPath = '';
$attachmentFilename = '';
if (!empty($_FILES['attachment']['name'])) {
    $uploadDir = __DIR__ . '/../backoffice/uploads/support/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
    if (in_array($ext, $allowed) && $_FILES['attachment']['size'] < 10 * 1024 * 1024) {
        $uniqueName = 'ticket_' . time() . '_' . $leadId . '.' . $ext;
        $destPath = $uploadDir . $uniqueName;
        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $destPath)) {
            $attachmentPath = 'backoffice/uploads/support/' . $uniqueName;
            $attachmentFilename = $uniqueName;
        }
    }
}

// Insert ticket into DB
$fnameDb   = mysqli_real_escape_string($link, $fname);
$lnameDb   = mysqli_real_escape_string($link, $lname);
$emailDb   = mysqli_real_escape_string($link, $email);
$phoneDb   = mysqli_real_escape_string($link, $phone);
$messageDb = mysqli_real_escape_string($link, $message);
$attachDb  = mysqli_real_escape_string($link, $attachmentPath);

$insertSql = "INSERT INTO support_tickets (lead_id, fname, lname, email, phone, message, attachment)
              VALUES ($leadId, '$fnameDb', '$lnameDb', '$emailDb', '$phoneDb', '$messageDb', '$attachDb')";
mysqli_query($link, $insertSql);
$ticketId = mysqli_insert_id($link);

// SMTP helper
function getSmtpSetting($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key = '$k'"));
    return $r ? $r['setting_value'] : '';
}

// Send e-mail to admin
$adminEmail = getSmtpSetting($link, 'admin_email');
if (!$adminEmail) {
    $adminEmail = getSmtpSetting($link, 'smtp_from_email');
}

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->CharSet  = 'UTF-8';
    $mail->Host     = getSmtpSetting($link, 'smtp_host');
    $mail->SMTPAuth = true;
    $mail->Username = getSmtpSetting($link, 'smtp_user');
    $mail->Password = getSmtpSetting($link, 'smtp_password');
    $mail->Port     = (int) getSmtpSetting($link, 'smtp_port');
    $mail->isHTML(true);

    $fromEmail = getSmtpSetting($link, 'smtp_from_email');
    $fromName  = getSmtpSetting($link, 'smtp_from_name');
    $mail->setFrom($fromEmail ?: 'noreply@simple2success.com', $fromName ?: 'Simple2Success');
    $mail->addAddress($adminEmail);

    $mail->Subject = "Support Ticket #{$ticketId} — Lead #{$leadId}";
    $mail->Body = "
        <h2>Support Ticket #{$ticketId}</h2>
        <table cellpadding='6' style='border-collapse:collapse;font-family:sans-serif;'>
            <tr><td><strong>Lead ID:</strong></td><td>#{$leadId}</td></tr>
            <tr><td><strong>Name:</strong></td><td>" . htmlspecialchars($fname . ' ' . $lname) . "</td></tr>
            <tr><td><strong>E-Mail:</strong></td><td>" . htmlspecialchars($email) . "</td></tr>
            <tr><td><strong>Telefon:</strong></td><td>" . htmlspecialchars($phone) . "</td></tr>
        </table>
        <h3 style='margin-top:16px;'>Nachricht:</h3>
        <p style='background:#f5f5f5;padding:12px;border-radius:4px;'>" . nl2br(htmlspecialchars($message)) . "</p>
        " . ($attachmentFilename ? "<p><em>Anhang: {$attachmentFilename}</em></p>" : "") . "
    ";

    if ($attachmentFilename && file_exists(__DIR__ . '/../' . $attachmentPath)) {
        $mail->addAttachment(__DIR__ . '/../' . $attachmentPath, $attachmentFilename);
    }

    $mail->send();
} catch (Exception $e) {
    // Mail failed but ticket is saved — still redirect as success
}

header('Location: ../backoffice/support.php?success=1');
exit();
