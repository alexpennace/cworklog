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

  function sumCheckedAmounts(){
     if (typeof(glbDataTable) != 'undefined'){
        //find record
        var recordSet = glbDataTable.getRecordSet();
        var records = recordSet.getRecords();
        var record = false;
        var total_calc_hours = 0;
        var total_calc_amount = 0;
        
        var array_details = [];
        
        for (var i = 0; i < records.length; ++i){
            if ($('input[name=cbx_wl_' + records[i].getData('id') + ']').prop('checked')){
                   if (records[i].getData('_calc_hours_')){
                       total_calc_hours += records[i].getData('_calc_hours_');
                   }                
                   if (records[i].getData('_calc_amount_')){
                       total_calc_amount += records[i].getData('_calc_amount_');
                   }
                   
                   array_details.push(records[i].getData());
             }
        }   
        return { total_calc_hours: total_calc_hours, total_calc_amount: total_calc_amount, details: array_details };
     }  
  }
  
  function updateWLChecked(cbx, id){
     if (typeof(cbx) != 'undefined' && typeof(id) != 'undefined'){
        //update the checked data value so we don't lose the checkbox
        var record = glbFindRecordById(id);
        if (record){
               record.setData('__checked__', $(cbx).prop('checked') );
               glbDataTable.getRecordSet(record).updateRecordValue ( record , '__checked__' , $(cbx).prop('checked') ); 
               //glbDataTable.render();  
        }                      
     }
     var checkedCount = $('.wlcbxes:checked').length;
     var tot_calc_hours;
     var tot_calc_amount;
     var details = [];
        
     if (checkedCount == 0){
        $('#selbox_with_wlchecked').hide();
        $('#selbox_with_wlchecked option[value=""]').text('With checked: ');
        tot_calc_hours = 0;
        tot_calc_amount = 0;
     }else{
        $('#selbox_with_wlchecked').show();
        var res = sumCheckedAmounts();
        $('#selbox_with_wlchecked option[value=""]').text('With ('+checkedCount+') checked: ' );
        tot_calc_hours = res.total_calc_hours;
        tot_calc_amount = res.total_calc_amount;
        details = res.details;
        //$('#selbox_with_wlchecked option[value=""]').text('With ('+checkedCount+') checked (' + res.total_calc_hours.toFixed(2) + 'hr => $' + res.total_calc_amount.toFixed(2) + '): ' );
     }
     
     if (checkedCount == 0 || (tot_calc_hours == 0 && tot_calc_amount == 0)){
         $('#divCheckedSummary').hide();
         
     }else{
        //$('#spnCheckedNum').text(checkedCount);
        //$('#spnCheckedTotalCalcHours').text(tot_calc_hours.toFixed(2)+' hr');
        //$('#spnCheckedTotalCalcAmount').text(tot_calc_amount.toFixed(2));
        $('#divCheckedSummary').show();
        
        var s = '';
        var sum_rate = 0;
        if (details.length > 0){
           s = '<table border=0 cellspacing=3 cellpadding=3>';
           //s += '<tr><td>Work Log</td><td>Calc Hours</td><td>Rate</td><td>Calc Amount</td><td>&nbsp;</td></tr>';
           s += '<tr><td colspan=5><b>' + checkedCount +'</b> Work Logs Selected</td></tr>';
           for(var i = 0; i < details.length; ++i){
              sum_rate += details[i]['rate'];
              s += '<tr><td align=left>'+details[i]['title']+'</td><td align=right>'+details[i]['_calc_hours_'].toFixed(3) + ' hr' + '</td><td align=right>@ $' + (details[i]['rate'] ? parseFloat(details[i]['rate']).toFixed(2) : '0.00') + '/hr</td><td align=right> $' + details[i]['_calc_amount_'].toFixed(2) + '</td><td>&nbsp;</td></tr>';
           }
           s += '<tr class="totals"><td align=right><b>Total: </b></td><td align=right><b>' + tot_calc_hours.toFixed(3)+' hr</b></td><td align=center> =&gt; </td><td align=right><b>$'+ tot_calc_amount.toFixed(2) +'</b></td><td align=right style="border-top: none;"><button onclick="glbDoWithChecked(\'generate-invoice\');">PDF Invoice</button></tr>';
           s += '</table>';
        }
        $('#divCheckedDetailed').html(s);
     }
  }
  
  $(document).ready(function() {
        $("#dlgAddNote").dialog({ autoOpen: false, width: 240, height: 190 });
          $("#dlgAddFile").dialog({ autoOpen: false, width: 240, height: 345 });
          $("#dlgAddTime").dialog({ autoOpen: false, width: 234, height: 207 });
         
                 function clearSelection() {
                      if(document.selection && document.selection.empty) {
                          document.selection.empty();
                      } else if(window.getSelection) {
                          var sel = window.getSelection();
                          sel.removeAllRanges();
                      }
                  }
        //can't make draggable because then you can't select text
        var flip = false;
        $( "#divCheckedSummary" ).dblclick(function(){ 
                 if (flip){ 
                    $(this).draggable('destroy').css('cursor', 'pointer'); 
                 } else{ 
                    $(this).draggable().css('cursor', 'move');
                    
                 }
                 flip = !flip;
                 clearSelection();

              } );
 
        updateWLChecked();
  });

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


    function glbGetCheckedCSVids(){
     var s = '';
     $('.wlcbxes:checked').each(function(){
        if (s != ''){ s += ','; }
        s += $(this).val();
     });
     return s;
}

