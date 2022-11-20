<?php
// Script qui est appelé lors de l'envoi d'un mail (afin de pouvoir lancer l'envoi de mail en arrière plan
// au lieu de bloquer l'utilisateur sur une page dont le chargement est très long)

use \PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use \PHPMailer\PHPMailer\Exception;

require_once('PHPMailer/src/Exception.php');
require_once('PHPMailer/src/PHPMailer.php');
require_once('PHPMailer/src/SMTP.php');

// Fichier en .gitignore contenant le mot de passe de l'email
require_once('config/emailconfig.php');

// Paramètres PHPMailer

$mail = new PHPMailer();
$mail->isSMTP();
$mail->SMTPDebug = SMTP::DEBUG_OFF;

$mail->Host = 'smtp.gmail.com';
$mail->Port = 465;
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->SMTPAuth = true;

$mail->Username = 'contact.mealrush@gmail.com';
$mail->Password = $EMAIL_PASS;

$mail->setFrom('contact.mealrush@gmail.com', 'MealRush');
$mail->addReplyTo('contact.mealrush@gmail.com', 'MealRush');
$mail->isHTML(true);
$mail->CharSet = 'UTF-8';

function EnvoyerMail($mail, $sujet, $contenu, $destinataire)
{
    $mail->addAddress($destinataire);
    $mail->Subject = $sujet;
    $mail->Body = $contenu;

    if (!$mail->send()) {
        array_push($erreurs, "Echec de l'envoi du mail " . $mail->ErrorInfo);
    }
}

EnvoyerMail($mail, $_SERVER['argv'][1], $_SERVER['argv'][2], $_SERVER['argv'][3]);
