<?php
      //$cfg['admin_email'] = 'ADMIN_EMAIL@example.com';

      //this file needs to be defined and then renamed to config.inc.php in order for the server to work
      //create user 'USER'@'localhost' IDENTIFIED BY 'PASSWORD';
      $cfg['db_host'] = 'localhost';
      $cfg['db_user'] = 'dbuser';
      $cfg['db_pass'] = 'dbpass';
      $cfg['db']      = 'work_log';
      //$cfg['db_dsn'] = '';   //optionally define full PDO DSN

      $cfg['site_title'] = "Contractor's Work Log";
      
      $cfg['use_php_mail'] = false;
      $cfg['email_from_header'] = 'From: Contractor\'s Work Log <noreply@cworklog.com>';
      $cfg['insert_mock_company_upon_registration'] = true;
      
      //$cfg['stripe_apikey'] = "<PUT-YOUR-APIKEY-HERE>";

      $cfg['environment'] = 'development'; ///or production 

      $cfg['verbose_debugging'] = false;   //verbose debugging will show SQL statements, etc
      
      $cfg['domain'] = 'cworklog.com';

      $cfg['default_from_email'] = 'noreply@'.$cfg['domain'];

      //DO NOT INCLUDE TRAILING SLASH SO VERIFY LINKS WILL WORK ETC
      $cfg['base_url'] = 'http://cworklog.com';

      /* TO ENABLE EMAIL USING SMTP UNCOMMENT BELOW AND FILL
      $cfg['smtp'] = array(
            'server'=>'smtp.gmail.com', 
            'port'=> 465, 
            'protocol'=>'ssl',
            'username'=> '', 
            'password'=> '', 
      );
      */

