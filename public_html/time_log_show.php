<?PHP
   require_once(dirname(__FILE__).'/lib/db.inc.php');
   require_once(dirname(__FILE__).'/lib/Members.class.php');
   require_once(dirname(__FILE__).'/lib/Site.class.php');
   require_once(dirname(__FILE__).'/lib/work_log.class.php');
   Members::SessionForceLogin();
   

   
   if (isset($_REQUEST['ajax'])){
		$result = array();
		
		if ($_REQUEST['ajax'] == 'datecheck'){
			$result['is_valid'] = strtotime($_REQUEST['value']);
			$result['tid'] = $_REQUEST['timelog_id'];
			if ($result['is_valid'] !== false){
			   $result['timestamp'] = $result['is_valid']; 
			}
			$result['input_name'] = $_REQUEST['name'];

		}else if ($_REQUEST['ajax'] == 'update' || $_REQUEST['ajax'] == 'timecheck'){
		    $sql = "SELECT * FROM time_log JOIN work_log ON time_log.work_log_id = work_log.id WHERE user_id = ".
			       (int)$_SESSION['user_id'].
				   " AND time_log.id = ".(int)$_REQUEST['timelog_id'];
			$prep = $DBH->prepare($sql);
$res = $prep->execute();
			if ($res){
               $tl_row = $prep->fetch();
               if ($tl_row['locked']){
                 //cannot edit a locked work log
                 $result['error'] = 'This work log is currently locked. Cannot update.';
		         die(json_encode($result));
               }
			   //ensure that stop time is > start_time
			   $uname = $_REQUEST['name'];
			   if (strpos($_REQUEST['name'], 'start') !== false){
			      $st_or_sp = 'start_time';
			   }else if (strpos($_REQUEST['name'], 'stop') !== false){
			      $st_or_sp = 'stop_time';
			   }else if (strpos($_REQUEST['name'], 'notes') !== false){
				  $st_or_sp = 'notes';
			   }
			   
			   $stt = strtotime($_REQUEST['value']);
			   if ($st_or_sp !== 'notes' && $stt === false){
			   		die(json_encode(array('error'=>'Invalid time string (parse error)')));
			   }

			   if ($st_or_sp == 'start_time'){
			      $ipt_val = date("Y-m-d H:i:s", strtotime($_REQUEST['value']));
			      if ($ipt_val >= $tl_row['stop_time']){
				     die(json_encode(array('error'=>'Start '.$ipt_val
				     	.' is not BEFORE '.$tl_row['stop_time']).' (stop time)'));
                  }				  
			   }else if ($st_or_sp == 'stop_time'){
			      $ipt_val = date("Y-m-d H:i:s", strtotime($_REQUEST['value']));
			      if ($tl_row['start_time'] >= $ipt_val){
			      	 die(json_encode(array('error'=>'Stop '.$ipt_val
				     	.' is not AFTER '.$tl_row['start_time']).' (start time)'));
                  }				  
			   }else if ($st_or_sp == 'notes'){
				  $ipt_val = $_REQUEST['value'];
			   }

			   if ($_REQUEST['ajax'] == 'timecheck'){
			   		//time conversion seems to be ok
			   	    $result['result_time'] = date("Y-m-d H:i:s", $stt);
			   	    $result['result_seconds'] = strtotime($tl_row['stop_time']) - strtotime($tl_row['start_time']);
			   	    $result['result_minutes'] = $result['result_seconds'] / 60;
			   }else{ //actually updating
			   
				   $sql = "UPDATE time_log SET $st_or_sp = '".$ipt_val.
				          "' WHERE id = ".(int)$_REQUEST['timelog_id'];  
				   $prep = $DBH->prepare($sql);
	$res = $prep->execute();
				   if ($res){
				      $sql = "SELECT * FROM time_log WHERE id = ".(int)$_REQUEST['timelog_id'];
					  $prep = $DBH->prepare($sql);
	$res = $prep->execute();
					  $result['time_log'] = $prep->fetch();
	                  $result['time_log']['__calc_seconds__'] = strtotime($result['time_log']['stop_time']) - strtotime($result['time_log']['start_time']);
				      $result['time_log']['__calc_minutes__'] = number_format($result['time_log']['__calc_seconds__'] / 60, 3); 
	                  $result['success'] = true;
				   }else{
				      $result['error'] = 'Could not update time log';
				   }
			   }
			}else{
			   $result['error'] = 'Invalid time log';
			}
		
		
		}else{
			$result['error'] = 'Invalid command';
		}
   
		die(json_encode($result));
   }
   
   
   $wid = isset($_GET['wid']) ? $_GET['wid'] : false;
   if ($wid === false){
      die('Work log id needed');
   }
   
   try
   {
    $work_log = new work_log($wid);
   }
   catch(Exception $e)
   {
     die('Invalid work log');
   }
   $wl_row = $work_log->getRow(); 
   
   if ($_SERVER['REQUEST_METHOD'] == 'POST'){
     if (!empty($_POST['add_entry'])){
        if (!$wl_row['locked']){    
           $starttime = 'NOW()';
           $stoptime = 'NOW()';
           $hms = '00:00:00';
           $total_min = 0;
           if (isset($_POST['to_parse_time'])){
              list($hours, $minutes, $total_min) = work_log::ParseTimeToHrMin($_POST['to_parse_time']);
              if ($total_min !== false){
                 $hms = work_log::sec2hms($total_min*60);
                 //TODO: make this work
                 $starttime .= ' - INTERVAL '.((int)$total_min).' MINUTE ';
              }
           }
           $sql = "INSERT INTO time_log (id, work_log_id, start_time, stop_time, notes) 
                                                   VALUES (NULL, :wid, $starttime, $stoptime, :notes);";
           $prep = $DBH->prepare($sql);
           if ($prep->execute(array(':wid'=>$wl_row['id'], ':notes'=>$_POST['notes']))){
             $success = 'Time log entry added for '.$total_min.' minutes, please double click to edit';
           }else{
             $error = 'Error adding time log entry: '.$sql;
           }
        }else{
           $error = 'Work log is locked, cannot add time log entry';
        }
     }
   }  
   $time_log = $work_log->fetchTimeLog();
    
   $super_total_seconds = 0;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Time Log</title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<link rel="stylesheet" type="text/css" href="css/theme.css" />
