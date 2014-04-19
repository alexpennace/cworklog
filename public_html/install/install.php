<?php  
/**
 * This file is used for a somewhat automated install of cworklog
 */

die('Automatic installation is disabled, Manually you need to create the /config/config.inc.php file and then run install.schema.sql');

error_reporting(E_ALL);
ini_set('display_errors', 1);



//a variety of flags get set depending on the installation
  $flags = array(
       'NO_CONFIG_FILE_FOUND'=>null,
       'INVALID_CONFIG_FILE'=>null,
       'NO_MOCK_CONFIG_FILE_FOUND'=>null,
       'MOCK_COPY_FAIL'=>null,
       'DB_ERROR_NOCONNECT'=>null,
       'DB_NO_INSTALL_SCHEMA'=>null,
       'DB_ERROR_NOTFOUND_TABLES'=>null,
       'LOGGED_IN_NAME'=>null,   //if someone is logged in with the browser it will show up here
       'FAULTY_INSTALL'=>null,
       'INSTALL_COUNT'=>0,
       'INSTALL_MULTIPLE'=>false,
       'ALREADY_INSTALLED_INFO'=>false,
       'IS_UPGRADE_OR_REPAIR'=>null,
       'EXTRA_ERROR_MSG'=>'',
       'ALLOW_REPLACE_CONFIG_FILE'=>null, 
  );

  define("CWL_CONFIG_FILE", dirname(__FILE__).'/../lib/config.inc.php');
  define("CWL_DEFAULT_CONFIG_FILE", dirname(__FILE__).'/../lib/config.default.inc.php');
  define("CWL_DB_INC_FILE", dirname(__FILE__).'/../lib/db.inc.php');
  define("DOT", dirname(__FILE__));

if (isset($_GET['ajax_db_check'])){
      define('CFG_DB_HOST', 'localhost');
      define('CFG_DB_USER', $_REQUEST['CFG_DB_USER']);
      define('CFG_DB_PASS', $_REQUEST['CFG_DB_PASS']);
      define('CFG_DB', $_REQUEST['CFG_DB']);
      try{
         $cfg['no_inc_config'] = true;
         include_once(CWL_DB_INC_FILE);
         $result['DB_ERROR_NOCONNECT'] = false;
      }catch(Exception $e){
         $result['DB_ERROR_NOCONNECT'] = true;
      }
      die(json_encode($result));
}

