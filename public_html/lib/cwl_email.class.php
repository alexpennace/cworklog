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
 
class cwl_email{
   public static function setup($log = false){
   		require_once(__DIR__.'/Site.class.php');
   	    $cfg = Site::cfg();

   	  	require_once(__DIR__.'/Swift-5.1.0/lib/swift_required.php');

	    if (empty($cfg['smtp'])){
	    	$transport = Swift_MailTransport::newInstance();
	 	}
	 	else{
	    	$transport = Swift_SmtpTransport::newInstance($cfg['smtp']['server'], $cfg['smtp']['port'], $cfg['smtp']['protocol'])
			  ->setUsername($cfg['smtp']['username'])
			  ->setPassword($cfg['smtp']['password']);
	    }
	    $mailer = Swift_Mailer::newInstance($transport);
	    
	    if ($log){
	    	// Or to use the Echo Logger
			$logger = new Swift_Plugins_Loggers_EchoLogger();
			$mailer->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));
        }else{
        	$logger = false;
        }


		$message = Swift_Message::newInstance();
		//by default use this from email address
        $message->setFrom('noreply@'.$cfg['domain']);
		return array($mailer, $message, $logger);
	}
}

  