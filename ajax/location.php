<?php
require_once(dirname(__FILE__).'/../library/controller.php');
require_once(dirname(__FILE__).'/../models/location.php');

class controller extends controller_base{
	public static function state_provs(){
		$country=self::param('string','country');
		return location::state_provs($country);
	}

	public static function cities(){
		$country=self::param('string','country');
		$state_prov=self::param('string','state_prov');
		return location::cities($country,$state_prov);
	}
	
	public static function zipcodes(){
		$country=self::param('string','country');
		$state_prov=self::param('string','state_prov');
		return location::zipcodes($country,$state_prov);
	}
	
	public static function submit_bad(){
		$ip=$_SERVER['REMOTE_ADDR'];
		$loc=location::from_ip($ip);
		$symptoms=self::param('object','symptoms');
		if($loc)
			feels::insert($ip,false,$loc,$symptoms);
	}
	
	public static function get_graph(){
		$ip=$_SERVER['REMOTE_ADDR'];
		if(!feels::ip_already_submitted_today($ip))
			return null;
		return feels::get_graph($date_begin,$date_end,$feels,$symptoms,$locations);
	}
};

controller::run_action();
