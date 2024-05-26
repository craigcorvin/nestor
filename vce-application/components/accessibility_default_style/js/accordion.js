$(document).ready(function() {

	$(document).on('click','.accordion-title', function(e) {
		if ($(this).hasClass('disabled') !== true) {
			$(this).attr("aria-expanded",($(this).attr("aria-expanded") != "true"));
			if ($(this).closest('.accordion-container').hasClass('accordion-open')) {	
				// $(this).closest('.accordion-container').addClass('accordion-closed');
				$(this).closest('.accordion-container').find('.accordion-content').first().slideUp('slow', function() {
					$(this).closest('.accordion-container').removeClass('accordion-open').addClass('accordion-closed');
				});
			} else {
				$(this).closest('.accordion-container').addClass('accordion-open');
				$(this).closest('.accordion-container').find('.accordion-content').first().slideDown('slow', function() {
					$(this).closest('.accordion-container').removeClass('accordion-closed');
				});
			}
		}
		e.preventDefault();
	});

});