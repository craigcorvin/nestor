$(document).ready(function() {

	function findplayer(clickbar) {
		var clickbarParent = $(clickbar).parent();
		console.log(clickbarParent);
		var mediaItem = $(clickbarParent).find('.media-item');
		if (mediaItem.length !== 0) {
			return $(mediaItem).find('.vidbox');
		}
		return findplayer($(clickbarParent));
	}

	$(document).on('change keyup keydown paste cut','.comment-input', function(e) {
 		$(this).height(0).height(this.scrollHeight);
	}).change();

	$(document).on('click','.comment-update-input', function(e) {
		if (!$(this).hasClass('expanded')) {
			$(this).addClass('expanded');
			$(this).height(0).height(this.scrollHeight);
		}
	}).change();


	$(document).on('click','.accordion-title', function(e) {

		if ($(this).closest('.accordion-container').hasClass('accordion-closed')) {
			if (typeof videoPlayer === 'object') {
			console.log($(this));
				var player = findplayer($(this)).attr('player');
				if (typeof videoPlayer[player] !== 'undefined') {
					if (typeof videoPlayer[player].pauseVideoPlayer === 'function') {
						videoPlayer[player].pauseVideoPlayer();
					}
				}
			}
		}
// 		restart video playback
// 		} else {
// 			if (typeof videoPlayer === 'object') {
// 				var player = findplayer($(this)).attr('player');
// 				if (typeof videoPlayer[player] !== 'undefined') {
// 					if (typeof videoPlayer[player].startVideoPlayer === 'function') {
// 						videoPlayer[player].startVideoPlayer();
// 					}
// 				}
// 			}
// 		}
	});

	$(document).on('click','.comment-timestamp', function(e) {
		var timestamp = $(this).attr('timestamp');
		if (typeof videoPlayer === 'object') {
			var vidbox = findplayer($(this));
			var player = vidbox.attr('player');
			videoPlayer[player].shuttleVideoPlayer(timestamp);
			var vidPosition = $('#' + player).offset();
			$("html, body").animate({ scrollTop: (vidPosition.top - 50) }, "slow");
		}
	});

	$(document).on('submit','.asynchronous-comment-form', function(e) {
		e.preventDefault();
	
		var formsubmitted = $(this);
		var layout_container = $(this).attr('combar');
		var submittable = true;

		var textareatest = $(this).find('textarea');
		textareatest.each(function(index) {
			if ($(this).val() == "" && $(this).attr('tag') == 'required') {
				$(this).closest('.input-label-style').addClass('highlight-alert');
				submittable = false;
			}
		});

		if (submittable) {
		
			var submitbutton = $(this).find('input[type=submit]');
			
			$(submitbutton).css('cursor','wait');
	
			var comment_text = $(this).find('textarea').val();

			var postdata = [];
			postdata.push(
				{name: 'dossier', value: $(this).find('input[name=dossier]').val()},
				{name: 'inputtypes', value: '[]'},
				{name: 'text', value: comment_text}
			);
			
			var closestMediaItemContainer = $(this).closest('.media-item-container');
 
			// add timestamp only to comments within a media item container
			if (closestMediaItemContainer.length > 0) {
				if (typeof videoPlayer === 'object') {
					var player = findplayer($(this)).attr('player');
					if (typeof videoPlayer[player] !== 'undefined') {
						var timestamp = videoPlayer[player].getVideoPlayerTimestamp();
						if (videoPlayer[player].getVideoPlayerTimestamp() !== 'undefined' && timestamp > 0) {
							var duration = videoPlayer[player].duration();
							if (timestamp < duration) {
								postdata.push(
									{name: 'timestamp', value: timestamp}
								);
							}
						}
					}
				}
			}
		
			$.post($(this).attr('action'), postdata, function(data) {
			
				if (data.response === "success") {
	
					if (typeof videoPlayer !== 'object') {
						$('#comments-asynchronous-content').find('.comment-timestamp').remove();
					}

					var asynchronousContent = $('#comments-asynchronous-content').html();
				
					update_text = comment_text;
					comment_text = comment_text.replace(/</g, '&lt;');
					comment_text = comment_text.replace(/>/g, '&gt;');
					comment_text = comment_text.replace(/(?:\r\n|\r|\n)/g, '<br>');

					asynchronousContent = asynchronousContent.replace(/{component-id}/g, data.component_id);
					asynchronousContent = asynchronousContent.replace(/{text}/g, comment_text);
					asynchronousContent = asynchronousContent.replace(/{update-text}/g, update_text);
					asynchronousContent = asynchronousContent.replace(/{dossier-for-create}/, data.dossier_for_create);
					asynchronousContent = asynchronousContent.replace(/{dossier-for-update}/, data.dossier_for_update);			
					asynchronousContent = asynchronousContent.replace(/{dossier-for-delete}/, data.dossier_for_delete);

					var created_at = new Date().toLocaleTimeString([], { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });

					asynchronousContent = asynchronousContent.replace(/{created-at}/, created_at);

					var timestamp = 0;
					var nicetimestamp;
 
					if (typeof videoPlayer === 'object') {
						nicetimestamp = '0:00:00';
						if (typeof videoPlayer[player] !== 'undefined') {
             				 if (typeof videoPlayer[player].getVideoPlayerTimestamp !== 'undefined' && videoPlayer[player].getVideoPlayerTimestamp() > 0) {
								if (videoPlayer[player].getVideoPlayerTimestamp() < videoPlayer[player].duration()) {
									timestamp = videoPlayer[player].getVideoPlayerTimestamp();
									nicetimestamp = videoPlayer[player].getVideoPlayerNiceTime();
								}
							}
						}

						if (typeof videoPlayer[player] !== 'undefined' && timestamp > 0 && timestamp < videoPlayer[player].duration()) {
							asynchronousContent = asynchronousContent.replace(/{timestamp}/, timestamp);
							asynchronousContent = asynchronousContent.replace(/{nice-timestamp}/, nicetimestamp);
						}
					}
				
					var newComment = $.parseHTML(asynchronousContent);
				
					if (timestamp === 0) {
						$(newComment).find('.comment-timestamp').remove();
					}
				
					$('.' + layout_container).before(newComment);

					if (typeof videoPlayer === 'object') {
						$(formsubmitted).closest('.vidbox-content').fadeOut('slow');
						if (typeof videoPlayer[player] !== 'undefined') {
							if (typeof videoPlayer[player].startVideoPlayer !== 'undefined') {
								videoPlayer[player].startVideoPlayer();
							}
						}
					
					}

					$(formsubmitted).find('textarea').val('');
					if ($('.' + layout_container).closest('.accordion-container').hasClass('accordion-open')) {
						$('.' + layout_container).find('.accordion-title').trigger( "click" );
					}

					$(submitbutton).css('cursor','pointer');
				
				} else {
				
					alert('Error: Your comment did not save. Please contact support and report this error: ' + JSON.stringify(data));
				
				}

			}, "json")
			.fail(function(response) {
				console.log('Error: Response was not a json object');
				$(formsubmitted).prepend('<div class="form-message form-error">Error: Your comment did not save. Please contact clvce@uw.edu and report this error: Not a json object.</div>');
			});
		}

	});
	
	
	// sub-comment-form
	$(document).on('submit','.sub-comment-form', function(e) {
		e.preventDefault();
		
		var current = $(this);
		
		postdata = $(this).serializeArray();
		$.post($(this).attr('action'), postdata, function(data) {
			if (data.response === "success") {
				
			var asyncontid = current.attr('asyncontid');
		
			var asynchronousContent = $('#' + asyncontid).html();
			
			var comment_text = current.find('textarea').val();
			
			update_text = comment_text;
			comment_text = comment_text.replace(/</g, '&lt;');
			comment_text = comment_text.replace(/>/g, '&gt;');
			comment_text = comment_text.replace(/(?:\r\n|\r|\n)/g, '<br>');

			asynchronousContent = asynchronousContent.replace(/{component-id}/g, data.component_id);
			asynchronousContent = asynchronousContent.replace(/{text}/g, comment_text);
			asynchronousContent = asynchronousContent.replace(/{update-text}/g, update_text);
			asynchronousContent = asynchronousContent.replace(/{dossier-for-create}/, data.dossier_for_create);
			asynchronousContent = asynchronousContent.replace(/{dossier-for-update}/, data.dossier_for_update);			
			asynchronousContent = asynchronousContent.replace(/{dossier-for-delete}/, data.dossier_for_delete);
			
			var newContent = $.parseHTML(asynchronousContent);
			
			current.closest('.accordion-container').prepend(newContent);
		
			current.find('textarea').val('');
		
			current.closest('.accordion-container').find('.accordion-title').click();	
				
			}
		}, "json");
		
	});
	
	
	$(document).on('click','.delete-comment', function(e) {
		if (confirm("Are you sure you want to delete?")) {
			var comment_id = '#comment-' + $(this).attr('comment');
			var postdata = [];
			postdata.push(
				{name: 'dossier', value: $(this).attr('dossier')},
				{name: 'inputtypes', value: '[]'}
			);
			$.post($(this).attr('action'), postdata, function(data) {
				if (data.response === "success") {
					$(comment_id).remove();
				} else {
					alert('An error occurred while deleting this comment');
				}
			}, "json");
		}
	});


	$(document).on('click','.reply-form-link', function(e) {
		$(this).closest('.comment-row-content').find('.reply-form').slideDown();
	});

	$(document).on('click','.reply-form-cancel', function(e) {
		e.preventDefault();
		$(this).closest('.reply-form').slideUp();
	});

	$(document).on('click','.edit-form-link', function(e) {
		$(this).closest('.comment-row-content').find('.update-form').show();
		$(this).closest('.comment-row-content').find('.comment-text').hide();
	});

	$(document).on('click','.update-form-cancel', function(e) {
		e.preventDefault();
		$(this).closest('.update-form').hide();
		$(this).closest('.comment-row-content').find('.comment-text').show();
	});
	
	$(document).on('click','.comment-reload', function(e) {
		window.location.reload(true);
	});

});