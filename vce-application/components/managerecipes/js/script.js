function onformsuccess(formsubmitted,data) {

	if (data.form == "edit") {
		window.location.reload(true);
	}

	if (data.form == "delete") {
		window.location.reload(true);
	}

	if (data.form == "update_json") {
		window.location.reload(true);
	}

}

$(document).ready(function() {

	$("#existing-recipes").tablesorter({
		headers: { 
            0: { sorter: false },1: {sorter: false },4: {sorter: false }
        } 
	}); 

    // activate Nestable for lists
    $('#nestable, #nestable2').nestable({
        group: 1
	});
	
	$('.update-json-form').on('submit', function(e) {
		if (confirm("Are you sure you want to alter this recipe?")) {
		}
	});

    
    $('.recipe-form').on('submit', function(e) {

    	e.preventDefault();
    	
   		var formsubmitted = $(this);
   		var list = $('.right-block');
    
    	submittable = true;
    
    	var typetest = $(list).find('input[type=hidden],input[type=text],input[type=email],input[type=password],textarea');
		
		if ($(list).find('textarea[name=json_text]').val()) {
			var rawrecipe = jQuery.parseJSON($(list).find('textarea[name=json_text]').val());
			$(list).find('input[name=recipe_name]').val(rawrecipe.recipe_name);
		} else {
			typetest.each(function(index) {
				if ($(this).val()) {
					$(this).closest('.dd-item').attr('data-' + $(this).attr('name'), $(this).val());
				}
				if ($(this).val() == "" && $(this).attr('tag') == 'required') {
					$(this).closest('.input-label-style').addClass('highlight-alert');
					$(this).closest('.dd-content-extended').show();
					submittable = false;
				}
			});
		}
		
		var selecttest = $(list).find('select');
		selecttest.each(function(index) {
			if ($(this).find('option:selected').val()) {
				$(this).closest('.dd-item').attr('data-' + $(this).attr('name'), $(this).find('option:selected').val());
			}
			
			
			if ($(this).find('option:selected').val() == "" && $(this).attr('tag') == 'required') {
				$(this).parent('label').addClass('highlight-alert');
				$(this).closest('.dd-content-extended').show();
				submittable = false;
			}
		});
		
		
		var checkboxtest = $(list).find('input[type=checkbox]');
			var required = {};
			var hadcheck = {};
			checkboxtest.each(function(index) {
			
			boxname = 'data-' + $(this).attr('name');
			boxvalue = $(this).val();	
			boxcheck = $(this).prop('checked');
			
			if ($(this).attr('tag') == 'required') {
				if (typeof required[boxname] === 'undefined') {
					required[boxname] = $(this);
				}
			}
			
			if (boxcheck === true) {
				var item = $(this).closest('.dd-item');
				current = item.attr(boxname);
				hadcheck[boxname] = true;
				if (current) {
					item.attr(boxname,current + '|' + boxvalue);
				} else {
					item.attr(boxname,boxvalue);
				}
			}
		});
		
		$.each(required, function (index, value) {
			if (!hadcheck[index]) {
				value.closest("label:not('.ignore')").addClass('highlight-alert');

			}
		});
		
		hierarchy = JSON.stringify($('#nestable2').nestable('serialize'));
		
		if (submittable) {
		
		
			var inputtypes = [];
		
			inputtypes.push(
				{name: 'dossier', type: 'text'},
				{name: 'recipe_name', type: 'text'},
				{name: 'full_object', type: 'checkbox'},
				{name: 'json_text', type: 'json'},
				{name: 'json', type: 'json'}
			);
		

			postdata = $(this).serializeArray();
			postdata.push(
				{name: 'json', value: hierarchy},
				{name: 'inputtypes', value: JSON.stringify(inputtypes)}
			);
		
			$.post( formsubmitted.attr('action'), postdata, function(data) {
				if (data.response == "error") {
					$(formsubmitted).children('.recipe-info').prepend('<div class="form-message form-error">' + data.message + '</div>');
					$('.form-message').delay(1000).fadeOut('slow');
				} else if (data.response == "success") {
					$(formsubmitted).children('.recipe-info').prepend('<div class="form-message form-success">' + data.message + '</div>');
					$('.form-message').delay(1000).fadeOut('slow');
					setTimeout( function() {
						window.location.reload(true);
					}, 2000);
				} else if (data.response == "updated") {
					$(formsubmitted).children('.recipe-info').prepend('<div class="form-message form-success">' + data.message + '</div>');
					$('.form-message').delay(1000).fadeOut('slow');
					setTimeout( function() {
						window.location.reload(true);
					}, 2000);
				} else {
					$(formsubmitted).children('.recipe-info').prepend('<div class="form-message form-error">An error occured</div>');
					$('.form-message').delay(1000).fadeOut('slow');
				}
			
			//console.log(data);	
			}, "json")
			.fail(function(response) {
				console.log('Error: Response was not a json object');
				$(formsubmitted).prepend('<div class="form-message form-error">' + response.responseText + '</div>');
			});
		}
    
    });


    $('select[name=type]').change(function() {
    	var oldroad = $(this).closest('.dd-content').children('.dd-content-extended').children('label');
    	var title = oldroad.children('input[name=title]').val();
        var url = oldroad.children('input[name=url]').val();
    	var component = $(this).val();
		$(this).closest('.dd-item').attr('data-type',component);
		var extended = $('.' + component + '-extended').clone();
    	extended.children('.remove-button').remove();
		$(this).closest('.dd-content').children('.dd-content-extended').replaceWith(extended);
		var partialroad = $(this).closest('.dd-content').children('.dd-content-extended');
		partialroad.slideToggle('slow');
		var newroad = partialroad.children('label');
		newroad.children('input[name=title]').val(title);
    	newroad.children('input[name=url]').val(url);
    });
    
    
    $('.category-display').on('click', function() {
    	var category_type = '.' + $(this).attr('category') + '-component';
    	$('#nestable .all-components').hide();
    	$('.category-display').removeClass('highlight');
    	$(this).addClass('highlight');
    	$('#nestable ' + category_type).show();
    });
    
    
    $('.view-recipe-object').on('click', function() {
		var recipe_object = '.recipe-object-' + $(this).attr('component_id');
    	$(recipe_object).slideToggle();
    });


	$(window).scroll(function () {  
  		var scrollTop = $(window).scrollTop(); 	
		var rightTop = $('.right-block').offset().top;
		var leftTopMargin = (rightTop - scrollTop) * -1;
		var leftHeight = $('.left-block').height();
		var rightHeight = $('.right-block').height();
		if (scrollTop < rightTop) {
			$('.left-block').css('margin-top', '0px');
		} else if (leftTopMargin > 0 && (leftHeight + leftTopMargin) < rightHeight) {
			$('.left-block').css('margin-top', leftTopMargin);
		}
	});


	$(document).click('.dd-toggle', function(e) {
		$(e.target).toggleClass('open');
	});

});
