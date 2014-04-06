$(function(){
   $('#emailinvoice').hide();
   $('#btnEmailInvoice').click(function(){
   		$('#emailinvoice').toggle();
   			

   			    var $body = $(this).parent().find('.body');
				$body.toggle();
				var invoice_top_helper_height;
				
				if ($body.is(":visible")){
						invoice_top_helper_height = '240px';
				}else{
						invoice_top_helper_height = '70px';
				}

				$('.invoice_helper').css('height', invoice_top_helper_height);
				$('.invoice').css('padding-top', invoice_top_helper_height);

   });


   		$('#moreinvoiceoptions .head').click(function(){
				var $body = $(this).parent().find('.body');
				$body.toggle();
				var invoice_top_helper_height;
				
				if ($body.is(":visible")){
						invoice_top_helper_height = '140px';
				}else{
						invoice_top_helper_height = '70px';
				}

				$('.invoice_helper').css('height', invoice_top_helper_height);
				$('.invoice').css('padding-top', invoice_top_helper_height);
		}).click();

});
