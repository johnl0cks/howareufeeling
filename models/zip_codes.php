<?php

require_once(dirname(__FILE__).'/../library/database.php');

class zip_codes{
	public static function from_long_and_lat($long,$lat){
		$long=deg2rad($long);
		$lat=deg2rad($lat);
		$x=cos($lat);
		$z=sin($lat);
		$y=$x*sin($long);
		$x=$x*cos($long);
		$row=database::select_first('select zip_code, (power(point_x-?,2)+power(point_y-?,2)+power(point_z-?,2)) as distance from zip_codes order by distance limit 1',$x,$y,$z);
		return $row->zip_code;
	}
};