<link rel="stylesheet" type="text/css" href="css/time_log_show.css" />

<script src="js/jquery.dialogPrompt.js"></script>
<script type="text/javascript" src="js/date.js"></script>
</head>
<body>
<?PHP Members::MenuBarCompact(); ?>
<script>
timelog_OnFormSubmit = function(form){
  //check for valid date?
  return false;
}
timelog_cancelChanges = function(ipt){
	var td = ipt.form.parentNode;
	if (td && td.tagName == 'TD'){
	   var div = document.getElementById('calculated_minutes_' + ipt.getAttribute('tid'));
	   if (div){
		   div.style.display = 'none';
	   }
	   var oldTime = td.getAttribute('old_innerHTML');
	   if (oldTime){
		  td.removeAttribute('old_innerHTML'); 
		  td.innerHTML = oldTime;
	   }
	   
	}else{
		//alert('Error, cannot cancel changes');
	}
}


timelog_saveOrCancelChanges = function(ipt){
    //if changed, save the changes!
            var querystr = '';
            querystr += 'ajax=update&name=' + ipt.name + '&value=' + ipt.value + '&timelog_id=' + ipt.getAttribute('tid'); 
            $.ajax({
              type: "GET",
              url: "time_log_show.php",
              dataType: "json",
              data: querystr
            }).done(function( msg ) {
                 if (msg.error){
                    //display error message                  
                    alert( "Error " + msg.error ); 
                 }else{
					//console.log(msg.time_log);
					//console.log(Date.parse(msg.time_log.start_time));
					//console.log(Date.parse(msg.time_log.stop_time));
					var spn_start = document.getElementById('spn_start_time_' + msg.time_log.id);
					if (spn_start){
					   spn_start.innerHTML = Date.parse(msg.time_log.start_time).toString('MMM d, yyyy hh:mm:ss tt');
					   //console.log('000 start: ' + spn_start.innerHTML);
					}
					var spn_stop = document.getElementById('spn_stop_time_' + msg.time_log.id);
					if (spn_stop){
						spn_stop.innerHTML = Date.parse(msg.time_log.stop_time).toString('MMM d, yyyy hh:mm:ss tt');	
						//console.log('000 stop: ' + spn_stop.innerHTML);					   
					}
                    
                    var spn_duration = document.getElementById('spn_duration_' + msg.time_log.id);
                    if (spn_duration){
                       spn_duration.innerHTML = msg.time_log.__calc_minutes__ + ' min';
                    }
					
					var td_notes = document.getElementById('timelog[notes]['+msg.time_log.id+']');
					if (td_notes && td_notes.innerHTML){
						td_notes.innerHTML = msg.time_log.notes;
						//td_notes.setAttribute();
					}
					//refresh the page for now (we can take this out if we update the duration properly)
                    window.location.href = window.location.href;
                 }  
            });	
	
	//if the same, just cancel changes
	timelog_cancelChanges(ipt);
}
timelog_ajaxVerifyDate = function(ipt){
            var querystr = '';
            querystr += 'ajax=datecheck&name=' + ipt.name + '&value=' + ipt.value + '&timelog_id=' + ipt.getAttribute('tid'); 
            $.ajax({
              type: "GET",
              url: "time_log_show.php",
              dataType: "json",
              data: querystr
            }).done(function( msg ) {
                 if (msg.error){
                    //display error message                  
                    alert( "Error " + msg.error ); 
                 }else{
				    if (typeof(msg.is_valid) != 'undefined'){
						var ipt = document.getElementById('id_'+msg.input_name);
						var div = document.getElementById('calculated_minutes_' + msg.tid);
						if (!msg.is_valid){
							if (ipt){ ipt.style.backgroundColor = '#FFB2B2'; }
							if (div){
								div.removeAttribute('title');
								div.style.display = 'none';
								div.innerHTML = 'error';
								div.style.backgroundColor = '';
							}
						}else{ //valid date
							var time_spot = 'start';
							if (ipt){ 
								ipt.style.backgroundColor = '#99FF99';
								if (ipt.name.indexOf('stop') >= 0){
									time_spot = 'stop';
								}else{
									time_spot = 'start';
								}
							}
							var start, stop;
							var start_td = document.getElementById('spn_start_time_' + msg.tid);
							var stop_td = document.getElementById('spn_stop_time_' + msg.tid);
							//console.log(start_td);
							//console.log(stop_td);
							
							var dt = new Date();
						    dt.setTime(msg.timestamp*1000);
							var sender = dt;
							//console.log(ipt.value);
							var ipt_date = new Date(Date.parse(ipt.value));
							
							if (time_spot == 'start' && stop_td){
							    start = ipt_date;
							    stop = Date.parse(stop_td.innerHTML);
							}else if (time_spot == 'stop' && start_td){
								start = Date.parse(start_td.innerHTML);
								stop = ipt_date;
							}
							//console.log('start time: ');
							//console.log(start);
							//console.log('stop time: ');
							//console.log(stop);
							
							var ms = stop.getTime() - start.getTime();
							if (ms <= 0){
								return;
							}
							var min = (ms / 1000 / 60).toFixed(3);
							//console.log('ms diff: ' + ms);
							//console.log('min diff: ' + min);
							
							if (div){ 
							   div.title = 'Calculated Minutes'; 
							   div.style.display = 'block'; 
							   div.innerHTML = min + ' min';
							   div.style.backgroundColor = '#99FF99';
							}
						}
					}
                 }  
            });
}
timelog_makeEditable = function(td, value, opts){
   if (typeof(opts) !== 'object'){ opts = {saveonblur:false}; }
   if (td.getAttribute('old_innerHTML')){ return false; }
   if (value == null){
      value = td.innerHTML;
   }
   td.setAttribute('old_innerHTML', td.innerHTML);
   var cellid = td.getAttribute('cellid');
   td.innerHTML = '<form name="frm_'+cellid+'" onsubmit="return timelog_OnFormSubmit(this);" style="display: inline;" method="POST" style="margin: 0px; padding: 0px;">' + 
                  '<input type="text" title="'+value+'" id="id_'+cellid+'" tid="'+ td.getAttribute('rowid') +'" name="' + td.getAttribute('cellid') + '" value="'+value+'" '+ (opts.saveonblur ? 'onblur="timelog_saveOrCancelChanges(this);" ' : ' ') + 'onchange="timelog_saveOrCancelChanges(this);" onkeyup="if (event.keyCode == 27){ timelog_cancelChanges(this); }else if(event.keyCode == 13){ timelog_saveOrCancelChanges(this); }else{ '+(cellid.indexOf('notes') ? ';' : 'timelog_keyUp(this); timelog_ajaxVerifyDate(this);')+' }" onchange="timelog_saveOrCancelChanges(this)" onblur="timelog_cancelChanges(this);" />' + 
				  '</form>';
   var ipt = document.getElementById('id_'+cellid);
   if (ipt){
	  ipt.focus();
	  ipt.select();
   }
}
timelog_setDuration = function(rowindex, rowid, dtStartTime){
   var dur = prompt('Set total number of minutes.\nUse m.mm or mm:ss format.\nWarning: This will replace your stop time.');
   if (!dur){ return false; }
    var myregexp = /^((\d\d?):(\d\d))|(\d+(\.(\d+))?)$/;
    var match = myregexp.exec(dur);
    if (match != null && match.length > 1) {
        if (match[1]){
           var mm = parseInt(match[2]);
           var ss = parseInt(match[3]);
           dur = mm + (ss / 60);
           //console.log(mm + ':'+ss + ' -> duration: ' + dur);
        }
    } else {
        alert('Invalid format, use x.xx or mm:ss as a format');
        return false;
    }
   
   var dtEndTime = new Date(dtStartTime.getTime() + dur*60000);
   var stop_time_td = $('#stop_time_'+rowindex)[0];
   if (stop_time_td){
     timelog_makeEditable(stop_time_td, dtEndTime.toString('MMM d, yyyy hh:mm:ss tt'), {saveonblur:true});
     return true;
   }else{
     alert('An error occurred, please contact support');
     return false;
   }
}
</script>
<div class="dataBlk" >
<?PHP if (!empty($error)){ ?>
<div class="error"><?=$error?></div>
<?PHP } ?>

