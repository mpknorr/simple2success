<?php
/**
 * head-tracking.php — Central tracking & consent script output
 *
 * IMPORTANT: Include this file ONLY in public-facing pages (<head> section).
 * Never include in admin/ or backoffice/ areas.
 *
 * Requires: $link (mysqli DB connection, from conn.php)
 *
 * Multilingual note:
 *   - Cookiebot detects visitor language automatically (no PHP changes needed)
 *   - Pixel IDs are global per domain (not per language)
 *   - Legal URLs: extend tracking_legal_links table with additional lang rows
 *     and read via getSiteLang() when multilingual system is implemented
 */

if (!isset($link)) return;

function getTrackSetting($link, $key) {
    static $cache = [];
    if (array_key_exists($key, $cache)) return $cache[$key];
    $k = mysqli_real_escape_string($link, $key);
    $r = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT setting_value FROM settings WHERE setting_key='$k' LIMIT 1"));
    return $cache[$key] = ($r ? $r['setting_value'] : '');
}

// ── 1. Cookiebot — must load BEFORE any other tracking scripts ────────────────
if (getTrackSetting($link, 'cookiebot_enabled') === '1') {
    $cbid = trim(getTrackSetting($link, 'cookiebot_cbid'));
    $mode = trim(getTrackSetting($link, 'cookiebot_blocking_mode')) ?: 'auto';
    $mode = in_array($mode, ['auto', 'manual'], true) ? $mode : 'auto';
    if ($cbid !== '') {
        $cbid = htmlspecialchars($cbid, ENT_QUOTES, 'UTF-8');
        echo '<script id="Cookiebot" src="https://consent.cookiebot.com/uc.js"'
           . ' data-cbid="' . $cbid . '"'
           . ' data-blockingmode="' . $mode . '"'
           . ' type="text/javascript"></script>' . "\n";
    }
}

// ── 2. Meta Pixel (Facebook / Instagram) ─────────────────────────────────────
if (getTrackSetting($link, 'meta_pixel_enabled') === '1') {
    $pid = preg_replace('/\D/', '', trim(getTrackSetting($link, 'meta_pixel_id')));
    if ($pid !== '') {
?>
<script>
!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
document,'script','https://connect.facebook.net/en_US/fbevents.js');
fbq('init','<?= $pid ?>');
fbq('track','PageView');
/* TODO: Add fbq('track','Lead') on successful lead submission (postlead.php) */
</script>
<noscript><img height="1" width="1" style="display:none"
 src="https://www.facebook.com/tr?id=<?= $pid ?>&ev=PageView&noscript=1"/></noscript>
<?php
    }
}

// ── 3. TikTok Pixel ───────────────────────────────────────────────────────────
if (getTrackSetting($link, 'tiktok_pixel_enabled') === '1') {
    $tid = preg_replace('/[^A-Z0-9]/i', '', trim(getTrackSetting($link, 'tiktok_pixel_id')));
    if ($tid !== '') {
        $tid_safe = htmlspecialchars($tid, ENT_QUOTES, 'UTF-8');
?>
<script>
!function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];
ttq.methods=["page","track","identify","instances","debug","on","off","once","ready",
"alias","group","enableCookie","disableCookie"];
ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};
for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)
ttq.setAndDefer(e,ttq.methods[n]);return e};
ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";
ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};
ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};
var o=document.createElement("script");o.type="text/javascript";o.async=!0;
o.src=i+"?sdkid="+e+"&lib="+t;
var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
ttq.load('<?= $tid_safe ?>');
ttq.page();
/* TODO: Add ttq.track('CompleteRegistration') on successful lead submission (postlead.php) */
</script>
<?php
    }
}
