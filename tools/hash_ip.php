<?php 
function hash_ip(string $ip){
	return hash('sha1','ip saltttt'.$ip,true);
}
