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

    // ── Migration v2: Full Terms of Use ──────────────────────────────────────
    $touHtml = '<h2>Terms of Use</h2>'
        . '<p><em>Last updated: April 22, 2026</em></p>'
        . '<p><strong>PLEASE READ THESE TERMS OF USE CAREFULLY BEFORE USING SIMPLE2SUCCESS.</strong><br>'
        . 'By accessing or using Simple2Success (&ldquo;the Platform&rdquo;, &ldquo;we&rdquo;, &ldquo;us&rdquo;, &ldquo;our&rdquo;), '
        . 'you agree to be bound by these Terms of Use (&ldquo;Terms&rdquo;). If you do not agree to these Terms, please do not use the Platform.</p>'

        . '<h3>1. About Simple2Success</h3>'
        . '<p>Simple2Success is a software platform that provides lead generation, onboarding, and team management tools for independent business partners. '
        . 'Simple2Success is operated by Marc-Philipp Knorr, Auf der Nachthut 3, 72534 Hayingen, Germany '
        . '(E-mail: <a href="mailto:info@simple2success.com">info@simple2success.com</a>).</p>'
        . '<p><strong>IMPORTANT:</strong> Simple2Success is a software tool only. It is not a business opportunity, investment product, '
        . 'or income-generating program in itself. Any income potential depends entirely on the underlying partner program you are '
        . 'associated with and your own efforts.</p>'

        . '<h3>2. Eligibility</h3>'
        . '<p>You must be at least 18 years of age to use the Platform. By using the Platform, you represent and warrant that you are '
        . 'at least 18 years old and have the legal capacity to enter into a binding agreement.</p>'

        . '<h3>3. Account Registration and Security</h3>'
        . '<p>When you register for an account, you agree to:</p>'
        . '<ul>'
        . '<li>Provide accurate, current, and complete information</li>'
        . '<li>Maintain the security and confidentiality of your login credentials</li>'
        . '<li>Notify us immediately of any unauthorised use of your account at <a href="mailto:info@simple2success.com">info@simple2success.com</a></li>'
        . '<li>Accept responsibility for all activities that occur under your account</li>'
        . '</ul>'
        . '<p>We reserve the right to suspend or terminate accounts that contain inaccurate information or that are used in violation of these Terms.</p>'

        . '<h3>4. Acceptable Use Policy</h3>'
        . '<p>You agree <strong>NOT</strong> to use the Platform to:</p>'
        . '<ul>'
        . '<li>Send unsolicited commercial e-mails (spam) or engage in any form of unsolicited marketing</li>'
        . '<li>Make false, misleading, or exaggerated income claims when promoting the platform or associated programs</li>'
        . '<li>Engage in any pyramid scheme, fraudulent activity, or illegal multi-level marketing practices</li>'
        . '<li>Upload, post, or transmit any content that is unlawful, harmful, threatening, abusive, harassing, defamatory, or otherwise objectionable</li>'
        . '<li>Violate any applicable local, national, or international law or regulation</li>'
        . '<li>Impersonate any person or entity or falsely state your affiliation with any person or entity</li>'
        . '<li>Attempt to gain unauthorised access to any part of the Platform or its related systems</li>'
        . '</ul>'
        . '<p>Violation of this Acceptable Use Policy may result in immediate account termination without notice.</p>'

        . '<h3>5. Income Disclaimer and Earnings Claims</h3>'
        . '<p><strong>THE PLATFORM MAKES NO GUARANTEES REGARDING INCOME OR EARNINGS.</strong></p>'
        . '<p>This is not a get-rich-quick program. We believe in hard work, integrity, and developing your skills if you want to earn more financially. '
        . 'As required by law, we cannot and do not make any guarantees about your ability to get results or earn any money with any of our products or services. '
        . 'Results will vary and depend on many factors, including but not limited to your background, experience, work ethic, and market conditions.</p>'
        . '<p>All business activities entail risk as well as consistent effort and action. If you are not willing to accept that, please do not use the Platform.</p>'
        . '<p>Any income figures or results mentioned in our marketing materials represent exceptional results, not typical results. '
        . 'The average participant earns significantly less or nothing at all. You should not expect to achieve similar results.</p>'
        . '<p>This disclaimer complies with the guidelines of the U.S. Federal Trade Commission (FTC) regarding income claims and endorsements.</p>'

        . '<h3>6. Intellectual Property</h3>'
        . '<p>All content on the Platform, including but not limited to text, graphics, logos, images, software code, and design, '
        . 'is the property of Simple2Success or its content suppliers and is protected by applicable copyright, trademark, and other intellectual property laws.</p>'
        . '<p>You are granted a limited, non-exclusive, non-transferable licence to access and use the Platform for its intended purpose. '
        . 'You may not reproduce, distribute, modify, create derivative works of, publicly display, or exploit any content from the Platform without our prior written consent.</p>'

        . '<h3>7. Third-Party Links and Services</h3>'
        . '<p>The Platform may contain links to third-party websites or services. These links are provided for your convenience only. '
        . 'We have no control over the content of those sites and accept no responsibility for them or for any loss or damage that may arise from your use of them.</p>'

        . '<h3>8. Limitation of Liability</h3>'
        . '<p>TO THE MAXIMUM EXTENT PERMITTED BY APPLICABLE LAW, SIMPLE2SUCCESS AND ITS OPERATORS SHALL NOT BE LIABLE FOR ANY INDIRECT, '
        . 'INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING BUT NOT LIMITED TO LOSS OF PROFITS, DATA, GOODWILL, OR OTHER '
        . 'INTANGIBLE LOSSES, RESULTING FROM:</p>'
        . '<ul>'
        . '<li>(a) YOUR ACCESS TO OR USE OF (OR INABILITY TO ACCESS OR USE) THE PLATFORM;</li>'
        . '<li>(b) ANY CONDUCT OR CONTENT OF ANY THIRD PARTY ON THE PLATFORM;</li>'
        . '<li>(c) ANY CONTENT OBTAINED FROM THE PLATFORM; OR</li>'
        . '<li>(d) UNAUTHORISED ACCESS, USE, OR ALTERATION OF YOUR TRANSMISSIONS OR CONTENT.</li>'
        . '</ul>'
        . '<p>IN NO EVENT SHALL OUR TOTAL LIABILITY TO YOU EXCEED THE AMOUNT YOU HAVE PAID TO US IN THE TWELVE (12) MONTHS PRECEDING THE CLAIM, '
        . 'OR EUR 100, WHICHEVER IS GREATER.</p>'
        . '<p>Nothing in these Terms shall limit or exclude our liability for death or personal injury caused by our negligence, fraud or fraudulent '
        . 'misrepresentation, or any other liability that cannot be excluded or limited by applicable law.</p>'

        . '<h3>9. Indemnification</h3>'
        . '<p>You agree to defend, indemnify, and hold harmless Simple2Success and its operators from and against any claims, liabilities, damages, '
        . 'losses, and expenses, including reasonable legal fees, arising out of or in any way connected with your access to or use of the Platform, '
        . 'your violation of these Terms, or your violation of any third-party rights.</p>'

        . '<h3>10. Right of Withdrawal (for EU Consumers)</h3>'
        . '<p>If you are a consumer resident in the European Union or European Economic Area, you have the right to withdraw from this agreement '
        . 'within 14 days without giving any reason (right of withdrawal / <em>Widerrufsrecht</em>).</p>'
        . '<p>The withdrawal period expires 14 days after the day you concluded the agreement. To exercise the right of withdrawal, you must inform us '
        . '(Marc-Philipp Knorr, <a href="mailto:info@simple2success.com">info@simple2success.com</a>) of your decision to withdraw from this agreement '
        . 'by an unequivocal statement (e.g., a letter sent by post or e-mail).</p>'
        . '<p><em>Note: Since Simple2Success is currently offered free of charge, the practical implications of withdrawal are limited. '
        . 'However, we inform you of this right in compliance with EU consumer protection law (Directive 2011/83/EU).</em></p>'

        . '<h3>11. Governing Law and Jurisdiction</h3>'
        . '<p>These Terms shall be governed by and construed in accordance with the laws of the Federal Republic of Germany, '
        . 'excluding the UN Convention on Contracts for the International Sale of Goods (CISG).</p>'
        . '<p>For consumers resident in the European Union: The choice of German law does not deprive you of the protection afforded to you '
        . 'by provisions that cannot be derogated from by agreement under the law of the country where you have your habitual residence.</p>'
        . '<p>For disputes arising from these Terms, the parties agree to submit to the jurisdiction of the courts of Reutlingen, Germany. '
        . 'However, if you are a consumer, you may also bring proceedings in the courts of your country of residence.</p>'

        . '<h3>12. Changes to These Terms</h3>'
        . '<p>We reserve the right to modify these Terms at any time. We will notify you of material changes by posting the updated Terms on the '
        . 'Platform with a new &ldquo;Last updated&rdquo; date. Your continued use of the Platform after such changes constitutes your acceptance '
        . 'of the new Terms. If you do not agree to the new Terms, you must stop using the Platform.</p>'

        . '<h3>13. Severability</h3>'
        . '<p>If any provision of these Terms is found to be invalid, illegal, or unenforceable, the remaining provisions shall continue in full '
        . 'force and effect. The invalid provision shall be replaced by a valid provision that most closely reflects the intent of the original provision.</p>'

        . '<h3>14. Entire Agreement</h3>'
        . '<p>These Terms, together with our Privacy Policy and any other legal notices published by us on the Platform, constitute the entire '
        . 'agreement between you and Simple2Success regarding your use of the Platform and supersede all prior agreements and understandings.</p>'

        . '<h3>15. Contact</h3>'
        . '<p>If you have any questions about these Terms of Use, please contact us:</p>'
        . '<p>Marc-Philipp Knorr<br>'
        . 'E-mail: <a href="mailto:info@simple2success.com">info@simple2success.com</a><br>'
        . 'Address: Auf der Nachthut 3, 72534 Hayingen, Germany</p>';

    $touEsc = mysqli_real_escape_string($link, $touHtml);
    mysqli_query($link,
        "UPDATE legal_documents
         SET content_html='$touEsc', title='Terms of Use', version_number=2, updated_at=NOW()
         WHERE slug='terms-of-use' AND language_code='en' AND market_code='global'
           AND version_number < 2"
    );

    // ── Migration v2: Full GDPR Privacy Policy ────────────────────────────────
    // Runs only once (version_number < 2). Safe to re-deploy — won't overwrite
    // any future admin edits that advance version_number beyond 2.
    $ppHtml = '<h2>Privacy Policy</h2>'
        . '<p><em>Last updated: April 22, 2026</em></p>'

        . '<h3>1. Introduction and Data Controller</h3>'
        . '<p>Simple2Success (&ldquo;we&rdquo;, &ldquo;us&rdquo;, or &ldquo;our&rdquo;) is operated by:</p>'
        . '<p>Marc-Philipp Knorr<br>Auf der Nachthut 3<br>72534 Hayingen, Germany<br>'
        . 'E-mail: <a href="mailto:info@simple2success.com">info@simple2success.com</a></p>'
        . '<p>We are the data controller responsible for the processing of your personal data as described in this Privacy Policy.</p>'

        . '<h3>2. What Data We Collect</h3>'
        . '<p>When you use Simple2Success, we may collect and process the following categories of personal data:</p>'
        . '<ul>'
        . '<li><strong>Registration Data:</strong> First name, e-mail address</li>'
        . '<li><strong>Technical Data:</strong> IP address, browser type, device type, operating system</li>'
        . '<li><strong>Usage Data:</strong> Pages visited, referrer URL, UTM tracking parameters (utm_source, utm_medium, utm_campaign), source identifier, language preference</li>'
        . '<li><strong>Location Data:</strong> Country of origin (automatically detected from IP address)</li>'
        . '<li><strong>Account Data:</strong> Username, hashed password, account status (Free/Paid), profile picture, sponsor/referrer ID, registration date</li>'
        . '<li><strong>Communication Data:</strong> E-mails we send you (welcome e-mail, follow-up communications)</li>'
        . '</ul>'

        . '<h3>3. How We Use Your Data (Purposes and Legal Bases)</h3>'
        . '<p>We process your personal data for the following purposes:</p>'

        . '<h4>a) To provide and manage your account</h4>'
        . '<p><em>Legal basis: Art. 6(1)(b) GDPR &mdash; performance of a contract</em></p>'
        . '<ul><li>Creating and managing your user account</li>'
        . '<li>Sending you your access credentials via e-mail</li>'
        . '<li>Routing you to the appropriate onboarding page</li></ul>'

        . '<h4>b) To communicate with you</h4>'
        . '<p><em>Legal basis: Art. 6(1)(b) GDPR / Art. 6(1)(a) GDPR for marketing</em></p>'
        . '<ul><li>Sending welcome e-mails and system notifications</li>'
        . '<li>Sending follow-up information about the partner program (only with your consent)</li></ul>'

        . '<h4>c) For security and fraud prevention</h4>'
        . '<p><em>Legal basis: Art. 6(1)(f) GDPR &mdash; legitimate interests</em></p>'
        . '<ul><li>Detecting and preventing fraudulent registrations</li>'
        . '<li>Logging re-signup attempts</li>'
        . '<li>Maintaining system integrity</li></ul>'

        . '<h4>d) For analytics and improvement</h4>'
        . '<p><em>Legal basis: Art. 6(1)(f) GDPR &mdash; legitimate interests</em></p>'
        . '<ul><li>Tracking which landing pages perform best (via UTM parameters)</li>'
        . '<li>Analysing registration sources to improve our marketing</li></ul>'

        . '<h4>e) Legal compliance</h4>'
        . '<p><em>Legal basis: Art. 6(1)(c) GDPR &mdash; legal obligation</em></p>'
        . '<ul><li>Complying with applicable laws and regulations</li></ul>'

        . '<h3>4. Data Sharing and Third-Party Processors</h3>'
        . '<p>We do not sell your personal data to third parties. We may share your data with the following categories of trusted service providers (data processors under Art. 28 GDPR), who process data only on our instructions:</p>'
        . '<ul>'
        . '<li><strong>Hosting Provider:</strong> [Please insert hosting provider name, e.g. Hetzner Online GmbH, Germany] &mdash; for server infrastructure</li>'
        . '<li><strong>E-Mail Service Provider:</strong> Brevo (Sendinblue) &mdash; for sending transactional e-mails</li>'
        . '<li><strong>Database Provider:</strong> [Please insert database provider name]</li>'
        . '</ul>'
        . '<p>We have concluded data processing agreements (DPAs) with all processors as required by Art. 28 GDPR.</p>'

        . '<h3>5. International Data Transfers</h3>'
        . '<p>If any of our service providers are located outside the European Economic Area (EEA), we ensure that your data is protected by appropriate safeguards, such as:</p>'
        . '<ul>'
        . '<li>Standard Contractual Clauses (SCCs) approved by the European Commission</li>'
        . '<li>Adequacy decisions (e.g., EU-US Data Privacy Framework for transfers to the USA)</li>'
        . '</ul>'

        . '<h3>6. Cookies and Tracking Technologies</h3>'
        . '<p>We use the following types of cookies and tracking technologies:</p>'
        . '<ul>'
        . '<li><strong>Strictly Necessary Cookies:</strong> Session cookies required for login and security (no consent required)</li>'
        . '<li><strong>Analytics/Tracking:</strong> UTM parameters and referrer tracking to measure marketing performance (requires consent)</li>'
        . '</ul>'
        . '<p>You can manage your cookie preferences at any time via our Cookie Consent Banner. For more information, please see our Cookie Policy.</p>'

        . '<h3>7. Data Retention</h3>'
        . '<p>We retain your personal data for as long as your account is active or as needed to provide you with our services. Specifically:</p>'
        . '<ul>'
        . '<li>Account data is retained until you request deletion of your account</li>'
        . '<li>Log data (IP addresses, event logs) is retained for a maximum of 12 months for security purposes</li>'
        . '<li>We may retain certain data longer if required by applicable law (e.g., tax records for 10 years under German law)</li>'
        . '</ul>'

        . '<h3>8. Your Rights</h3>'
        . '<p>Depending on your location, you have the following rights regarding your personal data:</p>'

        . '<h4>Rights under GDPR (EU/EEA residents):</h4>'
        . '<ul>'
        . '<li><strong>Right of access (Art. 15 GDPR):</strong> You can request a copy of the personal data we hold about you</li>'
        . '<li><strong>Right to rectification (Art. 16 GDPR):</strong> You can request correction of inaccurate data</li>'
        . '<li><strong>Right to erasure (Art. 17 GDPR):</strong> You can request deletion of your data (&ldquo;right to be forgotten&rdquo;)</li>'
        . '<li><strong>Right to restriction (Art. 18 GDPR):</strong> You can request that we limit how we use your data</li>'
        . '<li><strong>Right to data portability (Art. 20 GDPR):</strong> You can request your data in a machine-readable format</li>'
        . '<li><strong>Right to object (Art. 21 GDPR):</strong> You can object to processing based on legitimate interests</li>'
        . '<li><strong>Right to withdraw consent:</strong> Where processing is based on consent, you can withdraw it at any time</li>'
        . '<li><strong>Right to lodge a complaint:</strong> You have the right to lodge a complaint with your local data protection authority. '
        . 'In Germany: Landesbeauftragter f&uuml;r den Datenschutz Baden-W&uuml;rttemberg '
        . '(<a href="https://www.baden-wuerttemberg.datenschutz.de/" target="_blank" rel="noopener">https://www.baden-wuerttemberg.datenschutz.de/</a>)</li>'
        . '</ul>'

        . '<h4>Additional Rights for California Residents (CCPA/CPRA):</h4>'
        . '<ul>'
        . '<li>Right to know what personal information is collected, used, shared, or sold</li>'
        . '<li>Right to delete personal information</li>'
        . '<li>Right to opt-out of the sale or sharing of personal information</li>'
        . '<li>Right to non-discrimination for exercising your rights</li>'
        . '</ul>'
        . '<p><em>Note: We do not sell your personal information. To exercise your CCPA rights, contact us at '
        . '<a href="mailto:info@simple2success.com">info@simple2success.com</a>.</em></p>'

        . '<h4>Additional Rights for Brazilian Residents (LGPD):</h4>'
        . '<p>Brazilian residents have rights equivalent to those under the GDPR, including the right to access, correct, delete, and port your data. '
        . 'Contact us at <a href="mailto:info@simple2success.com">info@simple2success.com</a> to exercise these rights.</p>'

        . '<p>To exercise any of your rights, please contact us at: '
        . '<a href="mailto:info@simple2success.com">info@simple2success.com</a><br>'
        . 'We will respond within 30 days (GDPR) or 45 days (CCPA) of receiving your request.</p>'

        . '<h3>9. Children\'s Privacy</h3>'
        . '<p>Our services are not directed to individuals under the age of 18. We do not knowingly collect personal data from minors. '
        . 'If you believe we have inadvertently collected data from a minor, please contact us immediately at '
        . '<a href="mailto:info@simple2success.com">info@simple2success.com</a>.</p>'

        . '<h3>10. Security</h3>'
        . '<p>We implement appropriate technical and organisational measures to protect your personal data against unauthorised access, '
        . 'alteration, disclosure, or destruction. These include password hashing (bcrypt), HTTPS encryption, and access controls.</p>'

        . '<h3>11. Changes to This Privacy Policy</h3>'
        . '<p>We may update this Privacy Policy from time to time. We will notify you of significant changes by posting the new policy '
        . 'on this page with an updated &ldquo;Last updated&rdquo; date. We encourage you to review this policy periodically.</p>'

        . '<h3>12. Contact Us</h3>'
        . '<p>If you have any questions about this Privacy Policy or our data practices, please contact us:</p>'
        . '<p>Marc-Philipp Knorr<br>'
        . 'E-mail: <a href="mailto:info@simple2success.com">info@simple2success.com</a><br>'
        . 'Address: Auf der Nachthut 3, 72534 Hayingen, Germany</p>';

    $ppEsc = mysqli_real_escape_string($link, $ppHtml);
    mysqli_query($link,
        "UPDATE legal_documents
         SET content_html='$ppEsc', title='Privacy Policy', version_number=2, updated_at=NOW()
         WHERE slug='privacy-policy' AND language_code='en' AND market_code='global'
           AND version_number < 2"
    );
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
