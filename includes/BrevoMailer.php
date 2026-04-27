<?php
/**
 * BrevoMailer — zentraler E-Mail-Versand über Brevo Transactional API.
 * Ersetzt PHPMailer/SMTP in allen E-Mail-Dateien.
 */
class BrevoMailer
{
    private string $apiKey;
    private string $fromEmail;
    private string $fromName;
    private string $replyTo;

    private const API_URL = 'https://api.brevo.com/v3/smtp/email';
    private const TIMEOUT  = 15;

    public function __construct($link)
    {
        $this->apiKey    = $this->getSetting($link, 'brevo_api_key');
        $this->fromEmail = $this->getSetting($link, 'smtp_from_email') ?: 'noreply@simple2success.com';
        $this->fromName  = $this->getSetting($link, 'smtp_from_name')  ?: 'Simple2Success';
        $this->replyTo   = $this->getSetting($link, 'smtp_from_email') ?: 'noreply@simple2success.com';

        if (empty($this->apiKey)) {
            throw new \RuntimeException('Brevo API-Key fehlt (settings: brevo_api_key).');
        }
    }

    /**
     * E-Mail versenden.
     *
     * @param  string   $toEmail   Empfänger-E-Mail
     * @param  string   $toName    Empfänger-Name
     * @param  string   $subject   Betreff
     * @param  string   $htmlBody  HTML-Inhalt
     * @param  string[] $tags      Brevo-Tags (max. 10, je max. 50 Zeichen)
     * @param  array    $metadata  Wird NICHT im Webhook zurückgegeben —
     *                             nur zur Dokumentation / internem Logging.
     *                             Interne IDs immer in DB speichern!
     * @return string  Brevo messageId
     * @throws \RuntimeException bei API-Fehlern
     */
    public function sendEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        array  $tags     = [],
        array  $metadata = []
    ): string {
        $payload = [
            'sender'      => ['email' => $this->fromEmail, 'name' => $this->fromName],
            'to'          => [['email' => $toEmail, 'name' => $toName]],
            'replyTo'     => ['email' => $this->replyTo],
            'subject'     => $subject,
            'htmlContent' => $htmlBody,
        ];

        // Tags: Brevo erlaubt max. 10, Strings unter 50 Zeichen
        if (!empty($tags)) {
            $cleanTags = array_values(array_filter(
                array_map(fn($t) => substr((string)$t, 0, 50), $tags),
                fn($t) => $t !== ''
            ));
            $payload['tags'] = array_slice($cleanTags, 0, 10);
        }

        // params werden an Brevo übergeben (für Template-Variablen nützlich),
        // aber NICHT im Webhook zurückgegeben — messageId in DB ist der Anker.
        if (!empty($metadata)) {
            $payload['params'] = $metadata;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_TIMEOUT        => self::TIMEOUT,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'api-key: ' . $this->apiKey,   // API-Key nur im Header, nie geloggt
            ],
        ]);

        $body     = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            throw new \RuntimeException("Brevo cURL-Fehler: $curlErr");
        }

        $response = json_decode($body ?: '{}', true);

        // Brevo gibt HTTP 201 bei Erfolg zurück
        if ($httpCode !== 201) {
            $errMsg = $response['message'] ?? $response['code'] ?? 'Unbekannter Fehler';
            $preview = substr($body ?: '', 0, 300);
            throw new \RuntimeException("Brevo API HTTP $httpCode: $errMsg | Body: $preview");
        }

        $messageId = $response['messageId'] ?? '';
        if (empty($messageId)) {
            throw new \RuntimeException('Brevo: kein messageId in Response erhalten.');
        }

        return $messageId;
    }

    private function getSetting($link, string $key): string
    {
        $k   = mysqli_real_escape_string($link, $key);
        $res = mysqli_query($link, "SELECT setting_value FROM settings WHERE setting_key = '$k' LIMIT 1");
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ? (string)$row['setting_value'] : '';
    }
}
