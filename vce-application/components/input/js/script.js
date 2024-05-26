$(document).ready(function() {

	$(document).on('focus, focusin', 'textarea,input[type=text],input[type=date],input[type=number],input[type=email],input[type=password],select', function(e) {
		console.log($(this).closest('label'));
		$('.form-error').fadeOut(1000, function(){ 
    		$(this).remove();
		});
		// change from label to input-label-style
		$(this).closest('.input-label-style').removeClass('highlight-alert').addClass('highlight');
		$(this).parents().eq(1).children(':submit').addClass('active-button');
	});

	$(document).on('blur', 'textarea,input[type=text],input[type=date],input[type=number],input[type=email],input[type=password],select', function() {
		$(this).closest('label').removeClass('highlight');
		if ($(this).val() === "") {
			$(this).parents().eq(1).children(':submit').removeClass('active-button');
		}
	});
	
	$(document).on('click', 'input[type=checkbox]:not(.ignore)', function() {
		$(this).closest('label').removeClass('highlight-alert').addClass('highlight');
	});

	$(document).on('submit', '.asynchronous-form', function(e) {
		e.preventDefault();
		
		submit_button = $(this).find('input[type=submit]');
		
		if (submit_button.css('cursor') == 'wait') {
			return;
		} else {
			submit_button.css('cursor','wait');
		}

		var formsubmitted = $(this);
	
		var submittable = true;
	
		if ($(this).hasClass('delete-form')) {
			if (confirm("Are you sure you want to delete?")) {
				submittable = true;
			} else {
				submit_button.css('cursor','pointer');
				submittable = false;
				return false;
			}
		}

		var inputtypes = [];
	
		var hiddentest = $(this).find('input[type=hidden]');
		hiddentest.each(function(index) {
			var eachinput = {};
			eachinput.name = $(this).attr('name');
			eachinput.type = $(this).attr('type');
			if ($(this).attr('schema')) {
				eachinput.type = $(this).attr('schema');
			}
			inputtypes.push(eachinput);
			submittable = true;
		});
	
		var textareatest = $(this).find('textarea');
		textareatest.each(function(index) {
			var eachinput = {};
			eachinput.name = $(this).attr('name');
			eachinput.type = 'textarea';
			if ($(this).attr('schema')) {
				eachinput.type = $(this).attr('schema');
			}
			inputtypes.push(eachinput);
			if ($(this).is(':visible')) {
				if ($(this).val() == "" && $(this).attr('tag') == 'required') {
					$(this).closest('label').addClass('highlight-alert');
					$(this).closest('.input-label-style').addClass('highlight-alert');
					submittable = false;
				}
			}
		});
						
		var typetest = $(this).find('input[type=text],input[type=date],input[type=number],input[type=email],input[type=password]');
		typetest.each(function(index) {
			var eachinput = {};
			eachinput.name = $(this).attr('name');
			eachinput.type = $(this).attr('type');
			inputtypes.push(eachinput);
			if ($(this).is(':visible')) {
				if ($(this).val() == "" && $(this).attr('tag') == 'required') {
					$(this).closest('label').addClass('highlight-alert');
					$(this).closest('.input-label-style').addClass('highlight-alert');
					submittable = false;
				}
			}
		});
		
		var selecttest = $(this).find('select');
		selecttest.each(function(index) {
			var eachinput = {};
			eachinput.name = $(this).attr('name');
			eachinput.type = 'select';
			inputtypes.push(eachinput);
			if ($(this).find('option:selected').val() == "" && $(this).attr('tag') == 'required') {
				$(this).closest('label').addClass('highlight-alert');
				$(this).closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
			}
		});
	
		var checkboxtest = $(this).find('input[type=checkbox]');
		var box = {};
		var required = {};
		var labels = {};
		checkboxtest.each(function(index) {
		
			// get boxname
			if ($(this).attr('options-group')) {
				var boxname = $(this).attr('options-group');
			} else {
				var boxname = $(this).attr('name');
			}
			
			// create list of labels
			labels[boxname] = $(this);
			
			// is it required?
			if ($(this).attr('tag') == 'required') {
				required[boxname] = true;
			}
			
			var boxcheck = $(this).prop('checked');
			
			if (typeof box[boxname] !== 'undefined') {
				if (box[boxname] === false) {
					box[boxname] = boxcheck;
				}
			} else {
				var eachinput = {};
				eachinput.name = boxname;
				eachinput.type = $(this).attr('type');
				inputtypes.push(eachinput);
				box[boxname] = boxcheck;	
			}

		});
		$.each(labels, function(boxname,value) {
			if (box[boxname] === false && required[boxname] === true) {

				var verify = $(value).closest('label').parent('label').addClass('highlight-alert');
				if (typeof verify !== 'undefined') {
					$(value).closest('label').addClass('highlight-alert');
					$(value).closest('.input-label-style').addClass('highlight-alert');
				}
				submittable = false;
			}
		});

		var radiotest = $(this).find('input[type=radio]');
		var box = {};
		var required = {};
		radiotest.each(function(index) {
			var boxname = $(this).attr('name');
			if ($(this).attr('tag') == 'required') {
				required[boxname] = $(this);
			}
			if (typeof box[boxname] === 'undefined') {
				box[boxname] = false;
			}
			if ($(this).prop('checked')) {
				box[boxname] = true;
			}
		});
		$.each(required, function(value) {
  			if (!box[value]) {
  				radiobutton = $(required[value]);
				radiobutton.closest('label').addClass('highlight-alert');
				radiobutton.closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
  			}
		});
	
		if (submittable) {
			postdata = $(this).serializeArray();
			console.log(formsubmitted.attr('id'));
			var addinputtypes = true;
			$.each(postdata, function(key, value) {
   				if (value.name === 'inputtypes') {
   					addinputtypes = false;
   				}
   			});
			if (addinputtypes) {
				postdata.push({name: 'inputtypes', value: JSON.stringify(inputtypes)});
			}
			$.post(formsubmitted.attr('action'), postdata, function(data) {
				if (data.response == "error") {
					if (typeof onformerror === 'function') {
						onformerror(formsubmitted,data);
					} else {
						formerror(formsubmitted,data);
					}
				} else if (data.response === "success") {
					if (typeof onformsuccess == 'function') {
						onformsuccess(formsubmitted,data);
					} else {
						formsuccess(formsubmitted,data);
					}
				} else if (data.response === "warning") {
					if (typeof onformwarning == 'function') {
						onformwarning(formsubmitted,data);
					} else {
						formwarning(formsubmitted,data);
					}
				} else {
					if (typeof onformerror === 'function') {
						onformerror(formsubmitted,data);
					} else {
						formerror(formsubmitted,data);
					}
				}
				
				// adding a 5 second pause to help prevent double clicking
				setTimeout(function() {submit_button.css('cursor','pointer')}, 5000);
				
				console.log(data);	
			}, "json")
			.fail(function(response) {
				submit_button.css('cursor','pointer');
				console.log('Error: Response was not a json object');
				$(formsubmitted).prepend('<div class="form-message form-error">' + response.responseText + '</div>');
			});
		} else {
			submit_button.css('cursor','pointer');
		}
	});

	function formerror(formsubmitted,data) {
		if (data.procedure === "create") {
			$(formsubmitted).prepend('<div class="form-message form-error">' + data.message + '</div>');
		}
		if (data.procedure === "update") {
			$(formsubmitted).prepend('<div class="form-message form-error">' + data.message + '</div>');
		}
		if (data.procedure === "delete") {
			$(formsubmitted).parent().prepend('<div class="form-message form-error">' + data.message + '</div>');
		}
	}
	
	function formwarning(formsubmitted,data) {
		$(formsubmitted).prepend('<div class="form-message form-warning">' + data.message + '</div>')
	}

	function formsuccess(formsubmitted,data) {
		if (data.url) {
			window.location.href = data.url;
		} else {
			window.location.reload(true);		
		}
	}
	
	$('.cancel-button').on('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		window.location.reload(true);
	});

});