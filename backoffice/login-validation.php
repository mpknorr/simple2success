<?php
require_once '../includes/conn.php';
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["email"])) {
    $userEmail    = mysqli_real_escape_string($link, $_POST["email"]);
    $userPassword = $_POST["password"] ?? '';
    $loginIp      = mysqli_real_escape_string($link, getClientIp());

    $sql    = "SELECT * FROM users WHERE email = '$userEmail'";
    $result = mysqli_query($link, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        $storedHash = $user["password"] ?? '';

        // Passwort prüfen — Rückwärtskompatibel: leeres Passwort in DB = Alt-Account, Login ohne PW erlaubt
        $passwordOk = ($storedHash === '') || password_verify($userPassword, $storedHash);

        if ($passwordOk) {
            session_start();
            $_SESSION["userid"]   = $user["leadid"];
            $_SESSION["is_admin"] = !empty($user["is_admin"]);
            // ── Log successful login ─────────────────────────────────────
            $leadId = (int)$user["leadid"];
            mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, ip)
                VALUES ($leadId, 'login', '$loginIp')");
            header("Location: index.php");
            exit();
        } else {
            // ── Log failed login attempt ─────────────────────────────────
            $leadId = (int)$user["leadid"];
            mysqli_query($link, "INSERT INTO lead_events (lead_id, event_type, ip, meta)
                VALUES ($leadId, 'login_failed', '$loginIp', 'wrong password')");
            header("Location: login.php?error=password_incorrect");
            exit();
        }
    } else {
        header("Location: login.php?error=email_not_found");
        exit();
    }
}

mysqli_close($link);
?>
