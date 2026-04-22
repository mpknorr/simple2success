<?php
/**
 * Public legal document page.
 * Serves Privacy Policy, Terms of Use, Income Disclaimer.
 * Imprint/Impress uses its own legacy impress.php for backward compatibility.
 *
 * URL: /legal.php?doc=privacy-policy
 *       /legal.php?doc=terms-of-use
 *       /legal.php?doc=income-disclaimer
 */

require_once __DIR__ . '/includes/conn.php';
require_once __DIR__ . '/includes/legal.php';

legalEnsureTable($link);

$allowedSlugs = ['privacy-policy', 'terms-of-use'];
$slug = isset($_GET['doc']) ? trim($_GET['doc']) : '';

if (!in_array($slug, $allowedSlugs, true)) {
    header('HTTP/1.1 404 Not Found');
    exit('<h1>404 Not Found</h1>');
}

$doc = getLegalDocument($link, $slug);

if (!$doc) {
    header('HTTP/1.1 404 Not Found');
    exit('<h1>Document not found.</h1>');
}

$pageTitle = htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8');
// content_html is admin-controlled — output as-is (admin-only editable, trusted source)
$contentHtml = $doc['content_html'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $pageTitle ?> — Simple2Success</title>
    <link rel="shortcut icon" href="<?= $baseurl ?>/backoffice/app-assets/img/ico/favicon.ico">
    <link href="<?= $baseurl ?>/backoffice/app-assets/css/fonts/font-style.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="<?= $baseurl ?>/backoffice/app-assets/fonts/feather/style.min.css">
    <link rel="stylesheet" type="text/css" href="<?= $baseurl ?>/backoffice/app-assets/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="<?= $baseurl ?>/backoffice/app-assets/css/bootstrap-extended.css">
    <link rel="stylesheet" type="text/css" href="<?= $baseurl ?>/backoffice/app-assets/css/colors.css">
    <link rel="stylesheet" type="text/css" href="<?= $baseurl ?>/backoffice/app-assets/css/components.css">
    <link rel="stylesheet" type="text/css" href="<?= $baseurl ?>/backoffice/app-assets/css/themes/layout-dark.css">
    <style>
        body { background:#1a1a2e; color:#ccc; font-family:Arial,sans-serif; padding:40px 20px; }
        .legal-wrap { max-width:860px; margin:0 auto; background:#232334; border-radius:8px; padding:40px; }
        .legal-wrap h2 { color:#cb2ebc; margin-top:0; }
        .legal-wrap h4 { color:#a0a0c0; margin-top:24px; }
        .legal-wrap p { line-height:1.7; color:#ccc; }
        .legal-wrap a { color:#cb2ebc; }
        .legal-back { display:inline-block; margin-bottom:24px; color:#cb2ebc; text-decoration:none; font-size:14px; }
        .legal-back:hover { text-decoration:underline; }
    </style>
</head>
<body>
    <div class="legal-wrap">
        <a href="javascript:history.back();" class="legal-back"><i class="ft-arrow-left" style="font-size:13px;"></i> Back</a>
        <?= $contentHtml ?>
    </div>
</body>
</html>
