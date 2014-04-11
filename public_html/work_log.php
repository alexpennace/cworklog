<?PHP
   require_once(dirname(__FILE__).'/lib/db.inc.php');
   require_once(dirname(__FILE__).'/lib/Members.class.php');
   Members::SessionForceLogin();
   $cwluser = new CWLUser($_SESSION['user_id']);
   if (!$cwluser->isValid()){
      die('User not found error');
   }else{
      //echo 'unlocked: '.$cwluser->countUnlockedWorkLogs();
   }
   require_once(dirname(__FILE__).'/lib/work_log.class.php');
   require_once(dirname(__FILE__).'/lib/CWLUser.class.php');
   
   if (isset($_POST['title'])){
      if (!work_log::Add($_POST)){
         $ERROR_MSG = work_log::$last_error;
      }
   }else if (isset($_POST['delete_note'])){
      $wl = new work_log($_POST['work_log_id']);
      $wl->deleteNote($_POST['note_id']);
   }else if (isset($_POST['delete_file_modification'])){
      $wl = new work_log($_POST['work_log_id']);
      $wl->deleteFile($_POST['file'], $_POST['feature']);
   }
   else if (isset($_POST['work_log_id']) && isset($_POST['text']))
   {
      $wl = new work_log($_POST['work_log_id']);
      $wl->addNotes($_POST['text']);
   }
   else if (isset($_POST['work_log_id']) && isset($_POST['file'])){
      $wl = new work_log($_POST['work_log_id']);
	   $wl->addFile($_POST['file'], $_POST['change_type'], $_POST['feature'], $_POST['notes']);
   }
   
   if (isset($_REQUEST['ajaxedit'])){
       if (empty($_REQUEST['wid']) || !is_numeric($_REQUEST['wid'])){
          die(json_encode(array('error'=>'Invalid work log id provided')));
       }else{
         $wid = (int)$_REQUEST['wid'];
       }
       if (isset($_REQUEST['f']) && isset($_REQUEST['v'])){
          $field = $_REQUEST['f'];
          $field = preg_replace('/[^\w]/', '', $field);
          $value = $_REQUEST['v'];
       }else{
          die(json_encode(array('error'=>'No [f]ield or [v]alue provided.')));
       }
       //now do checks on the specific fields
       
       
       $prep = $DBH->prepare("SELECT work_log.*, company.name AS company_name 
                              FROM work_log JOIN company ON company_id = company.id
                              WHERE work_log.id = :work_log_id");
                $result =  $prep->execute(array(':work_log_id'=>(int)$wid));
       if ($result) {
       	$original_row = $prep->fetch();
       }else{
          //die(json_encode(array('error'=>$DBH->errorInfo())));
       }
       if (empty($original_row)){
          die(json_encode(array('error'=>'No work log found.')));
       }
       
       if ($original_row['locked'] && $field != 'locked'){
         die(json_encode(array('error'=>'Locked work log, cannot change')));
       }
       
       //everything else seemed to pass, now check if user is trying to lock a row in-progress
       if ($field == 'locked' && $value != 0 && $original_row['locked'] == false){
            $prep = $DBH->prepare("SELECT start_time, stop_time 
                                    FROM time_log 
                                    WHERE work_log_id = $wid 
                                      AND start_time IS NOT NULL 
                                      AND stop_time IS NULL");
                $result3 =  $prep->execute();
            if ($result3){
               if ($uf_time_log_row = $prep->fetch()){
                  //$row['_in_progress_'] = true;
                  die(json_encode(array('error'=>'This entry has a time log in progress and cannot be locked.')));
               }else{
                  //$row['_in_progress_'] = false;
               }
            }         
       }
       
       //ARE WE MODIFYING a DATE_ field, just check , 
       //if SO, USE strtotime()
       
       //convert to valid date first.
       if (strpos($field, 'date') === 0){
          if ($value != ''){
             $value = date('Y-m-d', strtotime($value));
          }
       }
       
       //if we made it down here, then everything is ok
       $sql = "UPDATE work_log SET $field = :value WHERE id = :work_log_id LIMIT 1";
       $prep = $DBH->prepare($sql);
       if (Site::cfg('verbose_debugging')) { echo $sql; }

       $result_upd =  $prep->execute(array('work_log_id'=>$wid, 'value'=>$value));
       if ($result_upd){
          
          try{ $worklog = new work_log($wid);}
          catch(Exception $e){
               die(json_encode(array('error'=>'Error')));
          }
          
          die(json_encode(array('success'=>'Updated.', 'row'=>$worklog->getRow())));
       }else{
          die(json_encode(array('error'=>$DBH->errorInfo())));
       }
   }

   //-- NORMAL PAGE VIEW ALL WORK LOGS
   $sql = "SELECT work_log.*,company.name AS company_name 
                          FROM work_log JOIN company ON company_id = company.id ";
   //only allow logged in user to see this work log
   $sql_where = " WHERE work_log.user_id = :user_id ";
   
   if (isset($_GET['search'])){ 
      $s = $_GET['search'];
      
      if (empty($sql_where)){ $sql_where = ' WHERE '; }
      else { $sql_where .= ' AND '; }
      $like_search = "'%".$DBH->quote($_GET['search'])."%'";
      $sql_where .= " ( title LIKE $like_search OR description LIKE $like_search OR company.name LIKE $like_search ";

      if (is_numeric($s)){
         $s /= 1.0;
         $sql_where .= ' OR (amount_billed >= '.round($s, 2).' AND amount_billed < '.(round($s, 2)+.01).') ';
         $sql_where .= ' OR (hours >= '.round($s, 2).' AND hours < '.(round($s, 2)+.01).') ';
      }
      
      $sql_where .= " ) ";
   }
   
   if (isset($_GET['company_id'])){ $_GET['company'] = $_GET['company_id']; }
   
   if (isset($_GET['company'])){
      if (empty($sql_where)){ $sql_where = ' WHERE '; }
      else { $sql_where .= ' AND '; }
      
      $sql_where .= " company_id = ".(int)$_GET['company'];
   }
   
   $unpaid = false;
   $paid = false;
   
   if (isset($_GET['paid']) && $_GET['paid'] == '0' || 
       isset($_GET['filter']) && in_array('unpaid', $_GET['filter'])){
      $unpaid = true;
	  if (empty($sql_where)){ $sql_where = ' WHERE '; }
      else { $sql_where .= ' AND '; }
      
	  
	  //we are not using amount_paid field
      //$sql_where .= " amount_paid IS NULL ";  
	  $sql_where .= " (date_paid IS NULL OR date_paid = '0000-00-00')";
   }else if (isset($_GET['paid']) && $_GET['paid'] == '1' || 
             isset($_GET['filter']) && in_array('paid', $_GET['filter'])){
      $paid = true;
	  
	  if (empty($sql_where)){ $sql_where = ' WHERE '; }
      else { $sql_where .= ' AND '; }
      
      $sql_where .= " (date_paid IS NOT NULL OR date_paid != '0000-00-00') ";  
   }
   
   if (isset($_GET['billed'])){
      if (empty($sql_where)){ $sql_where = 'WHERE '; }
      else { $sql_where .= ' AND '; }
      
      if ($_GET['billed']=='1'){
         $sql_where .= " (date_billed IS NOT NULL AND date_billed != '0000-00-00') ";
      }else{
         $sql_where .= " (date_billed IS NULL OR date_billed = '0000-00-00') ";
      }  
   }
   
   if (isset($_GET['locked'])){
      if (empty($sql_where)){ $sql_where = 'WHERE '; }
      else { $sql_where .= ' AND '; }
      
      if ($_GET['locked']=='1'){
         $sql_where .= " locked = 1 ";
      }else{
         $sql_where .= " locked = 0 ";
      }
   }
   
   $stt_aft_date_billed = false;
   if (isset($_GET['after_date_billed']) && ($stt_aft_date_billed = strtotime($_GET['after_date_billed'])) !== false){
      if (empty($sql_where)){ $sql_where = 'WHERE '; }
      else { $sql_where .= ' AND '; }
      
      $sql_where .= " date_billed >= '".date('Y-m-d', $stt_aft_date_billed)."'";
   }
   
   
   $stt_bef_date_billed = false;
   if (isset($_GET['before_date_billed']) && ($stt_bef_date_billed = strtotime($_GET['before_date_billed'])) !== false){
      if (empty($sql_where)){ $sql_where = 'WHERE '; }
      else { $sql_where .= ' AND '; }
      
      $sql_where .= " date_billed <= '".date('Y-m-d', $stt_bef_date_billed)."'";
   }
   
   if (isset($_GET['wid'])) {
      if (empty($sql_where)){ $sql_where = 'WHERE '; }
      else { $sql_where .= ' AND '; }
      
      $sql_where .= ' work_log.id = '. (int)$_GET['wid'];
      $_GET['notes'] = 'full';
   }                       
   
   $sql .= $sql_where." ORDER BY work_log.id DESC";

   if (Site::cfg('verbose_debugging')){
   		echo $sql;
   }

   $prep = $DBH->prepare($sql);
   $result = $prep->execute(array(':user_id'=>(int)$_SESSION['user_id']));

   $rows = array();
   $columns = array();
   $super_total_seconds = 0;
   $super_total_amount = 0.0;
   if (!$result){
      if (Site::cfg('verbose_debugging')){ echo $sql.$DBH->errorInfo(); }
   }
   $cal_events = array();
   while ($row = $prep->fetch()){
      
      $work_log = new work_log($row['id']);
      $row['note_log'] = json_encode($work_log->getNotes());
	  $row['files_log'] = json_encode($work_log->getFiles());
      
      $total_seconds = 0;
	 
      $prep2 = $DBH->prepare("SELECT start_time, stop_time 
                              FROM time_log 
                              WHERE work_log_id = :work_log_id 
                                AND start_time IS NOT NULL 
                                AND stop_time IS NOT NULL");
                $result2 =  $prep2->execute(array('work_log_id'=>(int)$row['id']));
      if ($result2){
         while($time_log_row = $prep2->fetch()){
		    $seconds = strtotime($time_log_row['stop_time']) - strtotime($time_log_row['start_time']);
            $total_seconds += $seconds;
			$hours = $seconds / 60 / 60;
			$hours_rnd = round($hours, 1);
			$dollars = round($row['rate']*$hours, 3);
			$cal_events[] = array('title'=>$row['company_name'].' '.$row['title'].' - $'.$dollars, 
								'description'=> '<b>'.$row['company_name'].' '.$row['title'].'</b><br>'.
												$time_log_row['start_time'].' - '.$time_log_row['stop_time'].'<br>'.
								                round($hours,3).' hrs @ $'.round($row['rate'],3).'/hr = $'.$dollars,
			                    'start'=>$time_log_row['start_time'], 
								'end'=>$time_log_row['stop_time']);
         }
      }
      
      $prep3 = $DBH->prepare("SELECT start_time, stop_time 
                              FROM time_log 
                              WHERE work_log_id = :work_log_id
                                AND start_time IS NOT NULL 
                                AND stop_time IS NULL");
      $result3 =  $prep3->execute(array('work_log_id'=>(int)$row['id']));
      if ($result3){
         if ($uf_time_log_row = $prep3->fetch()){
            $row['_in_progress_'] = true;
         }else{
            $row['_in_progress_'] = false;
         }
      }
                                      
      $super_total_seconds += $total_seconds;
      $row['_calc_hours_'] = $total_seconds / 60 / 60;
      $row['_calc_amount_'] = $row['_calc_hours_'] * $row['rate'];
      $super_total_amount += $row['_calc_amount_'];
      $row['_calc_hours_'] = round($row['_calc_hours_'], 3);
      if (empty($columns)){
         $columns = array_keys($row);
      }
      $rows[] = $row;   
   }//end while fetching all rows


   $super_total_hours = $super_total_seconds / 60 / 60;   
   if (isset($_GET['output'])){
       if ($_GET['output'] == 'json'){
          header('Content-type: text/javascript');
          die(json_encode($rows));
       }
       //spreadsheet output
       else if ($_GET['output'] == 'csv' || $_GET['output'] == 'xls'|| $_GET['output'] == 'xlsx'){
            /** Include PHPExcel */
            require_once(dirname(__FILE__).'/lib/PHPExcel.php');
            
            // Create new PHPExcel object
            $objPHPExcel = new PHPExcel();

            // Set document properties
            $objPHPExcel->getProperties()->setCreator($_SESSION['user_row']['name'])
                                         ->setLastModifiedBy($_SESSION['user_row']['name'])
                                         ->setTitle("Contractor's Work Log Export")
                                         ->setSubject("Contractor's Work Log Export")
                                         ->setDescription("Contractor's Work Log (cworklog.com) Excel Export")
                                         ->setKeywords("cworklog work log time logger billable")
                                         ->setCategory("Work Log Time Clock");       
     
            $objPHPExcel->setActiveSheetIndex(0);
            
            foreach($rows as $i => $row){
              if ($i == 0){
                 foreach($columns as $c => $col_name){
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($c, 1, $col_name); 
                 }
              }
              $col = 0;
              foreach($row as $key => $val){
                 $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $i+2, $val);
                 $col++;
              }
            }
            $objPHPExcel->setActiveSheetIndex(0);

            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            
            //header('Content-Disposition: attachment;filename="report.xlsx"');
            
            if ($_GET['output'] == 'xls'){
                header('Content-Type: application/vnd.ms-excel');
                header('Content-Disposition: attachment;filename="'.$excel_name.'.xls"');
                header('Cache-Control: max-age=0');

                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            }
            else if ($_GET['output'] == 'xlsx')
            {
                header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            }
            else if ($_GET['output'] == 'csv'){
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment;filename="work_log.csv"');
                header('Cache-Control: max-age=0');                      
                $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
                //$objWriter->setDelimiter(',');
                //$objWriter->setEnclosure('');
                //$objWriter->setLineEnding("\r\n");
                //$objWriter->setSheetIndex(0);
            }
            $objWriter->save('php://output');
            exit;
       }
   }
   
   include_once(dirname(__FILE__).'/lib/Site.class.php');
   require_once(dirname(__FILE__).'/lib/work_log.inc.php');
   
   $specific_company_id = isset($_GET['company']) ? (int)$_GET['company'] : false;
   $specific_work_log_id = isset($_GET['wid']) ? (int)$_GET['wid'] : false;
   if (!empty($specific_work_log_id)){

      $prep = $DBH->prepare("SELECT company.*, work_log.* 
                             FROM company JOIN work_log on company.id = company_id 
                             WHERE work_log.id = :work_log_id");
                $result =  $prep->execute(array('work_log_id'=>$specific_work_log_id));
      if ($result && $row = $prep->fetch()) {
      	$specific_company_id = $row['company_id'];
      }
   }
?>
<!doctype html>
<html>
<head>
<title>Work Log</title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<link href="css/work_log.css" media="screen" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="js/work_log_shared.js"></script>
<script type="text/javascript" src="js/work_log.js"></script>
</head>
<body class="yui-skin-sam">
<?PHP 
          Members::MenuBarOpenBottomLeftOpen();
          ?>
            <strong class="OrangeColor">Filter:</strong> 
            <?PHP makeFilterLink('Paid', 'paid', '1'); ?>,<?PHP makeFilterLink('Unpaid', 'paid', '0'); ?> | 
            <?PHP makeFilterLink('Billed', 'billed', '1'); ?>,<?PHP makeFilterLink('Not Billed', 'billed', '0'); ?> | 
            <?PHP makeFilterLink('Locked', 'locked', '1'); ?>,<?PHP makeFilterLink('Unlocked', 'locked', '0'); ?>
            &nbsp; 
            <strong class="OrangeColor">Export:</strong>
            <a href="<?=modQS(array('output'=>'csv'))?>" title="Export entries below to csv format"><img src="images/excel_csv.png"> csv</a>
            <?PHP /*** DOESN'T WORK CORRECTLY <a href="<?=modQS(array('output'=>'xls'))?>" title="Export entries below to Excel xls format"><img src="images/excel_xls.png"> xls</a> **/ ?>
            <a href="<?=modQS(array('output'=>'xlsx'))?>" title="Export entries below to Excel 2007 xlsx format"><img src="images/excel_xlsx.png"> xlsx</a>
            <a target="_blank" href="<?=modQS(array('output'=>'json'))?>" title="Export entries below to json format"><img src="images/json.png"> json</a>
            <?PHP /** Not sure anyone even uses the notes feature right now
            <a href="<?=modQS(array('notes'=>'off'))?>">Notes Off</a> | 
            <a href="<?=modQS(array('notes'=>'cut'))?>">Notes Trimmed</a> | 
             <a href="<?=modQS(array('notes'=>'full'))?>">Notes Full</a> | 
             
             <form name="frmBetween" style="display: inline;" method="GET">
             <label><input type="checkbox" name="filter_by_date" value="1"> Filter by Date</label> 
             <select name="date_type">
                <option value="date_billed">Date Billed</option>
                <option value="date_paid">Date Paid</option>
             </select>
             <input type="text" placeholder="Start Date" name="date_first" value="<?=isset($_REQUEST['date_end']) ? htmlentities($_REQUEST['date_end']) : ''?>" size=10> - 
             <input type="text" placeholder="End Date" name="date_end" value="<?=isset($_REQUEST['date_end']) ? htmlentities($_REQUEST['date_end']) : ''?>" size=10>
             </form>
             ***/ ?>
          <?PHP
          Members::MenuBarBottomLeftCloseRightOpen();
          ?>
          <select style="margin-top: 10px" onChange="if (this.value != ''){ window.location.href = 'work_log.php?company=' + this.value + '<?=modQS('',array('company','company_id','wid'))?>'.replace('?','&'); } else { window.location.href = 'work_log.php<?=modQS('',array('company','company_id'))?>'; }">
            <option value="">-- Company --</option>
            <option value="">[All Companies]</option>
            <?PHP
                 $sql = "SELECT company.name AS company_name, company.id as company_id FROM company WHERE user_id = :user_id ORDER BY name ASC";
                 $prep = $DBH->prepare($sql);
                 $result = $prep->execute(array('user_id'=> (int)$_SESSION['user_id']));
                 while ($row = $prep->fetch()) {
                   ?><option <?=$specific_company_id == $row['company_id'] ? 'selected ' : ''?> value="<?=$row['company_id']?>"><?=htmlentities($row['company_name'])?></option><?PHP
                 }
            ?>
          </select>
            <?PHP
              if (!empty($specific_company_id)){
            ?>
                <select onChange="if (this.value == 'unpaid'){ window.location.href = 'work_log.php?company=<?=$specific_company_id?>&filter[]=unpaid';}else if (this.value != ''){ window.location.href = 'work_log.php?wid=' + this.value; }else{ window.location.href = 'work_log.php?company=<?=$specific_company_id?>'; }">
                <option value="" selected>-- Work Log --</option>
                <option value="" <?=!$unpaid && !$paid? 'selected' : ''?>>[All Work Logs]</option>
                <option value="unpaid" <?=$unpaid ? 'selected' : ''?>>[Unpaid Work Logs]</option>
                <?PHP
                     $sql = "SELECT id, title FROM work_log WHERE company_id = ".$specific_company_id." ORDER BY id DESC";
                     $prep2 = $DBH->prepare($sql);
                     $result = $prep2->execute();
                     while ($row = $prep2->fetch()){
                       ?><option <?=$specific_work_log_id == $row['id'] ? 'selected ' : ''?> value="<?=$row['id']?>"><?=htmlentities($row['title'])?></option><?PHP
                     }
                ?>
                </select>
            <?PHP
             }
             ?>
          <?PHP
          Members::MenuBarBottomRightClose();
          Members::MenuBarClose(); 
?>
<div id="divCheckedSummary">
<div id="divCheckedDetailed">
</div>
</div>
<div class="dataBlk">
<?PHP
   if (isset($_GET['company'])){
      $prep = $DBH->prepare("SELECT name,phone FROM company WHERE id = ".(int)$_GET['company']);
$result = $prep->execute();
      if ($result && $row = $prep->fetch()){
         echo '<h2>'.$row['name'].' - '.$row['phone'].'</h2>';
      }else{
         echo '<h2 style="color: red">Invalid Company ID</h2>';
      }
   }
   
   if (isset($_GET['wid'])) {
      $prep = $DBH->prepare("SELECT company.*, work_log.* 
                             FROM company JOIN work_log on company.id = company_id 
                             WHERE work_log.id = ".(int)$_GET['wid']);
                $result =  $prep->execute();
      if ($result && $row = $prep->fetch()) {
        $company_wl_row = $row;
		echo '<h2><a href="?company='.$row['company_id'].'">'.$row['name'].'</a> - '.$row['phone'].'</h2>';
      	echo '<h2>'.$row['title'].'</h2>';
      }
   }
?>
<?PHP
  if (!empty($ERROR_MSG)){
?>
<div class="error">
<?=$ERROR_MSG?>
</div>
<br><br>
<?PHP } ?>
<div id="basic">
</div>
<div class="SummaryBlock">
<?PHP
 echo 'Total Work Logs: <strong>'.count($rows).'</strong>
      <br>Total Calculated Hours: <strong>'.round($super_total_hours, 3).'</strong> 
      <br>Total Calculated Amount: <strong>$'.round($super_total_amount, 2).'</strong>';
?>
</div>
</div>

<div id="dlgAddNote" title="Add Note" style="display: none;">
<form name="frmAddNote" method="POST" id="frmAddNote">
<input type="hidden" name="work_log_id" value="" />
<textarea name="text" ></textarea>
<input type="submit" value="Add Note" />
</form>
</div>

<div id="dlgAddTime" title="Add Time" style="display: none;">
<form name="frmAddTime" method="POST" action="time_log_show.php" id="frmAddTime" onsubmit="this.action = 'time_log_show.php?wid='+this.wid.value;">
<input type="hidden" name="wid" value="" />
<input type="hidden" name="add_entry" value="1" />
<label>Duration<input title="Enter string such as '15 min, 1:35, 1hr'" name="to_parse_time" value="15 min" /></label>
<label>Notes<input name="notes" value="" /></label>
<input type="submit" value="Add Time" />
</form>
</div>

<style>
table.tblFileMods{
  border: 0px;
}
table.tblFileMods td{ padding:2px; }
</style>

<div id="dlgAddFile" title="Add File/Database Change" style="display: none">
<form name="frmAddFile" method="POST" id="frmAddFile">
<input type="hidden" name="work_log_id" value="" />
<label>Feature 
<input type="text" name="feature" value="" style="width: 90%">
<?PHP
  $sql = "SELECT feature, file, change_type, notes FROM files_log JOIN work_log ON work_log.id = work_log_id WHERE user_id = :user_id ";
  if (isset($_GET['company_id'])){
     $sql .= " AND company_id = ".(int)$_GET['company_id'];
  }
  
  if (isset($company_wl_row)){
     $sql .= " AND company_id = ".$company_wl_row['company_id'];
  }
  
  if (isset($_GET['wid'])) {
     //$sql .= " AND work_log.company_id";
	 //$sql .= " WHERE work_log_id = ".(int)$_GET['wid'];
  }
  $sql .= " ORDER BY feature, file ASC";
  $prep = $DBH->prepare($sql);
  $result = $prep->execute(array(':user_id'=>(int)$_SESSION['user_id']));
  $features_assoc = array();
  $features = array();
  $files_assoc = array();
  $files = array();
  if ($result){
	 while($row = $prep->fetch()){
	    if (!isset($features_assoc[$row['feature']])){
		  $features_assoc[$row['feature']] = 1;
		  $features[] = $row['feature'];
		}
		if (!isset($files_assoc[$row['file']])){
		  $files_assoc[$row['file']] = 1;
		  $files[] = $row['file'];
		}
	 }
  }
?>
<script>
	$(document).ready(function() {
		$(document.frmAddFile.feature).autocomplete({
			minLength: 0,
			delay: 0
		});

		$(document.frmAddFile.file).autocomplete({
			minLength: 0,
			delay: 0
		});

        // load source
        $(document.frmAddFile.file).autocomplete({
            source: <?=json_encode($files)?>
        });


        // load source
        $(document.frmAddFile.feature).autocomplete({
            source: <?=json_encode($features)?>
        });




        $(document.frmAddFile.file).click(function(){
			var input = $(this);
            // fire search event
			input.autocomplete("search", "");
			input.focus();
			return false;
		});		
		
		$(document.frmAddFile.feature).click(function(){
			var input = $(this);

			// fire search event
			input.autocomplete("search", "");
			input.focus();
			return false;
		});		

	});
</script>
</label><br>
<label>Change Type: 
<select name="change_type">
<option value="file" selected>File</option>
<option value="db">Database</option>
</select>
</label>
<br>
<label>File/Table<input type="text" name="file" value="" style="width: 90%"/></label><br>
Notes<br>
<textarea name="notes" style="width: 90%"></textarea><br>
<input type="submit" value="Add File" />
</form>
</div>

<?php /*?><div class="SummaryBlock">
<?PHP
 echo 'Total Work Logs: <strong>'.count($rows).'</strong>
      <br>Total Calculated Hours: <strong>'.round($super_total_hours, 3).'</strong> 
      <br>Total Calculated Amount: <strong>$'.round($super_total_amount, 2).'</strong>';
?>
</div><?php */?>

<form name="frmDeleteNote" style="display: none" method="POST">
<input type="hidden" name="delete_note" value="1"/>
<input type="hidden" name="work_log_id" value=""/>
<input type="hidden" name="note_id" value=""/>
</form>

<form name="frmDeleteFileModification" style="display: none" method="POST">
<input type="hidden" name="delete_file_modification" value="1"/>
<input type="hidden" name="work_log_id" value=""/>
<input type="hidden" name="file" value=""/>
<input type="hidden" name="feature" value=""/>
</form>
<script type="text/javascript">

YAHOO.util.Event.addListener(window, "load", function() {

    YAHOO.example.Basic = function() {
       var gen_jquery_uimenu = function(id, locked, inprogress){
        return '<ul class="actionmenu"><li class="main"><a href="#" onclick="return false;">&nbsp;</a><ul>' + 
            (!locked && !inprogress ? '<li><a href="time_log.php?wid='+ id +'" onclick="document.frmAddTime.wid.value = '+id+'; $(\'#dlgAddTime\').dialog(\'open\'); return false;"><span class="ui-icon ui-icon-clock"></span>Quick-add time</a></li>' : '') +
            (locked ? '<li><a href="#" onclick="glbAjaxUpdateWorkLog('+id+',\'locked\',0, 1); return false;"><span class="ui-icon ui-icon-unlocked"></span>Unlock</a></li>' : 
                   '<li><a href="#" onclick="glbAjaxUpdateWorkLog('+id+',\'locked\',1, 0); return false;"><span class="ui-icon ui-icon-locked"></span>Lock</a></li>') + 
                   '<li><a target="_blank" href="invoicehelper.php?wid='+id+'&format=pdf"><span class="ui-icon ui-icon-document"></span>Create PDF Invoice</a></li>' + 
           (inprogress ? '<li><a href="#" onclick="poptimer(\'time_log.php?tid=latest&wid='+ id +'\'); return false;"><span class="ui-icon ui-icon-refresh"></span>Show Timer in Progress</a></li>' : '') + 
           (!locked && !inprogress ? '<li><a href="time_log.php?wid='+ id +'" onclick="poptimer(\'time_log.php?wid='+ id +'\'); return false;"><span class="ui-icon ui-icon-clock"></span>Start Timer</a></li>' : '') +
           
           ' <li><a target="_blank" href="time_log_show.php?wid='+id+'"><span class="ui-icon ui-icon-calculator"></span>'+(!locked ? 'Edit/':'')+'View Time Log</a></li>' + 
           (!locked ? ' <li><a href="#" onclick="document.frmAddNote.work_log_id.value = '+id+'; $(\'#dlgAddNote\').dialog(\'open\'); return false;"><span class="ui-icon ui-icon-comment"></span>Add Note</a></li>' : '') + 
           (!locked ? ' <li><a href="#" onclick="document.frmAddFile.work_log_id.value = '+id+'; $(\'#dlgAddFile\').dialog(\'open\'); return false;"><span class="ui-icon ui-icon-suitcase"></span>Add File/DB Change</a></li>' : '') + 
        '<li><a href="work_log.php?wid='+id+'"><span class="ui-icon ui-icon-contact"></span>View Details</a></li>' +
        (!locked ? '<li><a title="Permanently delete this work log" href="delete.php?wid='+id+'"><span class="ui-icon ui-icon-close"></span>Delete permanently</a></li>' : '') +
        ' </ul> </li> </ul><span style="clear: both"></span>';
       }
       
       var formatExtra = function(elLiner, oRecord, oColumn, oData) {
                var locked = oRecord.getData('locked') == '1';
                var inprogress = oRecord.getData('_in_progress_');
                var id = oRecord.getData('id');
                var $tr = $(elLiner).closest('tr');

                elLiner.innerHTML = '<input type=checkbox onclick="updateWLChecked(this, '+id+');" onchange="updateWLChecked(this, '+id+');" class="wlcbxes" name="cbx_wl_'+id+'" value="'+id+'"' + (oRecord.getData('__checked__') ? 'checked="checked"' : '') + '>&nbsp;';
                elLiner.innerHTML += locked ? '<a href="#" onclick="glbAjaxUpdateWorkLog('+oRecord.getData('id')+',\'locked\',0, 1); return false;"><img border=0 title="Locked" src="images/lock_locked.gif" /></a>' 
                                           : (inprogress ? ' <a href="#" class="poptimer" onclick="poptimer(\'time_log.php?tid=latest&wid='+ id +'\'); return false;"><img border=0 title="In-Progress" src="images/progressbar_ani.gif" /> <span id="running_time_log"></span></a>' : ' <a href="time_log.php?wid='+ id +'" onclick="poptimer(\'time_log.php?wid='+ id +'\'); return false;"><img border=0 title="Clock In" src="images/arrow_timer.png"/></a>');
                elLiner.innerHTML += gen_jquery_uimenu(oRecord.getData('id'), locked, inprogress);

                //show whole row in progress color
                if (inprogress){
                   $tr.addClass('_in_progress_');
                }else{
                   $tr.removeClass('_in_progress_');
                }
       }
			
        <?PHP
          $allow_edit = true;
        ?>
         var myCustomCompanyFormatter = function(elLiner, oRecord, oColumn, oData) {
             var cid = oRecord.getData("company_id");
             var str = '<a href="?company='+cid+'">'+oData+'</a>';
             elLiner.innerHTML = str;
         };
         
         var formatDescriptionAndNotes = function(elLiner, oRecord, oColumn, oData) {
             elLiner.innerHTML = oData;
             var str = '';
             <?PHP if (!isset($_GET['files']) || $_GET['files'] != 'off'){ ?>
             var files_log = eval('('+oRecord.getData("files_log")+')');
			 var oldfeature = '';
			 var locked = oRecord.getData('locked') == '1';
			 var count = 0;
             for (var i = 0; i < files_log.length; ++i){
				count++;
				if (i == 0){
				   str += '<table class="tblFileMods" border=0 cellspacing=0 cellpadding=3>';
				}
				//if (files_log[i].feature == ''){ files_log[i].feature = '&lt;no feature&gt;'; }
				//display a header every time the feature changes (assume it is ordered by feature)
				if (oldfeature != files_log[i].feature){
				   str += '<tr><td align=right>Feature: &nbsp;</td><td>';
				   str += '<b>'+files_log[i].feature+': </b><br>';
				   str += '</td><td>&nbsp;</td></tr>';
				   oldfeature = files_log[i].feature;
				}
				str += '<tr><td align=right>'+files_log[i].change_type+': &nbsp;</td><td title="'+htmlentities(files_log[i].notes)+'">';
                str += files_log[i].file;
				str += '</td><td>';
				if (!locked){ //allow deleting of file modification notice
				   str += ' <a title="Delete this file change" href="#" onclick="glbDeleteFileChange(' + oRecord.getData('id') + ',\''+ htmlentities(files_log[i].file) + '\',\'' + htmlentities(files_log[i].feature) + '\'); return false;"><img border=0 src="images/trash.png"></a>';
				}else{
				   str += '&nbsp;';
				}
				str += '</td></tr>';
             }
			 if (count > 0){
			   str += '</table>';
			 }
             
             //elLiner.innerHTML += !locked ? '<br><a href="#" onclick="document.frmAddNote.work_log_id.value = '+oRecord.getData('id')+'; $(\'#dlgAddNote\').dialog(\'open\'); return false;"><img title="Add Note" src="images/note_add.png" border=0 /></a>' : '';
             if (str != ''){
                elLiner.innerHTML += '<br> <b style="font-size: 10px">Files Modified:</b>' + str;
             }
             <?PHP } ?>
			 
			 var str2 = '';
             <?PHP if (!isset($_GET['notes']) || $_GET['notes'] != 'off'){ ?>
             var note_logs = eval('('+oRecord.getData("note_log")+')');
             for (var i = 0; i < note_logs.length; ++i){
                str2 += '<br>';
                str2 += '<b style="font-size: 8px">' + note_logs[i].date_added + '</b>: ';
                <?PHP if (!isset($_GET['notes']) || $_GET['notes'] == 'cut'){ ?>
                var threedots = note_logs[i].text;
                if (threedots.length > 103){ threedots = threedots.substr(0, 100) + '...'; }
                str2 += nl2br(threedots);
                <?PHP }else if (isset($_GET['notes']) && $_GET['notes'] == 'full') { ?>
                str2 += nl2br(note_logs[i].text);
                <?PHP } ?>
				if (!locked){
				   str2 += ' <a title="Delete note" href="#" onclick="glbDeleteNote(' + oRecord.getData('id') + ', ' + note_logs[i].id + '); return false;"><img border=0 src="images/trash.png"></a>';
				}
             }
             //elLiner.innerHTML += !locked ? '<br><a href="#" onclick="document.frmAddNote.work_log_id.value = '+oRecord.getData('id')+'; $(\'#dlgAddNote\').dialog(\'open\'); return false;"><img title="Add Note" src="images/note_add.png" border=0 /></a>' : '';
             if (str2 != ''){
                elLiner.innerHTML += '<br> <b style="font-size: 10px">Notes:</b>' + str2;
             }
             <?PHP } ?>
         };
            
        var myColumnDefs = [

            //{key:"check", sortable: false, label:'<input type="checkbox">', formatter:"checkbox"},

            //{key:"id", sortable:true, resizeable:true},
            //{key:"company_id", sortable:true, resizeable:true},
            //<input type=checkbox onclick='var self = this; $(\".wlcbxes\").each(function(){ $(this).prop(\"checked\", !!self.checked); }); updateWLChecked();'>
            {key:"_extra_", label:"<span style='font-size: 10px'><input type='checkbox' id='cbxCheckAll' onchange='checkAllWorkLogs(this.checked)'> Actions</span>", formatter:formatExtra, sortable:false,resizeable:false},
            <?PHP if (!isset($_GET['wid'])) { ?>{key:"title", label: "Title", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>}, <?PHP } ?>
            <?PHP if (!isset($_GET['company']) && !isset($_GET['wid'])){ ?>{key:"company_name", label: "Client", sortable:true, resizeable: true, formatter:myCustomCompanyFormatter},<?PHP } ?>
           {key:"_calc_hours_", label:"Calculated<br>Hours", sortable:true, resizeable:true},
            {key:"hours", label: "Actual<br>Hours", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"rate", label: "Price<br>Rate", formatter:YAHOO.widget.DataTable.formatCurrency, sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"_calc_amount_", label: "Calculated<br>Amount", formatter:YAHOO.widget.DataTable.formatCurrency, sortable:true, resizeable:true},
            {key:"amount_billed", label: "Actual<br>Amount Billed", formatter:YAHOO.widget.DataTable.formatCurrency, sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"date_billed", label: "Date<br>Billed", formatter:YAHOO.widget.DataTable.formatDate, sortable:true, sortOptions:{defaultDir:YAHOO.widget.DataTable.CLASS_DESC},resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"date_paid", label: "Date<br>Paid", formatter:YAHOO.widget.DataTable.formatDate, formatter:YAHOO.widget.DataTable.formatDate, sortable:true, sortOptions:{defaultDir:YAHOO.widget.DataTable.CLASS_DESC},resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"description", label: "Description <br>Files Changed / Notes", width: 25, formatter:formatDescriptionAndNotes, sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>}
                  
        ];	

        var myDataSource = new YAHOO.util.DataSource(<?=json_encode($rows)?>);

        myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
        myDataSource.responseSchema = {
            fields: ["__checked__", //is the field checked or not
                     "id","locked", "company_id","company_name","title","description", 
                     "files_log", "note_log",
                     "_calc_hours_","hours","rate","_calc_amount_",
                     "_in_progress_", "_extra", "amount_billed","date_billed","date_paid"]
        };



        var myDataTable = new YAHOO.widget.DataTable("basic",
                myColumnDefs, myDataSource, {draggableColumns:true});
         
        // --- START EDITING FLOW ---
        var highlightEditableCell = function(oArgs) {
            var elCell = oArgs.target;
            var oRecord = this.getRecord(oArgs.target);
            if (oRecord.getData('locked') == '1'){
               return;
            }
            if(YAHOO.util.Dom.hasClass(elCell, "yui-dt-editable")) {
                this.highlightCell(elCell);
            }
        };
        
        glbAjaxUpdateWorkLog = function(wid, column_key, oNewData, oOldData){
            
            //find record
            var recordSet = glbDataTable.getRecordSet();
            var records = recordSet.getRecords();
            var record = false;
            for (var i = 0; i < records.length; ++i){
                if (records[i].getData('id') == wid){
                   record = records[i];
                   break;
                }
            }
            
            if (record == false){
               alert('Invalid Record.');
               return;
            }
            
            //var oOldData = record.getData(column_key);
            
            
            var querystr = '';
            querystr += 'ajaxedit=1' + 
                        '&wid='+ record.getData('id') +
                        '&f='  + encodeURIComponent(column_key) + 
                        '&v='  + encodeURIComponent(oNewData);
            
            $.ajax({
              type: "GET",
              url: "work_log.php",
              dataType: "json",
              data: querystr
            }).done(function( msg ) {
            
                 if (msg.error){
                    //display error message                  
                    alert( "Error " + msg.error );
                    
                    
                    //set old data back
                    record.setData(column_key, oOldData);
                    recordSet.updateRecordValue ( record , column_key , oOldData );
                    glbDataTable.render(); 
                 }else{
                   //record.setData(msg.row);
                   //lets go ahead and set all the data based on the values
                    for (var col in msg.row){
                       record.setData(col, msg.row[col]);
                       recordSet.updateRecordValue ( record , col , msg.row[col] );   
                    }                   
                    glbDataTable.render();
                 }  
            });         
        
        } 
        
        
        myDataTable.subscribe("cellMouseoverEvent", highlightEditableCell);
        myDataTable.subscribe("cellMouseoutEvent", myDataTable.onEventUnhighlightCell);
        //myDataTable.subscribe("cellClickEvent", myDataTable.onEventShowCellEditor);  
        
        myDataTable.subscribe('cellClickEvent',function(ev) {
             var target = YAHOO.util.Event.getTarget(ev);
             var column = myDataTable.getColumn(target);
             var oRecord = myDataTable.getRecord(target);
             
             if (oRecord.getData('locked') == '1'){
                 return;
             } else {
                 myDataTable.onEventShowCellEditor(ev);
             }
         });
 
        myDataTable.subscribe("editorSaveEvent", function(oArgs) {
            
            var elCell = oArgs.editor.getTdEl();
            var oOldData = oArgs.oldData;
            var oNewData = oArgs.newData;
            
            if (oOldData == oNewData){ return; }
            
            var column_key = oArgs.editor.getColumn(elCell).key;
            var record = oArgs.editor.getRecord();
            
            //alert(YAHOO.lang.dump(oArgs));
            var recordSet = this.getRecordSet();
            
            glbAjaxUpdateWorkLog(record.getData('id'), column_key, oNewData, oOldData);
        });   
        // --- END EDITING FLOW --
 
        myDataTable.subscribe("postRenderEvent", function () {
                $( ".actionmenu" ).menu({ position: { my: "left top", at: "right top" } });
        });
        
	   glbDataTable = myDataTable;


            

        return {
            oDS: myDataSource,
            oDT: myDataTable
        };

    }();

});



var gridHelper = {

   doWithRow : function(action, oRecord){

     if (action == 'select'){

      glbDataTable.selectRow(oRecord);
      oRecord.setData("check", true);        

     }else if (action == 'unselect'){
        glbDataTable.unselectRow(oRecord);
        oRecord.setData("check", false);
     }
   },

   doWithRowsWhere : function(action, column, comparison, value){

	var num_selected = 0;
	var length = glbDataTable.getRecordSet().getLength();

   for (var i = 0; i < length; ++i){

		var oRecord = glbDataTable.getRecord(i);

      var data = oRecord.getData();

		if (comparison == 'csv_has'){
            var dc_ary = data[column].slice(',');
            for (var j = 0; j < dc_ary.length; ++j){
               if (dc_ary[j] == value){
                                 gridHelper.doWithRow(action, oRecord);
                  num_selected++;
                  break;
               }
		      }
		}		

		else if (comparison == 'contains'){

                   if (data[column].indexOf(value) >= 0){
                       gridHelper.doWithRow(action, oRecord);
                       num_selected++;
                    }
                }else if (comparison == '='){

		    if (data[column] == value){
                       gridHelper.doWithRow(action, oRecord);
                       num_selected++;
                    }

		}else if (comparison == '>='){

		    if (data[column] >= value){
                       gridHelper.doWithRow(action, oRecord);
                       num_selected++;
                    }

		}else if (comparison == '>'){

		    if (data[column] > value){
                       gridHelper.doWithRow(action, oRecord);
                       num_selected++;
                    }
		}else if (comparison == '<='){
		    if (data[column] <= value){
                       gridHelper.doWithRow(action, oRecord);
                       num_selected++;
                    }
		}else if (comparison == '<'){
		    if (data[column] < value){
                       gridHelper.doWithRow(action, oRecord);
                       num_selected++;
                    }
		}
	}

	if (num_selected > 0){
     	   glbDataTable.render();
	}

   },

   selectRowsWhere : function(column, comparison, value) { 
       gridHelper.doWithRowsWhere('select', column, comparison, value);
   }

};

checkAllWorkLogs = function(checked){
   $('.wlcbxes').prop('checked', checked).change();
}

</script>
<?PHP
if (isset($_GET['calendar'])){
?>
<link type="text/css" rel="stylesheet" href="js/qTip2/dist/jquery.qtip.css" />
<script type="text/javascript" src="js/qTip2/dist/jquery.qtip.min.js"></script>
<link rel='stylesheet' type='text/css' href='js/fullcalendar/fullcalendar.css' />
<link rel='stylesheet' type='text/css' href='js/fullcalendar/fullcalendar.print.css' media='print' />
<script type='text/javascript' src='js/fullcalendar/fullcalendar.min.js'></script>
<script type='text/javascript'>
	$(document).ready(function() {
		$('#calendar').fullCalendar({
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,basicWeek,basicDay'
			},
			editable: false,
			defaultView: 'basicWeek',
			eventSources: <?=json_encode(array(array('events'=>$cal_events, 'color'=>'black', 'textColor'=>'yellow')))?>,
			eventRender: function(event, element) {
					element.qtip({
						content: event.description
					});
			},
			eventDrop: function(event, delta) {
				alert(event.title + ' was moved ' + delta + ' days\n' +
					'(should probably update your database)');
			}
		});
		
	});
</script>
<br>
<br>
<div id="calendar" style="width: 700px; height: 300px;">
</div>
<?PHP
}//end if calendar in get string
?>

</body>
</html>
