<?php
/*
	PHP is a mess. This file makes all session functionallity consistant and includes helper functions
*/

require_once(dirname(__FILE__).'/../config/config.php');

class session{
	public static function start(){
		global $config;
		session_name($config['session_name']);
		session_start();
	}

	public static function destroy(){
		self::start();
		session_destroy();
	}

	public static function status(): bool{
		return session_status()===\PHP_SESSION_ACTIVE;
	}
	
	public static function regenerate_id(){
		session_regenerate_id();
	}

	public static function exists(string $key){
		if(!self::status())
			return false;
		return array_key_exists($key,$_SESSION);
	}

	public static function get(string $key){
		if(!self::status())
			throw new \exception('session not started');
		if(!array_key_exists($key,$_SESSION))
			throw new \exception("session does not contain key\"$key\"");
		return $_SESSION[$key];
	}

	public static function set(string $key,$value){
		$_SESSION[$key]=$value;
	}
};