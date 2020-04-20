<?php
require_once(dirname(__FILE__).'/../library/controller.php');
//require_once(dirname(__FILE__).'/../models/accounts.php');

class controller extends controller_base{
	public static function submit_feel_good(){
		$ip_hash=hash('sha1','ip saltttt'.$_SERVER['REMOTE_ADDR'],true);
		
		echo $ip_hash,"\n";
	}

	public static function submit_zip(){
		$ip_hash=hash('sha1','ip saltttt'.$_SERVER['REMOTE_ADDR'],true);
		$symptoms=self::param('object','symptoms');
		$zip=self::param('string','zip');
		
		echo $ip_hash,"\n";
		echo $zip,"\n";
		echo $feeling,"\n";
		print_r($symptoms);
	}
	
	public static function submit_geolocation(){
		$ip_hash=hash('sha1','ip saltttt'.$_SERVER['REMOTE_ADDR'],true);
		$symptoms=self::param('object','symptoms');
		$geo_latitude=self::param('num','latitude');
		$geo_longitude=self::param('num','longitude');
		$geo_accuracy=self::param('num','accuracy');
		
		echo $ip_hash,"\n";
		echo $feeling,"\n";
		print_r($symptoms);
	}
};

controller::run_action();
