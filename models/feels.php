<?php

require_once(dirname(__FILE__).'/../library/database.php');
require_once(dirname(__FILE__).'/symptoms.php');
require_once(dirname(__FILE__).'/../tools/hash_ip.php');

class feels{
	private static function symptoms_set_sql(stdclass $symptoms){
		$sql=[''];
		foreach($symptoms as $symptom=>$dummy){
			if(isset(symptoms::$symptom_map[$symptom]))
				$sql[]="symptom_$symptom=1";
		}
		$sql=implode(', ',$sql);
		return $sql;
	}
	
	public static function ip_already_submitted_today($ip,$already_hashed=false){
		if(!$already_hashed)
			$ip=hash_ip($ip);
		//$found=database::select_first('select count(*) as found from feels where ip_hash=?',$ip);
		$found=database::select_first('select count(*) as found from feels where ip_hash=? and DATE(time)=CURDATE()',$ip);
		$found=$found->found;
		return $found;
	}
	
	public static function submitted_location_id($ip,$already_hashed=false){
		if(!$already_hashed)
			$ip=hash_ip($ip);
		$found=database::select_first('select location_id from feels where ip_hash=? order by time desc limit 1',$ip);
		if($found)
			return $found->location_id;
		return null;
	}
	
	public static function insert(string $ip,bool $feel,stdclass $loc,$symptoms,string $time=null){
		$ip=hash_ip($ip);
		if(!static::ip_already_submitted_today($ip,true)){
			if($time){
				$sql='insert into feels set ip_hash=?, time=?, feel=?, latitude=?, longitude=?, sphere_x=?, sphere_y=?, sphere_z=?, location_id=?';
				if($symptoms)
					$sql.=static::symptoms_set_sql($symptoms);
				database::execute($sql,$ip,$time,$feel,$loc->latitude,$loc->longitude,$loc->sphere_x,$loc->sphere_y,$loc->sphere_z,$loc->location_id);
			}else{
				$sql='insert into feels set ip_hash=?, feel=?, latitude=?, longitude=?, sphere_x=?, sphere_y=?, sphere_z=?, location_id=?';
				if($symptoms)
					$sql.=static::symptoms_set_sql($symptoms);
				database::execute($sql,$ip,$feel,$loc->latitude,$loc->longitude,$loc->sphere_x,$loc->sphere_y,$loc->sphere_z,$loc->location_id);
			}
		}
	}

	private static function get_graph_line(&$min,&$max,string $date_begin,string $date_end,$feels,$symptoms,$locations){
		if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$date_begin) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$date_end)){
			$select=[];
			$select[]='date(time) as `date`';
			$select[]='count(*) as total_count';
			$where=[];
			$where[]='date(time)>=?';
			$where[]='date(time)<=?';

			if(is_array($feels) && count($feels)==1){
				if($feels[0])
					$where[]='feel=1';
				else
					$where[]='feel=0';
			}else if($feels=='%good'){
				$select[]='sum(if(feel=1,1,0)) as wanted_count';
			}else if($feels=='%bad'){
				$select[]='sum(if(feel=0,1,0)) as wanted_count';
			}
			
			if(is_array($symptoms)){
				if(empty($symptoms))
					$where[]='0=1';//this is dumb but if you specify wanting symptoms and don't give any you should get no results
				foreach($symptoms as $symptom){
					if(symptoms::$symptom_map[$symptom]??false)
						$where[]="symptom_$symptom=1";
				}
			}
			
			$join_locations=false;
			if(is_object($locations)){
				if($locations->countries??false){
					$join_locations=true;
					$values=[];
					foreach($locations->countries as $value)
						$values[]=database::quote($value);
					$where[]='locations.country in ('.implode(',',$values).')';
				}
				if($locations->state_provs??false){
					$join_locations=true;
					$values=[];
					foreach($locations->state_provs as $value)
						$values[]=database::quote($value);
					$where[]='locations.state_prov in ('.implode(',',$values).')';
				}
				if($locations->cities??false){
					$join_locations=true;
					$values=[];
					foreach($locations->cities as $value)
						$values[]=database::quote($value);
					$where[]='locations.city in ('.implode(',',$values).')';
				}
				if($locations->zipcodes??false){
					$join_locations=true;
					$values=[];
					foreach($locations->zipcodes as $value)
						$values[]=database::quote($value);
					$where[]='locations.zipcode in ('.implode(',',$values).')';
				}
				if($locations->districts??false){
					$join_locations=true;
					$values=[];
					foreach($locations->districts as $value)
						$values[]=database::quote($value);
					$where[]='locations.zipcode in ('.implode(',',$values).')';
				}
			}
			
			$sql='select '.implode(',',$select).' from feels';
			if($join_locations)
				$sql.=' join locations on feels.location_id=locations.location_id';

			$where=' where '.implode(' and ',$where);
			$sql.=$where;

			$sql.=' group by `date` order by `date`';
			//echo $sql,'<br/>';
			
			$points=[];
			$rows=database::select_all($sql,$date_begin,$date_end);
			if(empty($rows)){
				$min=0;
				$max=max($max,1);
			}else{
				if($rows[0]->wanted_count??false){
					$this_min=0;
					$this_max=0;
					foreach($rows as $row){
						if($row->total_count>0)
							$percent=$row->wanted_count/$row->total_count;
						else
							$percent=0;
						$points[$row->date]=$percent;
						if($min>$percent)
							$min=$percent;
						if($max<$percent)
							$max=$percent;
					}
					$this_min=floor($this_min/0.25)*0.25;
					$this_max=ceil($this_max/0.25)*0.25;
					$min=min($min,$this_min);
					$max=max($max,$this_max);
				}else{
					foreach($rows as $row){
						$count=(int)$row->total_count;
						$points[$row->date]=$count;
						if($min>$count)
							$min=$count;
						if($max<$count)
							$max=$count;
					}
				}
			}
			return $points;
		}
		return [];
	}

	public static function get_graph(string $date_begin,string $date_end,array $lines_describers){
		if(preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$date_begin) && preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',$date_end)){
			$todays_date=date('Y-m-d');
			if($date_end>$todays_date)
				$date_end=$todays_date;
			
			$data=new stdclass();
			$data->date_begin=$date_begin;
			$data->date_end=$date_end;
			$data->length_in_days=date_diff(new DateTime($date_end),new DateTime($date_begin))->days+1;
			$data->min=10000000000;
			$data->max=0;
			$data->lines=[];
			foreach($lines_describers as $lines_describer)
				$data->lines[]=static::get_graph_line($data->min,$data->max,$date_begin,$date_end,$lines_describer->feels??null,$lines_describer->symptoms??null,$lines_describer->locations??null);

			foreach($data->lines as $line){
				if(count($line)!=$data->length_in_days)
					$data->min=0;
			}
			return $data;
		}
		return null;
	}
};
