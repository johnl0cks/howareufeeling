<?php

require_once(dirname(__FILE__).'/../library/database.php');

class symptoms{
	public static function get(){
		$sql="select * from symptoms";
		$rows=database::select_all($sql);
		return $rows;
	}
};