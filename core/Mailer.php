<?php
/**
 * Mailer — Envoi d'emails via PHPMailer/SMTP
 * Laboratoire Drouot
 */
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class Mailer {

    /**
     * Envoie un email. Retourne true/false selon le succès, et ne
     * lève jamais d'exception vers l'appelant : les erreurs SMTP ne
     * doivent jamais interrompre le flux applicatif (ex: création
     * de compte), juste être journalisées.
     */
    public static function send(string $to, string $subject, string $htmlBody, string $toName = ''): bool {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE; // 'tls' ou 'ssl'
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to, $toName);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            return true;

        } catch (PHPMailerException $e) {
            error_log('Mailer::send échec vers ' . $to . ' — ' . $mail->ErrorInfo);
            return false;
        }
    }
}