$(document).ready(function() {

	$('.tooltip').on('mouseover focus', function(e) {
		$(this).children().show();
	}).mouseout(function() {
		$(this).children().hide();
	}).blur(function() {
		$(this).children().hide();
	});

});
