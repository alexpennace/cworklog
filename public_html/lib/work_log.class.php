<?PHP
require_once(dirname(__FILE__).'/db.inc.php');
require_once(dirname(__FILE__).'/CWLUser.class.php');

class work_log
{
  public static $last_error = '';
  
  //thanks to http://stackoverflow.com/questions/7127204/converting-seconds-to-hhmmss
  public static function sec2hms ($sec, $padHours = true) {
      $hms = "";
      $hours = intval(intval($sec) / 3600);
      $hms .= ($padHours)
      ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
      : $hours. ':';
      $minutes = intval(($sec / 60) % 60);
      $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';
      $seconds = intval($sec % 60);
      $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
      return $hms;
  }
  
  public static function ParseTimeToHrMin($str){
        preg_match_all('/(((?<hours>\d\d?)\s*(hr?|:))\s*)?((?<min>\d\d?)\s*(m?i?n?(utes?)?))?/im', $str, $result, PREG_SET_ORDER);
        $hours = 0;
        $minutes = 0;
        $total_minutes = 0;
        if (count($result) == 0){
           return array(false, false, false);
        }
        
        for ($matchi = 0; $matchi < count($result); $matchi++) {
            if (!empty($result[$matchi]['hours'])){
                $hours += $result[$matchi]['hours'];
            } 
            if (!empty($result[$matchi]['min'])){
                $minutes += $result[$matchi]['min'];
            }
        }
        $total_minutes = ($hours*60) + $minutes;
        return array($hours, $minutes, $total_minutes);
  }
  
  public static function HtmlFormAddWorkLog($specific_company_id){
    ?>
	<script type="text/javascript">
	  $(function() {
         $("#dlgAddWorkLog").dialog({ autoOpen: false, width: 300, height: 250 });
	  });
	</script>
	<div id="dlgAddWorkLog" title="Create work log" style="display: none;">
	<form id="frmAddWorkLog" name="frmAddWorkLog" method="POST" action="work_log.php">
	<label>Title<input type="text" name="title" maxlength=100 /></label>
	<br>
	<label>Description<input type="text" name="description"/></label>
	<br>
	<label>Client
	<select name="company_id" onchange="if (this.value == 'new'){ $('#new_company').toggle(true); $('#dlgAddWorkLog').dialog('option', 'height', 400); }else{ $('#new_company').toggle(false); $('#dlgAddWorkLog').dialog('option', 'height', 'auto'); }">
	<?PHP
	  $prep = $DBH->prepare("SELECT id, name, default_hourly_rate FROM company WHERE user_id = ".(int)$_SESSION['user_id']." ORDER BY id ASC");
$result = $prep->execute();
	  while ($row = $prep->fetch()) {
		  ?><option value="<?=$row['id']?>"<?PHP if ($specific_company_id == $row['id']){ echo ' selected '; }?>><?=htmlentities($row['name'].($row['default_hourly_rate'] > 0 ? ' ($'.$row['default_hourly_rate'].'/hr)':''))?></option><?PHP
	  }
	?>
	<option value="new">-- New Client--</option>
	</select>
    <script>
     $(document).ready(function() {
          //trigger the onchange event just in case there are no clients yet.
          $(frmAddWorkLog.company_id).trigger('change');
     });
    </script>
	</label>
	<div id="new_company" style="display: none">
	<fieldset>
	<legend>Add new client</legend>
	<label>Name<input type="text" name="name" maxlength=255 /></label><br>
	<label>Default Hourly Rate $<input type="text" name="default_hourly_rate" value="" size=8/></label><br>
	<label>Street <input type="text" name="street" maxlength=90 /></label><br>
	<label>Street 2<input type="text" name="street2" maxlength=90 /></label><br>
	<label>City<input type="text" name="city" maxlength=50 /></label>
	<label>State<input type="text" name="state" maxlength=50 size=5 /></label>
	<label>Zip<input type="text" name="zip" maxlength=25 size=5 /></label><br>
	<label>Country<input type="text" name="country" maxlength=50 /></label>
	<br>
	<label>Phone<input type="text" name="phone" maxlength=15 /></label>
	<label>Email<input type="text" name="email" maxlength=50 /></label><br>	
	<label>Notes: <br>
	<textarea name="notes" rows=2 cols=24></textarea></label>
	</fieldset>
	</div>
	<br>
	<input type="submit" value="Create Work Log" />
	</form>

	</div>
	<?PHP
  }
  
