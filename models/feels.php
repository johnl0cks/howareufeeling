<?php

require_once(dirname(__FILE__).'/../library/database.php');

class feels{
	private static function hash_ip(string $ip){
		return hash('sha1','ip saltttt'.$ip,true);
	}

	public static function ip_already_submitted_today($ip){
		$ip=static::hash_ip($ip);
		$found=database::select_first('select count(*) as found from feels where ip_hash=? and time-CURRENT_TIMESTAMP()<86400',$ip);
		$found=$found->found;
		return $found;
	}
};
