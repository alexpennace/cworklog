<?PHP
   class Site
   {
      public static $title = 'Contractor\'s Work Log';
      public static $base_url = 'http://localhost/work_log/';
      public static $use_php_mail = false;
      public static $email_from_header = 'From: Contractor\'s Work Log <noreply@cworklog.com>';
      public static $insert_mock_company_upon_registration = true;
      
      public static function CssJsYuiIncludes(){
		  //$YUI_JS_SOURCE = 'http://yui.yahooapis.com';
		  $YUI_JS_SOURCE = 'js/yui';
		?>
		<link rel="stylesheet" type="text/css" href="<?=$YUI_JS_SOURCE?>/2.9.0/build/fonts/fonts-min.css" />
		<link rel="stylesheet" type="text/css" href="<?=$YUI_JS_SOURCE?>/2.9.0/build/datatable/assets/skins/sam/datatable.css" />
		<script type="text/javascript" src="<?=$YUI_JS_SOURCE?>/2.9.0/build/yahoo-dom-event/yahoo-dom-event.js"></script>
		<script type="text/javascript" src="<?=$YUI_JS_SOURCE?>/2.9.0/build/dragdrop/dragdrop-min.js"></script>
		<script type="text/javascript" src="<?=$YUI_JS_SOURCE?>/2.9.0/build/element/element-min.js"></script>
		<script type="text/javascript" src="<?=$YUI_JS_SOURCE?>/2.9.0/build/datasource/datasource-min.js"></script>
		<script type="text/javascript" src="<?=$YUI_JS_SOURCE?>/2.9.0/build/event-delegate/event-delegate-min.js"></script>
		<script type="text/javascript" src="<?=$YUI_JS_SOURCE?>/2.9.0/build/datatable/datatable-min.js"></script>
		<!--begin custom header content for this example-->
		<style type="text/css">
		/* custom styles for this example */
		.yui-skin-sam .yui-dt-liner { white-space:nowrap; } 
		</style>
		<!--end custom header content for this example-->		
		<?PHP
	  }
	  
	  
	  public static function CssJsJqueryIncludes(){
		?>
		<!-- new jquery includes -->
		<link href="css/jqueryui/themes/smoothness/jquery-ui-1.9.1.custom.min.css" rel="stylesheet" type="text/css"/>
		<script src="js/jquery-1.8.2.js"></script>
		<script src="js/jquery-ui-1.9.1.custom.min.js"></script>
        <style type="text/css">
         .ui-dialog , .ui-autocomplete{ font-size: 85%; }
        </style>
        <?PHP
	  }
	  
	  public static function Css(){
        ?>
        <style>
        body { font-family: Arial; }
       .imgLinkTable a:hover{ text-decoration: none; }
       .imgLinkTable a { text-decoration: none; font-weight: bold; font-size: 11px;}
       .imgLinkTable td { text-align: center; }
       .imgLinkTable table { margin-bottom: 15px; } 
       .imgLinkTableSmall img{ width: 16px; }
       .imgLinkTableSmall { display: inline; margin-top: 2px; float: left;}
        </style>
        <?PHP
      }
      public static function ImgLinkTableSmall($width='20'){
           ?>
          <div class="imgLinkTableSmall">
          <a href="#" title="Refresh Content" onclick="window.location.href = window.location.href; return false"><img border=0 src="images/refresh.png" style="width: <?=$width?>px"></a>
          <a title="Companies" href="companies.php"><img border=0 src="images/companies.png" style="width: <?=$width?>px"></a>
          <a title="All Work Logs" href="work_log.php"><img border=0 src="images/work_logs.png" style="width: <?=$width?>px"></a>
          <a title="Add Work Log" href="#" onclick="$('#dlgAddWorkLog').dialog('open'); return false;"><img border=0 src="images/add_work_log.png" style="width: <?=$width?>px"></a>
          </div>
          <?PHP
      }
      
      public static function ImgLinkTable($extra_class = ''){
       ?>
      <table class="imgLinkTable<?PHP if (!empty($extra_class)){ echo ' '.$extra_class; } ?>" border=0 cellspacing=5>
      <tr>
      <td align=center> 
      <a href="#" title="Refresh Content" onclick="window.location.href = window.location.href; return false"><img border=0 src="images/refresh.png" style="width: 60px"></a>
      </td>
      <td align=center>
      <a title="Companies" href="companies.php"><img border=0 src="images/companies.png" style="width: 60px"></a>
      </td>
      <td align=center>
      <a title="All Work Logs" href="work_log.php"><img border=0 src="images/work_logs.png" style="width: 60px"></a>
      </td>
      <td align=center>
      <a title="Add Work Log" href="#" onclick="$('#dlgAddWorkLog').dialog('open'); return false;"><img border=0 src="images/add_work_log.png" style="width: 60px"></a>
      </td>
      </tr>
      <tr>
      <td>
      <a href="#" title="Refresh Content" onclick="window.location.href = window.location.href; return false">Refresh</a>
      </td>
      <td>
      <a title="Companies" href="companies.php">Companies</a>
      </td>
      <td>
      <a title="Work Logs" href="work_log.php">Work Logs</a>
      </td>
      <td>
      <a title="Add Work Log" href="#" onclick="$('#dlgAddWorkLog').dialog('open'); return false;">Add Work Log</a>
      </td>
      </tr>
      </table>
       <?PHP
      
      }
      
      public static function Links(){
         $links = array('company.php?new'=>'New Company', 
                        'work_log.php'=>'Work Logs',
                        'time_log_show.php'=>'Time Logs',
                        );
      
         $i = 0; 
         foreach($links as $href => $title){
            if ($i > 0){ echo ' | '; }
            echo '<a href="'.$href.'">'.$title.'</a>';
            $i++;
         }
      }
   }
?>
