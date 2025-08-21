<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader (created by composer, not included with PHPMailer)
require 'libs/vendor/autoload.php';

//Create an instance; passing `true` enables exceptions
$mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                     
    $mail->isSMTP();                                            
    $mail->Host = 'smtp.gmail.com';                     
    $mail->SMTPAuth = true;                                  
    $mail->Username = 'sofiaherrerazuniga@gmail.com';                    
    $mail->Password = 'snsdleokyyhkgibz';                               
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            
    $mail->Port = 465;                                    
    //Recipients
    $mail->setFrom($_POST["email"], $_POST["nombre"]);
    $mail->addAddress('sofiaherrerazuniga@gmail.com');

    //Content
    $mail->isHTML(true);                                 
    $mail->Subject = "Nuevo mensaje de contacto de " . $_POST["nombre"];
    $mail->Body = "Nombre: " . $_POST["nombre"] . "<br>Email: " . $_POST["email"] . "<br>Teléfono: " . $_POST["telefono"] . "<br>Mensaje: " . $_POST["mensaje"];

   $mail->send();
    echo "<script>alert('✅ Mensaje enviado correctamente.'); window.location.href='index.php';</script>";
} catch (Exception $e) {
    echo "❌ Error al enviar el mensaje: {$mail->ErrorInfo}";
}