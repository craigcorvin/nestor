$(document).ready(function() {
	$('.notifier-type-toggle').on('change', function(e) {
		$(this).closest('.notifier-type').find('.notifier-type-options').toggleClass('notifier-type-options-off');
	});
});