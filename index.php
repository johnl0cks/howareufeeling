<?php
require_once('models/symptoms.php');
$symptoms=symptoms::get();
?><html>
	<head>
		<title>How Are U Feeling?</title>
		<style>
			body{
				font-family: Verdana;
				font-size: 14pt;
				color: #EEE;
				padding: 1em 0;
				margin: 0;
				text-align: center;
				background-color: #454545;
				overflow-x: hidden;
			}
			
			h1{
				margin: 0.25em;
			}
			
			h2{
				font-size: 80%;
				font-weight: normal;
				font-style: italic;
				margin: 0 0 1em;
			}
			
			button{
				position: relative;
				background-color: hsl(206,62%,40%);
				padding: 0.5em;
				margin: 0;
				color: inherit;
				box-sizing: border-box;
				border: 4px solid hsl(205,62%,31%);
				cursor: pointer;
				outline: none;
				line-height: 1.2em;
				font-size: 100%;
			}
			
			button:active{
				z-index: 100;
				transform: scale(1.1);
			}

			button:disabled{
				background-color: hsl(206,31%,40%);
				border-color: hsl(205,31%,31%);
				color: #DDD;
			}
			
			input[type="text"]{
				font-size: 200%;
				width: 3.5em;
				height: 1em;
				text-align: left;
				padding: 0.75em 0.25em 0.65em;
				color: inherit;
				background-color: hsl(206,40%,40%);
				border: 4px solid hsl(205,62%,31%);
			}
			
			.popup{
				position: fixed;
				left: 0;
				top: 0;
				right: 0;
				bottom: 0;
				z-index: 1000;
				background-color: rgba(0,0,0,0.5);
			}
			
			.popup_box{
				position: relative;
				display: table;
				box-sizing: border-box;
				padding: 0.5em;
				border-width: 4px;
				max-width: 100%;
				margin: 6em auto 0 auto;
				text-align: center;
				background-color: hsl(206,62%,35%);
				border-style: solid ;
				border-color: hsl(205,62%,31%);
			}

			.popup_box>button{
				width: 100%;
				margin-top: 0.5em;
				font-size: 200%;
			}
			
			.popup_box>.close{
				position: absolute;
			    right: 0;
				top: 0;
				font-size: 150%;
				line-height: 0.7em;
				cursor: pointer;
			}
			
			div.panel{
				display: none;
				box-sizing: border-box;
				padding: 0 1em;
				width: 100%;
				position: absolute;
				left: 0%;
			}
			
			button.feeling{
				width: 40%;
				padding-bottom: 40%;
				background-color: #276FA5;
				background-image: url('images/thumbs.svg');
				background-size: 70%;
				background-repeat: no-repeat;
				background-position: 50% 33%;
				border-radius: 50%;
				box-sizing: border-box;
				font-size: 0;
				line-height: 0;
			}

			button.feeling:active{
				background-size: 77%;
				background-position: 50% 19%;
			}

			#feeling_bad{
				transform: scale(-1);
			}

			#feeling_bad:active{
				transform: scale(-1.1);
			}
			
			button.major{
				width: 100%;
				font-size: 200%;
				margin: 0 0 1em;
				padding: 0.5em;
				border-radius: 0.1em;
			}

			button.major:active{
				font-size: 250%;
				padding: calc(0.17em + 5px);
			}
			
			#symptoms{
				width: 100%;
				column-gap: 1em;
				margin-bottom: 1em;
			}
			
			button.symptom{
				display: block;
				width: 100%;
				padding: 0.5em 0.5em 0.5em 2em;
				margin: 1em 0;

				text-align: left;
				break-inside: avoid;
				position: relative;
				cursor: pointer;
				border-radius: 2px;
			}

			button.symptom::before{
				display: block;
				box-sizing: border-box;
				width: 1em;
				height: 1em;
				margin-top: -0.5em;
				position: absolute;
				left: 0.5em;
				top: 50%;
				border: solid 0.2em #DDD;
				border-radius: 50%;
				content: "";
			}

			button.symptom:active,button.symptom.selected:active{
				background-color: hsl(206,62%,35%);
				border-color: hsl(205,46%,41%);
			}
			
			button.symptom:active::before{
				background-color: #6589A3;
			}
			
			button.symptom:first-child{
				margin-top: 0;
			}

			button.symptom:last-child{
				margin-bottom: 0;
			}
			
			button.symptom.selected{
				background-color: hsl(206,62%,30%);
				border-color: hsl(205,31%,50%);
			}
			
			button.symptom.selected::before{
				background-color: #FFF;
			}
			
			#zip_popup input{
				margin-top: 0.5em;
			}
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
	</head>
	<body>
		<script>
			'use strict';

			$(function(){
				$('#panel_feeling').trigger('open');
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
			
			function submit(data,type){
				var settings={
					url:'ajax/submit_feel.php/'+type,
					data: JSON.stringify(data),
					dataType: 'json',
					method:'POST',
					processData:false,
				}
				$.ajax(settings);
			}
			
			function submit_feel_good(){
				submit({},'submit_feel_good');
			}

			function submit_zip(){
				var data={};
				data.zip=$('#zip_popup_value').val();
				data.symptoms={};
				submit(data,'submit_zip');
			}

			function submit_geolocation(geolocation){
				var data={};
				data.latitude=geolocation.latitude;
				data.longitude=geolocation.longitude;
				data.accuracy=geolocation.accuracy;
				data.symptoms={};
				submit(data,'submit_geolocation');
			}
		</script>

		<div class="popup" id="message_popup" style="display:none">
			<div class="popup_box" id="message_popup_box" style="width: 50%">
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
		<div class="panel" id="panel_feeling">
			<h1>How are U feeling?</h1>
			<h2>Help track disease and learn about its spread</h2>
			<button class="feeling" id="feeling_good">Good</button>
			<button class="feeling" id="feeling_bad">Bad</button>
			<script>
				'use strict';
				$('#panel_feeling').on('open',function(){
					$('div.panel').hide();
					$(this).show();
				});

				$('#feeling_good').click(function(){
					submit_feel_good();
				});
				
				$('#feeling_bad').click(function(){
					$('#panel_symptoms').trigger('open');
				});
			</script>
		</div>
		<div class="panel" id="panel_symptoms">
			<h1>Click on any symptoms you show</h1>
			<h2>Don't worry we don't know (or care) who you are we just record your symptoms</h2>
			<div id="symptoms_holder" style="display: none">
			<?php
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
					var symptoms_element=$('#symptoms');
					var symptom_elements=$('#symptoms_holder>*');
					while(symptom_elements.length>0){
						var index=Math.floor(Math.random()*symptom_elements.length);
						var element=symptom_elements.splice(index,1);
						symptoms_element.append(element);
					}
					$(window,this).resize(function(){
						var wanted_width=200;
						symptoms_element.css('column-count',Math.max(1,Math.round(symptoms_element.width()/wanted_width)));
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
					submit_zip();
					$('#zip_popup').fadeOut(250);
				});

				$('#zip_popup .close').click(function(){
					$('#zip_popup').fadeOut(250);
				});
			</script>
		</div>
		<div class="panel" id="panel_location">
			<h1>Let us know the area where you where you live</h1>
			<h2>We don't want your address just the general area</h2>
			<button class="major" id="use_zip">Enter Zip Code</button>
			<button class="major" id="use_geolocation">Use My Location</button>
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
					function success(position) {
						console.log(position);
					}				
					function error(error){
						console.log(error);
						if(error.code==1){
							popup("If you would rather not use your device location we get that. But to understand where people are getting sick we do need a general location for you. Please enter a zip code to continue");
							$('#use_geolocation').hide();
						}else{
							popup("There was an error getting your location. Please choose one of the other available options");
							$('#use_geolocation').hide();
						}
					}
					navigator.geolocation.getCurrentPosition(success,error);
					//$('#panel_location').hide();
					//$('#panel_symptoms').trigger('open');
				});
			</script>
		</div>
	</body>
</html>