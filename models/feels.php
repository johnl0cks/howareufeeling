<?php

require_once(dirname(__FILE__).'/../library/database.php');
require_once(dirname(__FILE__).'/symptoms.php');

class feels{
	private static function hash_ip(string $ip){
		return hash('sha1','ip saltttt'.$ip,true);
	}

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
		$ip=static::hash_ip($ip);
		$found=database::select_first('select count(*) as found from feels where ip_hash=?',$ip);
		$found=$found->found;
		return $found;
	}
	
	public static function insert_feel_good(string $ip){
		$ip=static::hash_ip($ip);
		$found=database::select_first('select count(*) as found from feels where ip_hash=?',$ip);
		if(!$found->found){
			$sql='insert into feels set feel=1, ip_hash=?, time=CURRENT_TIMESTAMP(), latitude=0, longitude=0, geo_accuracy=0, sphere_x=0, sphere_y=0, sphere_z=0, zip=0';
			database::execute($sql,$ip);
		}
	}
	
	public static function insert_feel_bad(string $ip,float $latitude,float $longitude,float $geo_accuracy,float $sphere_x,float $sphere_y,float $sphere_z,int $zip,stdclass $symptoms){
		$ip=static::hash_ip($ip);
		$found=database::select_first('select count(*) as found from feels where ip_hash=?',$ip);
		if(!$found->found){
			$sql='insert into feels set feel=0, ip_hash=?, time=CURRENT_TIMESTAMP(), latitude=?, longitude=?, geo_accuracy=?, sphere_x=?, sphere_y=?, sphere_z=?, zip=?';
			$sql.=static::symptoms_sql($symptoms);
			echo $sql,"\n";
			database::execute($sql,$ip,$latitude,$longitude,$geo_accuracy,$sphere_x,$sphere_y,$sphere_z,$zip);
		}
	}
	
};
