<?PHP
  require_once('lib/db.inc.php');
  require_once('lib/Members.class.php');
  require_once('lib/Site.class.php');
  Members::SessionForceLogin();
  $start_time = false;
  $work_log_id = isset($_GET['wid']) ? (int)$_GET['wid'] : false;
  $resume_time_log = false;

  
  //get a list of work logs that are not locked and unpaid so we can keep working on it
  $sql = "SELECT work_log.id, work_log.rate, work_log.title, work_log.description, company.name AS company_name FROM work_log JOIN company WHERE work_log.locked = 0 AND 
      (work_log.date_billed IS NULL OR work_log.date_billed = '0000-00-00') AND (work_log.date_paid IS NULL OR work_log.date_paid = '0000-00-00') AND work_log.company_id = company.id AND work_log.user_id = ".(int)$_SESSION['user_id'].
	  " ORDER BY name ASC";
   $prep = $DBH->prepare($sql);
$result = $prep->execute();
   if (!$result){
      die($DBH->errorInfo());
   }
   $work_log_rows = array();
   while($row = $prep->fetch()){ 
      if ($work_log_id == false){ $work_log_id = $row['id']; }
      $work_log_rows[] = $row;
   }
   
  if (!is_numeric($work_log_id)){
	 die('Invalid work log id (wid=X) or no available work logs found');
  }
    
   
  $prep = $DBH->prepare("SELECT * FROM work_log WHERE id = $work_log_id AND user_id = ".(int)$_SESSION['user_id']);
$result = $prep->execute();
  if (!$result){
    die($DBH->errorInfo());
  }
  $work_log_row = $prep->fetch();
  if (!$work_log_row){
    die('You must have a valid work_log_id (wid)');
  }else if ($work_log_row['locked']){
    die('This work_log has been locked.');
  }
  require_once('lib/work_log.class.php');
  $work_log = new work_log($work_log_id);
  $work_log_row = $work_log->getRow();
  //now we have total hours for this work log
  //calculate total hours for the day
  if (isset($_GET['tid']) && $_GET['tid'] == 'latest'){
     $resume_time_log = true;
     $time_log_id = false;
	 $prep = $DBH->prepare("SELECT * FROM time_log WHERE work_log_id = $work_log_id AND stop_time IS NULL ORDER BY start_time DESC LIMIT 1");
$result = $prep->execute();
	 if ($result && $time_log_row = $prep->fetch()){
		   $time_log_id = $time_log_row['id'];
	 }
	 else{
	    die('There is no latest time log for this work log');
	 }
  }else{
     $time_log_id = isset($_GET['tid']) ? (int)$_GET['tid'] : false;
  }
 
  if ($time_log_id > 0 && $work_log_id > 0 && isset($_REQUEST['notes'])){
     $sql = "UPDATE time_log SET notes = '".$_REQUEST['notes']."' WHERE work_log_id = ".$work_log_id." AND id = ".$time_log_id." LIMIT 1";
     $prep = $DBH->prepare($sql);
$result = $prep->execute();
  }
 
  $company = $work_log_row['company_id'];
  
  //grab company info (make sure it is valid)
  $prep = $DBH->prepare("SELECT name FROM company WHERE id = $company");
