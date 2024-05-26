$(document).ready(function() {

	$('input[name=title]').not('.prevent-check-url').change(function() {

		var thisform = $(this).closest('.asynchronous-form');
		var urlinput = $(thisform).find('input[name=url].check-url');

		if (urlinput.length) {
		
			var url = $(this).val().replace(/\//g,'');
			$(urlinput).val($(urlinput).attr('parent_url') + url);
			checkurl(urlinput,thisform);
		}
		
	});


	$('input[name=url].check-url').change(function() {
		if ($(this).length) {
			checkurl($(this),$(this).closest('.asynchronous-form'));
		}
	});


	checkurl = function(url,thisform) {
		var inputtypes = [];
		inputtypes.push(
		{name: 'dossier', type: 'hidden'},
		{name: 'url', type: 'text'}
		);
		var postdata = [];
		postdata.push(
			{name: 'dossier', value: url.attr('dossier')},
			{name: 'url', value: url.val()},
			{name: 'inputtypes', value: JSON.stringify(inputtypes)},
		);
		if (thisform.length > 0) {
			$.post(thisform.attr('action'), postdata, function(data) {
				$(url).val(data.url);
			}, "json");
		}
	}

});