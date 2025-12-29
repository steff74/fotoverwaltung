<?php
/**
 * E-Mail-Versand via PHPMailer
 */

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private array $mailConfig;
    private string $siteTitle;

    public function __construct(array $config)
    {
        $this->mailConfig = $config['mail'] ?? [];
        $this->siteTitle = $config['site']['title'] ?? 'Fotoverwaltung';
    }

    /**
     * Testet die SMTP-Verbindung ohne E-Mail zu senden
     * Gibt ['success' => bool, 'message' => string] zurück
     */
    public function testConnection(): array
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $this->mailConfig['smtp_host'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $this->mailConfig['smtp_user'] ?? '';
            $mail->Password = $this->mailConfig['smtp_password'] ?? '';
            $mail->SMTPSecure = $this->mailConfig['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->mailConfig['smtp_port'] ?? 587;
            $mail->Timeout = 10;

            # Verbindung testen
            if ($mail->smtpConnect()) {
                $mail->smtpClose();
                return [
                    'success' => true,
                    'message' => 'SMTP-Verbindung erfolgreich zu ' . $this->mailConfig['smtp_host'] . ':' . $this->mailConfig['smtp_port']
                ];
            }

            return [
                'success' => false,
                'message' => 'Verbindung fehlgeschlagen: ' . $mail->ErrorInfo
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Fehler: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Sendet eine Passwort-Reset-E-Mail
     */
    public function sendPasswordReset(string $toEmail, string $token, string $baseUrl): bool
    {
        $resetUrl = $baseUrl . '/reset?token=' . $token;

        $subject = 'Passwort zurücksetzen – ' . $this->siteTitle;

        $htmlBody = $this->getResetEmailHtml($resetUrl, $this->siteTitle);
        $textBody = $this->getResetEmailText($resetUrl, $this->siteTitle);

        return $this->send($toEmail, $subject, $htmlBody, $textBody);
    }

    /**
     * Sendet eine E-Mail via SMTP
     */
    private function send(string $to, string $subject, string $htmlBody, string $textBody = ''): bool
    {
        $mail = new PHPMailer(true);

        try {
            # SMTP-Konfiguration
            $mail->isSMTP();
            $mail->Host = $this->mailConfig['smtp_host'] ?? '';
            $mail->SMTPAuth = true;
            $mail->Username = $this->mailConfig['smtp_user'] ?? '';
            $mail->Password = $this->mailConfig['smtp_password'] ?? '';
            $mail->SMTPSecure = $this->mailConfig['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $this->mailConfig['smtp_port'] ?? 587;
            $mail->CharSet = 'UTF-8';

            # Absender und Empfänger
            $mail->setFrom(
                $this->mailConfig['from_email'] ?? 'noreply@example.com',
                $this->mailConfig['from_name'] ?? 'Fotoverwaltung'
            );
            $mail->addAddress($to);

            # Inhalt
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody;

            $mail->send();
            return true;

        } catch (Exception $e) {
            # Fehler loggen (in Produktion ggf. in Logfile schreiben)
            error_log('Mailer Error: ' . $mail->ErrorInfo);
            return false;
        }
    }

    /**
     * HTML-Template für Reset-E-Mail
     */
    private function getResetEmailHtml(string $resetUrl, string $siteName): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Passwort zurücksetzen</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <h2 style="color: #2c5530;">Passwort zurücksetzen</h2>

    <p>Du hast angefordert, dein Passwort für <strong>{$siteName}</strong> zurückzusetzen.</p>

    <p>Klicke auf den folgenden Link, um ein neues Passwort zu setzen:</p>

    <p style="margin: 30px 0;">
        <a href="{$resetUrl}"
           style="background-color: #2c5530; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">
            Passwort zurücksetzen
        </a>
    </p>

    <p style="color: #666; font-size: 14px;">
        Dieser Link ist <strong>1 Stunde</strong> gültig.
    </p>

    <p style="color: #666; font-size: 14px;">
        Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.
        Dein Passwort bleibt unverändert.
    </p>

    <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">

    <p style="color: #999; font-size: 12px;">
        Falls der Button nicht funktioniert, kopiere diesen Link in deinen Browser:<br>
        <a href="{$resetUrl}" style="color: #2c5530;">{$resetUrl}</a>
    </p>
</body>
</html>
HTML;
    }

    /**
     * Text-Template für Reset-E-Mail (Fallback)
     */
    private function getResetEmailText(string $resetUrl, string $siteName): string
    {
        return <<<TEXT
Passwort zurücksetzen – {$siteName}

Du hast angefordert, dein Passwort zurückzusetzen.

Öffne diesen Link, um ein neues Passwort zu setzen:
{$resetUrl}

Dieser Link ist 1 Stunde gültig.

Falls du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.
Dein Passwort bleibt unverändert.
TEXT;
    }
}
