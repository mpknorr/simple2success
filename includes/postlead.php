<?php
include "conn.php";

// getClientIp() is defined in conn.php (included above)
$userIP = getClientIp();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = mysqli_real_escape_string($link, $_POST["email"] ?? '');
    $name     = mysqli_real_escape_string($link, $_POST["name"] ?? '');
    $referer  = mysqli_real_escape_string($link, $_POST["referer"] ?? '');
    $source       = mysqli_real_escape_string($link, $_POST["source"]       ?? '');
    $a            = mysqli_real_escape_string($link, $_POST["a"]            ?? '');
    $tr           = mysqli_real_escape_string($link, $_POST["tr"]           ?? '');
    $page         = mysqli_real_escape_string($link, $_POST["page"]         ?? '');
    $utm_source   = mysqli_real_escape_string($link, $_POST["utm_source"]   ?? $_GET["utm_source"]   ?? '');
    $utm_medium   = mysqli_real_escape_string($link, $_POST["utm_medium"]   ?? $_GET["utm_medium"]   ?? '');
    $utm_campaign = mysqli_real_escape_string($link, $_POST["utm_campaign"] ?? $_GET["utm_campaign"] ?? '');
    // Language: from hidden field (landing page sets this) or auto-detected from browser
    $lang_raw = $_POST["lang"] ?? $_GET["lang"] ?? '';
    $lang     = mysqli_real_escape_string($link, detectLanguage($lang_raw));
    // Country: auto-detected from IP (silent failure on localhost/private IPs)
    $country_detected = mysqli_real_escape_string($link, detectCountry($userIP));
    $profile_pic = "user_default.png";
    $timestamp   = date("Y-m-d H:i:s");

    // Prüfen ob E-Mail bereits vorhanden
    $result = mysqli_query($link, "SELECT leadid FROM users WHERE email = '$email'");

    if (mysqli_num_rows($result) > 0) {
        // ── Log re-signup attempt for this known lead ────────────────────
        $existingRow    = mysqli_fetch_assoc($result);
        $existingLeadId = (int)$existingRow['leadid'];
        $esc_page         = mysqli_real_escape_string($link, $page);
        $esc_source       = mysqli_real_escape_string($link, $source);
        $esc_utm_source   = mysqli_real_escape_string($link, $utm_source);
        $esc_utm_medium   = mysqli_real_escape_string($link, $utm_medium);
        $esc_utm_campaign = mysqli_real_escape_string($link, $utm_campaign);
        $esc_ip           = mysqli_real_escape_string($link, $userIP);
        mysqli_query($link, "INSERT INTO lead_events
            (lead_id, event_type, page, source, utm_source, utm_medium, utm_campaign, ip)
            VALUES ($existingLeadId, 'signup_attempt', '$esc_page', '$esc_source',
                    '$esc_utm_source', '$esc_utm_medium', '$esc_utm_campaign', '$esc_ip')");

        $tmPhttp_redir = $_SERVER["HTTP_REFERER"];
        $tmpRedirUrl   = $tmPhttp_redir . "?err=eae";
        if (strpos($tmPhttp_redir, "err") !== false) {
            $tmpRedirUrl = $_SERVER["HTTP_REFERER"];
        }
        header("Location: " . $tmpRedirUrl);
        exit();
    } else {
        // ── Rotator: assign referer if empty ────────────────────────────
        $rotator_assigned = 0;
        if (empty($referer) || !is_numeric($referer) || (int)$referer === 0) {
            $rot_tables = mysqli_query($link, "SHOW TABLES LIKE 'rotator_settings'");
            if (mysqli_num_rows($rot_tables) > 0) {
                $rot_on = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM rotator_settings WHERE setting_key='is_active'"));
                if ($rot_on && $rot_on['setting_value'] === '1') {
                    $cur_pos_row = mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM rotator_settings WHERE setting_key='current_position'"));
                    $cur_pos = (int)($cur_pos_row['setting_value'] ?? 0);
                    $next_rot = mysqli_fetch_assoc(mysqli_query($link, "SELECT user_id, position FROM rotator WHERE is_active=1 AND position > $cur_pos ORDER BY position ASC LIMIT 1"));
                    if (!$next_rot) {
                        $next_rot = mysqli_fetch_assoc(mysqli_query($link, "SELECT user_id, position FROM rotator WHERE is_active=1 ORDER BY position ASC LIMIT 1"));
                    }
                    if ($next_rot) {
                        $referer = (int)$next_rot['user_id'];
                        $new_pos = (int)$next_rot['position'];
                        mysqli_query($link, "UPDATE rotator_settings SET setting_value='$new_pos' WHERE setting_key='current_position'");
                        $rotator_assigned = 1;
                    }
                }
            }
        }

        // Sicheres Passwort generieren
        $plainPassword = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#'), 0, 10);
        $passwordHash  = password_hash($plainPassword, PASSWORD_DEFAULT);
        $passwordHash  = mysqli_real_escape_string($link, $passwordHash);

        $sql = "INSERT INTO users (name, email, password, a, tr, page, timestamp, registered_at, user_ip, referer, source, utm_source, utm_medium, utm_campaign, lang, country_detected, paidstatus, profile_pic, username, signuproot, rotator_assigned)
                VALUES ('$name', '$email', '$passwordHash', '$a', '$tr', '$page', '$timestamp', '$timestamp', '$userIP', '$referer', '$source', '$utm_source', '$utm_medium', '$utm_campaign', '$lang', '$country_detected', 'Free', '$profile_pic', '', '', $rotator_assigned)";

        if (mysqli_query($link, $sql)) {
            $last_id = mysqli_insert_id($link);
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION = [];
            $_SESSION["userid"]   = $last_id;
            $_SESSION["is_admin"] = false;

            // Welcome-Mail senden
            require_once __DIR__ . '/sendWelcomeMail.php';
            $loginUrl = $baseurl . '/backoffice/login.php';
            sendWelcomeMail($link, $email, $name, $plainPassword, $loginUrl, (int)$last_id);

            header("Location: ../routeprocess.php");
            exit();
        } else {
            echo "Fehler beim Einfügen der Daten: " . mysqli_error($link);
        }
    }
}

mysqli_close($link);
?>
