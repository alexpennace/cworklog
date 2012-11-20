<?PHP
  require_once(dirname(__FILE__).'/config.inc.php');
  
  //connect to database using old mysql_ functions and also new PDO idea (to help with escaping etc)
  mysql_connect(CFG_DB_HOST, CFG_DB_USER, '');
  mysql_select_db(CFG_DB);

  $DBH = new PDO('mysql:host='.CFG_DB_HOST.';dbname='.CFG_DB, CFG_DB_USER, CFG_DB_PASS);
?>
