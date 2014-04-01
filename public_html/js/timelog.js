TimeLog = function(){

};

TimeLog.prototype.getChecked = function(){
    return $('input.cbxTimeLog:checked');
};

TimeLog.prototype.sumMinutes = function(){
    $cbxes = this.getChecked();
    if ($cbxes.length == 0){ return 0.0; }

    var minutes_sum = 0.0;
    $cbxes.each(function(i, cbx){
    	var tid = cbx.value;
    	var id = 'spn_duration_'+tid;
    	var $spn = $('#'+id);
    	var min = $spn.length > 0 ? parseFloat($spn.text()) : 0.0;
    	if (!isNaN(min)){
    		minutes_sum += min;
    	}
    });
    return minutes_sum;
};

$(function(){
//delay load this

timelog_keyUp = function (ipt){
	var $ipt = $(ipt);



	if (!$ipt.data('powertip')){
		$(ipt).powerTip({ 
					placement: 'n', 
					smartPlacement: true,
					openEvents: ['focus','mouseenter','keydown','click'], 
					closeEvents: [],
					keepAlive: false 
				});
		$ipt.data('powertip', 'Enter a valid time or string');
	}

	//first lets try javascript to parse
	try{
		var d = new Date(ipt.value);
		if (d && !isNaN(d.getTime())){
			$ipt.data('powertip', 'JavaScript parsed as: <i class="tooltipdatetime">'+d.toString('MMM d, yyyy hh:mm:ss tt')+'</i>');
		}
	}catch(ex){
		//do nothing
	}
	
	$.powerTip.show($ipt);
	$('#powerTip').html($ipt.data('powertip'));
    

    $.ajax({
          type: "GET",
          url: "time_log_show.php",
          dataType: "json",
          data: 'ajax=timecheck&name=' + ipt.name + '&value=' + ipt.value + '&timelog_id=' + ipt.getAttribute('tid')

        }).done(function(msg){
        	var m = msg.error;
        	if (!m){ 
        	   m = 'Good! <i class="tooltipdatetime">'+msg.result_time+'</i> (duration of '+msg.result_minutes.toFixed(2)+' min)'; 
            }
			$ipt.data('powertip', '<b>'+m+'</b>');
			$.powerTip.show($ipt);
			$('#powerTip').html($ipt.data('powertip'));
        });
};

});
