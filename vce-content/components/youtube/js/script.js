var videoPlayer = videoPlayer || { };

var ytvideo = ytvideo || { };


//Load the YouTube API asynchronously
function loadYTAPI() {

  var tag = document.createElement('script');
  tag.src = "https://www.youtube.com/iframe_api";
  var firstScriptTag = document.getElementsByTagName('script')[0];
  firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

}

  //Called by YouTube API when it's loaded
  function onYouTubeIframeAPIReady() {
	console.log("API ready");
    // find player iframes, bind to videoPlayer object 
    $('.player').each(function(index) {
      var playerID = $(this).attr('id');
      ytvideo.binder(playerID);
    });  
  }
  
$(document).ready(function() {

	ytvideo.binder = function(player) {
    var videotime = 0;
    
    var controller =  new YT.Player(player, {
        events: {
          'onReady': onPlayerReady,
          'onStateChange': onPlayerStateChange
        }
    });

    var playerid = '#' + player;
    
    //find closest vidbox to the player
    var vidbox = $(playerid).closest('.vidbox');
    var vidboxContent = $(vidbox).children('.vidbox-content');

    //capture clicks on parent vidBox
    var videoclick = false;
    $(vidbox).on('click', '.vidbox-click-control', function(e) {

    	console.log("click control");
	
		function findremotes(vidbox, counter) {
		var location = $(vidbox).find('.remote-container');
		if (location.length !== 0) {
		
			var vidboxiframe = $(vidbox).find('.player');
			var vidboxWidth = vidboxiframe.width();
			var vidboxHeight = vidboxiframe.height();
			var vidboxContent = $(vidbox).find('.vidbox-content');
			$(vidboxContent).css('width', (vidboxWidth - 80) + 'px').css('height', (vidboxHeight - 120) + 'px');

			return location;
		}
		counter++;
		if (counter < 6) {
			return findremotes($(vidbox).parent(), counter);
		}
		}
			
		var remotes = findremotes(vidbox,1);
		if ($(remotes).length) {
			if (!videoclick) {
				var remote = "";
				$(remotes).each(function(index) {
					if (!$(this).parents('.remote-ignore').length) {
						remote += $(this).html();
					}
				});
	  
				videoPlayer[player].addVidboxContent(remote);
				videoclick = true;
			}
		   
   
			videoPlayer[player].showVidbox();
			videoPlayer[player].pauseVideoPlayer();
   
		}
			
    });
    
    function onPlayerReady(event) {
      
      var curIFrame = event.target.getIframe().id;
      console.log(curIFrame + ' player ready');

      videoPlayer[curIFrame].metadataLoaded();
      
    } 
    
    var timeupdater;
    
    function onPlayerStateChange(event) {
  
      var player = event.target;
  
      curVidIframe = player.getIframe().id;
     
		//First click on player should allow it to play, so bring clickbox forward when it's first stopped or paused
		if ((event.data == 0) || (event.data==3)) {
			$(vidbox).find('.vidbox-click-control').css("z-index", 10);
			clearInterval(timeupdater);
		} else if (event.data == 1) {
			//when playing, update time every 250 ms
			function updateTime() {
				var micro = controller.getCurrentTime() * 1000;
				videoPlayer[curVidIframe].timeStampListener(micro);
				videoPlayer[curVidIframe].timeStamp = micro;
				videoPlayer[curVidIframe].percentageComplete = (controller.getCurrentTime()/controller.getDuration())*100;
				videoPlayer[curVidIframe].duration = function() {
					return (controller.getDuration() * 1000);
				}
				// console.log("ms: " + micro);
			} 
		} else {	
			clearInterval(timeupdater);
		}

		timeupdater = setInterval(updateTime, 1000);
	}

	// videoPlayer object
	videoPlayer[player] = {
		metadataLoaded: function() {
			// this is a placeholder function and can be reassigned in any
			// annotation type that needs to wait for all video data to be available
		},
		pauseVideoPlayer: function() {
			test = controller.pauseVideo();
		},
		startVideoPlayer: function() {
			test = controller.playVideo();
		},
		buffering: false,
		shuttleVideoPlayer: function(timestamp) {
			//controller.setCurrentTime((timestamp / 1000))
			controller.seekTo(timestamp / 1000, true);
		},
		timeStampListener: function(timestamp) {
        },
		timeStamp: 0,
		duration: function() {
			return (controller.getDuration() * 1000);
		},
		bufferVideo: function () {
		},
       	percentageComplete: 0,
		getVideoPlaybackState: function () {
        	return controller.getPlayerState();
		},
		getVideoPlayerTimestamp: function() {
			return this.timeStamp;
		},
		getVideoPlayerNiceTime: function() {
			function msToTime(s) {
				function addZ(n) {
					return (n < 10 ? '0' : '') + n;
				}
				var ms = s % 1000;
				s = (s - ms) / 1000;
				var secs = s % 60;
				s = (s - secs) / 60;
				var mins = s % 60;
				var hrs = (s - mins) / 60;
				return hrs + ':' + addZ(mins) + ':' + addZ(secs);
			}
			return msToTime(this.timeStamp)
		},
		showVidbox: function() {
        	$(vidboxContent).fadeIn('slow');
        	console.log("duration: " + this.duration);   
        	console.log("time: " + this.timeStamp);  
			console.log("percent: " + this.percentageComplete);  
		},
		hideVidbox: function() {
			$(vidboxContent).fadeOut('slow');
		},
		addVidboxContent: function(content) {
			$(vidboxContent).children('.vidbox-content-area').html($.parseHTML(content));
		}
    }
    
    $('.vidbox-content-close').on('click', function(e) {
			var player = $(this).closest('.vidbox').attr('player');
			videoPlayer[player].hideVidbox();
			videoPlayer[player].startVideoPlayer();
			e.stopPropagation();
		});
  }

});