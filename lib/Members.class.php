<?PHP
    require_once('lib/db.inc.php');
    require_once('lib/work_log.class.php');
	class Members
	{
        public static function MenuBarCompact(){
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

             <div style="float:left; line-height:37px; vertical-align:top;color:#fff" ><a href="index.php" title="Work Logs"><img border=0 src="images/Inr_hdr_logo.jpg" width="122" height="37" /></a>

 				<?PHP if (self::IsLoggedIn()){
                  require_once('lib/Site.class.php');
                  ?><?PHP
                  Site::ImgLinks();
                  
                  
                        $sql = "SELECT * FROM time_log JOIN work_log ON work_log_id = work_log.id 
                                WHERE stop_time IS NULL AND work_log.user_id = ".(int)$_SESSION['user_id'];
                        $result = mysql_query($sql);
                       $time_logs_unfinished = array();
                        if ($result){
                         
                           echo '<div id="unfinished" style="float:left"> &nbsp; ';
                           while($row = mysql_fetch_assoc($result)){
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
          $name = $_SESSION['user_row']['name'];
          if (empty($name)){
             $name = $_SESSION['user_row']['username'];
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
                  require_once('lib/Site.class.php');
                  Site::ImgLinkTableSmall();
                  
                        $sql = "SELECT * FROM time_log JOIN work_log ON work_log_id = work_log.id 
                                WHERE stop_time IS NULL AND work_log.user_id = ".(int)$_SESSION['user_id'];
                        $result = mysql_query($sql);
                       $time_logs_unfinished = array();
                        if ($result){
                         
                           echo '<div id="unfinished" style="float:left"> &nbsp; ';
                           while($row = mysql_fetch_assoc($result)){
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
                <div class="Col11" ><a href="index.php"title="Work Logs"><img border=0 src="images/inner_logo.jpg" width="297" height="83" class="logost" /></a> </div>
                <div class="Col12"> <a href="<?=$_SERVER['REQUEST_URI']?>" onclick="window.location.href = window.location.href; return false;"><img border=0 src="images/bgic_refresh.png" width="49" height="72"  /></a><a href="companies.php"><img border=0 src="images/bgic_clients.png" width="72" height="72" /></a><a href="work_log.php"><img border=0 src="images/bgic_work-logs.png" width="68" height="72" /></a><a href="#" onclick="$('#dlgAddWorkLog').dialog('open'); return false;"><img src="images/bgic_add-work-log.png" width="88" height="72" /></a> </div>
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
                work_log::HtmlFormAddWorkLog(isset($_GET['company_id']) ? $_GET['company_id'] : 0);             
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
                  require_once('lib/Site.class.php');
                  Site::ImgLinkTableSmall();
                  
                        $sql = "SELECT * FROM time_log JOIN work_log ON work_log_id = work_log.id 
                                WHERE stop_time IS NULL AND work_log.user_id = ".(int)$_SESSION['user_id'];
                        $result = mysql_query($sql);
                       $time_logs_unfinished = array();
                        if ($result){
                         
                           echo '<div style="display: inline; float: left;"> &nbsp; ';
                           while($row = mysql_fetch_assoc($result)){
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
		public static function SessionForceLogin()
		{
		   session_start();
		   if (!self::IsLoggedIn()){
		      if (!empty($_GET['mobile'])){ $mobile = 'mobile=1&'; } else { $mobile = ''; }
            header('Location: index.php?'.$mobile.'goto='.urlencode(self::makeUrl($_SERVER['PHP_SELF'],$_SERVER['QUERY_STRING'])));
			  exit;
		   }else{
		      //do nothing, user is logged in!
		   }
		}
		
		public static function SessionAllowLogin(){
		   session_start();
		}
		
		public static function GetUserByUsername($username)
		{
		   $sql = "SELECT * FROM user WHERE LOWER(username) = LOWER('%s')";
		   $result = mysql_query(sprintf($sql, $username));
		   if ($result && $row = mysql_fetch_assoc($result)){
		      return $row;
		   }else{
		      return false;
		   }
		}
		
		public static function GetUserByEmail($email)
		{
		   $sql = "SELECT * FROM user WHERE LOWER(email) = LOWER('%s')";
		   $result = mysql_query(sprintf($sql, $email));
		   if ($result && $row = mysql_fetch_assoc($result)){
		      return $row;
		   }else{
		      return false;
		   }
		}
		
		public static function Login($username_or_email, $password)
		{
		    $sql = "SELECT * FROM user WHERE password = MD5('%s') AND ";
		    if (strpos($username_or_email, '@') !== false){
			      $sql .= "LOWER(email) = '%s'";
			}else{
				  $sql .= "LOWER(username) = '%s'";
			}
			$sql .= " LIMIT 1";
			$result = mysql_query(sprintf($sql, $password, strtolower($username_or_email)));
			if ($result && $row = mysql_fetch_assoc($result)){
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