$result = $prep->execute();
  if (!$result){
     die('Company '.$company.' does not exist.'.$DBH->errorInfo());
  }
  $company_row = $prep->fetch();
  
  if (!$company_row){
    die('Company '.$company.' does not exist.');
  }
  
    
  if ($time_log_id !== false && !$resume_time_log)
  {
     $prep = $DBH->prepare("SELECT * FROM time_log WHERE id = $time_log_id");
$result2 = $prep->execute();
     if (!$result2) {
     	  die($DBH->errorInfo());
     }
     $time_log_row = $prep->fetch();
     if (!$time_log_row){
        //die('Invalid time_log_row');
        
        $prep = $DBH->prepare("INSERT INTO time_log (id, work_log_id, start_time, stop_time) VALUES
                     (NULL, $work_log_id, NOW(), NULL);
                $result_ins =  $prep->execute();");
        if (!$result_ins){
           die('Could not create new time log database');
        }else{
           $time_log_id = $DBH->lastInsertId();
           $prep = $DBH->prepare("SELECT * FROM time_log WHERE id = $time_log_id");
$result2 = $prep->execute();
           $time_log_row = $prep->fetch();
           $start_time = $time_log_row['start_time'];
        }
        
     }else{ //retrieve information about time_log_id
        $start_time = $time_log_row['start_time'];
        $stop_time = $time_log_row['stop_time'];
        
        if (is_null($stop_time)){
           $prep = $DBH->prepare("UPDATE time_log SET stop_time = NOW() WHERE id = $time_log_id");
$result_upd = $prep->execute();
           if ($result_upd){
              $done_logging_time = true;
           }
        }
        
     }
  }
  
  if (!empty($done_logging_time)){
     //start over with a new time_log_id, using the same work_log_id
     $start_time = false;
     $time_log_id = false;
     
  }
  
  

  ?>
  <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
  <html>
  <head>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
  <script type="text/javascript" src="js/date.js"></script>
  <script type="text/javascript">

    $(window).resize(function() {
        $(".bigButton").css('z-index', 1);
    });
  
  
   var total_ms = <?=$work_log_row['_calc_hours_']*60*60*1000?>;
   var hourly_rate = 0.0;
   <?PHP if (!empty($work_log_row['rate'])){?>
   hourly_rate = <?=$work_log_row['rate']?>;
   <?PHP } ?>
   var ms = 0;
    <?PHP
     if ($resume_time_log){
    ?>
       ms = <?= 1000 * ((time()-60*60*7) - strtotime($time_log_row['start_time']) )?>;
    <?PHP 
    }
	?>
   var state = 0;
   
   function padnumber(num){
     if (num < 10){
        return 0+""+num;
     }else{
        return num;
     }
   }
   
   function prettyms(ms){
	  //thank you date.js (www.datejs.com)
	  return (new Date).clearTime()
          .addSeconds(Math.round(ms/1000))
          .toString('H:mm:ss');
	  /**
	  hh = Math.round(ms / (1000*60*60));
      mm = Math.round((ms % (1000*60*60)) / (1000*60));
      ss = Math.round(((ms % (1000*60*60)) % (1000*60)) / 1000);
      **/
      return padnumber(hh) + ':' + padnumber(mm) + ':' + padnumber(ss);
   }
   
   function startstop() {
      if (state == 0) {
		  state = 1;
		  then = new Date();
		  then.setTime(then.getTime() - ms);
      } else {
		  state = 0;
		  now = new Date();
		  ms = now.getTime() - then.getTime();
		  var prettytime = prettyms(ms);
		  var session_amount = Math.round((hourly_rate * ms / 1000 / 60 / 60)*Math.pow(10,2))/Math.pow(10,2);
		  var work_log_amount = Math.round((hourly_rate * (total_ms + ms) / 1000 / 60 / 60)*Math.pow(10,2))/Math.pow(10,2);
		  document.getElementById('time').innerHTML = "Session Time: " + prettytime + " Amount: " + session_amount;
		  document.getElementById('wl_time').innerHTML = 'Total Work Log Time: ' + prettyms(total_ms + ms) + " Amount: " + work_log_amount;
		  document.title = prettytime;
      }
   }
   function swreset() {
      state = 0;
      ms = 0;
	  var prettytime = prettyms(ms);
	  document.getElementById('time').innerHTML = prettytime;
	  document.title = prettytime;
   }
   function display() {
      setTimeout("display();", 50);
      if (state == 1)  {
	      now = new Date();
		  ms = now.getTime() - then.getTime();
		  var prettytime = prettyms(ms);
		  var session_amount = Math.round((hourly_rate * ms / 1000 / 60 / 60)*Math.pow(10,2))/Math.pow(10,2);
		  var work_log_amount = Math.round((hourly_rate * (total_ms + ms) / 1000 / 60 / 60)*Math.pow(10,2))/Math.pow(10,2);
		  var s = prettytime + " @ $" + hourly_rate.toFixed(2) +" hr = $" + session_amount.toFixed(2);
		  var wl_time = prettyms(total_ms + ms) + " @ $" + hourly_rate.toFixed(2) +" hr = $" + work_log_amount.toFixed(2);
		  document.title = s;
		  document.getElementById('time').innerHTML = s;
		  document.getElementById('wl_time').innerHTML = wl_time;
      }
   }
   
   function winClose()
   {
       if (confirm("Are you sure you want to navigate away from this page?"))
       {
            return true;
       }
   
       return false;
   }   
   
  </script>
  <title><?=$company_row['name']?> - Time Log</title>
  <link rel="stylesheet" type="text/css" href="css/stylesheet.css" />
  <link rel="stylesheet" type="text/css" href="css/theme.css" />
  <?PHP
    include_once('lib/Mobile_Detect.php');
    $browser_detect = new Mobile_Detect();
  ?>
  <style>
  #company_name{ font-weight: bold; }
  .bigButton { text-align: center; width: 100%; <?=(strlen(strstr($_SERVER['HTTP_USER_AGENT'],"Firefox")) > 0 ? 'height: 170px;' : 'height: 80%; ')?>height: 80vh;  <?=$browser_detect->isMobile() ? 'font-size: 270px; font-size: 75vw;' : 'font-size: 75%; font-size: 35vw;'?> }
  .smallButton { margin-top: -13px; width: 100%; height: 50px; font-size: 15px; }
  #time, #wl_time{ font-size: 10px; }
  form { display: block; margin: 0px; padding: 0px;}
  div.timelog_image_links{ border:1px dashed gray;background-color:light-gray }
  div.timelog_image_links img{ width: 16px; height: 16px; border: 0px; padding: 2px; }
  
  #divButtonStartStop{
     height: 80%;
     width: 90%; 
     border: 4px outset black; 
     background-color: silver; 
     text-align: center;  
     vertical-align: middle;
     cursor: pointer;
     font-size: 5em;
     margin: 10px;
  }
  #divButtonStartStop.Start, divButtonStartStop.Start:active{
    background: rgb(59,103,158); /* Old browsers */
    background: -moz-linear-gradient(top,  rgba(59,103,158,1) 0%, rgba(43,136,217,1) 50%, rgba(32,124,202,1) 51%, rgba(125,185,232,1) 100%); /* FF3.6+ */
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(59,103,158,1)), color-stop(50%,rgba(43,136,217,1)), color-stop(51%,rgba(32,124,202,1)), color-stop(100%,rgba(125,185,232,1))); /* Chrome,Safari4+ */
    background: -webkit-linear-gradient(top,  rgba(59,103,158,1) 0%,rgba(43,136,217,1) 50%,rgba(32,124,202,1) 51%,rgba(125,185,232,1) 100%); /* Chrome10+,Safari5.1+ */
    background: -o-linear-gradient(top,  rgba(59,103,158,1) 0%,rgba(43,136,217,1) 50%,rgba(32,124,202,1) 51%,rgba(125,185,232,1) 100%); /* Opera 11.10+ */
    background: -ms-linear-gradient(top,  rgba(59,103,158,1) 0%,rgba(43,136,217,1) 50%,rgba(32,124,202,1) 51%,rgba(125,185,232,1) 100%); /* IE10+ */
    background: linear-gradient(to bottom,  rgba(59,103,158,1) 0%,rgba(43,136,217,1) 50%,rgba(32,124,202,1) 51%,rgba(125,185,232,1) 100%); /* W3C */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#3b679e', endColorstr='#7db9e8',GradientType=0 ); /* IE6-9 */
  }
  #divButtonStartStop.Start:hover{
  
    background: rgb(125,185,232); /* Old browsers */
    /* IE9 SVG, needs conditional override of 'filter' to 'none' */
    background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iIzdkYjllOCIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjQ5JSIgc3RvcC1jb2xvcj0iIzIwN2NjYSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjUyJSIgc3RvcC1jb2xvcj0iIzJiODhkOSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiMzYjY3OWUiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
    background: -moz-linear-gradient(top,  rgba(125,185,232,1) 0%, rgba(32,124,202,1) 49%, rgba(43,136,217,1) 52%, rgba(59,103,158,1) 100%); /* FF3.6+ */
    background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(125,185,232,1)), color-stop(49%,rgba(32,124,202,1)), color-stop(52%,rgba(43,136,217,1)), color-stop(100%,rgba(59,103,158,1))); /* Chrome,Safari4+ */
    background: -webkit-linear-gradient(top,  rgba(125,185,232,1) 0%,rgba(32,124,202,1) 49%,rgba(43,136,217,1) 52%,rgba(59,103,158,1) 100%); /* Chrome10+,Safari5.1+ */
    background: -o-linear-gradient(top,  rgba(125,185,232,1) 0%,rgba(32,124,202,1) 49%,rgba(43,136,217,1) 52%,rgba(59,103,158,1) 100%); /* Opera 11.10+ */
    background: -ms-linear-gradient(top,  rgba(125,185,232,1) 0%,rgba(32,124,202,1) 49%,rgba(43,136,217,1) 52%,rgba(59,103,158,1) 100%); /* IE10+ */
    background: linear-gradient(to bottom,  rgba(125,185,232,1) 0%,rgba(32,124,202,1) 49%,rgba(43,136,217,1) 52%,rgba(59,103,158,1) 100%); /* W3C */
    filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#7db9e8', endColorstr='#3b679e',GradientType=0 ); /* IE6-8 */  
  }
  
  #divButtonStartStop.Stop{
     border: 4px outset black;
        background: rgb(255,48,25); /* Old browsers */
        /* IE9 SVG, needs conditional override of 'filter' to 'none' */
        background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2ZmMzAxOSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNjZjA0MDQiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
        background: -moz-linear-gradient(top,  rgba(255,48,25,1) 0%, rgba(207,4,4,1) 100%); /* FF3.6+ */
        background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(255,48,25,1)), color-stop(100%,rgba(207,4,4,1))); /* Chrome,Safari4+ */
        background: -webkit-linear-gradient(top,  rgba(255,48,25,1) 0%,rgba(207,4,4,1) 100%); /* Chrome10+,Safari5.1+ */
        background: -o-linear-gradient(top,  rgba(255,48,25,1) 0%,rgba(207,4,4,1) 100%); /* Opera 11.10+ */
        background: -ms-linear-gradient(top,  rgba(255,48,25,1) 0%,rgba(207,4,4,1) 100%); /* IE10+ */
        background: linear-gradient(to bottom,  rgba(255,48,25,1) 0%,rgba(207,4,4,1) 100%); /* W3C */
        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ff3019', endColorstr='#cf0404',GradientType=0 ); /* IE6-8 */
  }
  #divButtonStartStop.Stop:hover{
        background: rgb(207,4,4); /* Old browsers */
        /* IE9 SVG, needs conditional override of 'filter' to 'none' */
        background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2NmMDQwNCIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiNmZjMwMTkiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
        background: -moz-linear-gradient(top,  rgba(207,4,4,1) 0%, rgba(255,48,25,1) 100%); /* FF3.6+ */
        background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(207,4,4,1)), color-stop(100%,rgba(255,48,25,1))); /* Chrome,Safari4+ */
        background: -webkit-linear-gradient(top,  rgba(207,4,4,1) 0%,rgba(255,48,25,1) 100%); /* Chrome10+,Safari5.1+ */
        background: -o-linear-gradient(top,  rgba(207,4,4,1) 0%,rgba(255,48,25,1) 100%); /* Opera 11.10+ */
        background: -ms-linear-gradient(top,  rgba(207,4,4,1) 0%,rgba(255,48,25,1) 100%); /* IE10+ */
        background: linear-gradient(to bottom,  rgba(207,4,4,1) 0%,rgba(255,48,25,1) 100%); /* W3C */
        filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#cf0404', endColorstr='#ff3019',GradientType=0 ); /* IE6-8 */
  }
  <?PHP
  if (!($time_log_id == false && !$resume_time_log)){
  ?>
  body{ background-color: red; }
  <?PHP
  }
  ?>
  </style>
  </head>
  <body <?PHP if ($time_log_id > 0) { /*echo 'onUnload="return winClose();; return false;"';*/ }?> >
 
  <form name="frmStartStop" method="GET" onsubmit="if (this.notes && this.notes.value == ''){ var s = prompt('Enter notes for this time log', ''); if (s === null){ return false; }else{ this.notes.value = s; return true; } } return true;">
  <input type="hidden" name="smallbtn" value="<?=!empty($_GET['smallbtn']) ? '1' : '0'?>"/>
  <input type="hidden" name="tid" value="<?=$time_log_id?>" />
  <input type="hidden" name="wid" value="<?=$work_log_id?>" />
  <?PHP if ($time_log_id > 0){ ?>
  <input type="hidden" name="notes" maxlength="255" value="" /><br>
  <?PHP } ?>
  <input style="display: none" type="submit" class="<?=!empty($_GET['smallbtn']) ? 'smallButton' :'bigButton'?>" value="<?=$time_log_id == false && !$resume_time_log  ? 'Start' : 'Stop'?>"/>
  </form>
  <div id="divButtonStartStop" class="<?=$time_log_id == false && !$resume_time_log  ? 'Start' : 'Stop'?>">
  <?=$time_log_id == false && !$resume_time_log  ? 'Start' : 'Stop'?>
  </div>
  <script>
  $("#divButtonStartStop").click(function(){
     document.frmStartStop.submit(); 
     return false;
  });
  </script>
  <?PHP if ($time_log_id > 0){ ?>
  <script>
    display();
    startstop();
  </script>
  <?PHP } ?>  
  <div>
  <?PHP
    if ($time_log_id == false && !$resume_time_log)
	{
	?>
	  <form method="GET">
	  <input type="hidden" name="smallbtn" value="<?=!empty($_GET['smallbtn'])?'1':'0'?>"/>
	  <select id="selbox_unlockedunpaid_worklogs" style="width: 100%" name="wid" <?=!empty($_GET['smallbtn']) ? 'style="font-size: 75%"':''?> onchange="if (this.value != ''){ this.form.submit(); }">
	  <option value="">-- Choose a work log --</option>
	  <?PHP
	  $selected = false;
	  foreach($work_log_rows as $i => $wlrow){
		 if ($work_log_row['id'] == $wlrow['id']){
		    $selected = true;
		 }
		 echo '<option title="'.htmlentities($wlrow['description']).'" value='.$wlrow['id'].' '.($work_log_row['id'] == $wlrow['id'] ? 'selected="selected" ':'').'>'.
			   htmlentities($wlrow['company_name'].' ($'.$wlrow['rate'].'/hr) - '.$wlrow['title']).'</option>';
	  }
	  if (!$selected){
	     $wlrow = $work_log_row;
		 echo '<option title="'.htmlentities($wlrow['description']).'" value='.$wlrow['id'].' '.($work_log_row['id'] == $wlrow['id'] ? 'selected="selected" ':'').'>'.
			   htmlentities($wlrow['company_name'].' - '.$wlrow['title']).'</option>';	    
	  }
	  ?>
	  </select>
	  </form>
	  <span id="work_log_title" title="<?=htmlentities($work_log_row['title'])?>"><?=htmlentities($work_log_row['title'])?></span>
	<?PHP
	}
	else
	{
      ?>
      <span id="company_name" title="<?=htmlentities($company_row['name'])?>"><?=htmlentities($company_row['name'])?></span> - <span id="work_log_title" title="<?=htmlentities($work_log_row['title'])?>"><?=htmlentities($work_log_row['title'])?></span>
	  <?PHP
	}
   ?>
  <div><span id="time" title="Current Session Time">Time &amp; Money Status</span></div>
  <div><span id="wl_time" title="Work Log Total">Total: <?=$work_log_row['_calc_hours_']?> hrs. =&gt; $<?= round($work_log_row['_calc_amount_'], 2)?></span></div>
  </div>
  <script>
	if (window.opener && window.opener.glbUpdateWorkLogJS){
	   var data = {id: <?=$work_log_row['id']?>, _in_progress_: <?=$time_log_id == false && !$resume_time_log ? 'false' : 'true';?>};
	   window.opener.glbUpdateWorkLogJS(data);
	}
  </script>
  <div class="timelog_image_links">
  <a atarget="_blank" title="All Companies" href="companies.php"><img src="images/companies.png"/></a>
  <a atarget="_blank" title="View Work Log" href="work_log.php?wid=<?=$work_log_id?>"><img src="images/view_details.gif"/></a> 
  <a atarget="_blank" title="View Detailed Time Log" href="time_log_show.php?wid=<?=$work_log_id?>"><img src="images/timelog.png"/></a>
 </div>
  </body>
  </html>
