function onformerror(formsubmitted,data) {
	$('.component-list').append('<div class="form-message form-error">' + data.message + '</div>');
	if (data.action === "reload") {
		var delaytime = (data.delay) ? data.delay : 2000;
		setTimeout(function() {
			if (data.url) {
				window.location.href = data.url;
			} else {
				window.location.reload(true);		
			}
		}, delaytime);
	}
}

function onformsuccess(formsubmitted,data) {
	$('.component-list').append('<div class="form-message form-success">' + data.message + '</div>');
	if (data.action === "reload") {
		var delaytime = (data.delay) ? data.delay : 2000;
		setTimeout(function() {
			if (data.url) {
				window.location.href = data.url;
			} else {
				window.location.reload(true);		
			}
		}, delaytime);
	}
}

$(document).ready(function() {

	$('.switch').on('click', function(e) {

		$(this).closest('.each-component-switch').children('.switch').toggleClass('highlight');

		if ($(this).hasClass('activated')) {
			$(this).closest('.each-component').attr('state','activated');
		}

		if ($(this).hasClass('disabled')) {
			$(this).closest('.each-component').attr('state','disabled');
		}

	});

    $('.components-form').on('submit', function(e) {
    	e.preventDefault();
    
       	var formsubmitted = $(this);
    
    	var components = {};
		$('.each-component').each(function(index) {
			if ($(this).attr('state') === "activated") {
			
				var contents = {};
			
				var thistype = $(this).attr('type');
				var thisparent = $(this).attr('parent');
				var thisurl = $(this).attr('url');
				
				contents.type = thistype;
				contents.parent = thisparent;
				contents.url = thisurl;
		
				components[thistype] = contents;
			}
		});
	
		var installedComponents = JSON.stringify(components);

		postdata = $(this).serializeArray();
		postdata.push(
		{name: 'json', value: installedComponents},
		{name: 'inputtypes', value: '[]'}
		);
		
		$.post(formsubmitted.attr('action'), postdata, function(data) {
			if (data.response === "error") {
				$(formsubmitted).prepend('<div class="form-message form-error">' + data.message + '</div>');
			} else if (data.response === "success") {
				$(formsubmitted).prepend('<div class="form-message form-success">' + data.message + '</div>');
				if (data.action === "reload") {
					var delaytime = (data.delay) ? data.delay : 2000;
					setTimeout(function() {
						if (data.url) {
							window.location.href = data.url;
						} else {
							window.location.reload(true);		
						}
					}, delaytime);
				}
			} else {
				$(formsubmitted).parent().prepend('<div class="form-message form-error">An error occured</div>');
			}
			console.log(data);	
		}, "json")
			.fail(function(response) {
				console.log('Error: Response was not a json object');
				$(formsubmitted).prepend('<div class="form-message form-error">' + response.responseText + '</div>');
			});

    });
    
    
    $('.configure-component').on('submit', function(e) {
   		e.preventDefault();

    	var formsubmitted = $(this);
    	
		postdata = $(this).serializeArray();
		postdata.push(
		{name: 'inputtypes', value: '[]'}
		);
		$.post(formsubmitted.attr('action'), postdata, function(data) {
			if (data.response == "success") {
				$(formsubmitted).prepend('<div class="form-message form-success">' + data.message + '</div>');
				if (data.action === "reload") {
					var delaytime = (data.delay) ? data.delay : 2000;
					setTimeout(function() {
					if (data.url) {
						window.location.href = data.url;
					} else {
						window.location.reload(true);		
					}
				}, delaytime);
				}
			} else {
				$(formsubmitted).prepend('.component-list').append('<div class="form-message form-error">An error occured</div>');
				$('.form-message').delay(2000).fadeOut('slow');
			}
		}, "json")
		.fail(function(response) {
			console.log('Error: Response was not a json object');
			$(formsubmitted).prepend('<div class="form-message form-error">' + response.responseText + '</div>');
		});
    });

    $('.category-display').on('click', function() {
    	var category_type = '.' + $(this).attr('category') + '-component';
    	$('.all-components').hide();
    	$('.category-display').removeClass('highlight');
    	$(this).addClass('highlight');
    	$(category_type).show();
    });

});