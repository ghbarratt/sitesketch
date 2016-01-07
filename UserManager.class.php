<?php

// User Manager class - Manages user account
// 
// part of the Sitesketch Framework
// property of Adept Sites LLC
// developed by Glen H. Barratt

 
require_once 'ContentBuilder.class.php';


class UserManager
{

	// CLASS VARS
	protected $hashing_passwords = true;
	protected $hash_method = 'sha256';

	protected $use_salt = true;
	protected $salt_alias = 'ap_salt';
	protected $salt_length = 8;
	protected $salt_field = 'salt';

	protected $users_table;	
	protected $identifier_field = 'id';	
	protected $password_field = 'password_hash';	
	protected $username_field = 'username';	
	protected $session_key = 'user_id';

	protected $errors = array();

	protected $db;
	protected $cb;

	protected $replacements = array();

	protected $user_id;	

	// CLASS FUNCTIONS 

	public function __construct($config)
	{
		global $dbh;

		$this->cb = new ContentBuilder();
		
		if(!isset($_SESSION)) session_start();
		
		if(!empty($config['dbh']) && is_object($config['dbh'])) $this->dbh = $config['dbh'];
		else if(isset($dbh) && is_object($dbh)) $this->dbh = $dbh;
		else 
		{
			die('You must have a database connection established in order to use the User Manager.');
		}

		if(!$users_table) $this->users_table = 'users'; // User table default
		else $this->users_table = $users_table;

		if(!$identifier_field) $this->identifier_field = 'id'; // User table unique identifier default
		else $this->identifier_field = $identifier_field;

	}// constructor


	public function getReplacements()
	{
		return array_merge($this->getDefaultReplacements(), $this->replacements);
	}// getReplacements


	public function addReplacements($replacements)
	{
		if(!is_array($this->replacements)) $this->replacements = array();
		$this->replacements = array_merge($replacements, $this->replacements);
	}// addReplacements


	public function addReplacement($tag, $value)
	{
		$this->replacements = array_merge(array($tag=>$value), $this->replacements);
	}// addReplacement


	public function insertUser($user_data)
	{

		if(!isset($user_data)) return false;

		if($this->hashing_passwords && isset($user_data['password']) && strlen($user_data['password'])!=64)
		{
			$plaintext_password = $user_data['password'];
			$user_data['password_hash'] = hash($this->hash_method, $plaintext_password);
		}

		$sql =
		"
			INSERT INTO ".$this->users_table."
			(".implode(',', array_keys($user_data)).")
			VALUES
			(".implode(',', $user_data).")
		";
		//echo 'DEBUG sql <pre>'.$sql.'</pre>';

		$count = $this->dbh->exec($sql);
		
		if(!$count) 
		{
			$this->errors[] = 'There was an SQL error trying to insert user: '.$result->getMessage();
		}
		
	}// insertUser


	public function addUser($user_data)
	{
		insertUser($user_data);
	}// alias for insertUser


	public function createUser($user_data)
	{
		insertUser($user_data);
	}// alias for insertUser


	public function getHashMethod()
	{
		return $this->hash_method;
	}


	private function getRandomString($length=false)
	{
		if(!$length) $length = $this->salt_length;
		$result = md5(uniqid(rand(), true));
		if(is_numeric($length) && $length) $result = substr($result, 0, $length);

		return $result; 
	}// getRandomString


	public function getSaltAlias()
	{
		return $this->salt_alias;
	}


	public function renewSalt($username=false, $salt=false)
	{
		//echo 'DEBUG Renew salt';

		// Basically the salt is stored on the client and in the database.
		$new_salt = $this->getRandomString($this->salt_length);

		//setcookie($this->salt_alias, $new_salt);
		//echo 'DEBUG cookie set to '.$new_salt."<br/>\n";
		$_SESSION[$this->salt_alias] = $new_salt;
		$this->salt = $new_salt;

		//echo 'DEBUG salt set to '.$new_salt;

		return $new_salt;

	}// renewSalt


	public function isUsingSalt()
	{
		return $this->use_salt;
	}// isUsingSalt


	public function getSalt($user_id=false)
	{
		if(isset($this->salt) && $this->salt) return $this->salt;

		if(isset($_SESSION) && isset($_SESSION[$this->salt_alias])) return $_SESSION[$this->salt_alias];

		return $this->renewSalt();

		//echo 'DEBUG NO SALT???? <pre>';
		//print_r($_SESSION);
		//echo '</pre>';
	}


	public function isUserValid($username, $password, $options)
	{
		if($this->hashing_passwords)
		{
			if(!is_array($options) || !isset($options['prehashed']) || !$options['prehashed'])
			$password = hash($this->hash_method, $password);
		}

		$sql =
		'
			SELECT '.$this->password_field.', '.$this->identifier_field.'
			FROM '.$this->users_table."
			WHERE ".$this->username_field." = ?
		";

		$sth = $this->dbh->prepare($sql);
		try
		{
			$sth->execute(array($username));
			$user_data = $sth->fetch();
		}
		catch (PDOException $e)
		{
			$this->errors[] = 'There was an SQL error '.$e->getMessage().' SQL: '.$sql;
			return false;
		}
		
		$user_id = false;

		if($password == hash($this->hash_method, $user_data['password_hash'].$this->getSalt()))
		{
			$user_id = $user_data[$this->identifier_field];
		}
		else
		{
			//echo 'DEBUG unfortunately the hashes do not match: '.hash($this->hash_method, $user_data['password_hash'].$this->getSalt())."<br/>\n";
			//echo 'DEBUG which is the hash of password_hash+salt: "'.$user_data['password_hash'].$this->getSalt()."\"<br/>\n";
			//echo 'DEBUG does not match: '.$password."<br/>\n";
		}
		
		return $user_id;
	
	}// isUserValid



	public function login($username, $password, $options)
	{

		if($user_id = $this->isUserValid($username, $password, $options))
		{
			$_SESSION[$this->session_key] = $user_id;			
			$this->user_id = $user_id;
			return true;
		}
		else
		{
			print_r($this->errors);
		}
		return false;

	}// login

	
	public function getUserID()
	{
		if(isset($this->user_id) && is_numeric($this->user_id)) return $this->user_id;
		else if(isset($this->session_key) && isset($_SESSION[$this->session_key]) && is_numeric($_SESSION[$this->session_key])) return $_SESSION[$this->session_key];
	}


	public function whoIsLoggedIn()
	{
		if(!isset($_SESSION)) session_start();
		if(isset($_SESSION[$this->session_key])) return $_SESSION[$this->session_key];
		else return false;
	}// whoIsLoggedIn


	public function bootAnonymous($url=false)
	{
		if(!$url) $url = '/login/';
		if($user_id = $this->whoIsLoggedIN())
		{
			return $user_id;
		}
		else		
		{
			header('Location: '.$url);
			exit;
		}
	}//


}// class PageBuilder


?>
