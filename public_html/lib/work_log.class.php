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
  
  /**
   * Parse a String to hours and minutes
   * @example
   * "15 min", "1:35", "1hr"
   */
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
    global $DBH;
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
       global $DBH;
       $user_id = (int)(isset($ary['user_id']) ? $ary['user_id'] : $_SESSION['user_id']);
       
       $cwluser = new CWLUser($user_id);
       if ($cwluser->planExceedsClients(true)){
          self::$last_error = 'Your plan can not have any more clients. Please upgrade or contact support';
          return false;
       }

       $whitelist = array('user_id'=>'',
              'name'=>'',
              'street'=>'',
              'street2'=>'',
              'city'=>'',
              'state'=>'',
              'zip'=>'',
              'country'=>'',
              'phone'=>'',
              'email'=>'',
              'notes'=>'',
              'default_hourly_rate'=>'',
              );

       $params = array_merge($whitelist, $ary);
       foreach($params as $key => $value){
          if (!isset($whitelist[$key])){ 
              unset($params[$key]); 
          }
       }
       $params['user_id'] = $user_id;
       $params['default_hourly_rate'] = (float)$ary['default_hourly_rate'];
         
       $sql = "INSERT INTO company (id, user_id, name, street, street2, city, state, zip, country, phone, email, notes, default_hourly_rate) ".
                "VALUES(NULL, :user_id, :name, :street, :street2, :city, :state, :zip, :country, :phone, :email, :notes, :default_hourly_rate);";
       $prep = $DBH->prepare($sql);
       
       $result = $prep->execute($params);

       if ($result){
            $ary['company_id'] = $DBH->lastInsertId();
			      return $ary['company_id'];
       }else{
            self::$last_error = 'Error adding company'.$DBH->errorInfo(); 
            return false;
       }  
  }
  
  public static function Add($ary){
      global $DBH;
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
      
      $prep = $DBH->prepare("SELECT name, default_hourly_rate FROM company WHERE id = :company_id AND user_id = :user_id");
      $result = $prep->execute(array(':company_id'=>$ary['company_id'], ':user_id'=>(int)$user_id));
      if ($result && $row = $prep->fetch()){
         //company exists!!!
      }else{
         self::$last_error = 'Company with id '.$ary['company_id'].' does not exist.';
         return false;
      }
      
      $sql = "INSERT INTO work_log (id, user_id, company_id, title, description, rate) ".
             "VALUES ( NULL , :user_id, :company_id, :title, :description, :default_hourly_rate );";
      $prep = $DBH->prepare($sql);
        $result = $prep->execute(array('user_id'=>(int)$user_id, 'company_id'=>(int)$ary['company_id'],
          'title'=>$ary['title'], 'default_hourly_rate'=>$row['default_hourly_rate'], 'description'=>$ary['description']));
      return $result;
  }
  
  private $wid = null;
  private $row = null;
  public function addFile($filename, $changetype, $feature_name, $notes){
    global $DBH;
    $sql = "INSERT INTO files_log (work_log_id, feature, file, change_type, notes, date_modified) 
	                      VALUES (:work_log_id, :feature, :file, :change_type, :notes, NOW())";

  	$prep = $DBH->prepare($sql);
    $result = $prep->execute(array('work_log_id'=>$this->wid, 
                            'feature'=>$feature_name, 
                            'file'=>$filename,
                            'change_type'=>$changetype, 
                            'notes'=>$notes));
  	return $result;
  }
  
  public function deleteFile($filename, $feature_name){
     global $DBH;
     $sql = "DELETE FROM files_log WHERE work_log_id = :work_log_id AND file = :file AND feature = :feature";
	   $prep = $DBH->prepare($sql);
     return $prep->execute(array('work_log_id'=> (int)$this->wid, 'file'=>$filename, 'feature'=>$feature_name));
  }
  
  public function getFiles($optional_feature_name = null){
    global $DBH;
    $exec_ary = array();
    $sql = "SELECT * FROM files_log WHERE work_log_id = ".(int)$this->wid;
	 if (!is_null($optional_feature_name)){
		$sql .= " AND feature = :feature";
		    $exec_ary['feature'] = $optional_feature_name;
	 }
	 if (is_null($optional_feature_name)){
	    $sql .= ' ORDER BY feature, date_modified ';
	 }else{
	    $sql .= ' ORDER BY date_modified DESC';
     }
	 
   $prep = $DBH->prepare($sql);
   $result = $prep->execute($exec_ary);
	 $files = array();
	 while ($row = $prep->fetch()){
		$files[] = $row;
	 }
	 return $files;
  }
  
  public function getRow(){ return $this->row; }
  public function __construct($wid){
      global $DBH;
      $prep = $DBH->prepare("SELECT work_log.*, company.name AS company_name 
                              FROM work_log JOIN company ON company_id = company.id
                              WHERE work_log.user_id = :user_id AND work_log.id = :work_log_id");
        $result =  $prep->execute(array('user_id'=>$_SESSION['user_id'], 'work_log_id'=>$wid));
       if ($result) {
       	$this->row = $prep->fetch();
       	if (!$this->row){
       	  throw new Exception('Work log wid '.$wid.' does not exist');
       	}
       	$this->wid = $wid;
       	//everything ok, just continue grabbing info about the work_log
       	$this->appendMoreDetailsToRow($this->row);
       	
       }else{
         throw new Exception('Error fetching work_log: '.$DBH->errorInfo());
       }
  }
  
  public function appendMoreDetailsToRow(&$row)
  {
      global $DBH;
      $total_seconds = 0;
      $prep = $DBH->prepare("SELECT start_time, stop_time 
                              FROM time_log 
                              WHERE work_log_id = :work_log_id
                                AND start_time IS NOT NULL 
                                AND stop_time IS NOT NULL");
      $result2 =  $prep->execute(array('work_log_id'=>$row['id']));
      if ($result2){
         while($time_log_row = $prep->fetch()){
            $total_seconds += strtotime($time_log_row['stop_time']) - strtotime($time_log_row['start_time']);
         }
      }
      
      $prep = $DBH->prepare("SELECT start_time, stop_time 
                              FROM time_log 
                              WHERE work_log_id = :work_log_id
                                AND start_time IS NOT NULL 
                                AND stop_time IS NULL");
                $result3 =  $prep->execute(array('work_log_id'=>$row['id']));
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
      global $DBH;
      $prep = $DBH->prepare("SELECT id, start_time, stop_time, notes 
                              FROM time_log 
                              WHERE work_log_id = :work_log_id 
                                AND start_time IS NOT NULL");
        $result2 =  $prep->execute(array('work_log_id'=>(int)$this->row['id']));
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
     global $DBH;
     $sql = "INSERT INTO note_log (id, work_log_id, text, date_added) VALUES ".
            "( NULL, :work_log_id, :text, NOW() );";
     $prep = $DBH->prepare($sql);
     $result = $prep->execute(array('work_log_id'=>$this->wid, 'text'=>$text));
     return $result;
  }
  
  public function deleteNote($note_id){
     global $DBH;
     $sql = "DELETE FROM note_log WHERE id = :note_id AND work_log_id = :work_log_id";
     $prep = $DBH->prepare($sql);
     $result = $prep->execute(array('note_id'=>$note_id, ':work_log_id'=>$work_log_id));
	   return $result;
  }
  
  
  public function getNotes($opts=array('asciionly'=>true))
  {
     global $DBH;

     $notes = array();
     $sql = "SELECT id, text, date_added FROM note_log WHERE work_log_id = :work_log_id ORDER BY date_added DESC";
     $prep = $DBH->prepare($sql);
     $result = $prep->execute(array('work_log_id'=>$this->wid));
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
