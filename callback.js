function callback_test_inputs() {
	
	var errors = 0;
	jQuery("#callback :input.required").map(function(){

		if (callback_fields.hasOwnProperty(this.name)) {
			if( !callback_fields[this.name].callback(this) ) {
				jQuery(this).addClass('warning');
				errors++;
			} else if (jQuery(this).val()) {
				jQuery(this).removeClass('warning');
			}
		}
	});
	
	if(errors > 0){
		jQuery("#instructions").hide();
		jQuery("#error").show();
		jQuery('.buttons').show();
		return true;
	} else {
		jQuery('.processing').show();
		jQuery('.buttons_working').show();
		return false;
	}
}

jQuery(document).ready(function($) {
	
	var $ = jQuery;
	
	$('#contact').keyup(function(event) {
		if ($('#contact').val().length > 5 && $.isNumeric($('#contact').val())) {
			$('#callwhen').show();
		} else {
			$('#callwhen').hide();
		}
	});
	
	// Opens toggle
	$('.opentoggle').on('click', function() {
		$('#callbackform').toggle('slow');
	});
	
	// Opens terms modal
	$('.openmodal').on('click', function() {
		$('#callbackform').show();
	});
	
	// Closes terms modal
	$('.closemodal').on('click', function() {
		$('#callbackform').hide();
	});

	$('#callback').submit(function() {
		$('.buttons').hide();
		event.preventDefault();
		var fail = callback_test_inputs();
		if (!fail) {
			// Do Ajax Submission
			var data = {};
			var nd = $(this).serializeArray();
			for (i in nd) {
				data[nd[i].name] = nd[i].value;
			}
			data['action'] = 'ajax_callback_submit';
			jQuery.post(callback_ajax_url, data, function(response) {
				jQuery('.modal-form').hide();
				jQuery('.modal-message').show();
				jQuery('.thankyoutitle').html(response.title);
				jQuery('.thankyoublurb').html(response.message);
				
			},'json');
		}
		return false;
	});
});