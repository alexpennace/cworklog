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
  require_once(dirname(__FILE__).'/config.inc.php');

  if (!defined(CFG_DB_DSN)){
  	   if (!defined('CFG_DB_DRIVER')){
  	   	  define('CFG_DB_DRIVER', 'mysql');
  	   }
  	   define('CFG_DB_DSN', CFG_DB_DRIVER.':host='.CFG_DB_HOST.';dbname='.CFG_DB);
  }

  if (!isset($cfg['driver_options'])){
   		$cfg['driver_options'] = array(
		   PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'", 
		   PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		   PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		);  
  }
  
  $DBH = new PDO(CFG_DB_DSN, CFG_DB_USER, CFG_DB_PASS, $cfg['driver_options']);
