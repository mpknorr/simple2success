<?php
/**
 * Email Footer Helper
 * ───────────────────
 * Generates a minimal, unobtrusive footer for outgoing emails.
 * - Transaction emails: reason + Imprint + Privacy
 * - Marketing emails:   reason + Imprint + Privacy + Unsubscribe
 *
 * Categorization rules:
 * - Looked up from email_templates.category when template_key matches a DB row
 * - Fallback heuristics: followup_* and trigger_* prefixes → marketing
 */

const EMAIL_FOOTER_REASONS = [
    'welcome_user'                     => 'This email was sent automatically as part of your registration at Simple2Success.',
    'password_reset'                   => 'This email was sent because a password reset was requested for your account.',
    'new_member'                       => 'This email was sent automatically because a new team partner completed Step 2 in your network.',
    'new_paid_customer'                => 'This email was sent automatically because a new paying customer registered in your network.',
    'support_ticket'                   => 'This email was sent automatically regarding your support request.',
    'daily_leads'                      => 'This email was sent because new leads were assigned to your account.',
    'trigger_clicked_not_converted'    => 'This email was sent automatically based on your recent activity on Simple2Success.',
    'trigger_step2_done_no_step4'      => 'This email was sent automatically based on your recent activity on Simple2Success.',
];

if (!function_exists('emailFooter_getUrls')) {
    function emailFooter_getUrls($link) {
        $baseurl = $GLOBALS['baseurl'] ?? 'https://www.simple2success.com';
        $imp = '';
        $priv = '';
        $r = @mysqli_fetch_assoc(@mysqli_query($link,
            "SELECT setting_key, setting_value FROM settings
             WHERE setting_key IN ('email_impressum_url','email_privacy_url')"));
        // Use separate queries for simpler handling
        $ri = @mysqli_fetch_assoc(@mysqli_query($link,
            "SELECT setting_value FROM settings WHERE setting_key='email_impressum_url' LIMIT 1"));
        $rp = @mysqli_fetch_assoc(@mysqli_query($link,
            "SELECT setting_value FROM settings WHERE setting_key='email_privacy_url' LIMIT 1"));
        $imp  = ($ri && !empty($ri['setting_value'])) ? $ri['setting_value'] : rtrim($baseurl, '/') . '/impress.php';
        $priv = ($rp && !empty($rp['setting_value'])) ? $rp['setting_value'] : rtrim($baseurl, '/') . '/legal.php?doc=privacy-policy';
        return ['impressum' => $imp, 'privacy' => $priv, 'base' => rtrim($baseurl, '/')];
    }
}

if (!function_exists('emailFooter_getOrCreateUnsubToken')) {
    function emailFooter_getOrCreateUnsubToken($link, int $uid): string {
        if ($uid <= 0) return '';
        $r = @mysqli_fetch_assoc(@mysqli_query($link,
            "SELECT unsubscribe_token FROM users WHERE leadid=$uid LIMIT 1"));
        if ($r && !empty($r['unsubscribe_token'])) return $r['unsubscribe_token'];
        try {
            $token = bin2hex(random_bytes(24));
        } catch (Throwable $e) {
            $token = bin2hex(openssl_random_pseudo_bytes(24));
        }
        $tEsc = mysqli_real_escape_string($link, $token);
        @mysqli_query($link, "UPDATE users SET unsubscribe_token='$tEsc' WHERE leadid=$uid");
        return $token;
    }
}

if (!function_exists('emailFooter_isOptedOut')) {
    function emailFooter_isOptedOut($link, int $uid): bool {
        if ($uid <= 0) return false;
        $r = @mysqli_fetch_assoc(@mysqli_query($link,
            "SELECT marketing_optout FROM users WHERE leadid=$uid LIMIT 1"));
        return $r && (int)$r['marketing_optout'] === 1;
    }
}

if (!function_exists('emailFooter_isMarketing')) {
    function emailFooter_isMarketing($link, string $templateKey): bool {
        if (strpos($templateKey, 'followup_') === 0) return true;
        if (strpos($templateKey, 'trigger_')  === 0) return true;
        $k = mysqli_real_escape_string($link, $templateKey);
        $r = @mysqli_fetch_assoc(@mysqli_query($link,
            "SELECT category FROM email_templates WHERE template_key='$k' LIMIT 1"));
        return $r && ($r['category'] ?? '') === 'marketing';
    }
}

if (!function_exists('emailFooter_getReason')) {
    function emailFooter_getReason(string $templateKey): string {
        if (isset(EMAIL_FOOTER_REASONS[$templateKey])) return EMAIL_FOOTER_REASONS[$templateKey];
        if (strpos($templateKey, 'followup_') === 0) {
            return 'You are receiving this email as part of our onboarding series following your registration.';
        }
        if (strpos($templateKey, 'trigger_') === 0) {
            return 'This email was sent automatically based on your recent activity on Simple2Success.';
        }
        return 'This email was sent by Simple2Success.';
    }
}

/**
 * Render the email footer block as HTML.
 *
 * @param mysqli $link
 * @param string $templateKey  e.g. 'welcome_user', 'daily_leads', 'followup_day_1'
 * @param int    $userId       optional; enables Unsubscribe link for marketing emails
 */
if (!function_exists('renderEmailFooter')) {
    function renderEmailFooter($link, string $templateKey, int $userId = 0): string {
        $urls     = emailFooter_getUrls($link);
        $reason   = emailFooter_getReason($templateKey);
        $isMkt    = emailFooter_isMarketing($link, $templateKey);

        $imp  = htmlspecialchars($urls['impressum'], ENT_QUOTES, 'UTF-8');
        $priv = htmlspecialchars($urls['privacy'],   ENT_QUOTES, 'UTF-8');

        $links = '<a href="' . $imp  . '" style="color:#999;text-decoration:underline;">Imprint</a>'
               . ' &middot; '
               . '<a href="' . $priv . '" style="color:#999;text-decoration:underline;">Privacy</a>';

        if ($isMkt && $userId > 0) {
            $token = emailFooter_getOrCreateUnsubToken($link, $userId);
            if ($token !== '') {
                $unsub = $urls['base'] . '/unsubscribe.php?uid=' . $userId . '&token=' . urlencode($token);
                $unsub = htmlspecialchars($unsub, ENT_QUOTES, 'UTF-8');
                $links .= ' &middot; <a href="' . $unsub . '" style="color:#999;text-decoration:underline;">Unsubscribe</a>';
            }
        }

        return '<div style="margin-top:28px;padding-top:10px;border-top:1px solid #eee;'
             . 'font-size:11px;color:#999;text-align:center;'
             . 'font-family:Arial,Helvetica,sans-serif;line-height:1.5;">'
             . '<div style="margin-bottom:4px;">' . htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') . '</div>'
             . '<div>' . $links . '</div>'
             . '</div>';
    }
}

/**
 * Helper to check opt-out before sending marketing emails.
 * Use in senders: if (emailFooter_shouldSkip($link, $uid, $templateKey)) continue;
 */
if (!function_exists('emailFooter_shouldSkip')) {
    function emailFooter_shouldSkip($link, int $uid, string $templateKey): bool {
        if (!emailFooter_isMarketing($link, $templateKey)) return false;
        return emailFooter_isOptedOut($link, $uid);
    }
}
