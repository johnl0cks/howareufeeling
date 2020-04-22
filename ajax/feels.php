<?php
require_once(dirname(__FILE__).'/../library/controller.php');
require_once(dirname(__FILE__).'/../models/feels.php');
require_once(dirname(__FILE__).'/../models/zip_codes.php');

class controller extends controller_base{
	public static function submit_feel_good(){
		$ip=$_SERVER['REMOTE_ADDR'];
		feels::insert_feel_good($ip);
	}

	public static function submit_zip(){

		$ip=$_SERVER['REMOTE_ADDR'];
		$zip_code=self::param('int','zip_code');
		$symptoms=self::param('object','symptoms');


		$location=zip_codes::to_long_and_lat($zip_code);
		$geo_accuracy=-1;

		feels::insert_feel_bad($ip,$location->latitude,$location->longitude,$geo_accuracy,$location->sphere_x,$location->sphere_y,$location->sphere_z,$zip_code,$symptoms);
	}
	
	public static function submit_geolocation(){
		$ip=$_SERVER['REMOTE_ADDR'];
		$symptoms=self::param('object','symptoms');
		$latitude=self::param('number','latitude');
		$longitude=self::param('number','longitude');
		$geo_accuracy=self::param('number','accuracy');
		
		$zip=zip_codes::from_long_and_lat($longitude,$latitude);

		feels::insert_feel_bad($ip,$latitude,$longitude,$geo_accuracy,$zip->sphere_x,$zip->sphere_y,$zip->sphere_z,$zip->zip_code,$symptoms);
	}
};

controller::run_action();
