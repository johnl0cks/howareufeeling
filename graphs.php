<?php
require_once(dirname(__FILE__).'/models/feels.php');
$location_id=feels::submitted_location_id($_SERVER['REMOTE_ADDR']);
if($location_id===null){
	header('location: index.php');
	exit;
}

require_once(dirname(__FILE__).'/models/location.php');
require_once(dirname(__FILE__).'/models/symptoms.php');

$location=location::location_from_id($location_id);
$date_begin=date('Y-m-d',strtotime('-90 day'));
$date_end=date('Y-m-d');
if($location->city){
	$search_data=[
		(object)[
			'feels'=>'%bad'
			,'symptoms'=>null
			,'locations'=>(object)['countries'=>[$location->country],'state_provs'=>[$location->state_prov]]
		]
	];
	$default_graph=feels::get_graph($date_begin,$date_end,$search_data);
}else{
	$default_graph=feels::get_graph($date_begin,$date_end,
		[
			(object)[
				'feels'=>'%bad'
				,'symptoms'=>null
				,'locations'=>(object)['countries'=>[$location->country]]
			]
		]
	);
}
?><html>
	<head>
		<title>who else is sick?</title>
		<link rel="stylesheet" type="text/css" href="css/graphs.css?<?php echo time();?>">
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
		<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
		<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	</head>
	<body>
		<h1>who else is sick?</h1>
		<h2>see how people are feeling around the world</h2>
		<svg id="graph" viewBox="0 0 200 100">
			<polyline points="10,0 10,95 200,95" stroke="hsl(206,62%,60%)" stroke-width="1" fill="transparent"/>				
		</svg>
		<script>
			'use strict';
			var default_graph_data=<?php echo json_encode($default_graph);?>;
			console.log(default_graph_data);
			
			function build_graph_horzontal_scale(data){
				var date=new Date(data.date_begin);
				var points=[];
				for(var i=0;i<data.length_in_days;++i){
					var date_key=date.toISOString().slice(0,10);
					var percent_through=i/(data.length_in_days-1);
					var x=180*percent_through+10;
					var x_changed=(Math.floor(8*percent_through)!=Math.floor(8*(i-1)/(data.length_in_days-1)));
					if(i>0 && x_changed){
						var polyline=document.createElementNS('http://www.w3.org/2000/svg','polyline');
						polyline.setAttribute('stroke','#777');
						polyline.setAttribute('stroke-width','1px');
						polyline.setAttribute('fill','none');
						polyline.setAttribute('points',x+",0 "+x+",97");
						polyline.classList.add('metric_line');
						$('#graph').append(polyline);
					}
					if(percent_through==0 || percent_through==1 || x_changed){
						var text=document.createElementNS('http://www.w3.org/2000/svg','text');
						text.setAttribute('x',x);
						text.setAttribute('y',100);
						text.setAttribute('fill','#EEE');
						text.setAttribute('font-size','2pt');
						text.setAttribute('text-anchor','middle');
						text.classList.add('metric_label');
						text.innerHTML=date_key;
						$('#graph').append(text);
					}
					date.setDate(date.getDate()+1);
				}
			}
			
			function build_graph_vertical_scale(data){
				var scale=1;
				if(data.min<1 && data.max<=1){
					scale=100;
				}
				for(var i=0;i<=8;++i){
					var v=(data.max-data.min)*i/8+data.min;
					v=Math.floor(v*scale)/scale;
					var adjusted_i=(v-data.min)*8/(data.max-data.min);
					var y=95*(1-adjusted_i/8);
					var text=document.createElementNS('http://www.w3.org/2000/svg','text');
					text.setAttribute('x',9);
					text.setAttribute('y',y+1.25);
					text.setAttribute('fill','#EEE');
					text.setAttribute('font-size','2pt');
					text.setAttribute('text-anchor','end');
					text.classList.add('metric_label');
					text.innerHTML=v;
					$('#graph').append(text);

					if(i>0){
						var polyline=document.createElementNS('http://www.w3.org/2000/svg','polyline');
						polyline.setAttribute('stroke','#999');
						polyline.setAttribute('stroke-width','1px');
						polyline.setAttribute('fill','none');
						polyline.setAttribute('points',"10,"+y+" 200,"+y);
						polyline.classList.add('metric_line');
						$('#graph').append(polyline);
					}
				}
			}
			
			function graph_line_color(line_index,line_count){
				return 'hsl('+((200+360*line_index/line_count)%360)+',100%,80%)';
			}
			function build_graph_line(data,line_index){
				var data_points=data.lines[line_index];
				var date=new Date(data.date_begin);
				var points=[];
				for(var i=0;i<data.length_in_days;++i){
					var date_key=date.toISOString().slice(0,10);
					var percent_through=i/(data.length_in_days-1);
					var x=180*percent_through+10;
					if(data_points[date_key]){
						var count=data_points[date_key];
						var y=95*(1-(count-data.min)/(data.max-data.min));
						points.push(x+","+y);
					}else{
						points.push(x+",95");
					}
					date.setDate(date.getDate()+1);
					
				}
				var polyline=document.createElementNS('http://www.w3.org/2000/svg','polyline');
				polyline.setAttribute('stroke',graph_line_color(line_index,data.lines.length));
				polyline.setAttribute('stroke-width','1px');
				polyline.setAttribute('fill','none');
				polyline.setAttribute('points',points.join(' '));
				polyline.classList.add('graph_line');
				$('#graph').append(polyline);
			}
			
			function build_graph(data){
				$('#graph .metric_label').remove();
				$('#graph .metric_line').remove();
				$('#graph .graph_line').remove();
				build_graph_horzontal_scale(data);
				build_graph_vertical_scale(data);
				for(var i=0;i<data.lines.length;++i)
					build_graph_line(data,i);
			}

			$(function(){
				build_graph(default_graph_data);
			});
		</script>
