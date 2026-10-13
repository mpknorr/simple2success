<?php
/** New keys keep old, unreviewed database copy out of behavioural reminders. */
function s2sFollowupBody(string $text, string $cta): string {
    return '<html><body style="font-family:Arial,sans-serif;line-height:1.6;color:#222;max-width:600px;margin:24px auto;padding:16px;">'
        . '<p>Hi {{name}},</p>' . $text
        . '<p><a href="{{cta_url}}">' . $cta . '</a></p>'
        . '<p>Your Simple2Success Eagle Team</p></body></html>';
}
function s2sTriggerTemplates(): array {
    return [
        'trigger_setup_help_v2' => [
            'subject' => 'Already registered? Save your Partner ID',
            'body' => s2sFollowupBody('<p>If you have completed your partner registration, copy your Partner ID from your confirmation email or partner account and save it in Step 2.</p><p>Your setup is complete once that number is saved. Enter only the ID, never your partner password. If registration is still open or you need help, the start page has the instructions and a support link.</p>', 'Continue my setup'),
        ],
        'trigger_next_action_v2' => [
            'subject' => 'Your PM Ref-ID is saved — continue with Step 3',
            'body' => s2sFollowupBody('<p>Your PM Ref-ID is saved. Steps 1 and 2 are complete. Continue with Step 3: use the traffic guide to plan a measured first campaign with your personal link.</p><p>Then follow Step 4 for your product start and Step 5 to repeat and review. If you have already started, continue with the next open step. Check package terms and your budget before ordering. Income is not guaranteed.</p>', 'Review my next steps'),
        ],
        'trigger_overview_v2' => [
            'subject' => 'A clear overview before your next step',
            'body' => s2sFollowupBody('<p>Your start page explains the partner registration and where to save your Partner ID afterwards.</p><p>Take time to understand the process and any later costs before deciding. You can watch the overview, read the instructions, or use the support link if something is unclear.</p>', 'View my start page'),
        ],
    ];
}
