<?php
/**
* 
*/
class Database
{
	private $db_host;
	private $db_name;
	private $db_user;
	private $db_pass;
	private $pdo;

	public function __construct($db_host, $db_name, $db_user, $db_pass){
		$this->db_host = $db_host;
		$this->db_name = $db_name;
		$this->db_user = $db_user;
		$this->db_pass = $db_pass;
	}

	private function getPDO(){
		if (!empty($this->pdo)) {
			return $this->pdo;
		}else{	
			try {
				$pdo = new PDO('mysql:dbname='.$this->db_name.';host:'.$this->db_host.'', ''.$this->db_user.'', ''.$this->db_pass.'');
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$this->pdo = $pdo;
				return $pdo;
			} catch (PDOException $e) {
				print "Erreur !: " . $e->getMessage() . "<br/>";
				die();
			}
		}
	}
	/*
	$req = $this->db->query(
		'SELECT crw_user.* ,crw_right.right
		FROM crw_user
		LEFT JOIN crw_user_right
		ON crw_user_right.crw_user_id = crw_user.id
		LEFT JOIN crw_right
		ON crw_right.id = crw_user_right.crw_right_id');
	*/
	public function query($query, $class_name = null, $one = false){
		$req = $this->getPDO()->query($query);
		if (strrpos($query, 'UPDATE') === 0 || 
			strrpos($query, 'INSERT') === 0 || 
			strrpos($query, 'DELETE') === 0) {
			return $req;
		}
		if ($class_name === null) {
			$req->setfetchMode(PDO::FETCH_OBJ);
		}else{
			$req->setfetchMode(PDO::FETCH_CLASS, $class_name);
		}
		if ($one) {
			$datas = $req->fetch();
		}else{
			$datas = $req->fetchAll();
		}
		return $datas;
	}
	/*
	$req = $this->db->prepare(
		'SELECT crw_user.* ,crw_right.right
		FROM crw_user
		LEFT JOIN crw_user_right
		ON crw_user_right.crw_user_id = :id
		LEFT JOIN crw_right
		ON crw_right.id = crw_user_right.crw_right_id
		WHERE crw_user.id = :id', array('id' => intval($id)), null, true);
	*/
	public function prepare($statement, $attributes, $class_name = null, $one = false){
		$req = $this->getPDO()->prepare($statement);
		$res = $req->execute($attributes);
		if (strrpos($statement, 'UPDATE') === 0 || 
			strrpos($statement, 'INSERT INTO') === 0 || 
			strrpos($statement, 'DELETE') === 0) {
			return $res;
		}
		if ($class_name === null) {
			$req->setfetchMode(PDO::FETCH_OBJ);
		}else{
			$req->setfetchMode(PDO::FETCH_CLASS, $class_name);
		}
		if ($one) {
			$datas = $req->fetch();
		}else{
			$datas = $req->fetchAll();
		}
		// echo "datas database";
		// var_dump($datas);
		return $datas;
	}

	/*
	$req = $this->db->transaction(
		array(
				array(
					'prepare',
					'INSERT INTO crw_user (name, firstname, login, password, mail, birthDate)
					VALUES ( :name, :firstname, :login, :password, :mail, :birthDate)',
					array('name' => $name, 'firstname' => $firstname, 'login' => $login, 'password' => $password, 'mail' => $mail, 'birthDate' => $birthDate)
					),
				array(
					'prepare',
					'INSERT INTO crw_user_right (crw_user_id, crw_right_id)
					VALUES (LAST_INSERT_ID(), :right_id)',
					array('right_id' => $right_id)
					)
			)
		);
	*/
	public function transaction($statements){
		try {
			$this->getPDO()->beginTransaction();
			// var_dump($statements);
			$reqs = [];
			foreach ($statements as $key => $value) {
				if (count($value) == 3) {
					$req = $this->$value[0]($value[1], $value[2]);
				}elseif (count($value) == 4) {
					$req = $this->$value[0]($value[1], $value[2], $value[3]);
				}elseif (count($value) == 5) {
					$req = $this->$value[0]($value[1], $value[2], $value[3], $value[4]);
				}else{
					$req = $this->$value[0]($value[1]);					
				}
				array_push($reqs, $req);
			}
			$this->getPDO()->commit();
			return $reqs;
		}
		catch(PDOException $e) {
		    $db->rollback();
		    return $e->getMessage();
		}
	}


	/* Tools */
	public function lastInsertId(){
		return $this->getPDO()->lastInsertId();
	}
}