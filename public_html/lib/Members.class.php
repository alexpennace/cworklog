<?PHP
/**
 *  This file helps manage Membership and logins
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
  require_once(dirname(__FILE__).'/db.inc.php');
  require_once(dirname(__FILE__).'/work_log.class.php');
	class Members
	{
       
        public static function LoggedInEmail(){
           if (!self::IsLoggedIn()){ return false; }
           $email = $_SESSION['user_row']['email'];
           return $email;
        }       

         public static function LoggedInShortName(){
           if (!self::IsLoggedIn()){ return false; }
           $name = $_SESSION['user_row']['name'];
           if (empty($name)){
             $name = $_SESSION['user_row']['username'];
           }
           return $name;
        }

        public static function MenuBarCompact(){
          global $DBH;
          $name = $_SESSION['user_row']['name'];
          if (empty($name)){
             $name = $_SESSION['user_row']['username'];
          }
          ?>
        <style>
        #Header2{
            background:url(images/top_nav_block.jpg) repeat-x left top ;
            height:37px;
            width:98%;
            padding:0 1%;
            margin:0
        }
        </style>
        
        <div id="Header2">
          <div class="Row" >

             <div style="float:left; line-height:37px; vertical-align:top;color:#fff" >
 				<?PHP if (self::IsLoggedIn()){
                  require_once(dirname(__FILE__).'/Site.class.php');
                  ?><?PHP
                  Site::ImgLinks();
                  
                  
                        $sql = "SELECT * FROM time_log JOIN work_log ON work_log_id = work_log.id 
                                WHERE stop_time IS NULL AND work_log.user_id = :user_id";
                        $prep = $DBH->prepare($sql);
						$result = $prep->execute(array(':user_id'=>$_SESSION['user_id']));
                        $time_logs_unfinished = array();
                        if ($result){
                         
                           echo '<div id="unfinished" style="float:left"> &nbsp; ';
                           while($row = $prep->fetch()){
                              $time_logs_unfinished[] = $row;
                              
                           }
                           $num_unfinished = count($time_logs_unfinished);
                     
                           if ($num_unfinished > 0){
                                foreach($time_logs_unfinished as $tlrow){
                                   $wl = new work_log($tlrow['work_log_id']);
                                   $wlrow = $wl->getRow();
                                   echo '<a href="#work_log.php" title="'.htmlentities($wlrow['company_name'].' - '.$wlrow['title']).'" onclick="poptimer(\'time_log.php?tid=latest&wid='.$tlrow['work_log_id'].'\'); return false;" title=" in progress"><img src="images/progressbar.png" border=0></a>';
                                }
                           }
                           echo '</div>';
                        }
                    }
                ?></div>   
              <div style="float:right" id="topnavigation"><a href="settings.php"><img src="images/user_blue.png" alt="User Image" align="absmiddle"  /> Welcome <?=$name?></a>| <a href="settings.php"><img src="images/setting_icon.jpg" width="20" height="26" alt="Setting" align="absmiddle" /> Settings</a>| <a href="index.php?logout=1"><img src="images/logout_icon.jpg" width="18" height="26" alt="Logout" align="absmiddle" /> LogOut</a></div>                
          </div> 
        </div>
           <?PHP
        }
        
        public static function MenuBarOpenBottomLeftOpen(){
          global $DBH;
          $name = isset($_SESSION['user_row']['name']) ? $_SESSION['user_row']['name'] : '';
          if (empty($name)){
             $name = isset($_SESSION['user_row']['username']) ? $_SESSION['user_row']['username'] : '';
          }
          ?>
            <style>
             .lefttopbar{
                  position: absolute;
                  text-align: left;
                  left: 10px;
             }
            </style>
            <div id="Header">
             <div id="topnavigation">
              <div class="lefttopbar">
				        <?PHP if (self::IsLoggedIn()){
                  require_once(dirname(__FILE__).'/Site.class.php');
                  Site::ImgLinkTableSmall();
                  
                        $sql = "SELECT * FROM time_log JOIN work_log ON work_log_id = work_log.id 
                                WHERE stop_time IS NULL AND work_log.user_id = ".(int)$_SESSION['user_id'];
                        $prep = $DBH->prepare($sql);
$result = $prep->execute();
                       $time_logs_unfinished = array();
                        if ($result){
                         
                           echo '<div id="unfinished" style="float:left"> &nbsp; ';
                           while($row = $prep->fetch()){
                              $time_logs_unfinished[] = $row;
                              
                           }
                           $num_unfinished = count($time_logs_unfinished);
                     
                           if ($num_unfinished > 0){
                                foreach($time_logs_unfinished as $tlrow){
                                   $wl = new work_log($tlrow['work_log_id']);
                                   $wlrow = $wl->getRow();

                                   echo '<a href="#work_log.php" title="'.htmlentities($wlrow['company_name'].' - '.$wlrow['title']).'" onclick="poptimer(\'time_log.php?tid=latest&wid='.$tlrow['work_log_id'].'\'); return false;" title=" in progress"><img src="images/progressbar.png" border=0></a>';

                                }
                           }
                           echo '</div>';
                        }
                    }
                ?>              
              </div>




            <div style="float:right;">
              <a href="settings.php" title="Settings"><img src="images/user_blue.png" alt="User Image" align="absmiddle"  /> Welcome <?=$name?></a>| <a href="settings.php"><img src="images/setting_icon.jpg" width="20" height="26" alt="Setting" align="absmiddle" /> Settings</a>| <a href="index.php?logout=1"><img src="images/logout_icon.jpg" width="18" height="26" alt="Logout" align="absmiddle" /> LogOut</a> </div>
              </div>
              <div id="logoblock">
                <div id="filterblock">
                  <div  style="float:left">
          <?PHP
        }
        
        public static function MenuBarBottomLeftCloseRightOpen(){
         ?></div>
                  <div style="float:right;"><?PHP
        }
        
        public static function MenuBarBottomRightClose(){
         ?></div><?PHP
        }
        
        public static function MenuBarClose(){
         ?></div><!-- end filterblock -->
              </div>
            </div><?PHP
              if (self::IsLoggedIn()){
                 $specific_id = isset($_GET['company_id']) ? $_GET['company_id'] : false;
                 if (empty($specific_id) && isset($_GET['company'])){
                    $specific_id = (int)$_GET['company'];
                 }
                 work_log::HtmlFormAddWorkLog($specific_id);             
              }
        }
        
        /** 
         * This is the old MenuBar() function which has now been replaced by the 4 functions below.
         * It will still work, but the bottom black bar will be empty
         * @see work_log.php
         */
		public static function MenuBar()
		{
          self::MenuBarOpenBottomLeftOpen();
          self::MenuBarBottomLeftCloseRightOpen();
          self::MenuBarBottomRightClose();
          self::MenuBarClose();
          return;
          ?>
		   <style>
				.topbar{
				   width: 100%;
				   height: 24px;
				   background-color: #F2F0F0;
				   border-bottom: 1px solid silver;
				   position: absolute;
				   top: 0;
				   left: 0;
                   margin-bottom: 10px;
				}
				.righttopbar{ 
					position: absolute;
					text-align: right; 
					right: 55px;
				}
                .lefttopbar{
                  position: absolute;
                  text-align: left;
                  left: 10px;
                }
                .topbaremail{
                  font-size: 80%;
                }
		   </style>
			<div class="topbar">
                <div class="lefttopbar">
				<?PHP if (self::IsLoggedIn()){
                  require_once(dirname(__FILE__).'/Site.class.php');
                  Site::ImgLinkTableSmall();
                  
                        $sql = "SELECT * FROM time_log JOIN work_log ON work_log_id = work_log.id 
                                WHERE stop_time IS NULL AND work_log.user_id = ".(int)$_SESSION['user_id'];
                        $prep = $DBH->prepare($sql);
$result = $prep->execute();
                       $time_logs_unfinished = array();
                        if ($result){
                         
                           echo '<div style="display: inline; float: left;"> &nbsp; ';
                           while($row = $prep->fetch()){
                              $time_logs_unfinished[] = $row;
                              
                           }
                           $num_unfinished = count($time_logs_unfinished);
                     
                           if ($num_unfinished > 0){
                                foreach($time_logs_unfinished as $tlrow){
                                   $wl = new work_log($tlrow['work_log_id']);
                                   $wlrow = $wl->getRow();
                                   echo '<a href="#work_log.php" title="'.htmlentities($wlrow['company_name'].' - '.$wlrow['title']).'" onclick="poptimer(\'time_log.php?tid=latest&wid='.$tlrow['work_log_id'].'\'); return false;" title=" in progress"><img src="images/progressbar.png" border=0></a></div>';
                                }
                           }
                           echo '</div>';
                        }
                    }
                ?>
                </div>
			    <div class="righttopbar">
                <?PHP if (self::IsLoggedIn()){ ?>
			   <span title="Logged in as <?=$_SESSION['user_row']['email']?>">Logged in as <b><?=$_SESSION['user_row']['username']?></b></span> | <a title="Change account, invoice, and other settings" href="settings.php">Settings</a>  | <a href="index.php?logout=1">Log out</a></li>
				<?PHP } else { ?>
				<a href="index.php">Log in</a>
				<?PHP } ?>
				</div>
			</div>
            <?PHP
              if (self::IsLoggedIn()){
                work_log::HtmlFormAddWorkLog(isset($_GET['company_id']) ? $_GET['company_id'] : 0);             
              }
            ?>
			<br><br>
		  <?PHP
		}

		public static function SessionForceLogin($return_no_redirect = false, $allow_verify_code_login = false)
		{
       session_set_cookie_params(3600*24*7); // sessions lasts 7 days
		   session_start();
       if ($allow_verify_code_login && !empty($_REQUEST['code'])){
            $prep = pdo()->prepare('SELECT * FROM user WHERE verify_code = :verify_code');
            $prep->execute(array('verify_code'=>$_REQUEST['code']));
            $matching_users = $prep->fetchAll();
            if (count($matching_users) === 1){
                $row = $matching_users[0];

                $_SESSION['user_row'] = $row;
                $_SESSION['user_id'] = $row['id'];

                //mark that it has been verified
                $prep2 = pdo()->prepare('UPDATE user SET status = 1 AND verify_code = \'\' WHERE user.id = :id');
                $result = $prep2->execute(array('id'=>$row['id']));
                $_SESSION['verify_code_result'] = $result;
            }
       }

		   if (!self::IsLoggedIn()){
          if ($return_no_redirect){ return false; }

		      if (!empty($_GET['mobile'])){ $mobile = 'mobile=1&'; } else { $mobile = ''; }
            header('Location: '.Site::cfg('base_url').'/index.php?'.$mobile.'goto='.urlencode(self::makeUrl($_SERVER['PHP_SELF'],$_SERVER['QUERY_STRING'])));
			  exit;
		   }else{
		      //do nothing, user is logged in!
          return true;
		   }
		}
		
		public static function SessionAllowLogin(){
		   session_set_cookie_params(3600*24*7); // sessions lasts 7 days
         session_start();
		}
		
		public static function GetUserByUsername($username)
		{
           global $DBH;
		   $sql = "SELECT * FROM user WHERE LOWER(username) = LOWER(:username)";
		   $prep = $DBH->prepare($sql);
		   $result = $prep->execute(array('username'=>$username));
		   if ($result && $row = $prep->fetch()){
		      return $row;
		   }else{
		      return false;
		   }
		}
		
		public static function GetUserByEmail($email)
		{
       	   global $DBH;
		   $sql = "SELECT * FROM user WHERE LOWER(email) = LOWER(:email)";
		   $prep = $DBH->prepare($sql);
		   $result = $prep->execute(array('email'=>$email));
		   if ($result && $row = $prep->fetch()){
		      return $row;
		   }else{
		      return false;
		   }
		}
		
      public static function SuperLogin($username_or_email_or_id){
          global $DBH;
      		 $sql = "SELECT * FROM user WHERE ";
      		 $exec_ary = array();
      		 if (is_numeric($username_or_email_or_id)){
                   $sql .= ' id = :id ';
                   $exec_ary['id'] = $username_or_email_or_id;
               }
               else if (strpos($username_or_email_or_id, '@') !== false){
      			      $sql .= "LOWER(email) = :email";
      			      $exec_ary['email'] = $username_or_email_or_id;
      			}else{
      				  $sql .= "LOWER(username) = :username";
      				  $exec_ary['username'] = $username_or_email_or_id;
      			}
      			$sql .= " LIMIT 1";
      			$prep = $DBH->prepare($sql);
      			$result = $prep->execute($exec_ary);
      			if ($result && $row = $prep->fetch()){
      				$_SESSION['user_row'] = $row;
      				$_SESSION['user_id'] = $row['id'];
              $_SESSION['superlogin'] = true;
      			}else{
      			    $_SESSION['user_row'] = false;
      			}
			      return $_SESSION['user_row'];      
      }
      
      public static function CheckUsernamePassword($username_or_email, $password)
      {
        	global $DBH;
		    $sql = "SELECT * FROM user WHERE password = MD5(:password) AND ";
		    $exec_ary = array('password'=>$password);
		    if (strpos($username_or_email, '@') !== false){
			      $sql .= "LOWER(email) =  :email";
			      $exec_ary['email'] = $username_or_email;
			}else{
				  $sql .= "LOWER(username) = :username";
				   $exec_ary['username'] = $username_or_email;
			}
			$sql .= " LIMIT 1";
			$prep = $DBH->prepare($sql);
			$result = $prep->execute($exec_ary);
			if ($result && $row = $prep->fetch()){
            return $row;
         }else{
            return false;
         }
      }
      
		public static function Login($username_or_email, $password)
		{
         if ($row = self::CheckUsernamePassword($username_or_email, $password)){
				$_SESSION['user_row'] = $row;
				$_SESSION['user_id'] = $row['id'];
			}else{
			    $_SESSION['user_row'] = false;
			}
			return $_SESSION['user_row'];
		}
		
		public static function IsLoggedIn()
		{
			return !empty($_SESSION['user_row']) && !empty($_SESSION['user_id']);
		}
		
		public static function Logout()
		{
			$_SESSION = array();
			// If it's desired to kill the session, also delete the session cookie.
			// Note: This will destroy the session, and not just the session data!
			if (ini_get("session.use_cookies")) {
				$params = session_get_cookie_params();
				setcookie(session_name(), '', time() - 42000,
					$params["path"], $params["domain"],
					$params["secure"], $params["httponly"]
				);
			}

			// Finally, destroy the session.
			return session_destroy();
		}
		
		
		// makeUrl('index.php', $_SERVER['QUERY_STRING'], 'name=value&name2=value2');
		function makeUrl($path, $qs = false, $qsAdd = false)
		{    
			$var_array = array();
			$varAdd_array = array();
			$url = $path;
			
			if($qsAdd)
			{
				$varAdd = explode('&', $qsAdd);
				foreach($varAdd as $varOne)
				{
					$name_value = explode('=', $varOne);
					
					$varAdd_array[$name_value[0]] = $name_value[1];
				}
			}

			if($qs)
			{
				$var = explode('&', $qs);
				foreach($var as $varOne)
				{
					$name_value = explode('=', $varOne);
					
					//remove duplicated vars
					if($qsAdd)
					{
						if(!array_key_exists($name_value[0], $varAdd_array))
						{
							$var_array[$name_value[0]] = $name_value[1];
						}
					}
					else
					{
						$var_array[$name_value[0]] = $name_value[1];
					}
				}
			}
				
			//make url with querystring    
			$delimiter = "?";
			
			foreach($var_array as $key => $value)
			{
				$url .= $delimiter.$key."=".$value;
				$delimiter = "&";
			}
			
			foreach($varAdd_array as $key => $value)
			{
				$url .= $delimiter.$key."=".$value;
				$delimiter = "&";
			}
			
			return $url;
		}
	}
?>