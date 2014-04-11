<?PHP
/**
 *  This file helps connect to the database. (Uses PDO)
 * 
 *  Coders/Contractors Work Log - A time tracking/invoicing app 
 *  Copyright (C) 2014 Jim A Kinsman (cworklog.com) relipse@gmail.com github.com/relipse 
 *
 *  LICENSES - GPL 3. (If you need a different commercial license please contact Jim 
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
  require_once(__DIR__.'/Site.class.php');

  $cfg = Site::cfg();

  if (empty($cfg['db_dsn'])){
  	   if (empty($cfg['db_driver'])){
          $cfg['db_driver'] = 'mysql';
       }

       $cfg['db_dsn'] = $cfg['db_driver'].':host='.$cfg['db_host'].';dbname='.$cfg['db'];
  }

  if (!isset($cfg['driver_options'])){
   		$cfg['driver_options'] = array(
		   PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'", 
		   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		);  
  }

  $DBH = new PDO($cfg['db_dsn'], $cfg['db_user'], $cfg['db_pass'], $cfg['driver_options']);

  function pdo(){
     global $DBH;
     return $DBH;
  }
