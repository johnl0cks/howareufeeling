<?php
/*
	This file should be outside of htdocs folder but for demo purposes I want it visible
*/
	
$config=[
	'database_host'=>'localhost'
	,'database_name'=>'lockwop2_howareufeeling'
	,'database_user'=>'lockwop2_hauf'
	,'database_password'=>''
	
	,'session_name'=>'howareufeeling_session'
	
	,'location_cache_lifetime'=>(8*24*60*60) //8 days
];

//this is kinda hacky but it allows me to switch any page anywhere over to the fake data by adding the flag to the url
if($_GET['fake']??false)
	$config['database_name']='lockwop2_howareufeeling_fake';
