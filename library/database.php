<?php
require_once(dirname(__FILE__).'/../config/config.php');

class database_select_rows{
	private $stmt=null;

	public function __construct($stmt){
		$this->stmt=$stmt;
	}
	
	public function fetch(){
		$row=$this->stmt->fetch(\PDO::FETCH_OBJ);
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
		$row=$stmt->fetch(\PDO::FETCH_OBJ);
		$stmt->closeCursor();
		return $row;
	}

	public static function select_all(string $sql, ...$values){
		$pdo=self::connect();
		$stmt=$pdo->prepare($sql);
		foreach($values as $i=>$v)
			$stmt->bindValue(1+$i,$v);
		$r=$stmt->execute();
		$rows=$stmt->fetchAll(\PDO::FETCH_OBJ);
		$stmt->closeCursor();
		return $rows;
	}

	public static function select(string $sql, ...$values){
		$pdo=self::connect();
		$stmt=$pdo->prepare($sql);
		foreach($values as $i=>$v)
			$stmt->bindValue(1+$i,$v);
		$r=$stmt->execute();
		return new database_select_rows($stmt);
	}
}
