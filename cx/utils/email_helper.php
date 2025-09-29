<?php
/**
 * Email Helper para envío de correos usando PHPMailer
 * Configurado con las credenciales de Coomultiunion
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

/**
 * Envía un email usando PHPMailer con configuración SMTP real
 * 
 * @param string $to Email del destinatario
 * @param string $subject Asunto del email
 * @param string $body Contenido del email (HTML)
 * @param bool $isHTML Si el contenido es HTML
 * @return bool True si se envió correctamente, false en caso contrario
 */
function sendEmail($to, $subject, $body, $isHTML = true) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = 'mail.coomultiunion.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'no-reply@coomultiunion.com';
        $mail->Password = 'SB7KHyQZ9_E*';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Configuración de caracteres
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Recipients
        $mail->setFrom('no-reply@coomultiunion.com', 'Coomultiunion');
        $mail->addAddress($to);

        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body = $body;

        return $mail->send();
    } catch (Exception $e) {
        error_log("Error sending email: " . $e->getMessage());
        return false;
    }
}

/**
 * Función de respaldo para desarrollo local (sin SMTP)
 * Descomentar si necesitas simular emails en desarrollo
 */
/*
function sendEmailSimulated($to, $subject, $body, $isHTML = true) {
    error_log("=== EMAIL SIMULADO ===");
    error_log("Para: $to");
    error_log("Asunto: $subject");
    error_log("Contenido: " . substr($body, 0, 500) . "...");
    error_log("=====================");
    return true;
}
*/
?>