function glbGrabRunningTimerInfo(){
  if (typeof(glbAjaxFetch) !== 'function'){
        return false;
  }
  glbAjaxFetch('running_time_log', function(data){
        console.log(data);
        if (data.time_log === false){
            return;
        }
        var min = data.time_log._diff_seconds_ / 60;
        $('#running_time_log').html(min.toFixed(1) + ' min ');
  });
}

var glbGrabRunningTimerInfo_helper = function(){
     var min = parseFloat($('#running_time_log').html());
     if (isNaN(min)){ glbGrabRunningTimerInfo(); return; }
     min += 0.1;
     $('#running_time_log').html(min.toFixed(1) + ' min ');
}
//this is used in conjunction with the running timer above
//just helps display the time currently increasing
//MATHHELP: 6000 is 6 seconds which is .1 minutes 
glbGrabRunningTimerInfo_helper();
setInterval(glbGrabRunningTimerInfo_helper, 6000);

function glbAjaxFetch(f, callback){
        $.get('/lib/ajax_fetch.php', {'f': f}, 
        function (data){
          if (typeof(callback) == 'function'){ callback(data); }
        }, 'json');
}

function glbPostAction(action, csv_worklog_ids, callback){
        $.post('ajax_service.php', {'action': action, 'csv_worklog_ids':csv_worklog_ids}, 
        function (data){
          if (typeof(callback) == 'function'){ callback(data); }
        }, 'json');
}

function glbDoWithChecked(action){
   var s = glbGetCheckedCSVids();
   var ids = s.split(',');
   if (action == 'generate-invoice'){
        window.location.href = 'invoice.php?wid='+s+'&format=pdf';
        return;
   }else if (action == 'lock-worklogs'){
       glbPostAction(action, s, function(data){
          if (!data.error){
             for (var i = 0; i < data.work_logs.length; ++i){
                glbUpdateWorkLogJS(data.work_logs[i]);
             }
          }
       });
   }else if (action == 'unlock-worklogs'){
       glbPostAction(action, s, function(data){
          if (!data.error){
             for (var i = 0; i < data.work_logs.length; ++i){
                glbUpdateWorkLogJS(data.work_logs[i]);
             }
          }
       });
   }else if (action == 'invoice-date-today'){
       glbPostAction(action, s, function(data){
          if (!data.error){
             for (var i = 0; i < data.work_logs.length; ++i){
                glbUpdateWorkLogJS(data.work_logs[i]);
             }
          }
       });
   }else if (action == 'mark-paid-today'){
       glbPostAction(action, s, function(data){
          if (!data.error){
             for (var i = 0; i < data.work_logs.length; ++i){
                glbUpdateWorkLogJS(data.work_logs[i]);
             }
          }
       });
   }
}

$(function(){
    $('#selbox_with_wlchecked').change(function(){
       var action = $(this).val();
       $(this).val('');
       if (action != ''){
           if ($('.wlcbxes:checked').length == 0){
              alert('Please check a worklog before trying to perform an action');
              return;
           }
           glbDoWithChecked(action);
       }
    });
});

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


glbFindRecordById = function(id){
   //find record by oData.id
   var recordSet = glbDataTable.getRecordSet();
   var records = recordSet.getRecords();
   var record = false;

   for (var i = 0; i < records.length; ++i){
      if (records[i].getData('id') == id){
         record = records[i];
         break;
      }
   }
   return record;
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

