<?php
/**
 * Impress / Imprint page — backward-compatible legacy URL.
 * Content is now loaded from legal_documents (slug: 'impress').
 * All existing links to /impress.php remain valid.
 */

require_once __DIR__ . '/includes/conn.php';
require_once __DIR__ . '/includes/legal.php';

legalEnsureTable($link);

$doc = getLegalDocument($link, 'impress');
$pageTitle   = $doc ? htmlspecialchars($doc['title'], ENT_QUOTES, 'UTF-8') : 'Impressum';
$contentHtml = $doc ? $doc['content_html'] : '<h2>Impressum</h2><p>Content not found.</p>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= $pageTitle ?> — Simple2Success</title>
    <link rel="shortcut icon" href="<?= $baseurl ?>/backoffice/app-assets/img/ico/favicon.ico">
    <style>
        body { background:#1a1a2e; color:#ccc; font-family:Arial,sans-serif; padding:40px 20px; margin:0; }
        .legal-wrap { max-width:860px; margin:0 auto; background:#232334; border-radius:8px; padding:40px; }
        .legal-wrap h2 { color:#cb2ebc; margin-top:0; }
        .legal-wrap h4 { color:#a0a0c0; margin-top:24px; }
        .legal-wrap p  { line-height:1.7; color:#ccc; }
        .legal-wrap a  { color:#cb2ebc; }
        .legal-back { display:inline-block; margin-bottom:24px; color:#cb2ebc; text-decoration:none; font-size:14px; }
        .legal-back:hover { text-decoration:underline; }
        .legal-footer { margin-top:32px; padding-top:16px; border-top:1px solid #333; text-align:center; font-size:12px; color:#555; }
        .legal-footer a { color:#666; text-decoration:none; margin:0 8px; }
        .legal-footer a:hover { color:#cb2ebc; }
    </style>
</head>
<body>
    <div class="legal-wrap">
        <a href="javascript:history.back();" class="legal-back">&#8592; Back</a>
        <?= $contentHtml ?>
        <div class="legal-footer">
            &copy; <?= date('Y') ?> Simple2Success. All Rights Reserved.<br style="margin-bottom:6px;">
            <a href="<?= $baseurl ?>/impress.php">Impressum</a> |
            <a href="<?= $baseurl ?>/legal.php?doc=privacy-policy">Privacy Policy</a> |
            <a href="<?= $baseurl ?>/legal.php?doc=terms-of-use">Terms of Use</a>
        </div>
    </div>
</body>
</html>
