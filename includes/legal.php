<?php
/**
 * Legal Documents Helper
 *
 * Central functions for loading legal documents from the database.
 * Prepared for multi-language support — currently always delivers 'en'.
 *
 * Table: legal_documents
 */

/**
 * Ensure the legal_documents table exists and seed default documents.
 */
function legalEnsureTable($link) {
    mysqli_query($link, "CREATE TABLE IF NOT EXISTS legal_documents (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        slug             VARCHAR(100) NOT NULL,
        document_type    VARCHAR(50)  NOT NULL DEFAULT 'page',
        language_code    VARCHAR(10)  NOT NULL DEFAULT 'en',
        market_code      VARCHAR(20)  NOT NULL DEFAULT 'global',
        title            VARCHAR(255) NOT NULL DEFAULT '',
        content_html     LONGTEXT     NOT NULL,
        content_text     LONGTEXT     NOT NULL,
        footer_snippet   TEXT         NOT NULL,
        status           ENUM('draft','published') NOT NULL DEFAULT 'published',
        version_number   INT          NOT NULL DEFAULT 1,
        show_in_footer        TINYINT(1) NOT NULL DEFAULT 0,
        show_on_premium_pages TINYINT(1) NOT NULL DEFAULT 0,
        show_on_registration  TINYINT(1) NOT NULL DEFAULT 0,
        show_on_checkout      TINYINT(1) NOT NULL DEFAULT 0,
        created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        published_at     DATETIME  DEFAULT NULL,
        UNIQUE KEY uq_slug_lang_market (slug, language_code, market_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Seed default English documents if not present
    $defaults = [
        [
            'slug'             => 'privacy-policy',
            'document_type'    => 'page',
            'title'            => 'Privacy Policy',
            'content_html'     => '<h2>Privacy Policy</h2><p>Your privacy is important to us. This policy explains how Simple2Success collects and uses your information.</p><p>We collect your email address when you register in order to send you access and follow-up information. We do not sell or share your data with third parties without your consent.</p><p>You may request deletion of your data at any time by contacting us at info@simple2success.com.</p>',
            'footer_snippet'   => '',
            'show_in_footer'   => 1,
            'show_on_premium_pages' => 0,
        ],
        [
            'slug'             => 'terms-of-use',
            'document_type'    => 'page',
            'title'            => 'Terms of Use',
            'content_html'     => '<h2>Terms of Use</h2><p>By using Simple2Success you agree to these terms. This website is provided for informational and business purposes.</p><p>You are responsible for maintaining the confidentiality of your account credentials. Misuse of the platform or violation of applicable law may result in account termination.</p><p>All content on this site is owned by Simple2Success unless stated otherwise.</p>',
            'footer_snippet'   => '',
            'show_in_footer'   => 1,
            'show_on_premium_pages' => 0,
        ],
        [
            'slug'             => 'impress',
            'document_type'    => 'page',
            'title'            => 'Impress / Imprint',
            'content_html'     => '<h2>Impress</h2><h4>Details according to &sect; 5 TMG</h4><p>Marc-Philipp Knorr<br>Auf der Nachthut 3<br>72534 Hayingen<br>Germany</p><h4>Contact</h4><p>Phone: +49 151 40438186<br>E-mail: info@simple2success.com</p><p>Source: <a href="https://www.e-recht24.de">eRecht24</a></p><p>1. Content Warning: The free and freely accessible content of this website has been created with the utmost care. However, the provider of this website assumes no responsibility for the accuracy and timeliness of the free and freely accessible journalistic guides and news provided.</p><p>2. External Links: This website contains links to third-party websites ("external links"). These websites are subject to the liability of the respective operators. When the external links were first created, the provider checked the third-party content for any legal violations. No legal violations were apparent at the time.</p><p>3. Copyright: The content published on this website is subject to German copyright and ancillary copyright law. Any use not permitted by German copyright and ancillary copyright law requires the prior written consent of the provider or respective rights holder.</p><p>4. Special Terms of Use: Insofar as special conditions for individual uses of this website deviate from the aforementioned paragraphs, this will be expressly pointed out at the appropriate place.</p>',
            'footer_snippet'   => '',
            'show_in_footer'   => 1,
            'show_on_premium_pages' => 0,
        ],
        [
            'slug'             => 'income-disclaimer',
            'document_type'    => 'page',
            'title'            => 'Income Disclaimer',
            'content_html'     => '<h2>Income Disclaimer</h2><p>This is not a get rich quick program nor do we believe in overnight success. We believe in hard work, integrity and developing your skills if you want to earn more financially.</p><p>As stipulated by law, we cannot and do not make any guarantees about your ability to get results or earn any money with any of our products or services. Results will vary and depend on many factors, including but not limited to your background, experience, and work ethic.</p><p>All business entails risk as well as consistent effort and action. If you are not willing to accept that, please do not sign up for our program.</p>',
            'footer_snippet'   => 'This is not a get rich quick program nor do we believe in overnight success. We believe in hard work, integrity and developing your skills if you want to earn more financially. As stipulated by law, we cannot and do not make any guarantees about your ability to get results or earn any money with any of our products or services. Results will vary and depend on many factors, including but not limited to your background, experience, and work ethic. All business entails risk as well as consistent effort and action.',
            'show_in_footer'   => 1,
            'show_on_premium_pages' => 1,
        ],
    ];

    foreach ($defaults as $doc) {
        $slug    = mysqli_real_escape_string($link, $doc['slug']);
        $exists  = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT id FROM legal_documents WHERE slug='$slug' AND language_code='en' AND market_code='global'"
        ));
        if (!$exists) {
            $dtype   = mysqli_real_escape_string($link, $doc['document_type']);
            $title   = mysqli_real_escape_string($link, $doc['title']);
            $chtml   = mysqli_real_escape_string($link, $doc['content_html']);
            $fsnip   = mysqli_real_escape_string($link, $doc['footer_snippet']);
            $infoot  = (int)$doc['show_in_footer'];
            $inprem  = (int)$doc['show_on_premium_pages'];
            mysqli_query($link,
                "INSERT INTO legal_documents
                    (slug, document_type, language_code, market_code, title, content_html, content_text, footer_snippet,
                     status, show_in_footer, show_on_premium_pages, published_at)
                 VALUES
                    ('$slug','$dtype','en','global','$title','$chtml','','$fsnip',
                     'published',$infoot,$inprem,NOW())"
            );
        }
    }
}

