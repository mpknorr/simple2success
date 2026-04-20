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
$router->setBasePath('/simple2success');

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
        $allowed = ['link1', 'link2', 'link3', 'linkp1', 'linkp2', 'linkp3'];
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
        $allowed = ['link1', 'link2', 'link3', 'linkp1', 'linkp2', 'linkp3'];
        if (in_array($page, $allowed, true)) {
            require __DIR__ . '/' . $page . '/index.php';
        }
    }
});
// ...

// Run it!
$router->run();


// if(isset($_GET["referer"])){
//     $referer = $_GET["referer"];
//     $page = $_GET["page"];
//     require_once $page.'/index.php';
// }