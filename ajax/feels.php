<?php
require_once(dirname(__FILE__).'/../library/controller.php');
require_once(dirname(__FILE__).'/../models/feels.php');
require_once(dirname(__FILE__).'/../models/location.php');

class controller extends controller_base{
	public static function submit_good(){
		$ip=$_SERVER['REMOTE_ADDR'];
		$loc=location::from_ip($ip);
		if($loc)
			feels::insert($ip,true,$loc,null);
	}
	
	public static function submit_bad(){
		$ip=$_SERVER['REMOTE_ADDR'];
		$loc=location::from_ip($ip);
		$symptoms=self::param('object','symptoms');
		if($loc)
			feels::insert($ip,false,$loc,$symptoms);
	}
	
	public static function get_graph(){
		$date_begin=self::param('string','date_begin');
		$date_end=self::param('string','date_end');
		$line_describers=self::param('array','line_describers');

		$ip=$_SERVER['REMOTE_ADDR'];
		if(!feels::ip_already_submitted_today($ip))
			return null;
		return feels::get_graph($date_begin,$date_end,$line_describers);
	}
};

controller::run_action();
