<?PHP
      //this file needs to be defined and then renamed to config.inc.php in order for the server to work
      //create user 'USER'@'localhost' IDENTIFIED BY 'PASSWORD';
      define('CFG_DB_HOST', 'localhost'); 
      define('CFG_DB_USER', '');
      define('CFG_DB_PASS', '');
      define('CFG_DB', '');
      //define('CFG_DB_DSN', '');   //optionally define full PDO DSN

      define('CFG_SITE_TITLE', 'Contractor\'s Work Log');
      define('CFG_BASE_URL', 'http://localhost/work_log/');
      define('CFG_USE_PHP_MAIL', false);
      define('CFG_EMAIL_FROM_HEADER', 'From: Contractor\'s Work Log <noreply@cworklog.com>');
      define('CFG_INSERT_MOCK_COMPANY_UPON_REGISTRATION', true);
      
      define('CFG_STRIPE_APIKEY', "<PUT-YOUR-APIKEY-HERE>");


      define('CWL_ENVIRONMENT', 'development'); ///or production 

      define('CWL_VERBOSE_DEBUGGING', false);   //verbose debugging will show SQL statements, etc
      

      $cwl_config['domain'] = 'cworklog.com';

      /* TO ENABLE EMAIL USING SMTP UNCOMMENT BELOW AND FILL
      $cwl_config['smtp'] = array(
            'server'=>'smtp.gmail.com', 
            'port'=> 465, 
            'protocol'=>'ssl',
            'username'=> '', 
            'password'=> '', 
      );
      */