/**
 * Load a legal document by slug.
 * Returns the row array or null if not found.
 * Multi-language ready: add language/market params later without architecture change.
 *
 * @param  mysqli $link
 * @param  string $slug          e.g. 'privacy-policy'
 * @param  string $languageCode  currently always 'en'
 * @param  string $marketCode    currently always 'global'
 * @return array|null
 */
function getLegalDocument($link, $slug, $languageCode = 'en', $marketCode = 'global') {
    $s = mysqli_real_escape_string($link, $slug);
    $l = mysqli_real_escape_string($link, $languageCode);
    $m = mysqli_real_escape_string($link, $marketCode);
    try {
        $result = mysqli_query($link,
            "SELECT * FROM legal_documents
             WHERE slug='$s' AND language_code='$l' AND market_code='$m' AND status='published'
             LIMIT 1"
        );
    } catch (\Exception $e) {
        return null;
    }
    if (!$result) return null;
    $row = mysqli_fetch_assoc($result);
    return $row ?: null;
}

/**
 * Load footer snippet for a given slug (used on premium pages).
 * Safe to call even before legalEnsureTable() — falls back gracefully.
 *
 * @param  mysqli $link
 * @param  string $slug
 * @param  string $languageCode
 * @return string  Escaped plain text ready for output, or hardcoded fallback
 */
function getLegalFooterSnippet($link, $slug, $languageCode = 'en') {
    // Suppress potential warning if table doesn't exist yet
    $doc = @getLegalDocument($link, $slug, $languageCode);
    if ($doc && !empty($doc['footer_snippet'])) {
        return htmlspecialchars($doc['footer_snippet'], ENT_QUOTES, 'UTF-8');
    }
    // Static fallback — ensures landing pages never show blank disclaimer
    $fallbacks = [
        'income-disclaimer' => 'This is not a get rich quick program nor do we believe in overnight success. We believe in hard work, integrity and developing your skills if you want to earn more financially. As stipulated by law, we cannot and do not make any guarantees about your ability to get results or earn any money with any of our products or services. Results will vary and depend on many factors, including but not limited to your background, experience, and work ethic. All business entails risk as well as consistent effort and action.',
    ];
    return $fallbacks[$slug] ?? '';
}

/**
 * Returns the public URL for a legal document page.
 *
 * @param  string $baseurl
 * @param  string $slug
 * @return string
 */
function getLegalPageUrl($baseurl, $slug) {
    // impress keeps its legacy URL for backward compatibility
    if ($slug === 'impress') {
        return $baseurl . '/impress.php';
    }
    return $baseurl . '/legal.php?doc=' . urlencode($slug);
}
