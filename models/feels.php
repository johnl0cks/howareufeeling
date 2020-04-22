<?php

require_once(dirname(__FILE__).'/../library/database.php');
require_once(dirname(__FILE__).'/symptoms.php');
require_once(dirname(__FILE__).'/../tools/hash_ip.php');

class feels{
	private static function symptoms_sql(stdclass $symptoms){
		$sql=[''];
		foreach($symptoms as $symptom=>$dummy){
			if(isset(symptoms::$symptom_map[$symptom]))
				$sql[]="symptom_$symptom=1";
		}
		$sql=implode(', ',$sql);
		return $sql;
	}
	
	public static function ip_already_submitted_today($ip){
		$ip=hash_ip($ip);
		//$found=database::select_first('select count(*) as found from feels where ip_hash=?',$ip);
		$found=database::select_first('select count(*) as found from feels where ip_hash=? and DATE(time)=CURDATE()',$ip);
		$found=$found->found;
		return $found;
	}
	
	public static function insert(string $ip,bool $feel,stdclass $loc,$symptoms){
		$ip=hash_ip($ip);
		$found=database::select_first('select count(*) as found from feels where ip_hash=?',$ip);
		if(!$found->found){
			$sql='insert into feels set ip_hash=?, feel=?, latitude=?, longitude=?, sphere_x=?, sphere_y=?, sphere_z=?, location_id=?';
			if($symptoms)
				$sql.=static::symptoms_sql($symptoms);
			database::execute($sql,$ip,$feel,$loc->latitude,$loc->longitude,$loc->sphere_x,$loc->sphere_y,$loc->sphere_z,$loc->location_id);
		}
	}
};
