$(document).ready(function() {

	$('.zip-download').on('click', function(e) {

		e.preventDefault();

		if (!$(this).hasClass('clicked')) {

			var thisDownload = $(this);

			thisDownload.addClass('clicked');

			var oldText = thisDownload.text();

			var dossier = $(this).attr('dossier');
			
			postdata = [];
			postdata.push({name: 'dossier', value: dossier});
			$.post($(this).attr('action'), postdata, function(data) {
				//e.preventDefault() prevents both the first and the second clicks on thisDownload. I tried "unbind" in various ways, but 
				//it doesn't stop the preventDefault. The only way I could find to use thisDefault is to have it call the method which prepares 
				//the download, then append another hyperlink and click on it to download the file.
				//$(thisDownload).unbind("click").text('Downloading: ' + oldText).attr("id", data.uid).attr("href", data.url).trigger('click');
				$(thisDownload).text('Downloading: ' + oldText);
				// the media component creates an id for each media item, which can be the same as this, and spaces must be removed.
				var uid = 'dl-' + data.uid.replace(/\s+/g, '-').toLowerCase();
				$('body').append('<a id="' + uid + '" href="' + data.url + '">&nbsp;</a>');
				$('#' + uid)[0].click();
			}, 'json');
		}

	});

});