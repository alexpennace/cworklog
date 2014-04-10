<?php
/**
 *  This file helps manage Membership and logins
 * 
 *  Coders/Contractors Work Log - A time tracking/invoicing app 
 *  Copyright (C) 2014 Jim A Kinsman (cworklog.com) relipse@gmail.com github.com/relipse 
 *
 *  LICENSES - GPL 3. (If you need a different commercial license please contact Jim) 
 *
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License.
 * 
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *  
 *   You should have received a copy of the GNU General Public License
 *   along with this program (gpl.txt).  If not, see <http://www.gnu.org/licenses/>.
 */

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