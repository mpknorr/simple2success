<?php

// Require composer autoloader
require __DIR__ . '/vendor/autoload.php';

// DB connection + $baseurl (needed for homepage_mode check)
require_once __DIR__ . '/includes/conn.php';

function getHomepageSetting($link, $key) {
    $k = mysqli_real_escape_string($link, $key);
    $r = @mysqli_fetch_assoc(mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key = '$k'"));
    return $r ? $r['setting_value'] : '';
}

// Create Router instance
$router = new \Bramus\Router\Router();
// Derive basePath from $baseurl so lokaler MAMP (/simple2success) und Produktiv-Domain (/) beide funktionieren
$basePath = parse_url($baseurl, PHP_URL_PATH) ?: '';
$router->setBasePath(rtrim($basePath, '/'));

// Define routes
// ...
$router->get('/', function() use ($link, $baseurl) {
    $mode   = getHomepageSetting($link, 'homepage_mode');
    $target = getHomepageSetting($link, 'homepage_target');

    if ($mode === 'maintenance') {
        require __DIR__ . '/maintenance.php';
        return;
    }

    if ($mode === 'page' && !empty($target)) {
        $allowed = ['link1', 'link2', 'link3', 'link4', 'linkp1', 'linkp2', 'linkp3', 'linkp4'];
        if (in_array($target, $allowed, true)) {
            // No personal referer — rotator will assign a sponsor on form submit
            $referer = 0;
            $page = $target;
            require __DIR__ . '/' . $target . '/index.php';
            return;
        }
    }

    // Default
    require __DIR__ . '/index.html.bak';
});
$router->get('/go/(\d+)/(\w+)', function($referer, $page) use ($link, $baseurl) {
    if (!empty($referer)) {
        $allowed = ['link1', 'link2', 'link3', 'link4', 'linkp1', 'linkp2', 'linkp3', 'linkp4'];
        if (in_array($page, $allowed, true)) {
            require __DIR__ . '/' . $page . '/index.php';
        }
    }
});

// Link Rotator: /r/<slug>[?source=<param>]
$router->get('/r/([a-zA-Z0-9_-]+)', function($slug) use ($link, $baseurl) {
    $slugEsc = mysqli_real_escape_string($link, $slug);
    $source  = isset($_GET['source'])
        ? substr(preg_replace('/[^a-zA-Z0-9_\-]/', '', $_GET['source']), 0, 100)
        : '';
    $sourceEsc = mysqli_real_escape_string($link, $source);

    $rotator = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT * FROM link_rotators WHERE slug='$slugEsc' AND is_active=1 LIMIT 1"));
    if (!$rotator) { http_response_code(404); die('Rotator not found or inactive.'); }

    $rid      = (int)$rotator['id'];
    $mode     = $rotator['rotation_mode'];
    $fallback = $rotator['fallback_url'];

    $itRes = mysqli_query($link,
        "SELECT * FROM link_rotator_items
         WHERE rotator_id=$rid AND is_active=1
           AND (click_limit=0 OR clicks < click_limit)
         ORDER BY position ASC, id ASC");
    $items = [];
    if ($itRes) { while ($r = mysqli_fetch_assoc($itRes)) $items[] = $r; }

    $target     = $fallback;
    $selectedId = null;

    if ($items) {
        if ($mode === 'random') {
            $pool = [];
            foreach ($items as $i => $it) {
                $w = max(1, (int)$it['weight']);
                for ($k = 0; $k < $w; $k++) $pool[] = $i;
            }
            $sel = $items[$pool[array_rand($pool)]];
        } elseif ($mode === 'sequential') {
            $sel = $items[0];
        } else { // balanced
            usort($items, function($a, $b) { return (int)$a['clicks'] <=> (int)$b['clicks']; });
            $sel = $items[0];
        }
        $target     = $sel['url'];
        $selectedId = (int)$sel['id'];
        mysqli_query($link, "UPDATE link_rotator_items SET clicks=clicks+1 WHERE id=$selectedId");
    }

    $ip    = mysqli_real_escape_string($link, $_SERVER['REMOTE_ADDR'] ?? '');
    $iidSql = $selectedId ? (string)$selectedId : 'NULL';
    mysqli_query($link, "INSERT INTO link_rotator_stats (rotator_id, item_id, source_param, ip_address)
        VALUES ($rid, $iidSql, '$sourceEsc', '$ip')");

    if (!empty($source) && !empty($target)) {
        $sep = (parse_url($target, PHP_URL_QUERY) === null) ? '?' : '&';
        $target .= $sep . 'source=' . urlencode($source);
    }

    if (!empty($target)) { header('Location: ' . $target); exit; }
    http_response_code(404); die('No valid target URL found.');
});
// ...

// Run it!
$router->run();


// if(isset($_GET["referer"])){
//     $referer = $_GET["referer"];
//     $page = $_GET["page"];
//     require_once $page.'/index.php';
// }