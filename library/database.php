<?php
require_once(dirname(__FILE__).'/../config/config.php');

class database_select_rows{
	private $stmt;
	public $fetch_type;
	
	public function __construct($stmt,$fetch_type){
		$this->stmt=$stmt;
		$this->fetch_type=$fetch_type;
	}
	
	public function fetch(){
		$fetch_type=end(static::$fetch_type);
		if(is_array($fetch_type))
			$row=$this->stmt->fetch($fetch_type[0],$fetch_type[1]);
		else
			$row=$this->stmt->fetch($fetch_type);
		if(!$row)
			$this->stmt->closeCursor();
			
		return $row;
	}
	
	public function close(){
		$this->stmt->closeCursor();
	}
	
};

class database{
	private static $pdo=null;
	private static $fetch_type=[\PDO::FETCH_OBJ];

	private static function connect(){
		global $config;
		if(!self::$pdo){
			$dsn='mysql:dbname='.$config['database_name'].';host='.$config['database_host'];
			self::$pdo=new PDO($dsn,$config['database_user'],$config['database_password']);
			self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);;
		}
		return self::$pdo;
	}
	
	public static function execute(string $sql, ...$values): bool{
		$pdo=self::connect();
		$stmt=$pdo->prepare($sql);
		foreach($values as $i=>$v)
			$stmt->bindValue(1+$i,$v);
		$r=$stmt->execute();
		$stmt->closeCursor();
		return $r;
	}
	
	public static function last_insert_id(): int{
		return self::$pdo->lastInsertId();
	}
	
	public static function select_first(string $sql, ...$values){
		$pdo=self::connect();
		$stmt=$pdo->prepare($sql);
		foreach($values as $i=>$v)
			$stmt->bindValue(1+$i,$v);
		$r=$stmt->execute();
		$fetch_type=end(static::$fetch_type);
		if(is_array($fetch_type))
			$row=$stmt->fetch($fetch_type[0],$fetch_type[1]);
		else
			$row=$stmt->fetch($fetch_type);
		$stmt->closeCursor();
		return $row;
	}

	public static function select_all(string $sql, ...$values){
		$pdo=self::connect();
		$stmt=$pdo->prepare($sql);
		foreach($values as $i=>$v)
			$stmt->bindValue(1+$i,$v);
		$r=$stmt->execute();
		$fetch_type=end(static::$fetch_type);
		if(is_array($fetch_type))
			$rows=$stmt->fetchAll($fetch_type[0],$fetch_type[1]);
		else
			$rows=$stmt->fetchAll($fetch_type);
		$stmt->closeCursor();
		return $rows;
	}

	public static function select(string $sql, ...$values){
		$pdo=self::connect();
		$stmt=$pdo->prepare($sql);
		foreach($values as $i=>$v)
			$stmt->bindValue(1+$i,$v);
		$r=$stmt->execute();
		return new database_select_rows($stmt,[end(static::$fetch_type)]);
	}
	
	public static function quote($value){
		$pdo=self::connect();
		return $pdo->quote($value);
	}
	
	public static function push_fetch_type($fetch_type){
		array_push(static::$fetch_type,$fetch_type);
	}

	public static function pop_fetch_type(){
		array_pop(static::$fetch_type);
	}
}
