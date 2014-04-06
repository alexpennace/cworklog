<?php
class cwl_email{
   public static function setup($log = false){
   	    require_once(__DIR__.'/config.inc.php');
   	  	require_once(__DIR__.'/Swift-5.1.0/lib/swift_required.php');

	    if (empty($cwl_config['smtp'])){
	    	$transport = Swift_MailTransport::newInstance();
	 	}
	 	else{
	    	$transport = Swift_SmtpTransport::newInstance($cwl_config['smtp']['server'], $cwl_config['smtp']['port'], $cwl_config['smtp']['protocol'])
			  ->setUsername($cwl_config['smtp']['username'])
			  ->setPassword($cwl_config['smtp']['password']);
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

		return array($mailer, $message, $logger);
	}
}

  