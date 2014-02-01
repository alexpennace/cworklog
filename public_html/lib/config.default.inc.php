<?PHP
      //create user 'DBUSER'@'localhost' IDENTIFIED BY 'DBPASS';
      define('CFG_DB_HOST', 'localhost'); 
      define('CFG_DB_USER', 'DBUSER');
      define('CFG_DB_PASS', 'DBPASS');
      define('CFG_DB', 'work_log_db');
      
      define('CFG_SITE_TITLE', 'Contractor\'s Work Log');
      define('CFG_BASE_URL', 'http://cworklog.com');
      define('CFG_USE_PHP_MAIL', true);
      define('CFG_EMAIL_FROM_HEADER', 'From: Contractor\'s Work Log <noreply@cworklog.com>');
      define('CFG_INSERT_MOCK_COMPANY_UPON_REGISTRATION', true);
      
      define('CFG_STRIPE_APIKEY', "sk_test_1s5RKWNH3xxnha61GNG9Rlqc");