<?PHP if (!empty($success)){ ?>
<div class="success"><?=$success?></div>
<?PHP } ?>

<h2><?=$wl_row['title']?></h2>
<h3>Billing To: <?=$wl_row['company_name']?></h3>


<label><input type="checkbox" class="cbxAllNoneTimeLogs">All/None</label>

<select id="selTimeLogsChecked">
<option value="">With checked</option>
<option value="move_to_worklog"> &nbsp; Move to another work log</option>
</select>




<script>
$(function(){
	$('.cbxAllNoneTimeLogs').click(function(){
		var checked = $('.cbxAllNoneTimeLogs').prop('checked');
		$('.cbxTimeLog').prop('checked', checked).last().click();
	});

   countCheckedTimeLogs = function(){
      return $('.cbxTimeLog:checked').length;
   };
   
   getCheckedTimeLogsCSV = function(){
       var s = '';
       $('.cbxTimeLog:checked').each(function(){
          if (s != ''){ s += ','; }
          s += $(this).val();
       });
       return s;
   }
   

   
   $('.cbxTimeLog').click(function(){
   	   var $checked = $('.cbxTimeLog:checked');
   	   var allchecked = $('.cbxTimeLog').length == $checked.length
   	   $(".cbxAllNoneTimeLogs").prop("indeterminate", false);
   	   if (allchecked){
   	   	  	$(".cbxAllNoneTimeLogs").prop("checked", true);
   	   }else if ($checked.length > 0){
   	   	 	//some checked
			$(".cbxAllNoneTimeLogs").prop("indeterminate", true);
   	   }else{ //none checked
   	   		$(".cbxAllNoneTimeLogs").prop("checked", false);
   	   }

       if (typeof(glbTimeLog) == 'undefined'){
   			glbTimeLog = new TimeLog();
   	   }
   	   var min = glbTimeLog.sumMinutes();
   	   var hr = min/60;
   	   var tot_hr = hr.toFixed(2)+' hr';
   	   var tot_min = min.toFixed(1) +' min'; 
   	   //grab dollar hourly rate and remove all non digits or periods
   	   var smoney = $('td#rate').text().replace(/[^\d.]/img, "");

   	   var money = parseFloat(smoney);
   	   var tot_money = '$'+(hr*money).toFixed(2);

       var count = countCheckedTimeLogs();
       $('#selTimeLogsChecked option:eq(0)').text(
       	     'With ('+count+' checked, Totals:' + tot_hr + ' => ' + tot_money + ')'
       	     ).prop('selected', true);

   });
   
   
   glbPostAction = function(action, csv_timelog_ids, ex_obj, callback){
        if (csv_timelog_ids == ''){
           alert('Please choose a timelog');
           return false;
        }
        var obj = {'action': action, 'csv_timelog_ids':csv_timelog_ids};
        if (typeof(ex_obj) == 'object'){
           $.extend(obj, ex_obj);
        }
        $.post('ajax_service.php', obj , 
        function (data){
          if (typeof(callback) == 'function'){ callback(data); }
        }, 'json');
   };
   <?PHP
       //grab all unlocked work logs
       $prep = $DBH->prepare("SELECT work_log.id AS value, CONCAT(company.name, ' - ', work_log.title) AS text 
                                FROM work_log JOIN company ON company.id = work_log.company_id 
                                WHERE work_log.id != :cur_wl_id AND work_log.user_id = :uid AND !locked");  
       $prep->execute(array(':cur_wl_id'=>$wl_row['id'],':uid'=>$_SESSION['user_id']));
       $unlocked_worklogs = $prep->fetchAll(PDO::FETCH_ASSOC);
   ?>
   unlocked_worklog_options = <?=json_encode($unlocked_worklogs)?>;
    
   doWithSelectedTimeLogs = {	
      move_to_worklog:function(){
         if (unlocked_worklog_options.length == 0){
            alert('You have no other unlocked work logs');
            return;
         }
         var options = [];//[{value:'',text:'Choose a work log'}];

         for(var i = 0; i < unlocked_worklog_options.length; ++i){
            options.push(unlocked_worklog_options[i]);
         }
         var size = options.length;
         if (size > 30){ size = 30; }
         
         $.dialogPrompt({title:'Move ' + countCheckedTimeLogs() + ' time logs to work log', type:{type:'select',options:options, size:size}, 
            success:function(value){
                 if (value != ''){
                    glbPostAction('move_timelogs_to_worklog', getCheckedTimeLogsCSV(), {worklog_id: value}, 
                       function(data){
                         if (data.error){
                            alert('Error occurred');
                            console.log(data);
                         }
                         else{
                            alert('Work logs moved =)');
                         }
                         window.location.href = window.location.href;
                       });
                 }else{
                    alert('You have to choose a work log');
                    return false;
                 }
            }});
      }
   };
   
   $('#selTimeLogsChecked').change(function(){
     if (countCheckedTimeLogs() > 0){
        var doo = doWithSelectedTimeLogs[$(this).val()];
        if (typeof(doo) == 'function'){
            doo();    
        }
     }else{
       alert('You must check a time log first');
     }
     $(this).val('');
   });
});
</script>

<table border=0 cellpadding=2 cellspacing=0 class="datatable">
<thead>
<tr><th><form method="POST" style="display:inline"><input title="Add Time Log Entry" type="image" name="add_entry" value="1" style="width: 22px" src="images/plus_sign.png"></form></th><th>Start Time</th><th>Stop Time</th><th>Duration</th><th>Notes</th></tr>
</thead>
<tbody>
<?PHP foreach($time_log as $i => $row){ ?>
<tr>
<td class="rowopts">
<div class="btn left">
<input class="cbxTimeLog" type="checkbox" name="timelogs_checked[]" value="<?=$row['id']?>"/>
</div>
<div class="btn right">
<a class="deltimelog" title="Delete this time log entry" href="delete.php?time_log_id=<?=$row['id']?>"><img src="images/delete.png" style="border: 0px; width: 16px;"/></a>
</div>
</td>
<td class="editable" rowid="<?=$row['id']?>" cellid="timelog[start_time][<?=$row['id']?>]" title="Double-Click to edit start time" ondblclick="timelog_makeEditable(this, '<?=date('M j, Y g:i:s A', strtotime($row['start_time']))?>');"><span id="spn_start_time_<?=$row['id']?>"><?=date('D, M j, Y g:i:s A', strtotime($row['start_time']))?></span></td>
<?PHP
      $prep = $DBH->prepare("SELECT NOW()");
	  $now_res = $prep->execute();
	  $now_row = $prep->fetch(); 
?>
<td class="editable" rowid="<?=$row['id']?>" cellid="timelog[stop_time][<?=$row['id']?>]" title="Double-Click to edit stop time" id="stop_time_<?=$i?>" <?=is_null($row['stop_time'])?'style="color: orange;"':''?> ondblclick="timelog_makeEditable(this, '<?=date('M j, Y g:i:s A', strtotime($row['stop_time']))?>');"><span style="display: none;"><input type="text" name="time[stop_time][<?=$row['id']?>]" value="<?=date('M j, Y g:i:s A', !is_null($row['stop_time']) ? strtotime($row['stop_time']) : strtotime($now_row['NOW()']))?>"/><br><a href="#save" onclick="alert('saved'); return false;">Save</a> <a href="#cancel" onclick="this.parentNode.style.display = 'none'; this.parentNode.parentNode.getElementsByTagName('SPAN')[1].style.display = 'inline'; return false;">Cancel</a>  </span><span id="spn_stop_time_<?=$row['id']?>"><?=date('D, M j, Y g:i:s A', !is_null($row['stop_time']) ? strtotime($row['stop_time']) : strtotime($now_row['NOW()']))?></span></td>
<td align=right <?=is_null($row['stop_time'])?'style="color: orange;"':''?>><?PHP
   if (!is_null($row['stop_time'])){
      $total_seconds = strtotime($row['stop_time']) - strtotime($row['start_time']);
      ?><a href="#setduration" title="Set Time Duration (rather than end time)" 
      onclick="timelog_setDuration(<?=$i?>, <?=$row['id']?>, new Date('<?=date('M j, Y g:i:s A', strtotime($row['start_time']))?>')); return false;"><img align=left border=0 style="width: 16px" src="images/goldalarm.png"/></a>
      &nbsp; <span id="spn_duration_<?=$row['id']?>"><?PHP
      echo number_format($total_seconds / 60, 3).' min';
      ?></span><?PHP
      $super_total_seconds += $total_seconds; 
   }/*else if ($row['stop_time'] < $row['start_time']){
      echo '<b style="color: red">Error</b>';
   }*/
   else {//currently in-progress
      $dynamic_row = $row;
	  $total_seconds = strtotime($now_row['NOW()']) - strtotime($row['start_time']);
      //$total_seconds = (time()-(60*60*6)) - strtotime($row['start_time']);
	  echo '<b class="dynamicTimeNow" id="time_'.$i.'">'.number_format($total_seconds / 60, 3).' min</b>';
	  $dynamic_time_i = $i;
	  $dynamic_time_id = 'time_'.$i;
	  $dynamic_stop_time_id = 'stop_time_'.$i;
	  $dynamic_total_seconds = $total_seconds;
	  $super_total_seconds += $total_seconds;
   }
?><div id="calculated_minutes_<?=$row['id']?>" style="display: none"></div></td>
<td class="editable" id="timelog[notes][<?=$row['id']?>]" rowid="<?=$row['id']?>" cellid="timelog[notes][<?=$row['id']?>]" title="Double-Click to edit notes" ondblclick="timelog_makeEditable(this, null);"
><?=!empty($row['notes']) ? htmlentities($row['notes']) : '&nbsp;'?></td>
</tr>
<?PHP } ?>
</tbody>
<tfoot>
<tr><td colspan=2 align=right>Total</td><td id="total_hours" <?=!empty($dynamic_row)?'style="color: orange;"':''?>><?=number_format($super_total_seconds/60/60, 3).' hrs.'?></td></tr>
<tr><td colspan=2 align=right>Rate</td><td id="rate">$<?=number_format($wl_row['rate'], 2)?></td></tr>
<tr><td colspan=2 align=right >Amount</td><td id="total_amount" <?=!empty($dynamic_row)?'style="color: orange;"':''?>>$<?=number_format($super_total_seconds/60/60 * $wl_row['rate'], 2)?></td></tr>
</tfoot>
</table>
<?PHP
if (!empty($dynamic_time_id)){
?>
	<script>
	var ms_passed = 0;
	var ms_timeout = 500;
	var js_date_format = 'MMM d, yyyy hh:mm:ss tt';
	function updateDynamicTime(){
		ms_passed += ms_timeout;
		var elm_time = document.getElementById('<?=$dynamic_time_id?>');
		var elm_stop_time = document.getElementById('<?=$dynamic_stop_time_id?>');
		var elm_total_hrs = document.getElementById('total_hours');
		var elm_total_amount = document.getElementById('total_amount');
		var dynamic_total_seconds = <?=$dynamic_total_seconds?> + (ms_passed / 1000);
		var dynamic_total_hours = dynamic_total_seconds / 60 / 60;
		var super_total_seconds = <?=$super_total_seconds?>;
		var work_log_amount = Math.round((<?=$wl_row['rate']?> * (super_total_seconds + dynamic_total_seconds) / 60 / 60)*Math.pow(10,2))/Math.pow(10,2);	  
		elm_stop_time.innerHTML = (new Date).toString(js_date_format);
		elm_time.innerHTML = (dynamic_total_seconds / 60).toFixed(3) + ' min'; //(new Date).clearTime().addSeconds(Math.round(dynamic_total_seconds)).toString('H:mm:ss');
		elm_total_hrs.innerHTML = dynamic_total_hours.toFixed(2) + ' hrs.';
		elm_total_amount.innerHTML = '$' + work_log_amount.toFixed(2);
		setTimeout(updateDynamicTime, ms_timeout);
	}
	setTimeout(updateDynamicTime, ms_timeout);
	</script>
<?PHP
}


//select all features that this user worked on for this particular client
$prep = $DBH->prepare('SELECT feature, date_modified FROM files_log 
                      WHERE work_log_id = :wid
                      GROUP BY feature
                      ORDER BY date_modified DESC');
$prep->execute(array(':wid'=>$wid));
$features = $prep->fetchAll(PDO::FETCH_ASSOC);


$assoc_w_this_work_log = 0;


foreach($features as &$feature){
   $prep2 = $DBH->prepare('SELECT file, change_type, notes, date_modified, work_log_id 
                      FROM files_log 
                     WHERE feature = :feature 
                       AND work_log_id IN (SELECT id FROM work_log WHERE company_id = :company_id)');
   $prep2->execute(array(':feature'=>$feature['feature'], ':company_id'=>$wl_row['company_id'])); 
   $files = $prep2->fetchAll(PDO::FETCH_ASSOC);
   
   $feature['_files_'] = $files; 
      
}

if (count($features) > 0){
?><br><br>
<h3><?=count($features)?> features for this worklog</h3><?PHP
  if (count($features) > 0){
     foreach($features as $i => $feature){
       ?><br><h2><?=$feature['feature']?></h2><?PHP
       ?><table border=0><tr><th>Type</th><th>File</th><th>Last modified</th></tr><?PHP
       foreach($feature['_files_'] as $j => $file_log){
          ?><tr><td><?=$file_log['change_type']?></td>
                <td <?=$wid == $file_log['work_log_id'] ? 'style="color: red"':''?>><?=$file_log['file']?></td>
                <td><?=$file_log['date_modified']?></td></tr><?PHP
       }
       ?></table><?PHP       
     }
  }
}
?>
</div>
<script src="js/timelog.js"></script>
</body>
</html>
