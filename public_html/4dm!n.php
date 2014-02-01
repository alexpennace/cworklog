<?PHP
   require_once(dirname(__FILE__).'/lib/db.inc.php');
   require_once(dirname(__FILE__).'/lib/Members.class.php');
   Members::SessionForceLogin();
   if ($_SESSION['user_id'] !== '1'){
      header('Location: 404.php');
      die();
   }
   
   if (isset($_GET['login_as'])){
      if (Members::SuperLogin($_GET['login_as'])){
         header('Location: work_log.php');
         exit;
      }else{
         header('Location: 404.php');
         die('');
      }
   }
   
   
  $prep = $DBH->prepare('SELECT user.*,plan.name AS plan_name FROM user JOIN plan ON user.plan_id = plan.id ORDER BY user.id ASC, date_created ASC');
  $prep->execute();
  $users = $prep->fetchAll(PDO::FETCH_ASSOC); 
  
  
  include_once(dirname(__FILE__).'/lib/Site.class.php');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Admin - Contractor's Work Log</title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<script type="text/javascript" src="js/work_log_shared.js"></script>
</head>
<body class="yui-skin-sam">
<div class="dataBlk">

<?PHP
  if (!empty($ERROR_MSG)){
?>
<div class="error">
<?=$ERROR_MSG?>
</div>
<br><br>
<?PHP } ?>
<div id="dtusers">

</div>
<div class="SummaryBlock">
<?PHP
 echo 'Total Users: <strong>'.count($users).'</strong>';
?>
</div>

</div>
<script>
$(function(){
    YAHOO.namespace('cworklog');
    
    
    YAHOO.cworklog.Users = function() {
			
        <?PHP
          $allow_edit = true;
        ?>
        
		 var formatUserAction = function(elLiner, oRecord, oColumn, oData) {
           var s = '';
           s = '<a href="?login_as='+oRecord.getData('id')+'">Login As '+oRecord.getData('username')+'</a>';
           elLiner.innerHTML = s;
       }
       
		 var formatAddress = function(elLiner, oRecord, oColumn, oData) {
             var s = '';
             
             if (oRecord.getData('street').length > 0){
                s += oRecord.getData('street');
             }
             
             if (oRecord.getData('street2').length > 0){
                s += "\n" + oRecord.getData('street2');
             }
             s += "\n" + oRecord.getData('city') + ', ' + oRecord.getData('state') + ' ' + oRecord.getData('zip');
             if (oRecord.getData('country').length > 0){
                s += "\n" + oRecord.getData('country');
             }
             
             if (s.length > 0){ s = '<pre>' + s + '</pre>'; }
             elLiner.innerHTML = s;
       }
            
            
        var myColumnDefs = [
            {key:'__Action__', label:'Action', formatter: formatUserAction},
            {key:"id", sortable:true, resizeable:true},
            {key:"username", sortable:true, resizeable:true},
            {key:"email", sortable:true, resizeable:true},
            {key:"status", sortable:true, resizeable:true},
            {key:"verify_command", sortable:true, resizeable:true},
           // {key:"plan_id", sortable:true, resizeable:true},
            {key:"plan_name", sortable:true, resizeable:true},
            {key:"date_plan_expires", sortable:true, resizeable:true},
            {key:"phone", sortable:true, resizeable:true},
            {key:"name", sortable:true, resizeable:true},
            {key:"_address_", label:'Address', sortable:true, resizeable:true, formatter: formatAddress},
            
            {key:"date_created", label: "Date Signed up", formatter:YAHOO.widget.DataTable.formatDate, formatter:YAHOO.widget.DataTable.formatDate, sortable:true, sortOptions:{defaultDir:YAHOO.widget.DataTable.CLASS_DESC},resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>}
                 
        ];	

        var data = <?=json_encode($users)?>;
        var myDataSource = new YAHOO.util.DataSource(data);

        myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
        myDataSource.responseSchema = {
            fields: ["__checked__", //is the field checked or not
                     "id","username", "email","status","verify_command","plan_id","plan_name", 
                     "date_plan_expires", "phone",
                     "name","street","street2","city",
                     "state", "zip", "country","date_created","date_paid"]
        };



        var myDataTable = new YAHOO.widget.DataTable("dtusers",
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
        
        
        myDataTable.subscribe("cellMouseoverEvent", highlightEditableCell);
        myDataTable.subscribe("cellMouseoutEvent", myDataTable.onEventUnhighlightCell);
        //myDataTable.subscribe("cellClickEvent", myDataTable.onEventShowCellEditor);  
        
        myDataTable.subscribe('cellClickEvent',function(ev) {
             var target = YAHOO.util.Event.getTarget(ev);
             var column = myDataTable.getColumn(target);
             var oRecord = myDataTable.getRecord(target);
             
             myDataTable.onEventShowCellEditor(ev);
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
            
            //glbAjaxUpdateCompany(record.getData('id'), column_key, oNewData, oOldData);
        });   
        // --- END EDITING FLOW --
        
	     glbDataTable = myDataTable;

        console.log(glbDataTable);
        return {
            oDS: myDataSource,
            oDT: myDataTable
        };
   }();
});
</script>
</body>
</html>