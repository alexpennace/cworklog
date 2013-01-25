<?PHP
/** 
 * This page is to permanently delete things, it serves as a confirmation page
 */
 
require_once('lib/Members.class.php');
require_once('lib/misc.inc.php');
require_once('lib/Site.class.php');
require_once('lib/work_log.class.php');

Members::SessionForceLogin();
$areyousuremessage = '';
$hidden_elements = array();

function delete_work_log($worklog){
  global $DBH;
  $stmt = $DBH->prepare('DELETE FROM time_log WHERE work_log_id = :worklog_id');
  $deleted_timelogs = $stmt->execute(array(':worklog_id'=>$worklog['id']));
  
  $stmt = $DBH->prepare('DELETE FROM files_log WHERE work_log_id = :worklog_id');
  $deleted_fileslogs = $stmt->execute(array(':worklog_id'=>$worklog['id']));  
  
  $stmt = $DBH->prepare('DELETE FROM note_log WHERE work_log_id = :worklog_id');
  $deleted_notelogs = $stmt->execute(array(':worklog_id'=>$worklog['id']));
 
  if ($deleted_timelogs && $deleted_fileslogs && $deleted_notelogs){
     //success deleting all attached information, now go ahead and delete the work log
     $stmt = $DBH->prepare('DELETE FROM work_log WHERE id = :worklog_id AND user_id = :user_id');
     $deleted_worklog = $stmt->execute(array(':worklog_id'=>$worklog['id'], ':user_id'=>$_SESSION['user_id']));
     if ($deleted_worklog){ return true; }
  }
  return array('deleted_worklog'=>$deleted_worklog, 
              'deleted_timelogs'=>$deleted_timelogs, 
             'deleted_fileslogs'=>$deleted_fileslogs, 
             'deleted_notelogs'=>$deleted_notelogs);
}