  public static function AddCompany($ary){
       $user_id = (int)(isset($ary['user_id']) ? $ary['user_id'] : $_SESSION['user_id']);
       
       $cwluser = new CWLUser($user_id);
       if ($cwluser->planExceedsClients(true)){
          self::$last_error = 'Your plan can not have any more clients. Please upgrade or contact support';
          return false;
       }
  
         
         $sql = "INSERT INTO company (id, user_id, name, street, street2, city, state, zip, country, phone, email, notes, default_hourly_rate) ".
                "VALUES(NULL, ".$user_id.", '".
                mysql_real_escape_string($ary['name'])."', '".
				mysql_real_escape_string($ary['street'])."', '".
                mysql_real_escape_string($ary['street2'])."', '".
                mysql_real_escape_string($ary['city'])."', '".
                mysql_real_escape_string($ary['state'])."', '".
                mysql_real_escape_string($ary['zip'])."', '".
                mysql_real_escape_string($ary['country'])."', '".
                mysql_real_escape_string($ary['phone'])."', '".
                mysql_real_escape_string($ary['email'])."', '".
                mysql_real_escape_string($ary['notes'])."', ".
                (float)$ary['default_hourly_rate']." );";
         $prep = $DBH->prepare($sql);
$result = $prep->execute();
         if ($result){
            $ary['company_id'] = mysql_insert_id();
			return $ary['company_id'];
         }else{
            self::$last_error = 'Error adding company'.mysql_error(); 
            return false;
         }  
  }
  
  public static function Add($ary){
      if ($ary['company_id'] == 'new'){
         $ary['company_id'] = self::AddCompany($ary);
         if ($ary['company_id'] === false){
            return false;
         }
      }
      $user_id = false;
      if (isset($_SESSION['user_id'])){
          $user_id = $_SESSION['user_id'];
      }else{
          $user_id = $ary['user_id'];
      }
      $cwluser = new CWLUser($user_id);
      if ($cwluser->planExceedsActiveWorkLogs(true)){
          self::$last_error = 'Your plan can not have any more active work logs. Please upgrade or contact support';
          return false;
      }
      
      $prep = $DBH->prepare("SELECT name, default_hourly_rate FROM company WHERE id = ".(int)$ary['company_id']." AND user_id = ".(int)$user_id);
$result = $prep->execute();
      if ($result && $row = $prep->fetch()){
         //company exists!!!
      }else{
         self::$last_error = 'Company with id '.$ary['company_id'].' does not exist.';
         return false;
      }
      
      $sql = "INSERT INTO work_log (id, user_id, company_id, title, description, rate) ".
             "VALUES ( NULL , ".(int)$user_id.", ".(int)$ary['company_id'].", '".mysql_real_escape_string($ary['title'])."', '".
             mysql_real_escape_string($ary['description'])."', ".$row['default_hourly_rate']." );";
      $prep = $DBH->prepare($sql);
$result = $prep->execute();
      return $result;
  }
  
  private $wid = null;
  private $row = null;
  public function addFile($filename, $changetype, $feature_name, $notes){
    $sql = "INSERT INTO files_log (work_log_id, feature, file, change_type, notes, date_modified) 
	                      VALUES (".(int)$this->wid.", '%s', '%s','%s', '%s', NOW())";
	$sqls = sprintf($sql, $feature_name, $filename, $changetype, $notes);
	$prep = $DBH->prepare($sqls);
$result = $prep->execute();
	return $result;
  }
  
  public function deleteFile($filename, $feature_name){
    $sql = "DELETE FROM files_log WHERE work_log_id = ".(int)$this->wid." AND file = '%s' AND feature = '%s'";
	$sqls = sprintf($sql, $filename, $feature_name);
	return mysql_query($sqls);
  }
  
