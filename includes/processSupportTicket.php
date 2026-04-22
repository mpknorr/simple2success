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

// Ensure email_templates table exists and seed support_ticket template if missing
mysqli_query($link, "CREATE TABLE IF NOT EXISTS email_templates (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL DEFAULT '',
    template_key VARCHAR(100) NOT NULL UNIQUE,
    subject      VARCHAR(255) NOT NULL DEFAULT '',
    body         LONGTEXT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$defaultSupportSubj = mysqli_real_escape_string($link, 'Support Ticket #{{ticket_id}} — Lead #{{lead_id}}');
$defaultSupportBody = mysqli_real_escape_string($link,
    '<h2 style="font-family:sans-serif;">Support Ticket #{{ticket_id}}</h2>'
    . '<table cellpadding="6" style="border-collapse:collapse;font-family:sans-serif;">'
    . '<tr><td><strong>Lead ID:</strong></td><td>#{{lead_id}}</td></tr>'
    . '<tr><td><strong>Name:</strong></td><td>{{fname}} {{lname}}</td></tr>'
    . '<tr><td><strong>E-Mail:</strong></td><td>{{email}}</td></tr>'
    . '<tr><td><strong>Telefon:</strong></td><td>{{phone}}</td></tr>'
    . '</table>'
    . '<h3 style="margin-top:16px;font-family:sans-serif;">Nachricht:</h3>'
    . '<p style="background:#f5f5f5;padding:12px;border-radius:4px;font-family:sans-serif;">{{message}}</p>'
    . '{{attachment_note}}'
);
mysqli_query($link, "INSERT IGNORE INTO email_templates (name, template_key, subject, body)
    VALUES ('Support Ticket (Admin)', 'support_ticket', '$defaultSupportSubj', '$defaultSupportBody')");

// Load template from DB
$tpl = mysqli_fetch_assoc(mysqli_query($link,
    "SELECT subject, body FROM email_templates WHERE template_key = 'support_ticket' LIMIT 1"));
$tplSubject = $tpl['subject'] ?? "Support Ticket #{$ticketId} — Lead #{$leadId}";
$tplBody    = $tpl['body']    ?? '';

$attachNote = $attachmentFilename ? '<p style="font-family:sans-serif;"><em>Anhang: ' . htmlspecialchars($attachmentFilename) . '</em></p>' : '';

$mailSubject = str_replace(['{{ticket_id}}', '{{lead_id}}'],
                           [$ticketId, $leadId],
                           $tplSubject);
$mailBody = str_replace(
    ['{{ticket_id}}', '{{lead_id}}', '{{fname}}', '{{lname}}', '{{email}}', '{{phone}}', '{{message}}', '{{attachment_note}}'],
    [$ticketId, $leadId, htmlspecialchars($fname), htmlspecialchars($lname),
     htmlspecialchars($email), htmlspecialchars($phone),
     nl2br(htmlspecialchars($message)), $attachNote],
    $tplBody
);

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
    $mail->Subject = $mailSubject;
    $mail->Body    = $mailBody;

    if ($attachmentFilename && file_exists(__DIR__ . '/../' . $attachmentPath)) {
        $mail->addAttachment(__DIR__ . '/../' . $attachmentPath, $attachmentFilename);
    }

    $mail->send();
} catch (Exception $e) {
    // Mail failed but ticket is saved — still redirect as success
}

header('Location: ../backoffice/support.php?success=1');
exit();
