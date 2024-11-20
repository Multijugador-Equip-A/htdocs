<?php
//PHPMailer
//https://www.youtube.com/watch?v=fSfNTACbplA
//https://www.youtube.com/watch?v=g78MNlDQkys
use PHPMailer/PHPMailer/Exeption;
use PHPMailer/PHPMailer/PHPMailer;
use PHPMailer/PHPMailer/SMTP;

require 'PHPMailer/src/Exeption.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require 'config.php';

function sendMail($email, $subject, $message){
    $mail = new PHPMailer(true);

    $mail->isMTP();

    $mail->SMTPAuth = true;

    $mail->Host = MAILHOST;

    $mail->Username = USERNAME;

    $mail->Password = PASSWORD;

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;

    $mail->setFrom(SEND_FROM, SEND_FROM_NAME);

    $mail->addAdress($email);

    $mail->addReplayTo(REPLY_TO, REPLY_TO_NAME);

    $mail->IsHTML(true);

    $mail->Subject = $subject;

    $mail->Body = $message;

    $mail->AltBody = $message;

    if(!mail->send()){
        return "Email not send.";
    }else{
        return "success";
    }
}