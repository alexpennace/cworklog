<?PHP
  //create user 'php'@'localhost' IDENTIFIED BY '18JdhGlIo9xGw3';
  mysql_connect('localhost', 'php', '18JdhGlIo9xGw3');
  mysql_select_db('work_log_db');
  

  $DBH = new PDO('mysql:host=localhost;dbname=work_log_db', 'php', '18JdhGlIo9xGw3');
?>