  public function getFiles($optional_feature_name = null){
     $sql = "SELECT * FROM files_log WHERE work_log_id = ".(int)$this->wid;
	 if (!is_null($optional_feature_name)){
		$sql .= " AND feature = '%s'";
		$sql = sprintf($sql, $optional_feature_name);
	 }
	 if (is_null($optional_feature_name)){
	    $sql .= ' ORDER BY feature, date_modified ';
	 }else{
	    $sql .= ' ORDER BY date_modified DESC';
     }
	 $prep = $DBH->prepare($sql);
$result = $prep->execute();
	 $files = array();
	 while ($row = $prep->fetch()){
		$files[] = $row;
	 }
	 return $files;
  }
  
  public function getRow(){ return $this->row; }
  public function __construct($wid){
      $result = mysql_query("SELECT work_log.*, company.name AS company_name 
                              FROM work_log JOIN company ON company_id = company.id
                              WHERE work_log.user_id = ".$_SESSION['user_id']." AND work_log.id = ".(int)$wid);
       if ($result) {
       	$this->row = $prep->fetch();
       	if (!$this->row){
       	  throw new Exception('Work log wid '.$wid.' does not exist');
       	}
       	$this->wid = $wid;
       	//everything ok, just continue grabbing info about the work_log
       	$this->appendMoreDetailsToRow($this->row);
       	
       }else{
         throw new Exception('Error fetching work_log: '.mysql_error());
       }
  }
  
  public function appendMoreDetailsToRow(&$row)
  {
      $total_seconds = 0;
      $result2 = mysql_query("SELECT start_time, stop_time 
                              FROM time_log 
                              WHERE work_log_id = ".(int)$row['id']." 
                                AND start_time IS NOT NULL 
                                AND stop_time IS NOT NULL");
      if ($result2){
         while($time_log_row = $prep->fetch()){
            $total_seconds += strtotime($time_log_row['stop_time']) - strtotime($time_log_row['start_time']);
         }
      }
      
      $result3 = mysql_query("SELECT start_time, stop_time 
                              FROM time_log 
                              WHERE work_log_id = ".(int)$row['id']." 
                                AND start_time IS NOT NULL 
                                AND stop_time IS NULL");
      if ($result3){
         if ($uf_time_log_row = $prep->fetch()){
            $row['_in_progress_'] = true;
         }else{
            $row['_in_progress_'] = false;
         }
      }
                                      
      //$super_total_seconds += $total_seconds;
      $row['_calc_hours_'] = $total_seconds / 60 / 60;
      $row['_calc_amount_'] = $row['_calc_hours_'] * $row['rate'];
      //$super_total_amount += $row['_calc_amount_'];
      $row['_calc_hours_'] = round($row['_calc_hours_'], 3);  
  }
  
  public function fetchTimeLog(){
      $result2 = mysql_query("SELECT id, start_time, stop_time, notes 
                              FROM time_log 
                              WHERE work_log_id = ".(int)$this->row['id']." 
                                AND start_time IS NOT NULL");
      if ($result2){
         $rows = array();
         while($time_log_row = $prep->fetch()){
            $rows[] = $time_log_row;
         }
         return $rows;
      }else{
		 return false;
      }   
  }
  
  public function addNotes($text)
  {
     $sql = "INSERT INTO note_log (id, work_log_id, text, date_added) VALUES ".
            "( NULL, ".$this->wid.", '".mysql_real_escape_string($text)."', NOW() );";
     $prep = $DBH->prepare($sql);
$result = $prep->execute();
     return $result;
  }
  
  public function deleteNote($note_id){
     $sql = "DELETE FROM note_log WHERE id = ".(int)$note_id." AND work_log_id = ".(int)$this->wid;
     $prep = $DBH->prepare($sql);
$result = $prep->execute();
	 return $result;
  }
  
  
  public function getNotes($opts=array('asciionly'=>true))
  {
     $notes = array();
     $sql = "SELECT id, text, date_added FROM note_log WHERE work_log_id = ".$this->wid." ORDER BY date_added DESC";
     $prep = $DBH->prepare($sql);
$result = $prep->execute();
     while($row = $prep->fetch()){
	   if (isset($opts['asciionly'])){
	      $row['text'] = preg_replace('/[^\x00-\x7F]+/', '', $row['text']);
	   }
	   if (isset($opts['htmlentities'])){
	      $row['text'] = htmlentities($row['text']);
	   }
	   
       $notes[] = $row;
     }
     return $notes;
  }

}
