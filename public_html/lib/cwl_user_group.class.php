<?php
/**
 *  This file helps with selecting a group of users
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

require_once(__DIR__.'/db.inc.php');
class cwl_user_group{
	private $group = null;
	public function setGroup($g){ $this->group = $g; }

    private $select = 'SELECT * FROM user';
    private $orderby = 'ORDER BY date_created ASC, id ASC';

    private $groups = array(
    	 'ALL'=>'',
    	 'ZEROSTATUS'=>'WHERE user.status = 0',
         'GREATERTHAN31'=>'WHERE user.id >= 31',
    );

    private $rows = null;

    public function __construct($group = 'ALL'){
    	global $DBH;
    	$this->group = $group;
    }

    public function fetch($params = array()){ 
    	$group = $this->group;   	
    	if (!isset($this->groups[$group])){
    		return null;
    		//throw new Exception('Invalid group '.$group);
    	}
    	$sql = $this->select.' '.$this->groups[$group].' '.$this->orderby;
    	$prep = pdo()->prepare($sql);
    	$result = $prep->execute($params);
    	if ($result){
    		$this->rows = $prep->fetchAll();
        }else{
        	$this->rows = false;
        }
        return $this->rows;
    }
}
