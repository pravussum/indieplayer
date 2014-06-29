<!DOCTYPE html>
<html>
	<head>
		<link rel="stylesheet" href="style.css" media="only screen and (min-device-width: 801px)"/>
		<link rel="stylesheet" type="text/css" media="only screen and (max-device-width: 800px)" href="small-device.css" />
		<script type="text/javascript" src="jquery-1.10.2.js"></script>
		<script type="text/javascript" src="md5.js"></script>
		<script type="text/javascript" src="jquery.cookie.js"></script>
		<script type="text/javascript" src="lastfm.js"></script>
		<script type="text/javascript">
			var curDir;
			var curArtist;
			var curTitle;
			var curStartTs;
			var scrobbled = false;
			$(function() {
				console.debug("loading");
				$("#audioplayer").on("ended", function() {
					console.debug("audioplayer event: ended");
					next();
				});
				$("#audioplayer").on("emptied", function() {
					console.debug("audioplayer event: emptied");
					curArtist = undefined;
					curTitle = undefined;
					curStartTs = undefined;
					scrobbled = false;
					var old = $("#playlist li.current");
					if(old.length > 0) {
						console.debug("emptied: removeClass current");
						old.removeClass("current");
					}
                    $(".trackinfo").empty();
                    $(".trackImage").empty();
                    document.title = "";                                  
				});
				$("#audioplayer").on("timeupdate", function(){
					var curTime = $("#audioplayer").get(0).currentTime;
					var duration = $("#audioplayer").get(0).duration;
					
					// Scrobble to last.fm after half the length of the track or 4 minutes (whichever comes first)
					if( !scrobbled && sessiontoken && $(".togglescrobble").attr("scrobbling") === "on" &&  
						curArtist && curTitle && curStartTs && curTime && duration && duration > 30.0 && 
						((curTime / duration) > 0.5 || curTime > 240.0)) {
						
						console.log("Scrobbling '" + curArtist + " - " + curTitle + "'");
						
						doAuthCall("track.scrobble", 
									{ artist: curArtist, track: curTitle, timestamp: curStartTs },
									sessiontoken,
									function(updateTrackData) {
										if(updateTrackData.error) {
											$("<div class='scrobbleInfo'><b>Scrobbling failed:</b> Error" + updateTrackData.error + "(" + updateTrackData.message + ")" + "</div>").appendTo(".lastfminfopanel").fadeIn().delay(10000).fadeOut('slow');
										} else {
											$("<div class='scrobbleInfo'>Scrobbled " + curArtist + " - " + curTitle + "</div>").appendTo(".lastfminfopanel").fadeIn().delay(5000).fadeOut('slow');
										}
									}
						);
						scrobbled = true;				
					}
				});
				$(".nextButton").click(next);
				$(".prevButton").click(prev);
				$(".playButton").click(startPlaylist);
				$(".togglescrobble").click(function(){
					if($(this).attr("scrobbling") === "off") {
						$(this).attr("scrobbling", "on");
						$(this).html("Scrobbling is on");
					} else {
						$(this).attr("scrobbling", "off");
						$(this).html("Scrobbling is off");						
					}
				});
				
				// get old last.fm session token
				sessiontoken = $.cookie("indieplayer_lastfm_session_token");
				lastfmname = $.cookie("indieplayer_lastfm_name");
				
				// get last.fm token
				if(!sessiontoken) {
					doNoAuthCall("auth.gettoken", null, 
									function(data) {
										auth_token = data.token;
										var popup = window.open("http://www.last.fm/api/auth/?api_key=2984bad455d32a635e9729cd8f51ad87&token=" + auth_token);
										$(".playerPanel").append($("<button class='lastfmbutton'>Continue Last.FM authorization...</button>").click(function(){
											console.debug("starting session auth");	
											
											doNoAuthCall("auth.getSession", {token: auth_token}, 
																	function(sessionData) {
																			console.debug(JSON.stringify(sessionData));
																			if(sessionData.error) {
																				alert("Error " + sessionData.error + ": " + sessionData.message);
																				return;
																			}
																			if(!sessionData.session) {
																				alert("Can't find the session data in the response." + JSON.stringify(sessionData));
																				return;
																			}
																			lastfmname = sessionData.session.name;
																			sessiontoken = sessionData.session.key;
																			console.debug(sessiontoken);
																			console.debug(lastfmname);
																			if(sessiontoken) {
																				$.cookie("indieplayer_lastfm_session_token", sessiontoken);
																				$.cookie("indieplayer_lastfm_name", lastfmname);
																				$(".lastfmusername").html("Logged in to Last.FM as <b> " + lastfmname + "</b>");
																				if($(".togglescrobble").attr("scrobbling") === "off") {
																					$(".togglescrobble").click();
																				} 
																			}
																	},
																	function(err) {
																		alert("Failed to request Last.FM session token: " + JSON.stringify(err));
																	}
											);
										
											$(".lastfmbutton").remove();
										
										}));
									}, 
								 	function(err) {
										alert(JSON.stringify(err));
									}
					);

				} else {
					$(".lastfmusername").html("Logged in to Last.FM as <b> " + lastfmname + "</b>");
                                        if($(".togglescrobble").attr("scrobbling") === "off") {
                                                $(".togglescrobble").click();
                                        }                                         
				}				
				
				
				<?php if(isset($_GET["dir"])) 
						$dir = addcslashes($_GET["dir"],'\\');
					else
						$dir = "."; 
					echo "changeDir(\"$dir\");";
				?>
			});
			
			function play(url, title, tagurl) {
				console.debug("play");
				curArtist = undefined;
				curTitle = undefined;
				curStartTs = undefined;
				scrobbled = false;
				$("#audioplayer").attr({
					"src": url,
					"autoplay" : "autoplay"
				});
				$.getJSON(
					tagurl, 
					function(data) {
						$(".trackinfo").html("<b>" + data.artist + " - " + data.title);
                                                document.title = data.artist + " - " + data.title;
						curArtist = data.artist;
						curTitle = data.title;
						curStartTs = Math.floor(new Date().getTime() / 1000);
						// update last fm now playing
						if(sessiontoken && curArtist && curTitle && $(".togglescrobble").attr("scrobbling") === "on") {
							doAuthCall("track.updateNowPlaying", 
										{artist: curArtist, track: curTitle},
										sessiontoken,
										function(updateTrackData) {
											if(updateTrackData.error) {
												$(".lastfmusername").append("<br><b>Scrobbling failed:</b> Error " + updateTrackData.error + "(" + updateTrackData.message + ")");
											}
										}
							);						
						} else {
							console.log("No Last.FM session token or artist or title empty (ID3 tags correct?) or scrobbling disabled?");
						}
						// get artist image
						doNoAuthCall("artist.getInfo", {artist: curArtist, username: lastfmname}, 
									 function(data) {
										if(data.artist) {
											for(i=0; i<data.artist.image.length; i++) {
												if(data.artist.image[i].size === "large") {
													$(".trackImage").html("<a href='" + data.artist.url + "' target='_blank'><img class='artistImage' src='" + data.artist.image[i]["#text"] + "' /></a>").fadeIn(1200);
													return;
												}
											}											
										}
										$(".trackImage").empty();		 	
									 } 
						);
					}				
				).fail(function(err){
					alert(JSON.stringify(err));
				});
								
			}
			function next() {				
				console.debug("next");
				var old = $("#playlist li.current");
				
				var marked = $("#playlist li.next");
				var next_;
				if (marked.length > 0) {
					next_ = marked.first();
				} else {
					next_ = old.next("li").first();
				}
				
				if(next_.length > 0) {
					play(next_.attr("audiourl"), next_.attr("audiotitle"), next_.attr("tagurl"));
					$("#audioplayer").on("loadstart", function() {
						console.debug("next(): addclass current");
						next_.removeClass("next");
						next_.addClass("current");
						$("#audioplayer").off("loadstart");
					});
				} else {
					curArtist = undefined;
					curTitle = undefined;
					curStartTs = undefined;
					console.debug("next(): removeClass current for previous track");
					old.removeClass("current");
					scrobbled = false;
				}
			}
			
			function prev() {
				console.debug("prev");
				var old = $("#playlist li.current");
				if(old.length > 0) {
					var current = old.prev("li");
					if(current.length > 0) {
						play(current.attr("audiourl"), current.attr("audiotitle"), current.attr("tagurl"));
						$("#audioplayer").on("loadstart", function() {
							console.debug("prev(): addClass current");
							current.addClass("current");
							$("#audioplayer").off("loadstart");
						});
					} else {
						curArtist = undefined;
						curTitle = undefined;
						curStartTs = undefined;
						console.debug("prev(): removeClass current for previous track");
						old.removeClass("current");
						scrobbled = false;						
					}
				}
			}			
			
			function startPlaylist() {
				console.debug("startPlaylist");
				var first = $("#playlist li").eq(0);
				if(first.length > 0) {					
					play(first.attr("audiourl"), first.attr("audiotitle"), first.attr("tagurl"));
					$("#audioplayer").on("loadstart", function() {
						console.debug("startPlaylist(): addclass current");
						first.addClass("current");
						$("#audioplayer").off("loadstart");
					});					
				}
			}
			
			function changeDir(newDir) {
				console.debug("changeDir");
				console.debug("changedir " + newDir);
				$.getJSON(	"getdirinfo.php?dir=" + encodeURIComponent(newDir),
							function(data) {
								curDir = newDir;
								$("#playlist").empty();
								data.entries.forEach(function(entry) {
									if(entry.type === "audio") {
										var li = $("<li audiourl=\"getaudio.php?file=" + encodeURIComponent(entry.fullpath) + "\" tagurl=\"gettaginfo.php?file=" + encodeURIComponent(entry.fullpath) + "\" audiotitle=\"" + entry.file + "\">" + entry.file + "</li>");
										$("#playlist").append(li);	
									} else if (entry.type === "dir") {
										var dirlink = $('<div class="dirlink directory" dir="' + encodeURIComponent(entry.fullpath) + '">' + entry.file + '</div>');
										$("#playlist").append(dirlink);										
									} else {
										$("#playlist").append(entry.file + "<br>");
									}
									
								});
								$(".headlink").html(data.headLink);
								
								$(".dirlink").click(function(){
									changeDir($(this).attr("dir"));
								});
								
								$("#playlist li").on("click", function(e) {
									var curItem = $(this);
									if(e.ctrlKey) {
										$(this).addClass("next");										
									} else {										
										play($(this).attr("audiourl"), $(this).attr("audiotitle"), $(this).attr("tagurl"));
										$("#audioplayer").on("loadstart", function() {
											console.debug("li click(): addClass current");
											curItem.addClass("current");
											$("#audioplayer").off("loadstart");
										});										

									}
								});
																
							}								
				).fail(function(err){
					alert(JSON.stringify(err));
				});
			}
		</script>
	</head>
	<body>
	<div class='headerPanel'><h2 class="headlink"></h2>
		<div class="lastfminfopanel">
			<div class="lastfmusername"></div>
			<button class="togglescrobble stylishbutton" scrobbling="off">Scrobbling is off</button>
		</div>
		<div class="trackImage"></div>
		<div class="trackPanel">
			<div class="trackinfo">&nbsp;</div>
			<button class="prevButton stylishbutton">Prev</button>
			<button class="playButton stylishbutton">Play</button>
			<button class="nextButton stylishbutton">Next</button>
			<div class="playerPanel">
				<audio id='audioplayer' controls src=''>
					HTML5 audio not supported
				</audio>
			</div>
		</div>
	</div>
	<div class='contentPanel'>

    <ul id='playlist'></ul>
</div>
</body>
</html>