<?php if($_GET['fake']??false):?>
	<div style="color: white;text-align: center;font-size: 150%;">You are in fake data mode!</div>
<?php endif;?>
		<div>
			Date Range <input type="text" class="datepicker" id="date_begin" value="<?php echo htmlentities($date_begin);?>"/>
			to <input type="text" class="datepicker" id="date_end" value="<?php echo htmlentities($date_end);?>"/>
			<button id="update_graph">Update Graph</button>
		</div>

		<div id="line_describers">
			<div class="line_describer">
				<button class="close">×</button>
				<div>
					Color <span class="line_color"></span>
				</div>
				<div>
					Region Type <select class="region_type">
						<option value="world">World Wide</option>
						<option value="country">Country</option>
						<option value="country_and_state_prov" selected>Country and State(or Province)</option>
						<option value="country_state_prov_and_city">Country,State(or Province), and City</option>
						<option value="country_state_prov_and_zipcode">Country,State(or Province), and Zipcode</option>
					</select>
					<table class="region_specifics">
						<tr>
							<th class="country">
								Country
							</th>
							<th class="state_prov">
								State or Province
							</th>
							<th class="city">
								City
							</th>
							<th class="zipcode">
								Zipcode
							</th>
						</tr>
						<tr>
							<td class="country">
								<select class="country">
									<option value="undecided">Choose</option>
								<?php 
									$countries=location::countries();
									foreach($countries as $value){
										if($value===$location->country){
											$value=htmlentities($value);
											echo "<option value=\"$value\" selected>$value</option>";
										}else{
											$value=htmlentities($value);
											echo "<option value=\"$value\">$value</option>";
										}
									}
								?>
								</select>
							</td>
							<td class="state_prov">
								<select class="state_prov" data-synced_with="<?php echo htmlentities($location->country);?>">
									<option value="undecided">Choose</option>
								<?php
									$state_provs=location::state_provs($location->country);
									foreach($state_provs as $value){
										if($value===$location->state_prov){
											$value=htmlentities($value);
											echo "<option value=\"$value\" selected>$value</option>";
										}else{
											$value=htmlentities($value);
											echo "<option value=\"$value\">$value</option>";
										}
									}
								?>
								</select>
							</td>
							<td class="city">
								<select class="city">
								</select>
							</td>
							<td class="zipcode">
								<select class="zipcode">
								</select>
							</td>
						</tr>
					</table>
				</div>
				<div class="report_specifics">
					Report Type
					<select class="report_type">
						<option value="report_percent_good">Percent feeling good</option>
						<option value="report_percent_bad" selected>Percent feeling bad</option>
						<option value="report_all">All Reports</option>
						<option value="report_good">All feeling good reports</option>
						<option value="report_bad">All feeling bad reports</option>
						<option value="report_symptoms">Specific Symptoms</option>
					</select>
					<div class="symptoms" style="display: none">
					<?php
						$symptoms=symptoms::$symptoms;
						usort($symptoms,function($a,$b){return $a->name>$b->name;});
						foreach($symptoms as $symptom)
							echo '<button class="symptom" data-symptom_id="',htmlentities($symptom->column_name),'">',htmlentities($symptom->name),'</button>';
					?>
					</div>
				</div>
			</div>
		</div>
		<button id="new_line">&#65291;</button>
		
		<script>
			'use strict';
			var default_country=<?php echo json_encode($location->country);?>;
			var default_state_prov=<?php echo json_encode($location->state_prov);?>;
			var default_city=<?php echo json_encode($location->city);?>;
			var default_zipcode=<?php echo json_encode($location->zipcode);?>;
			
			$(function(){
				$('.datepicker').datepicker({dateFormat: 'yy-mm-dd' });
				$('.city').hide();
				$('.zipcode').hide();
				$('.line_color').each(function(index){
					this.style.backgroundColor=graph_line_color(index,$('.line_color').length);
				});
			});
			
			$('#new_line').click(function(){
				var new_line=$('#line_describers>.line_describer:last-child').clone(true);
				$('#line_describers').append(new_line);
				$('.line_color').each(function(index){
					this.style.backgroundColor=graph_line_color(index,$('.line_color').length);
				});
			});
			
			$('.line_describer .close').click(function(){
				$(this).closest('.line_describer').remove();
			});
			
			$('.region_type').change(function(){
				var line_describer=$(this).closest('.line_describer');
				if(this.value=='world'){
					line_describer.find('.region_specifics').hide();
				}else{
					line_describer.find('.region_specifics').show();
					line_describer.find('.country').val('undecided').change();
				}
			});
			
			function fetch_location_options(type,data,success){
				function proccess_options(options){
					console.log(options);
					for(var i=0;i<options.length;++i)
						options[i]='<option value="'+options[i]+'">'+options[i]+'</option>';
					var html='<option value="undecided">Choose</option>'+options.join();
					success(html);
				}
			
				var settings={
					url:'ajax/location.php/'+type+'<?php if($_GET["fake"]??false) echo "?fake=1";?>',
					data: JSON.stringify(data),
					dataType: 'json',
					method:'POST',
					processData:false,
					success: proccess_options
				}
				$.ajax(settings);
			}
			
			$('select.country').change(function(){
				var line_describer=$(this).closest('.line_describer');
				var region_type=line_describer.find('.region_type').val();
				var country=this.value;
				if(region_type=='country' || country=='undecided'){
					line_describer.find('.state_prov').hide();
					line_describer.find('.city').hide();
					line_describer.find('.zipcode').hide();
				}else{
					var select=line_describer.find('select.state_prov');
					if(select.data('synced_with')==country){
						line_describer.find('.state_prov').show();
						//if(select.find('option[value="'+default_state_prov+'"]'))
						//	select.val(default_state_prov);
						//else
							select.val('undecided')
					}else{
						line_describer.find('select').prop('disable',true);
						fetch_location_options('state_provs',{country:country},function(options){
							line_describer.find('select').prop('disable',false);
							select.data('synced_with',country);
							select.html(options);
							line_describer.find('.state_prov').show();
						});
					}
				}
			});	
			
			$('select.state_prov').change(function(){
				var line_describer=$(this).closest('.line_describer');
				var region_type=line_describer.find('.region_type').val();
				var country=$(this).data('synced_with');
				var state_prov=this.value;
				if(region_type=='country_and_state_prov' || state_prov=='undecided'){
					line_describer.find('.city').hide();
					line_describer.find('.zipcode').hide();
				}else if(region_type=='country_state_prov_and_city'){
					var select=line_describer.find('select.city');
					if(select.data('synced_with')==country+' '+state_prov){
						line_describer.find('.city').show();
						//if(select.find('option[value="'+default_city+'"]'))
						//	select.val(default_city);
						//else
							select.val('undecided')
					}else{
						line_describer.find('select').prop('disable',true);
						fetch_location_options('cities',{country:country,state_prov:state_prov},function(options){
							line_describer.find('select').prop('disable',false);
							select.data('synced_with',country+' '+state_prov);
							select.html(options);
							line_describer.find('.city').show();
						});
					}
				}else if(region_type=='country_state_prov_and_zipcode'){
					var select=line_describer.find('select.zipcode');
					if(select.data('synced_with')==country+' '+state_prov){
						line_describer.find('.zipcode').show();
						//if(select.find('option[value="'+default_zipcode+'"]'))
						//	select.val(default_zipcode);
						//else
							select.val('undecided')
					}else{
						line_describer.find('select').prop('disable',true);
						fetch_location_options('zipcodes',{country:country,state_prov:state_prov},function(options){
							line_describer.find('select').prop('disable',false);
							select.data('synced_with',country+' '+state_prov);
							select.html(options);
							line_describer.find('.zipcode').show();
						});
					}
				}
			});

			$('select.report_type').change(function(){
				var line_describer=$(this).closest('.line_describer');
				if(this.value=='report_symptoms'){
					line_describer.find('.symptoms').trigger('show');
				}else{
					line_describer.find('.symptoms').hide();
				}
			});
			
			$('#line_describers').on('show','.symptoms',function(){
				$(this).show();
				$(this).resize();
			});
			
			$(window,'.symptoms').resize(function(){
				var symptoms_elements=$('.symptoms:visible');
				if(symptoms_elements.length){
					var wanted_width=200;
					var symptoms_element=$(symptoms_elements[0]);
					symptoms_element.css('column-count',Math.max(1,Math.round(symptoms_element.width()/wanted_width)));
				}
			});
			
			$('.symptom').click(function(){
				this.classList.toggle('selected');
			});
			
			$('#update_graph').click(function(){
				$('input,button,select').prop('disable',true);
				var data={};
				data.date_begin=$('#date_begin').val();
				data.date_end=$('#date_end').val();
				data.line_describers=[];
				$('.line_describer').each(function(){
					var line_describer={}
					var region_type=$(this).find('select.region_type').val();
					if(region_type!='world'){
						line_describer.locations={};
						line_describer.locations.countries=[$(this).find('select.country').val()];
						if(region_type.indexOf('state_prov')>-1)
							line_describer.locations.state_provs=[$(this).find('select.state_prov').val()]
						if(region_type.indexOf('city')>-1)
							line_describer.locations.cities=[$(this).find('select.city').val()]
						if(region_type.indexOf('zipcode')>-1)
							line_describer.locations.zipcodes=[$(this).find('select.zipcode').val()]
					}
					
					var report_type=$(this).find('select.report_type').val();
					if(report_type=='report_percent_good'){
						line_describer.feels='%good';
					}else if(report_type=='report_percent_bad'){
						line_describer.feels='%bad';
					}else if(report_type=='report_all'){
						line_describer.feels=[false,true];
					}else if(report_type=='report_good'){
						line_describer.feels=[true];
					}else if(report_type=='report_bad'){
						line_describer.feels=[false];
					}else if(report_type=='report_symptoms'){
						line_describer.feels=[false];
						line_describer.symptoms=[];
						$(this).find('.symptom.selected').each(function(){
							line_describer.symptoms.push(this.dataset.symptom_id);
						});
					}
					data.line_describers.push(line_describer);
				});
				console.log(data);
				function success(graph_data){
					build_graph(graph_data);
					$('input,button,select').prop('disable',false);
					
				}
				var settings={
					url:'ajax/feels.php/get_graph<?php if($_GET["fake"]??false) echo "?fake=1";?>',
					data: JSON.stringify(data),
					dataType: 'json',
					method:'POST',
					processData:false,
					success: success
				}
				$.ajax(settings);
				
			});
		</script>

	</body>
</html>
