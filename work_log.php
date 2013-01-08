<?PHP
   require_once('lib/db.inc.php');
   require_once('lib/Members.class.php');
   Members::SessionForceLogin();
   require_once('lib/work_log.class.php');
   
   
   if (isset($_POST['title'])){
      if (!work_log::Add($_POST)){
         die(work_log::$last_error);
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
          $value = $_REQUEST['v'];
       }else{
          die(json_encode(array('error'=>'No [f]ield or [v]alue provided.')));
       }
       //now do checks on the specific fields
       
       
       $result = mysql_query("SELECT work_log.*, company.name AS company_name 
                              FROM work_log JOIN company ON company_id = company.id
                              WHERE work_log.id = $wid");
       if ($result) {
       	$original_row = mysql_fetch_assoc($result);
       }else{
          //die(json_encode(array('error'=>mysql_error())));
       }
       if (empty($original_row)){
          die(json_encode(array('error'=>'No work log found.')));
       }
       
       if ($original_row['locked'] && $field != 'locked'){
         die(json_encode(array('error'=>'Locked work log, cannot change')));
       }
       
       //everything else seemed to pass, now check if user is trying to lock a row in-progress
       if ($field == 'locked' && $value != 0 && $original_row['locked'] == false){
            $result3 = mysql_query("SELECT start_time, stop_time 
                                    FROM time_log 
                                    WHERE work_log_id = $wid 
                                      AND start_time IS NOT NULL 
                                      AND stop_time IS NULL");
            if ($result3){
               if ($uf_time_log_row = mysql_fetch_assoc($result3)){
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
       $result_upd = mysql_query("UPDATE work_log SET ".mysql_real_escape_string($field)." = '".mysql_real_escape_string($value)."' ".
                   "WHERE id = $wid ");
       if ($result_upd){
          
          try{ $worklog = new work_log($wid);}
          catch(Exception $e){
               die(json_encode(array('error'=>'Error')));
          }
          
          die(json_encode(array('success'=>'Updated.', 'row'=>$worklog->getRow())));
       }else{
          die(json_encode(array('error'=>mysql_error())));
       }
   }

   //-- NORMAL PAGE VIEW ALL WORK LOGS
   $sql = "SELECT work_log.*,company.name AS company_name 
                          FROM work_log JOIN company ON company_id = company.id ";
   //only allow logged in user to see this work log
   $sql_where = " WHERE work_log.user_id = ".(int)$_SESSION['user_id'];
   
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
   if (isset($_GET['wid'])) {
      if (empty($sql_where)){ $sql_where = 'WHERE '; }
      else { $sql_where .= ' AND '; }
      
      $sql_where .= ' work_log.id = '. (int)$_GET['wid'];
      $_GET['notes'] = 'full';
   }                       
   
   $sql .= $sql_where." ORDER BY work_log.id DESC";

   $result = mysql_query($sql);
   $rows = array();
   $columns = array();
   $super_total_seconds = 0;
   $super_total_amount = 0.0;
   if (!$result){
      echo $sql.mysql_error();
   }
   $cal_events = array();
   while ($row = mysql_fetch_assoc($result)){
      
      $work_log = new work_log($row['id']);
      $row['note_log'] = json_encode($work_log->getNotes());
	  $row['files_log'] = json_encode($work_log->getFiles());
      
      $total_seconds = 0;
	 
      $result2 = mysql_query("SELECT start_time, stop_time 
                              FROM time_log 
                              WHERE work_log_id = ".(int)$row['id']." 
                                AND start_time IS NOT NULL 
                                AND stop_time IS NOT NULL");
      if ($result2){
         while($time_log_row = mysql_fetch_assoc($result2)){
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
      
      $result3 = mysql_query("SELECT start_time, stop_time 
                              FROM time_log 
                              WHERE work_log_id = ".(int)$row['id']." 
                                AND start_time IS NOT NULL 
                                AND stop_time IS NULL");
      if ($result3){
         if ($uf_time_log_row = mysql_fetch_assoc($result3)){
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
   }
   $super_total_hours = $super_total_seconds / 60 / 60;   
   if (isset($_GET['output'])){
       if ($_GET['output'] == 'json'){
          header('Content-type: text/javascript');
          die(json_encode($rows));
       }
       //spreadsheet output
       else if ($_GET['output'] == 'csv' || $_GET['output'] == 'xls'|| $_GET['output'] == 'xlsx'){
            /** Include PHPExcel */
            require_once('lib/PHPExcel.php');
            
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
   
   include_once('lib/Site.class.php');
   
   $specific_company_id = isset($_GET['company']) ? (int)$_GET['company'] : false;
   $specific_work_log_id = isset($_GET['wid']) ? (int)$_GET['wid'] : false;
   if (!empty($specific_work_log_id)){

      $result = mysql_query("SELECT company.*, work_log.* 
                             FROM company JOIN work_log on company.id = company_id 
                             WHERE work_log.id = ".$specific_work_log_id);
      if ($result && $row = mysql_fetch_assoc($result)) {
      	$specific_company_id = $row['company_id'];
      }
   }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Work Log</title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<style>
.ui-menu { width: 150px; font-size: .7em; }
.actionmenu{ 
  margin-left: 7px;
  width: 22px;
  float: right; 
}
.ui-menu a:hover{ color: white; }

.ui-state-default, .ui-widget-content .ui-state-focus{
    border: 1px solid #f09424;
    background: orange;
    font-weight: normal/*{fwDefault}*/;
    color: white;
    outline: none;
}
</style>
<script type="text/javascript" src="js/work_log_shared.js"></script>
<script type="text/javascript">
var FALSE_FUNCTION = new Function( "return false" );

/**
 * Called to disable F1, F3, and F5.
 */
function disableShortcuts() {
  // Disable online help (the F1 key).
  //
  document.onhelp = FALSE_FUNCTION;
  window.onhelp = FALSE_FUNCTION;

  // Disable the F1, F3 and F5 keys. Without this, browsers that have these
  // function keys assigned to a specific behaviour (i.e., opening a search
  // tab, or refreshing the page) will continue to execute that behaviour.
  //
  document.onkeydown = function disableKeys() {
    // Disable F1, F3 and F5 (112, 114 and 116, respectively).
    //
    if( typeof event != 'undefined' ) {
      if( (event.keyCode == 112) ||
          (event.keyCode == 114) ||
          (event.keyCode == 116) ) {
        event.keyCode = 0;
        return false;
      }
    }
  };

  // For good measure, assign F1, F3, and F5 to functions that do nothing.
  //
  shortcut.add( "f1", FALSE_FUNCTION );
  shortcut.add( "f3", FALSE_FUNCTION );
  shortcut.add( "f5", function(){ window.location.href = window.location.href; } );
}

$(document).bind('keydown', function(e) {
    if(e.which === 116) {
       console.log('super javascript refresh!');
       window.location.href = window.location.href;
       return false;
    }
    if(e.which === 82 && e.ctrlKey) {
       console.log('blocked');
       return false;
    }
});

</script>
<script>
  $(document).ready(function() {
        $("#dlgAddNote").dialog({ autoOpen: false, width: 240, height: 190 });
		$("#dlgAddFile").dialog({ autoOpen: false, width: 240, height: 345 });
  });
</script>
	<style>
	.ui-combobox {
		position: relative;
		display: inline-block;
	}
	.ui-combobox-toggle {
		position: absolute;
		top: 0;
		bottom: 0;
		margin-left: -1px;
		padding: 0;
		/* adjust styles for IE 6/7 */
		*height: 1.7em;
		*top: 0.1em;
	}
	.ui-combobox-input {
		margin: 0;
		padding: 0.3em;
	}
	</style>
	<script>
	(function( $ ) {
		$.widget( "ui.combobox", {
			_create: function() {
				var input,
					self = this,
					select = this.element.hide(),
					selected = select.children( ":selected" ),
					value = selected.val() ? selected.text() : "",
					wrapper = this.wrapper = $( "<span>" )
						.addClass( "ui-combobox" )
						.insertAfter( select );

				input = $( "<input>" )
					.appendTo( wrapper )
					.val( value )
					.addClass( "ui-state-default ui-combobox-input" )
					.autocomplete({
						delay: 0,
						minLength: 0,
						source: function( request, response ) {
							var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
							response( select.children( "option" ).map(function() {
								var text = $( this ).text();
								if ( this.value && ( !request.term || matcher.test(text) ) )
									return {
										label: text.replace(
											new RegExp(
												"(?![^&;]+;)(?!<[^<>]*)(" +
												$.ui.autocomplete.escapeRegex(request.term) +
												")(?![^<>]*>)(?![^&;]+;)", "gi"
											), "<strong>$1</strong>" ),
										value: text,
										option: this
									};
							}) );
						},
						select: function( event, ui ) {
							ui.item.option.selected = true;
							self._trigger( "selected", event, {
								item: ui.item.option
							});
						},
						change: function( event, ui ) {
							if ( !ui.item ) {
								var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
									valid = false;
								select.children( "option" ).each(function() {
									if ( $( this ).text().match( matcher ) ) {
										this.selected = valid = true;
										return false;
									}
								});
								if ( !valid ) {
									// remove invalid value, as it didn't match anything
									$( this ).val( "" );
									select.val( "" );
									input.data( "autocomplete" ).term = "";
									return false;
								}
							}
						}
					})
					.addClass( "ui-widget ui-widget-content ui-corner-left" );

				input.data( "autocomplete" )._renderItem = function( ul, item ) {
					return $( "<li></li>" )
						.data( "item.autocomplete", item )
						.append( "<a>" + item.label + "</a>" )
						.appendTo( ul );
				};

				$( "<a>" )
					.attr( "tabIndex", -1 )
					.attr( "title", "Show All Items" )
					.appendTo( wrapper )
					.button({
						icons: {
							primary: "ui-icon-triangle-1-s"
						},
						text: false
					})
					.removeClass( "ui-corner-all" )
					.addClass( "ui-corner-right ui-combobox-toggle" )
					.click(function() {
						// close if already visible
						if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
							input.autocomplete( "close" );
							return;
						}

						// work around a bug (likely same cause as #5265)
						$( this ).blur();

						// pass empty string as value to search for, displaying all results
						input.autocomplete( "search", "" );
						input.focus();
					});
			},

			destroy: function() {
				this.wrapper.remove();
				this.element.show();
				$.Widget.prototype.destroy.call( this );
			}
		});
	})( jQuery );

	$(function() {
		//$( "#add_file_featurecombo" ).combobox();
	});
</script>
</head>
<style>
.unfilter{
  color: #00b000;
}
</style>
<body class="yui-skin-sam">
<?PHP           
          function makeFilterLink($text, $key, $value)
          {
            ?><a <?PHP if (isset($_GET[$key]) && $_GET[$key] == $value){ 
                ?>href="<?=modQS('',array($key))?>" class="unfilter" title="Unfilter <?=htmlentities($text)?>"<?PHP 
            }else{
                ?>href="<?=modQS(array($key=>$value))?>"<?PHP 
            }?>><?=$text?></a><?PHP
          }
          
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
                 $sql = "SELECT company.name AS company_name, company.id as company_id FROM company WHERE user_id = ".(int)$_SESSION['user_id']." ORDER BY name ASC";
                 $result = mysql_query($sql);
                 while ($row = mysql_fetch_assoc($result)){
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
                     $result = mysql_query($sql);
                     while ($row = mysql_fetch_assoc($result)){
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
<?PHP
   function modQS($ary_or_qs, $ary_unset = array()){
     if (is_string($ary_or_qs)){
       $ary_or_qs = parse_str($ary_or_qs);
     }
     if (!is_array($ary_or_qs)) {
     	  $ary_or_qs = array();
     }
     
     $CURQS = array_merge($_GET, $ary_or_qs);
     $qs = '';
     foreach($CURQS as $key => $val)
     {
        if (in_array($key, $ary_unset)){ unset($CURQS[$key]); continue; }
        if (isset($ary_or_qs[$key])) {
        	   $CURQS[$key] = $ary_or_qs[$key];
        }
        if ($qs == ''){ $qs = '?'; }
        else { $qs .= '&'; }
        
		if (is_string($key) && is_string($CURQS[$key])){
           $qs .= urlencode($key).'='.urlencode($CURQS[$key]);
		}
     }
     return $qs;
   }
?>
<div class="dataBlk">

<?PHP
   if (isset($_GET['company'])){
      $result = mysql_query("SELECT name,phone FROM company WHERE id = ".(int)$_GET['company']);
      if ($result && $row = mysql_fetch_assoc($result)){
         echo '<h2>'.$row['name'].' - '.$row['phone'].'</h2>';
      }else{
         echo '<h2 style="color: red">Invalid Company ID</h2>';
      }
   }
   
   if (isset($_GET['wid'])) {
      $result = mysql_query("SELECT company.*, work_log.* 
                             FROM company JOIN work_log on company.id = company_id 
                             WHERE work_log.id = ".(int)$_GET['wid']);
      if ($result && $row = mysql_fetch_assoc($result)) {
        $company_wl_row = $row;
		echo '<h2><a href="?company='.$row['company_id'].'">'.$row['name'].'</a> - '.$row['phone'].'</h2>';
      	echo '<h2>'.$row['title'].'</h2>';
      }
   }
?>

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
  $sql = "SELECT feature, file, change_type, notes FROM files_log JOIN work_log ON work_log.id = work_log_id WHERE user_id = ".(int)$_SESSION['user_id'];
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
  $result = mysql_query($sql);
  $features_assoc = array();
  $features = array();
  $files_assoc = array();
  $files = array();
  if ($result){
	 while($row = mysql_fetch_assoc($result)){
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
generator = null;
var debug = [];

glbDeleteFileChange = function(wid, file, feature){
   if (!confirm('Are you sure you want to delete this file or db change modification?')){ return false; }
   var f = document.frmDeleteFileModification;
   f.work_log_id.value = wid;
   f.file.value = file;
   f.feature.value = feature;
   f.submit();
}

glbDeleteNote = function(wid, note_id){
  var f = document.frmDeleteNote;
  f.work_log_id.value = wid;
  f.note_id.value = note_id;
  f.submit();
}

/**
 * Update the datatable row with data by a given record 
 *(it will attempt to find the record if oRecord is undefined)
 */
glbUpdateWorkLogJS = function(oData, oRecord){
            //find record by oData.id
            var recordSet = glbDataTable.getRecordSet();
            var records = recordSet.getRecords();
            var record = false;
			
			if (typeof(oRecord) == 'undefined'){
				for (var i = 0; i < records.length; ++i){
					if (records[i].getData('id') == oData['id']){
					   record = records[i];
					   break;
					}
				}
			}else{
			   record = oRecord;
			}
			
			if (record){
                    //set all the values stored in oData (which should be an object with key-value pairs)
					for (var col in oData){
                       record.setData(col, oData[col]);
                       recordSet.updateRecordValue ( record , col , oData[col] );   
                    }                   
                    glbDataTable.render();			
			}
}

YAHOO.util.Event.addListener(window, "load", function() {

    YAHOO.example.Basic = function() {
       var gen_jquery_uimenu = function(id, locked, inprogress){
        return '<ul class="actionmenu"><li class="main"><a href="#" onclick="return false;">&nbsp;</a><ul>' + 
            (locked ? '<li><a href="#" onclick="glbAjaxUpdateWorkLog('+id+',\'locked\',0, 1); return false;"><span class="ui-icon ui-icon-unlocked"></span>Unlock</a></li>' : 
                   '<li><a href="#" onclick="glbAjaxUpdateWorkLog('+id+',\'locked\',1, 0); return false;"><span class="ui-icon ui-icon-locked"></span>Lock</a></li>') + 
                   '<li><a target="_blank" href="invoice.php?wid='+id+'&format=pdf"><span class="ui-icon ui-icon-document"></span>Create PDF Invoice</a></li>' + 
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
                
                elLiner.innerHTML = locked ? '<a href="#" onclick="glbAjaxUpdateWorkLog('+oRecord.getData('id')+',\'locked\',0, 1); return false;"><img border=0 title="Locked" src="images/lock_locked.gif" /></a>' 
                                           : (inprogress ? ' <a href="#" onclick="poptimer(\'time_log.php?tid=latest&wid='+ id +'\'); return false;"><img border=0 title="In-Progress" src="images/progressbar.png" /></a>' : ' <a href="time_log.php?wid='+ id +'" onclick="poptimer(\'time_log.php?wid='+ id +'\'); return false;"><img border=0 title="Clock In" src="images/arrow_timer.png"/></a>');
                elLiner.innerHTML += gen_jquery_uimenu(oRecord.getData('id'), locked, inprogress);
                return; //TODO: figure out something with previous little icons
                
                elLiner.innerHTML += locked ? '<a href="#" onclick="glbAjaxUpdateWorkLog('+oRecord.getData('id')+',\'locked\',0, 1); return false;"><img border=0 title="Locked" src="images/lock_locked.gif" /></a>' : '<a href="#" onclick="glbAjaxUpdateWorkLog('+oRecord.getData('id')+',\'locked\',1, 0); return false;"><img border=0 title="Not locked" src="images/lock_unlock.png" /></a>';
                
                elLiner.innerHTML += ' <a target="_blank" href="invoice.php?wid='+oRecord.getData('id')+'&format=pdf"><img title="Create Invoice" border=0 src="images/add_invoice.png"/></a>';
                elLiner.innerHTML += inprogress ? ' <a href="#" onclick="poptimer(\'time_log.php?tid=latest&wid='+ oRecord.getData('id') +'\'); return false;"><img border=0 title="In-Progress" src="images/progressbar.png" /></a>' : '';
                elLiner.innerHTML += !locked && !inprogress ? ' <a href="time_log.php?wid='+ oRecord.getData('id') +'" onclick="poptimer(\'time_log.php?wid='+ oRecord.getData('id') +'\'); return false;"><img border=0 title="Clock In" src="images/arrow_timer.png"/></a>' : '';
                elLiner.innerHTML += '<a target="_blank" href="time_log_show.php?wid='+oRecord.getData('id')+'"><img border=0 title="View'+(!locked ? '/Edit':'')+' Time Log" src="images/timelog.png" style="width: 16px; height: 16px"></a>';
                elLiner.innerHTML += !locked ? ' <a href="#" onclick="document.frmAddFile.work_log_id.value = '+oRecord.getData('id')+'; $(\'#dlgAddFile\').dialog(\'open\'); return false;"><img title="Add File/DB Modification" src="images/add_file.png" border=0 style="width:16px"></a>' : '';
				elLiner.innerHTML += !locked ? ' <a href="#" onclick="document.frmAddNote.work_log_id.value = '+oRecord.getData('id')+'; $(\'#dlgAddNote\').dialog(\'open\'); return false;"><img title="Add Note" src="images/note_add.png" border=0 /></a>' : '';
                
				elLiner.innerHTML += ' <a href="work_log.php?wid='+oRecord.getData('id')+'"><img src="images/view_details.gif" title="View Work Details" border=0 /></a>';
                if (!locked){
                   elLiner.innerHTML += ' <a title="Permanently delete this work log" href="delete.php?wid='+oRecord.getData('id')+'"><img src="images/delete.png" style="width: 16px" border=0></a>';
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
            {key:"_extra_", label:"<span style='font-size: 10px'>Actions</span>", formatter:formatExtra, sortable:true,resizeable:false},
            <?PHP if (!isset($_GET['wid'])) { ?>{key:"title", label: "Title", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>}, <?PHP } ?>
            <?PHP if (!isset($_GET['company']) && !isset($_GET['wid'])){ ?>{key:"company_name", label: "Company", sortable:true, resizeable: true, formatter:myCustomCompanyFormatter},<?PHP } ?>
            {key:"description", label: "Description <br>Files Changed / Notes", width: 167, formatter:formatDescriptionAndNotes, sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"_calc_hours_", label:"Calculated<br>Hours", sortable:true, resizeable:true},
            {key:"hours", label: "Actual<br>Hours", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"rate", label: "Price<br>Rate", formatter:YAHOO.widget.DataTable.formatCurrency, sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"_calc_amount_", label: "Calculated<br>Amount", formatter:YAHOO.widget.DataTable.formatCurrency, sortable:true, resizeable:true},
            {key:"amount_billed", label: "Actual<br>Amount Billed", formatter:YAHOO.widget.DataTable.formatCurrency, sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"date_billed", label: "Date<br>Billed", formatter:YAHOO.widget.DataTable.formatDate, sortable:true, sortOptions:{defaultDir:YAHOO.widget.DataTable.CLASS_DESC},resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"date_paid", label: "Date<br>Paid", formatter:YAHOO.widget.DataTable.formatDate, formatter:YAHOO.widget.DataTable.formatDate, sortable:true, sortOptions:{defaultDir:YAHOO.widget.DataTable.CLASS_DESC},resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>}
                 
        ];	

        var myDataSource = new YAHOO.util.DataSource(<?=json_encode($rows)?>);

        myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
        myDataSource.responseSchema = {
            fields: ["id","locked", "company_id","company_name","title","description", "files_log", "note_log","_calc_hours_","hours","rate","_calc_amount_","_in_progress_", "_extra", "amount_billed","date_billed","date_paid"]
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
