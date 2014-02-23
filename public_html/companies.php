<?PHP
   require_once(dirname(__FILE__).'/lib/db.inc.php');
   require_once(dirname(__FILE__).'/lib/Members.class.php');
   Members::SessionForceLogin();
   require_once(dirname(__FILE__).'/lib/work_log.class.php');

	 if (isset($_REQUEST['ajaxedit'])){
       if (empty($_REQUEST['cid']) || !is_numeric($_REQUEST['cid'])){
          die(json_encode(array('error'=>'Invalid company id provided')));
       }else{
         $cid = (int)$_REQUEST['cid'];
       }
       if (isset($_REQUEST['f']) && isset($_REQUEST['v'])){
          $field = $_REQUEST['f'];
          $value = $_REQUEST['v'];
       }else{
          die(json_encode(array('error'=>'No [f]ield or [v]alue provided.')));
       }
       //now do checks on the specific fields
       
       
       $prep = $DBH->prepare("SELECT * FROM company WHERE id = $cid AND user_id = ".(int)$_SESSION['user_id']);
       $result = $prep->execute();
       if ($result) {
       	$original_row = $prep->fetch(PDO::FETCH_ASSOC);
       }
       if (empty($original_row)){
          die(json_encode(array('error'=>'Company not found.')));
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
       $prep = $DBH->prepare("UPDATE company SET ".$DBH->quote($field)." = '".$DBH->quote($value)."' ".
                   "WHERE id = $cid ");

       $result_upd = $prep->execute();

       if ($result_upd){
          
          $prep2 = $DBH->prepare("SELECT * FROM company WHERE id = $cid AND user_id = ".(int)$_SESSION['user_id']);
          $result_after_upd = $prep2->execute();

          if ($result_after_upd && $row = $prep2->fetch()){
				die(json_encode(array('success'=>'Updated.', 'row'=>$row)));
		  }else{
		        die(json_encode(array('error'=> $DBH->errorInfo())));
		  }
       }else{
          die(json_encode(array('error'=>$DBH->errorInfo())));
       }
   }   
   

   //-- NORMAL PAGE VIEW ALL COMPANIES
   $sql = "SELECT * FROM company ";
   //only allow logged in user to see this work log
   $sql_where = " WHERE user_id = ".(int)$_SESSION['user_id'];
   
   if (isset($_GET['company'])){
      if (empty($sql_where)){ $sql_where = ' WHERE '; }
      else { $sql_where .= ' AND '; }
      
      $sql_where .= " id = ".(int)$_GET['company'];
   }       
   
   $sql .= $sql_where." ORDER BY name ASC";
   $prep = $DBH->prepare($sql);
   
   $result = $prep->execute();
   $rows = array();

   if (!$result){
      echo $sql.$DBH->errorInfo();
   }
   while ($row = $prep->fetch()){
      $rows[] = $row;   
   }
   include_once(dirname(__FILE__).'/lib/Site.class.php');
   
   $specific_company_id = isset($_GET['company']) ? (int)$_GET['company'] : false;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Companies</title>
<?PHP
  Site::CssJsYuiIncludes();
  Site::CssJsJqueryIncludes();
  Site::Css();
?>
<script type="text/javascript" src="js/work_log_shared.js"></script>
</head>
<body class="yui-skin-sam">
<?PHP Members::MenuBar(); ?>

<div id="basic" style="font-size: 10px">
</div>

<script type="text/javascript">
var debug = [];

YAHOO.util.Event.addListener(window, "load", function() {

    YAHOO.example.Basic = function() {
        <?PHP
          $allow_edit = true;
        ?>
         var myCustomCompanyFormatter = function(elLiner, oRecord, oColumn, oData) {
             var cid = oRecord.getData("id");
             var str = '<a title="Show all work logs" href="work_log.php?company='+cid+'"><img border=0 src="images/worklogs.png" style="width:16px"/></a>, <a title="Unpaid work logs" href="work_log.php?company='+cid+'&filter[]=unpaid"><img border=0 src="images/money_20_cancel.png" style="width:16px"/></a>, <a title="Paid work logs" href="work_log.php?company='+cid+'&filter[]=paid"><img border=0 src="images/money_20.png" style="width:16px"/></a>, <a title="Delete this Company" href="delete.php?company_id='+cid+'"><img border=0 src="images/delete.png" style="width: 16px"></a>';
             elLiner.innerHTML = str;
         };
		 
		 var defaultHourlyRateFormatter = function(elLiner, oRecord, oColumn, oData) {
		     elLiner.innerHTML = '$' + oData;
		 }
         
            
        var myColumnDefs = [
		    {key:"__actions",label:"Actions", sortable: false, resizeable: true, formatter:myCustomCompanyFormatter},
            {key:"name", label: "Company", sortable:true, resizeable: true <?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
			{key:"default_hourly_rate", label: "Default<br>Hourly Rate", formatter:defaultHourlyRateFormatter, width:48, sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
			{key:"street", label: "Street 1", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
			{key:"street2", label: "Street 2", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
			{key:"city", label: "City", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
			{key:"state", label: "State", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
			{key:"zip", label: "Zip", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
			{key:"country", label: "Country", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
			{key:"phone", label: "Phone", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
			{key:"email", label: "Email", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>},
            {key:"notes", label: "Notes", sortable:true, resizeable:true<?PHP if ($allow_edit){ ?>, editor: new YAHOO.widget.TextboxCellEditor({disableBtns:true})<?PHP } ?>}
      ];	

        var myDataSource = new YAHOO.util.DataSource(<?=json_encode($rows)?>);

        myDataSource.responseType = YAHOO.util.DataSource.TYPE_JSARRAY;
        myDataSource.responseSchema = {
            fields: ["id","name","street","street2","city","state","zip","country","phone","email","notes","default_hourly_rate"]
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
        
        glbAjaxUpdateCompany = function(cid, column_key, oNewData, oOldData){
            
            //find record
            var recordSet = glbDataTable.getRecordSet();
            var records = recordSet.getRecords();
            var record = false;
            for (var i = 0; i < records.length; ++i){
                if (records[i].getData('id') == cid){
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
                        '&cid='+ record.getData('id') +
                        '&f='  + encodeURIComponent(column_key) + 
                        '&v='  + encodeURIComponent(oNewData);
            
            $.ajax({
              type: "GET",
              url: "companies.php",
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
            
            glbAjaxUpdateCompany(record.getData('id'), column_key, oNewData, oOldData);
        });   
        // --- END EDITING FLOW --
        
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
</body>
</html>