include_once(dirname(__FILE__).'/installhelper.class.php');

 $tables_check_base = array('install','company','user','work_log','time_log','note_log','files_log');

  $VALS = array();
  function val($key, $default=''){
      global $VALS;
      if (isset($VALS[$key])){
         return $VALS[$key];
      }else{
        return $default;
      }
  }

  if ( file_exists(CWL_CONFIG_FILE) ){
     try{
        $VALS = array();
        include(CWL_CONFIG_FILE);
        $VALS = array('CFG_DB_HOST'=>CFG_DB_HOST, 
            'CFG_DB_USER'=>CFG_DB_USER, 'CFG_DB_PASS'=>CFG_DB_PASS,'CFG_DB'=>CFG_DB, 
             'CFG_SITE_TITLE'=>CFG_SITE_TITLE, 
             'CFG_BASE_URL'=>CFG_BASE_URL, 
             'CFG_USE_PHP_MAIL'=>CFG_USE_PHP_MAIL, 'CFG_EMAIL_FROM_HEADER'=>CFG_EMAIL_FROM_HEADER,
             'CFG_INSERT_MOCK_COMPANY_UPON_REGISTRATION'=>CFG_INSERT_MOCK_COMPANY_UPON_REGISTRATION,
       );
     }catch(Exception $e){
        $VALS = array();
        $flags['INVALID_CONFIG_FILE'] = true;
     }

     //if doing a db check we will modify db values to whatever sent via ajax

     try{

        include_once(CWL_DB_INC_FILE);
        
        //lets grab all tables from the information schema and make sure they match
        $prep = $DBH->prepare("SELECT table_name
          FROM INFORMATION_SCHEMA.TABLES
          WHERE table_schema = :DB");

       $prep->execute(array('DB'=>val('CFG_DB')));
       $tables = $prep->fetchAll(PDO::FETCH_ASSOC);

       //at this point, we have successfully connected to the database in the given config file
       //lets check if the user clicked the big yellow "Install Database" button
       if (!empty($_POST['install_db'])){
            list($statements, $errors) = 
                  installhelper::import(dirname(__FILE__).'/install.schema.sql', $DBH);
            if (!empty($errors)){
                $flags['INSTALL_DB_IMPORT_ERRORS'] = $errors;
            }else{
                $flags['INSTALL_DB_COMMIT'] = 'DISABLE_BECAUSE_OF_DEBUG_MODE';
            }
       }

       $tables_not_found = array();
       foreach($tables_check_base as $table){
          $found = false;
          foreach($tables as $row){
              if ($table == $row['table_name']){
                  $found = true;
                  break;
              }
          }
          if (!$found){
              $tables_not_found[] = $table;
          }
       }

       if (!empty($tables_not_found)){
          $flags['DB_ERROR_NOTFOUND_TABLES'] = implode(' ', $tables_not_found);
       }else{
          $flags['DB_ERROR_NOTFOUND_TABLES'] = false;

          $prep = $DBH->prepare("SELECT * FROM install ORDER BY date_installed DESC");

          $prep->execute(array('DB'=>$VALS['CFG_DB']));
          $installs = $prep->fetchAll(PDO::FETCH_ASSOC);

          $flags['INSTALL_COUNT'] = count($installs);
          if ($flags['INSTALL_COUNT'] == 0){
            $flags['ALREADY_INSTALLED_INFO'] = false;
          }else{ //already installed.

            $flags['ALREADY_INSTALLED_INFO'] = $installs[0];
            if ($flags['INSTALL_COUNT'] >= 2){
                $flags['INSTALL_MULTIPLE'] = true;
            }
          }
       }

     }catch(Exception $e){
        $flags['DB_ERROR_NOCONNECT'] = true;
        
     }

     try{
        include_once(DOT.'/../lib/Members.class.php');
        Members::SessionAllowLogin();
        $flags['LOGGED_IN_NAME'] = Members::LoggedInShortName();
        $flags['FAULTY_INSTALL'] = false;
     }catch(Exception $e){
        $flags['FAULTY_INSTALL'] = true;
        $flags['EXTRA_ERROR_MSG'] = $e->getMessage();
     }

  }else{ //initial config file does not exist
     $flags['DB_ERROR_NOCONNECT'] = true;
     $flags['NO_CONFIG_FILE_FOUND'] = true;

  //   //try copying default config file over
  //   $flags['MOCK_COPY_FAIL'] = !copy(CWL_DEFAULT_CONFIG_FILE, CWL_CONFIG_FILE);

  //   //if the copy above succeeded, then it should flag false (but if no permission)
  //   $flags['NO_CONFIG_FILE_FOUND'] = !file_exists(CWL_CONFIG_FILE);

  //   try{
  //       $VALS = include(CWL_CONFIG_FILE);
  //    }catch(Exception $e){
  //       $VALS = array();
  //       $flags['INVALID_CONFIG_FILE'] = true;
  //    }
  }//end else if (config file does not exist)
   
  if (!file_exists(DOT.'/install.schema.sql') ){
     $flags['DB_NO_INSTALL_SCHEMA'] = true;
  }
?>
<!doctype html>
<html>
<head>
<title>CWorkLog Install Kit</title>
<link rel="stylesheet" href="style.css" type="text/css" media="screen">
<link rel="stylesheet" href="install.css" type="text/css" media="screen">
<script src="../js/jquery-1.11.0.min.js" type="text/javascript" charset="utf-8"></script>
<script src="../js/ace-builds/src-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>
</head>
<body>
<div id="header-wrapper">
      <div id="header">
          <img class="logo" src="../betarelease_images/logo.png" title="Contractor's WorkLog" 
          alt="Contractor's WorkLog">
          <div class="cotitle">Installation</div>
          <img class="beta" src="../betarelease_images/it's_beta.png">
        </div>
</div>
<div id="content">
<form method="POST" action="install.php">
<h3>Install/Upgrade/Repair Checklist</h3>
<ul id="install_checklist" class="checklist">
<?php
if (!empty($_POST['install_db'])){

   if (empty($flags['INSTALL_DB_IMPORT_ERRORS']) ){
        ?><li class="success">
             You have successfully ran the database install (install.schema.sql) which should undergo repairs and installs.
        </li><?php  
   }else{
      ?><li class="error">Some errors occurred with the install.schema.sql, you may want to try and import this manually using mysqldump or phpmyadmin or something. Details: <pre><?=print_r($flags['INSTALL_DB_IMPORT_ERRORS'])?></pre></li>
      <?php 
   }
}

if ( !empty($flags['EXTRA_ERROR_MSG']) ){
    ?><li class="error"><?=$flags['EXTRA_ERROR_MSG']?></li>
    <?php 
}

if ( PHP_VERSION_ID <= 50400){
    ?><li class="error">You are running PHP Version:  <span title="<?=PHP_VERSION_ID?>"><?=PHP_VERSION?></span> which is not supported.</li><?php
}else{
  ?><li class="success">
     PHP Version: <span title="<?=PHP_VERSION_ID?>"><?=PHP_VERSION?></span> supported.
   </li><?php  
}


if ( !empty($flags['ALREADY_INSTALLED_INFO']) ){
    ?><li class="error">Already installed. <?=json_encode($flags['ALREADY_INSTALLED_INFO']);?></li><?php
}

if ( !empty($flags['NO_CONFIG_FILE_FOUND']) ){
    $flags['ALLOW_REPLACE_CONFIG_FILE'] = 'create';
    ?><li class="error">Config file <b>../lib/config.inc.php does not exist.</li><?php

}else{ 
  //config file exists!
  if ( !empty($flags['INVALID_CONFIG_FILE']) ){
       $flags['ALLOW_REPLACE_CONFIG_FILE'] = 'replace';
      ?><li class="error">Config file <b>../lib/config.inc.php</b> is invalid. Please Fix.</li><?php
  }else{
    ?><li class="success">
       Config file <b>../lib/config.inc.php</b> exists.
     </li><?php  
  }
}


if ( !empty($flags['FAULTY_INSTALL']) ){
    ?><li class="error">Your installation is corrupt, or there is a syntax error,
     please get the latest version.</li>
    <?php
}

if ( !empty($flags['LOGGED_IN_NAME']) ){
    $flags['IS_UPGRADE_OR_REPAIR'] = true;
    ?><li class="warning">It appears you are already logged in as <b><?=$flags['LOGGED_IN_NAME']?></b> (<a href="../index.php?logout=1" target="_blank">logout</a>) <br>
    Which most likely means you are doing an upgrade or repair instead of a first-time install.
    </li><?php
}

if ( !empty($flags['DB_NO_INSTALL_SCHEMA']) ){
    ?><li class="error">Error: install.schema.sql does not exist. This is fine if your database tables are already installed properly, but usually means your /install/ directory is corrupt.
    </li><?php
}


if ( !empty($flags['DB_ERROR_NOCONNECT']) ) {
  ?>  
   <li class="error">There is no connectivity to the database.</li>
  <?php
}else {
   ?><li class="success">
      The database was connected to have been verified.
   </li><?php

   if ( !empty($flags['DB_ERROR_NOTFOUND_TABLES']) ){
    ?><li class="error">
    The database appears to be corrupt and is missing some tables: <?=$flags['DB_ERROR_NOTFOUND_TABLES']?>
    Running the install should repair these tables.
    </li><?php
    }else{
      ?><li class="success">
          Database Tables are already installed (<?=implode(',',$tables_check_base)?>)
       </li><?php  
    }
}
?>
</ul>
<?php
//display some of the configuration options
if (empty($VALS['CFG_EMAIL_FROM_HEADER'])){
    $VALS['CFG_EMAIL_FROM_HEADER'] = "From: Contractor's Work Log <noreply@cworklog.com>";
}
?>
<div class="round_box config_box">
<div class="header">
Config File Helper Form <p class="hint small">Begin typing values and we will generate a config file for you</p>
</div>
    <div class="body">

       <label for="CFG_DB_USER">Database User:</label> 
         <input type="text" id="CFG_DB_USER" name="CFG_DB_USER" value="">
         <br>
   <label for="CFG_DB_PASS">Database User's Password</label>
        <input type="password" id="CFG_DB_PASS" name="CFG_DB_PASS" value="">
        <br>
       <label for="CFG_DB">Database:</label> 
      <input type="text" id="CFG_DB" name="CFG_DB" value="<?=val('CFG_DB')?>">
      <div id="dbstatus" class="question"></div>

      <label for="CFG_SITE_TITLE">Site Title:</label> 
      <input type="text" id="CFG_SITE_TITLE" name="CFG_SITE_TITLE" value="<?=val('CFG_SITE_TITLE')?>">
      <br>
      <label for="CFG_BASE_URL">Base Url:</label> 
      <input type="text" id="CFG_BASE_URL" name="CFG_BASE_URL" value="<?=val('CFG_BASE_URL')?>">  

      <br>
      <label for="CFG_EMAIL_FROM_HEADER">Email From Header:</label> 
      <input type="text" id="CFG_EMAIL_FROM_HEADER" name="CFG_EMAIL_FROM_HEADER" value="<?=val('CFG_EMAIL_FROM_HEADER')?>" placeholder="From: Contractor's Work Log <noreply@cworklog.com>"> 
      <br>
       <label for="CFG_USE_PHP_MAIL"><input id="CFG_USE_PHP_MAIL" type="checkbox" name="CFG_USE_PHP_MAIL" value="1" <?=!empty(val('CFG_USE_PHP_MAIL'))?'checked="checked" ':''?> >
           Email enabled on registration (uses php mail())</label>   
      <br>
       <label title="Recommended to help first-time users." for="CFG_INSERT_MOCK_COMPANY_UPON_REGISTRATION"><input id="CFG_INSERT_MOCK_COMPANY_UPON_REGISTRATION" type="checkbox" name="CFG_INSERT_MOCK_COMPANY_UPON_REGISTRATION" value="1" <?=!empty(val('CFG_INSERT_MOCK_COMPANY_UPON_REGISTRATION'))?'checked="checked" ':''?> >
           Insert a Mock-Company.</label>          
    </div>
</div>

<div class="round_box config_file_box">
<div class="header">
/lib/config.inc.php
</div>
    <div class="body">
    <div id="divaceconfigfile">
    </div>
    <textarea id="config_inc_file_gen">
    </textarea>
    </div>
</div>
 <?php
         if (!empty($flags['ALLOW_REPLACE_CONFIG_FILE'])){
            ?><br><button class="create_config_file" type="submit" name="create_config_file" value="<?=$flags['ALLOW_REPLACE_CONFIG_FILE']?>"><?=ucfirst($flags['ALLOW_REPLACE_CONFIG_FILE'])?> config file</button><?php 
         }
      ?>
<?php 
   if (empty($flags['DB_ERROR_NOCONNECT']) ) {
      if (empty($flags['DB_ERROR_NOTFOUND_TABLES'])){
         $label = 'Repair';
      }else{
         $label = 'Install';
      }
      ?><button type="submit" name="install_db" value="1"><?=$label?> Database</button><?php
   }
?>
</form>
</div>
<script src="install.js"></script>
</body>
</html>
