$(document).ready(function() {
    $('select[name=filter]').change(function() {
    
    	full_url = window.location.href.split('?');
    	
    	url = full_url[0];
    
    	var menu = $(this);
    	selected = menu.find('option:selected').val();
    	
    	var query = '';
    	
    	if (selected !== '') {
    		query = '?filter=' + selected;
    	}
    	
    	window.location.href = url + query;
    	
	});
});