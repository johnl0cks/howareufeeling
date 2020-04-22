<?php

require_once(dirname(__FILE__).'/../library/database.php');
require_once(dirname(__FILE__).'/../tools/hash_ip.php');
require_once(dirname(__FILE__).'/../config/config.php');

class location{
	public static $earth_radius=6378.1;//in kilometers

	private static function longitude_and_latitude_to_sphere_coords($loc){
		//finds the 3 dimensional coorindates of the lat and long if the center of the earth is 0,0,0
		//used for finding closeness to other points
		$longitude=deg2rad($loc->longitude);
		$latitude=deg2rad($loc->latitude);
		$loc->sphere_x=cos($latitude);
		$loc->sphere_z=sin($latitude);
		$loc->sphere_y=$loc->sphere_x*sin($longitude);
		$loc->sphere_x=$loc->sphere_x*cos($longitude);
		$loc->sphere_x*=static::$earth_radius;
		$loc->sphere_y*=static::$earth_radius;
		$loc->sphere_z*=static::$earth_radius;
	}

	public static function location_id(string $country,string $state_prov,string $city,string $zipcode,string $district){
		$found=database::select_first('select location_id from locations where country=? and state_prov=? and city=? and zipcode=? and district=?',$country,$state_prov,$city,$zipcode,$district);
		if($found)
			return $found->location_id;
		database::execute('insert into locations (country,state_prov,city,zipcode,district) values(?,?,?,?,?)',$country,$state_prov,$city,$zipcode,$district);
		return database::last_insert_id();
	}
	
	public static function from_ip_cached(string $ip_hash){
		global $config;
		$found=database::select_first('select *,CURRENT_TIMESTAMP()-time as age from location_cache where ip_hash=?',$ip_hash);
		if($found && $found->age>$config['location_cache_lifetime'])
			return false;
		return $found;
	}
	
	public static function cache_ip(string $ip_hash,$loc){
		$sql='insert into location_cache (ip_hash,latitude,longitude,sphere_x,sphere_y,sphere_z,location_id) value(?,?,?,?,?,?,?)';
		$sql.='on duplicate key update time=CURRENT_TIMESTAMP(),latitude=values(latitude),longitude=values(longitude),sphere_x=values(sphere_x),sphere_y=values(sphere_y),sphere_z=values(sphere_z),location_id=values(location_id)';
		database::execute($sql,$ip_hash,$loc->latitude,$loc->longitude,$loc->sphere_x,$loc->sphere_y,$loc->sphere_z,$loc->location_id);
	}
	
	public static function from_ip(string $ip){
		$ip_hash=hash_ip($ip);
		$loc=static::from_ip_cached($ip_hash);
		if($loc)
			return $loc;
		
		$url='https://api.ipgeolocation.io/ipgeo?apiKey=0632d0ce382d40bdbace3a8ff085464c&fields=city,zipcode,latitude,longitude,country_code2,state_prov,district&ip='.urlencode($ip);

		$ch=curl_init();
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$loc=curl_exec($ch);
		curl_close($ch);
		
		if($loc===false){
			//error contacting server
		}else{
			$loc=@json_decode($loc);
			if($loc===null){
				//json decode error
			}else{
				if(isset($loc->success) && $loc->success===false){
					//over limit probably
				}else{
					$loc->country=$loc->country_code2;
					unset($loc->country_code2);
					$loc->location_id=static::location_id($loc->country,$loc->state_prov,$loc->city,$loc->zipcode,$loc->district);
					static::longitude_and_latitude_to_sphere_coords($loc);
					static::cache_ip($ip_hash,$loc);
					return $loc;
				}
			}
		}
		return false;
	}
};
