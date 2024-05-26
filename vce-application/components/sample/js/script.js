$(document).ready(function() {

	var selector = selector || { };
	
	selector.binder = function(eachSelector) {
	
		function asc_sort(a, b){
			return ($(b).text().toUpperCase()) < ($(a).text().toUpperCase());    
		}

		function update_users() {
			var eachSelectedUser = $(eachSelector).find('.selected-users');
			var selected = $(eachSelector).find('.group-members li');
			var ids = new Array();
			$.each(selected, function (index, value) {
				var thisId = $(value).attr('user_id');
				ids.push(thisId);
			});
			eachSelectedUser.val(ids.join('|'));
		}


		$(document).find(eachSelector).on('click keydown', '.remove-members, .remove-current-members', function(e) {
			var parentLi = $(this).parent();
			parentLi.remove();
			update_users();
		});
		
		$(eachSelector).find('input[name=search]').on('keyup', function(e) {
			if ($(this).val().length > 2) {
				var search = $(this);
				var action = search.attr('action');
				postdata = [];
				postdata.push(
				{name: 'dossier', value: search.attr('dossier')},
				{name: 'search', value: search.val()},
				{name: 'inputtypes', value: '[]'}
				);
				$.post(action, postdata, function(data) {
					console.log(data);
					if (data.response == "error") {
						console.log(data);
					}
					if (data.response == "success") {
						var SelectedUser = $(eachSelector).find('.selected-user');
						var selectedUserContainer = $(eachSelector).find('.selected-user-container');
						SelectedUser.empty();
						if (data.results !== null) {
							selectedUserContainer.show();
							var results = JSON.parse(data.results);
							$.each(results,function(key,value) {
								var user = value.first_name + ' ' + value.last_name + ' (' + value.email + ')';
								SelectedUser.append($("<option></option>").attr("value",key).attr("firstname",value.first_name).attr("lastname",value.last_name).text(user));
							});
							SelectedUser.attr('size',Object.keys(results).length);
						} else {
							selectedUserContainer.hide();
						}
					}
				}, "json");
			}
		});
		
		$(eachSelector).find('.select-user-button').on('click', function(e) {
		
			selected = $(eachSelector).find('.selected-user').find(":selected");
		
			if (selected.length) {
	
				userid = selected.val();
				firstname = selected.attr('firstname');
				lastname = selected.attr('lastname');
		
				additional = '<li class="ui-state-default ui-sortable-handle" user_id="' + userid + '" tabindex="0" aria-grabbed="false" aria-haspopup="true" role="listitem" style="position: relative; left: 0px; top: 0px;"><span class="remove-members" title="remove">x</span>' + firstname + ' ' + lastname + '</li>';

				$(eachSelector).find('.group-members').append(additional);
	
				update_users();
		
				clear_search();
		
			}
			
		});
		
		$(eachSelector).find('.clear-button').on('click', function(e) {
			e.preventDefault();
			clear_search();
		});
	
		function clear_search() {
			$(eachSelector).find('.selected-user').empty();
			$(eachSelector).find('.selected-user-container').hide();
			$(eachSelector).find('input[name=search]').val('');
		}

		// keyboard accessibility
		function createKeyboardDragDrop(selectedUser) {
			var popup;
			var availability;
			var text;

			$(eachSelector).find('.popup').remove();

			availability = 'selected';
			text = 'Remove from Selected Users';

			popup = $('<ul class="popup" role="menu">')
				.append(
					$('<li role="menuitem" tabindex="0" class="move-to">')
						.data('value', availability)
						.text(text)
				);

			selectedUser.append(popup);
			$(eachSelector).find('.move-to').focus();
		}

		$(document).find(eachSelector).on('keypress', '.ui-state-default', function(e) {
			createKeyboardDragDrop($(this));
		});

		// keypress listener for keyboard drag and drop menu
		$(document).find(eachSelector).on('keydown click', '.move-to', function(e) {
			// cancel out of menu on escape or tab
			if (e.which === 27 || e.which === 9) {
				$(eachSelector).find('.popup').remove();
			} else {
				$(this).closest('.ui-state-default').remove();
				$(eachSelector).find('.popup').remove();
				update_users();
			}
		});


	};
	
	
	$('.selector-container').each(function() {
		selector.binder($(this));
	});

});
