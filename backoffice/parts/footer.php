<?php
if (!function_exists('getLegalFooterLinks')) {
    @include_once __DIR__ . '/../../includes/legal.php';
}
// Ensure "Legal Notice" title is up to date in DB (replaces legacy "Impress / Imprint" and "Impressum / Legal Notice")
if (isset($link)) {
    @mysqli_query($link,
        "UPDATE legal_documents SET title='Legal Notice'
         WHERE slug='impress' AND title != 'Legal Notice'"
    );
}
$_footerLinks = (isset($link) && function_exists('getLegalFooterLinks'))
    ? getLegalFooterLinks($link) : [];
$_footerBase = $baseurl ?? '';
?>
<!-- BEGIN : Footer-->
<footer class="footer undefined undefined">
    <p class="clearfix text-muted m-0">
        <span>Copyright &copy; <?= date('Y') ?> &nbsp;</span>
        <a href="https://www.simple2success.com" id="s2sLink" target="_blank">SIMPLE2SUCCESS</a>
        <span class="d-none d-sm-inline-block">, All rights reserved.</span>
        <?php if (!empty($_footerLinks) && function_exists('getLegalPageUrl')): ?>
        <span class="d-none d-sm-inline-block" style="margin-left:12px;">
            <?php foreach ($_footerLinks as $i => $fl): ?>
                <?= $i > 0 ? ' &middot; ' : '' ?>
                <a href="<?= htmlspecialchars(getLegalPageUrl($_footerBase, $fl['slug'])) ?>" class="text-muted"><?= htmlspecialchars($fl['title']) ?></a>
            <?php endforeach; ?>
        </span>
        <?php endif; ?>
    </p>
</footer>
<!-- End : Footer-->
