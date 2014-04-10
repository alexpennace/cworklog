<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(__DIR__.'/../lib/cwl_email.class.php');

list($mailer, $message, $logger) = cwl_email::setup(true);

$message->setSubject('Contractor\'s Work Log - Test Email');
$message->setBody("This is a <b>test</b> email", 'text/html');

 
$message->setTo(array($_GET['email']));
$message->setBcc(array('cworklog@gmail.com'));

$result = $mailer->send($message);

if ($mailer->send($message, $failures)){
		echo 'Sent!';
}else{
		echo 'Failed to send!'.$failures;
}