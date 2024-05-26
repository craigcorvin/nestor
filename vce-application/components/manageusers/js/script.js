function onformerror(formsubmitted,data) {

	if (data.form == "create") {
		$(formsubmitted).prepend('<div class="form-message form-error">' + data.message + '</div>');
	}

	if (data.form == "masquerade") {
		$(formsubmitted).prepend('<div class="form-message form-error">' + data.message + '</div>');
	}

	if (data.form == "delete") {
		$(formsubmitted).parent().prepend('<div class="form-message form-error">' + data.message + '</div>');
	}

	if (data.form == "password") {
		$(formsubmitted).parent().prepend('<div class="form-message form-error">' + data.message + '</div>');
	}

}

function onformsuccess(formsubmitted,data) {

	console.log(formsubmitted);
	
	if (data.form === "edit") {
		window.location.reload(true);
	}

	if (data.form == "create") {

		$(formsubmitted).prepend('<div class="form-message form-success">' + data.message + '</div>');
	
		setTimeout(function(){
	   	window.location.reload(true);
		}, 2000);

	}

	if (data.form == "password") {

		$(formsubmitted).prepend('<div class="form-message form-success">' + data.message + '</div>');
	
		setTimeout(function(){
	   	window.location.reload(true);
		}, 2000);

	}


	if (data.form == "masquerade") {

		window.location.href = data.action;	   	

	}

	if (data.form == "delete") {

		window.location.reload(true);

	}

}


$(document).ready(function() {
	
	$('.pagination-button, .sort-icon').on('click', function(e) {
		e.preventDefault();
		postdata = [];
		postdata.push(
		{name: 'dossier', value: JSON.stringify($(this).attr('dossier'))},
		{name: 'pagination_current', value: $(this).attr('pagination')},
		{name: 'sort_by', value: $(this).attr('sort')},
		{name: 'sort_direction', value: $(this).attr('direction')},
		{name: 'inputtypes', value: '[]'}
		);
		$.post($(this).attr('action'), postdata, function(data) {
			// console.log(data);
			window.location.reload(true);	
		}, 'json');
	});
	
	$('.pagination-input').on('change', function(e) {
		e.preventDefault();
		var pagination = $(this).val();
		if (!isNaN(parseFloat(pagination)) && isFinite(pagination)) {
			postdata = [];
			postdata.push(
			{name: 'dossier', value: JSON.stringify($(this).attr('dossier'))},
			{name: 'pagination_current', value: pagination},
			{name: 'sort_by', value: $(this).attr('sort')},
			{name: 'sort_direction', value: $(this).attr('direction')},
			{name: 'inputtypes', value: '[]'}
			);
			$.post($(this).attr('action'), postdata, function(data) {
				// console.log(data);
				window.location.reload(true);	
			}, 'json');
		}
	});


	$('#generate-password').on('click', function(e) {
	
	
		e.preventDefault();
		postdata = [];
		postdata.push(
		{name: 'dossier', value: JSON.stringify($(this).attr('dossier'))},
		{name: 'inputtypes', value: '[]'}
		);
		$.post($(this).attr('action'), postdata, function(data) {
			// console.log(data);
			$('input[name=password], input[name=password2]').val(data.message);
		}, 'json');


	});
	
	
	$('.filter-form-submit').on('click', function(e) {

		var dossier = $(this).attr('dossier');
		var action = $(this).attr('action');
		var pagination = $(this).attr('pagination');
		
		var postdata = [];
		postdata.push(
			{name: 'dossier', value: dossier},
			{name: 'pagination_current', value: pagination}
		);
		
		$('.filter-form').each(function(key, value) {
			var selectedName = 'filter_by_' + $(value).attr('name');
			var selectedValue = $(value).val();
				if (selectedValue !== "") {
				postdata.push(
					{name: selectedName, value: selectedValue}
				);
			}
		});

		$.post(action, postdata, function(data) {
			console.log(data);
			if (data.response === 'success') {
				window.location.reload(true);
			}
		}, 'json');
	});
	

});