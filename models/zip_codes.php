<?php

require_once(dirname(__FILE__).'/../library/database.php');

class zip_codes{
	public static function from_long_and_lat(float $long,float $lat){
		$long=deg2rad($long);
		$lat=deg2rad($lat);
		$x=cos($lat);
		$z=sin($lat);
		$y=$x*sin($long);
		$x=$x*cos($long);
		$row=database::select_first('select zip_code, (power(sphere_x-?,2)+power(sphere_y-?,2)+power(sphere_z-?,2)) as distance from zip_codes order by distance limit 1',$x,$y,$z);
		$row->sphere_x=$x;
		$row->sphere_y=$y;
		$row->sphere_z=$z;
		return $row;
	}

	public static function to_long_and_lat(int $zip_code){
		$row=database::select_first('select longitude, latitude, sphere_x, sphere_y, sphere_z from zip_codes where zip_code=?',$zip_code);
		return $row;
	}
};