if (isset($_REQUEST['wid'])){
   $hidden_elements[] = array('delete'=>'work_log');
   $hidden_elements[] = array('wid'=>(int)$_REQUEST['wid']);
   require_once('lib/work_log.class.php');
   try{
     $wl = new work_log($_REQUEST['wid']);
     $wl_row = $wl->getRow();
     if ($wl_row['locked']){
        $warning = 'This work log is locked and must be unlocked before deleted';
     }else{
        if (isset($_POST['delete']) && $_POST['delete'] == 'work_log'){
           $deleted = delete_work_log($wl_row);
           if ($deleted === true){
             $success = 'Successfully deleted <b>'.$wl_row['title'].'</b> and all attached time logs';
           }else{
             $warning = 'Some items were not able to be deleted: '.implode($deleted, ',');
           }
        }else{
           $timelogs = $wl->fetchTimeLog();
           $areyousuremessage = 'Are you sure you want to delete work log <b>'.htmlentities($wl_row['title']).'</b> with <b>'.count($timelogs).'</b> attached time logs.';
        }
     }
   }catch(Exception $e){
       $warning = 'This work log has already been deleted or does not exist'; 
   }
}
else if (isset($_REQUEST['time_log_id'])){
   $hidden_elements[] = array('delete'=>'time_log');
   $hidden_elements[] = array('time_log_id'=> (int)$_REQUEST['time_log_id']);
   
   $prep = $DBH->prepare('SELECT * FROM time_log 
                          WHERE id = :time_log_id 
                            AND work_log_id 
                             IN (SELECT id FROM work_log WHERE user_id = :user_id)');
   $prep->execute(array(':time_log_id'=>$_REQUEST['time_log_id'], ':user_id'=>$_SESSION['user_id']));
   $time_log = $prep->fetch(PDO::FETCH_ASSOC);
   
   if (empty($time_log)){
      $warning = 'This time log has already been deleted or does not exist'; 
   }else{
     $wl = new work_log($time_log['work_log_id']);
     $wl_row = $wl->getRow();
     if ($wl_row['locked']){
       $warning = 'The work log is locked and must be unlocked before child time log can be deleted.';
     }else{
        //work log not locked
        if (isset($_POST['delete']) && $_POST['delete'] == 'time_log'){
           $prep2 = $DBH->prepare('DELETE FROM time_log WHERE id = :tid');
           $prep2->execute(array(':tid'=>$time_log['id']));
           $duration = is_null($time_log['stop_time']) ? 'unfinished' : work_log::sec2hms(strtotime($time_log['stop_time']) - strtotime($time_log['start_time']));
           $success = 'Successfully deleted <b>'.$duration.'</b> time log. <br>
               <a href="time_log_show.php?wid='.$wl_row['id'].'">Go back to time logs</a>';  
        }else{
           if (is_null($time_log['stop_time'])){
             $areyousuremessage = 'Are you sure you want to delete the unfinished time log starting at '.date('M j g:i:s a',strtotime($time_log['start_time']));
           }else{
              $areyousuremessage = 'Are you sure you want to delete the time log containing <b>'.
                       work_log::sec2hms(strtotime($time_log['stop_time']) - strtotime($time_log['start_time'])).'</b> hours of work time?';
           }
        }
     }
   
   }
}
else if (isset($_REQUEST['company_id'])){
   $hidden_elements[] = array('delete'=>'company');
   $hidden_elements[] = array('company_id'=> (int)$_REQUEST['company_id']);
   $stmt = $DBH->prepare('SELECT * FROM company WHERE user_id = :user_id AND id = :company_id');
   $stmt->execute(array(':user_id'=>$_SESSION['user_id'], ':company_id'=>$_REQUEST['company_id']));
   $company = $stmt->fetch(PDO::FETCH_ASSOC);
   if (!$company){ $warning = 'This company has already been deleted or does not exist'; }
   else{
       
       
       
       $stmt = $DBH->prepare('SELECT * FROM work_log WHERE user_id = :user_id AND company_id = :company_id');
       $stmt->execute(array(':company_id'=>$company['id'], ':user_id'=>$_SESSION['user_id']));
       $work_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
       if ($work_logs && count($work_logs) > 0){
          $warning = 'You must first delete <a href="work_log.php?company_id='.$company['id'].'">'.count($work_logs).' work log(s)</a> individually before deleting <b>'.$company['name'].'</b> company.';
       }//end if work logs exist
       else{
          //all work logs have been deleted, go ahead and allow deletion of the company
          if (isset($_POST['company_id']) && isset($_POST['delete']) && $_POST['delete'] == 'company'){
                       $stmt = $DBH->prepare('DELETE FROM company WHERE id = :company_id');
                       $deleted_company = $stmt->execute(array(':company_id'=>$company['id']));
                       if ($deleted_company && $stmt->rowCount() == 1){
                          //success!!
                          $success = 'Successfully deleted company <b>'.$company['name'].'</b>';
                       }else{
                          $warning = 'Could not delete company, but deleted the rest of attached information';
                       }           
          }else{ //not deleted yet, just confirmation
             $areyousuremessage = 'Are you sure you want to delete <b>'.$company['name'].'</b>?';
          }
       }
       
   }//end if company exists
   
   
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
<head>
<title>Confirm Permanent Delete - <?=Site::$title?></title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<script type="text/javascript" src="js/work_log_shared.js"></script>
</head>
<body class="yui-skin-sam">
<?PHP Members::MenuBar(); ?>

<?PHP 
if (!empty($warning)){
?><div class="warning">
<?=$warning?>
</div><?PHP
}else if (!empty($success)){
?><div class="success">
<?=$success?>
</div><?PHP
}
else if (!empty($areyousuremessage)){ ?>
<form id="Deletelog" method="POST" onsubmit="if (confirm('You will permanently lose all information. Are you sure you want to continue?')){ return true; }else{ return false; }">
<?PHP
  foreach($hidden_elements as $he){
     foreach($he as $key => $value){
        ?><input type="hidden" name="<?=$key?>" value="<?=$value?>"/><?PHP
     }
  }
?>
<div class="areyousure">
<?=$areyousuremessage?>
<br>
<input type="submit" name="confirm_delete" value="Yes"/> <input type="button" value="No" onclick="window.history.back(); return false;"/>
</div>
</form>
<?PHP } ?>

</body>
</html>
