<?php
require_once(dirname(__FILE__).'/models/feels.php');
$already_submitted=feels::ip_already_submitted_today($_SERVER['REMOTE_ADDR']);
?><html>
	<head>
		<title>how are u feeling?</title>
		<link rel="stylesheet" type="text/css" href="css/index.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	</head>
	<body>
<?php if(!$already_submitted):?>
		<script>
			'use strict';

			$(function(){
				$('#panel_feel').trigger('open');
			});

			function slide_in_panel(wanted_panel_element){
				$(wanted_panel_element).show().css('left','100%');
				$('div.panel:visible').animate(
					{left:'-=100%'}
					,{
						duration: 400
						,complete: function(){
							$(this).hide();
							$(wanted_panel_element).show();
						}
					}
				);
			}
			
			function submit(data,type,complete){
				console.log(data);
				var settings={
					url:'ajax/feels.php/'+type,
					data: JSON.stringify(data),
					dataType: 'json',
					method:'POST',
					processData:false,
					complete: complete
					
				}
				setTimeout(function(){$.ajax(settings);},1000);
					
			}
		</script>
		<div class="popup" id="message_popup" style="display:none">
			<div class="popup_box" id="message_popup_box">
				<div id="message_popup_text">
				</div>
				<button>OK</button>
			</div>
			<script>
				'use strict';
				function popup(text){
					$('#message_popup_text').text(text);
					var popup=$('#message_popup');
					var popup_box=$('#message_popup_box');
					popup.show();
					popup_box.css('width','');
					var area=popup_box.width()*popup_box.height();
					popup_box.width(Math.sqrt(area));
					popup.hide().fadeIn(250);
				}
				$('#message_popup button').click(function(){
					$('#message_popup').fadeOut(250,function(){
					});
				});
			</script>
		</div>
		<div class="popup" id="waiting_popup" style="display:none">
			<div class="popup_box" id="waiting_popup_box">
				<img src="images/thumbs.svg"/>
			</div>
			<script>
				'use strict';
				function waiting_popup_show(){
					$('#waiting_popup').fadeIn(250);
				}
				function waiting_popup_hide(finished){
					$('#waiting_popup').stop();
					$('#waiting_popup').fadeOut(250,finished);
				}
			</script>
		</div>
		<div class="panel" id="panel_feel">
			<h1>how are u feeling?</h1>
			<h2>Help track disease and learn about its spread</h2>
			<div id="feels">
				<button class="feel" id="feeling_good">Good</button>
				<button class="feel" id="feeling_bad">Bad</button>
			</div>
			<script>
				'use strict';
				$('#panel_feel').on('open',function(){
					$('div.panel').hide();
					//we randomize the order of the feel buttons to reduce polling bias
					if(Math.random()<0.5)
						$('#feels').append($('#feeling_good').remove());
					$(this).show();
					
					$(window,this).resize(function(){
						var panel=$('#panel_feel:visible');
						if(panel.length){
							var area_width=$('#panel_feel').innerWidth();
							var area_height=window.innerHeight-$('#feels').offset().top;
							var button_size=area_height*0.9/2;
							button_size=Math.max(button_size,area_width*0.8/2);
							button_size=Math.min(button_size,area_height*0.9);
							$('button.feel').outerWidth(button_size).outerHeight(button_size);
						}
					});
					$(this).resize();

					$('#feeling_good').click(function(){
						waiting_popup_show();
						submit({},'submit_feel_good',function(){
							waiting_popup_hide(function(){
								$('#panel_finished').trigger('open');
							});
						});
					});
					
					$('#feeling_bad').click(function(){
						$('#panel_symptoms').trigger('open');
					});
				});

			</script>
		</div>
		<div class="panel" id="panel_symptoms">
			<h1>Click on any symptoms you show</h1>
			<h2>Don't worry we don't know (or care) who you are we just record your symptoms</h2>
			<div id="symptoms_holder" style="display: none">
			<?php
				require_once('models/symptoms.php');
				$symptoms=symptoms::get();
				foreach($symptoms as $symptom)
					echo '<button class="symptom" data-symptom_id="',htmlentities($symptom->column_name),'">',htmlentities($symptom->name),'</button>';
			?>
			</div>
			<div id="symptoms">
			</div>
			<button class="major" id="symptoms_done">Done</button>
			<script>
				'use strict';
				$('#panel_symptoms').on('open',function(){
					$(this).show();
					//we randomize the order of the symptoms to reduce polling bias
					var symptoms_element=$('#symptoms');
					var symptom_elements=$('#symptoms_holder>*');
					while(symptom_elements.length>0){
						var index=Math.floor(Math.random()*symptom_elements.length);
						var element=symptom_elements.splice(index,1);
						symptoms_element.append(element);
					}
					$(window,this).resize(function(){
						if($('#panel_symptoms:visible').length){
							var wanted_width=200;
							symptoms_element.css('column-count',Math.max(1,Math.round(symptoms_element.width()/wanted_width)));
						}
					});
					$(this).resize();
					slide_in_panel(this);
				});

				$('.symptom').click(function(){
					this.classList.toggle('selected');
				});

				$('#symptoms_done').click(function(){
					$('#panel_location').trigger('open');
				});

				function symptoms_data(){
					var data={};
					$('button.symptom.selected').each(function(){
						data[this.dataset.symptom_id]=1;
					});
					return data;
				}
			</script>
		</div>
		<div class="popup" id="zip_popup" style="display:none">
			<div class="popup_box" id="zip_popup_box" style="width: 50%">
				<div class="close">&times;</div>
				<div>Enter your ZIP Code</div>
				<input id="zip_popup_value" name="zip" type="text" inputmode="numeric" pattern="^\d{5}$" placeholder="00000" required>
				<button disabled>Done</button>
			</div>
			<script>
				'use strict';
				function zip_popup(){
					var popup=$('#zip_popup');
					var popup_box=$('#zip_popup_box');
					popup.show();
					popup_box.css('width','');
					var area=popup_box.width()*popup_box.height();
					popup_box.width(Math.sqrt(area));
					popup.hide().fadeIn(250);
				}
				
				$('#zip_popup_value').on('change keyup keydown',function(){
					console.log(this.validity.valid);
					$('#zip_popup button').prop('disabled',!this.validity.valid);
				});
				
				$('#zip_popup button').click(function(){
					$('#zip_popup').fadeOut(250);
					waiting_popup_show();
					var zip=$('#zip_popup_value').val();

					var data={};
					data.zip=zip;
					data.symptoms=symptoms_data();
					submit(data,'submit_zip',function(){
						waiting_popup_hide(function(){
							$('#panel_finished').trigger('open');
						});
					});
				});

				$('#zip_popup .close').click(function(){
					$('#zip_popup').fadeOut(250);
				});
			</script>
		</div>
		<div class="panel" id="panel_location">
			<h1>Let us know the area where you where you live</h1>
			<h2>We don't want your address just the general area</h2>
			<button class="major" id="use_geolocation">Use My Location</button>
			<button class="major" id="use_zip">Enter Zip Code</button>
			<script>
				'use strict';
				$('#panel_location').on('open',function(){
					$('#use_geolocation').hide();
					if(navigator && navigator.geolocation){
						if(navigator.permissions && navigator.permissions.query){
							navigator.permissions.query({name: 'geolocation'}).then(function(status){
								if(status.state!='denied')
									$('#use_geolocation').show();
							});
						}else
							$('#use_geolocation').show();
					}

					slide_in_panel(this);
				});

				$('#use_zip').click(function(){
					zip_popup();
				});
				
				$('#use_geolocation').click(function(){
					function success(geolocation) {
						var data={};
						data.latitude=geolocation.coords.latitude;
						data.longitude=geolocation.coords.longitude;
						data.accuracy=geolocation.coords.accuracy;
						data.symptoms=symptoms_data();
						submit(data,'submit_geolocation',function(){
							waiting_popup_hide(function(){
								$('#panel_finished').trigger('open');
							});
						});
					}				
					function error(error){
						waiting_popup_hide();
						if(error.code==1){
							popup("If you would rather not use your device location we get that. But to understand where people are sick we do need a general location for you. Please enter a zip code to continue");
							$('#use_geolocation').hide();
						}else{
							console.log(error);
							popup("There was an error getting your location. Please choose one of the other available options");
							$('#use_geolocation').hide();
						}
					}
					waiting_popup_show();
					navigator.geolocation.getCurrentPosition(success,error);
				});
			</script>
		</div>
		<div class="panel" id="panel_finished">
			<h1>Thank You</h1>
			<h2>If you'd like to check out the data we've collected hit the button below</h2>
			<a class="button" href="graphs.php">
				<svg width="100" height="100">
					<polyline points="0,80 20,20 40,70 60,30 80,60 100,40" style="fill:none;stroke:#EEE;stroke-width:3" />
					<line x1="0" y1="0" x2="0" y2="100" style="stroke:hsl(206,62%,60%);stroke-width:8" />				
					<line x1="0" y1="100" x2="100" y2="100" style="stroke:hsl(206,62%,60%);stroke-width:8" />				
				</svg>
			</a>
			<script>
				'use strict';
				$('#panel_finished').on('open',function(){
					slide_in_panel(this);
				});
			</script>
		</div>
<?php else:?>
		<script>
			'use strict';
			$(function(){
				$('#panel_already_submitted').fadeIn(250);
			});
		</script>
		<div class="panel" id="panel_already_submitted">
			<h1>It looks like you've already reported today</h1>
			<h2>We only take self reporting once per day. Please let us know how you feel tomorrow. If you'd like to check out the data we've collected hit the button below</h2>
			<a class="button" href="graphs.php">
				<svg width="100" height="100">
					<polyline points="0,80 20,20 40,70 60,30 80,60 100,40" style="fill:none;stroke:#EEE;stroke-width:3" />
					<line x1="0" y1="0" x2="0" y2="100" style="stroke:hsl(206,62%,60%);stroke-width:8" />				
					<line x1="0" y1="100" x2="100" y2="100" style="stroke:hsl(206,62%,60%);stroke-width:8" />				
				</svg>
			</a>
			<script>
				'use strict';
				$('#panel_already_submitted').on('open',function(){
					slide_in_panel(this);
				});
			</script>
		</div>
<?php endif;?>
	</body>
</html>