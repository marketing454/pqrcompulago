<?php

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Clase Mailer - Gestión de Correos Profesionales
 * Diseñada para Compulago con estándares premium.
 */

class Mailer {
    
    private static $primary_color = "#6E8800";
    private static $accent_color = "#B0C80B";
    private static $logo_url = "https://compulago.b-cdn.net/Logo-Compulago/SVG/SVG%20LOGO%20BLACK.svg";

    public static function sendTicketConfirmation($to_email, $client_name, $radicado) {
        $safe_client_name = htmlspecialchars($client_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $subject = "✅ Tu solicitud Compulago ha sido recibida: $radicado";
        
        $body = self::getTemplate("¡Gracias por contactarnos!", "
            <p>Hola <strong>$safe_client_name</strong>,</p>
            <p>Hemos recibido tu solicitud correctamente. Nuestro equipo de soporte ha asignado el siguiente número de radicado para tu seguimiento:</p>
            
            <div style='background: #fcfef7; border: 2px dashed " . self::$accent_color . "; padding: 25px; border-radius: 12px; text-align: center; margin: 25px 0;'>
                <span style='display: block; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; font-weight: 700;'>Número de Radicado</span>
                <span style='font-size: 32px; font-weight: 800; color: " . self::$primary_color . "; font-family: monospace;'>$radicado</span>
            </div>
            
            <p>Estaremos analizando tu caso y te responderemos a la brevedad posible a través de este correo electrónico.</p>
            <p style='margin-top: 30px;'>Atentamente,<br><strong>Equipo de Experiencia al Cliente Compulago</strong></p>
        ", $radicado);
        
        return self::send($to_email, $subject, $body);
    }

    public static function sendTicketReply($to_email, $client_name, $radicado, $reply_message) {
        $safe_client_name = htmlspecialchars($client_name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $subject = "🔔 Respuesta a tu PQR Compulago [$radicado]";
        
        $body = self::getTemplate("Actualización de tu Solicitud", "
            <p>Hola <strong>$safe_client_name</strong>,</p>
            <p>Un especialista de nuestro equipo de soporte ha respondido a tu requerimiento con radicado <strong style='color: " . self::$primary_color . "; font-family: monospace;'>$radicado</strong>:</p>
            
            <div style='background: #f8fafc; border-left: 5px solid " . self::$accent_color . "; padding: 20px 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin: 25px 0; font-size: 15px; line-height: 1.7; color: #1e293b;'>
                " . nl2br(htmlspecialchars($reply_message)) . "
            </div>
            
            <p>Si deseas añadir información adicional o responder a este mensaje, puedes responder directamente a este correo electrónico.</p>
            <p style='margin-top: 30px;'>Atentamente,<br><strong>Centro de Atención y Garantías Compulago</strong></p>
        ", $radicado);

        return self::send($to_email, $subject, $body);
    }

    private static function getTemplate($title, $content, $radicado) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap');
            </style>
        </head>
        <body style='margin: 0; padding: 0; background-color: #f8faf9; font-family: \"Plus Jakarta Sans\", Arial, sans-serif;'>
            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f8faf9; padding: 40px 0;'>
                <tr>
                    <td align='center'>
                        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                            <!-- Header -->
                            <tr>
                                <td align='center' style='background: linear-gradient(135deg, #b0c80b 0%, #6e8800 100%); padding: 35px 20px;'>
                                    <h2 style='color: #ffffff; font-size: 24px; font-weight: 800; margin: 0; letter-spacing: -0.5px;'>Compulago<span style='color: #f7fee7;'>PQR</span></h2>
                                    <p style='color: #ffffff; font-size: 14px; margin: 8px 0 0; opacity: 0.95;'>$title</p>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style='padding: 35px; color: #334155; font-size: 15px; line-height: 1.6;'>
                                    $content
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td align='center' style='padding: 25px 30px; background-color: #f8fafc; border-top: 1px solid #edf2f7; color: #64748b; font-size: 12px;'>
                                    <p style='margin: 0 0 6px; font-weight: 600;'>&copy; " . date('Y') . " Compulago S.A.S. | Todos los derechos reservados</p>
                                    <p style='margin: 0;'>Radicado oficial: <strong style='color: #6e8800; font-family: monospace;'>$radicado</strong></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";
    }

    private static function send($to, $subject, $message) {
        if (preg_match('/[\r\n]/', $to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            error_log('Rejected invalid email recipient.');
            return false;
        }

        if (SMTP_PASS === '' || !filter_var(MAIL_FROM_EMAIL, FILTER_VALIDATE_EMAIL)) {
            error_log('SMTP is not configured with a valid Resend credential and sender.');
            return false;
        }

        try {
            $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = SMTP_HOST;
            $mailer->Port = SMTP_PORT;
            $mailer->SMTPAuth = true;
            $mailer->Username = SMTP_USER;
            $mailer->Password = SMTP_PASS;
            $mailer->SMTPSecure = SMTP_SECURE === 'ssl'
                ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->Timeout = 10;
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mailer->addAddress($to);

            if (filter_var(MAIL_REPLY_TO, FILTER_VALIDATE_EMAIL)) {
                $mailer->addReplyTo(MAIL_REPLY_TO);
            }

            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $message;
            $mailer->AltBody = html_entity_decode(strip_tags($message), ENT_QUOTES | ENT_HTML5, 'UTF-8');

            return $mailer->send();
        } catch (Throwable $e) {
            error_log('Resend SMTP delivery failed: ' . $e->getMessage());
            return false;
        }
    }
}
?>
