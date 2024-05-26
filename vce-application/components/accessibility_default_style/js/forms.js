$(document).ready(function() {

	$('.input-label-style').on('focus', 'textarea, input[type=text], input[type=email], input[type=password], select', function(e) {		
		$(this).closest('.input-label-style').removeClass('highlight-alert').addClass('highlight');
	});
	
	$('.input-label-style').on('change', 'input[type=checkbox], input[type=radio]', function(e) {		
		$(this).closest('.input-label-style').removeClass('highlight-alert').addClass('highlight');
	});

	$('.input-label-style').on('blur', 'textarea, input[type=text], input[type=email], input[type=password], select', function() {
		$(this).closest('.input-label-style').removeClass('highlight');
	});
	
	$('.datepicker').each(function() {
		if ($(this).hasClass('empty')) {
			date = '';
		} else {
			if ($(this).val()) {
				date = $(this).val();
			} else {
				date = new Date();
			}
		}
		$(this).datepicker().datepicker('setDate', date);
	});

});