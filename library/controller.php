<?php

class controller_base{
	private static $parameters=null;
	
	public static function run_action(){
		$action=explode('/',$_SERVER['PATH_INFO']);
		if(count($action)==2){
			$action=$action[1];
			$return=static::$action();
			if(!headers_sent())
				echo json_encode($return);
		}
	}

	public static function parameters(){
		if(self::$parameters===null){
			$raw_post=file_get_contents('php://input');
			if($raw_post)
				self::$parameters=json_decode($raw_post);
			else
				self::$parameters=new stdClass();
			if(self::$parameters instanceof stdClass){
				foreach($_GET as $key=>$value){
					if(!property_exists(self::$parameters,$key)){
						if($value==='true')
							$value=true;
						else if($value==='false')
							$value=false;
						self::$parameters->$key=$value;
					}
				}
			}
		}
		return self::$parameters;
	}
	
	private static function check_type(string $wanted_type,$name,$value){
		$got_type=gettype($value);
		if($got_type==='integer')
			$got_type='int';
		if($got_type==='boolean')
			$got_type='bool';
		if($wanted_type==='int' && $got_type==='string'){
			if(preg_match('/[1-9]*[0-9]+/',$value)){
				$value=(int)$value;
				$got_type='int';
			}
		}
		if($wanted_type==='number'){
			if($got_type==='int')
				return $value;
			if($got_type==='double')
				return $value;
		}
		if($wanted_type!==$got_type){
			if($name!==null)
				throw new \Exception("bad parameter type for \"$name\" wanted \"$wanted_type\" got \"".gettype($value)."\"");
			else
				throw new \Exception("bad parameter type wanted \"$wanted_type\" got \"".gettype($value)."\"");
		}
		return $value;
	}
	
	public static function param(string $wanted_type,string $name=null){
		$parameters=self::parameters();
		if($name===null){
			$value=$parameters;
		}else{
			if(!($parameters instanceof stdClass) || !property_exists($parameters,$name))
				throw new \Exception("missing parameter \"$name\"");
			$value=$parameters->$name;
		}
		return self::check_type($wanted_type,$name,$value);
	}

	public static function param_opt(string $wanted_type,string $name,$default=null){
		$parameters=self::parameters();
		if(!($parameters instanceof stdClass) || !property_exists($parameters,$name))
			return $default;
		$value=$parameters->$name;
		return self::check_type($wanted_type,$name,$value);
	}
};
