<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'libs/vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = SMTP::DEBUG_OFF; 
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sofiaherrerazuniga@gmail.com';
    $mail->Password = 'snsdleokyyhkgibz'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    $mail->setFrom('sofiaherrerazuniga@gmail.com', 'Formulario Web');

    if (!empty($_POST["email"]) && filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
        $mail->addReplyTo($_POST["email"], $_POST["nombre"] ?? 'Usuario');
    }

    $mail->addAddress('sofiaherrerazuniga@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = "Nuevo mensaje de contacto de " . ($_POST["nombre"] ?? 'Sin nombre');
    $mail->Body = "Nombre: " . ($_POST["nombre"] ?? '') . "<br>Email: " . ($_POST["email"] ?? '') . "<br>Teléfono: " . ($_POST["telefono"] ?? '') . "<br>Mensaje: " . ($_POST["mensaje"] ?? '');

    $mail->send();
    echo "<script>alert('✅ Mensaje enviado correctamente.'); window.location.href='index.php';</script>";
} catch (Exception $e) {
    echo "❌ Error al enviar el mensaje: {$mail->ErrorInfo}";
}
