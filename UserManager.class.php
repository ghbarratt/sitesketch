<?php

	// User Manager class - Manages user accounts
	// 
	// part of the Sitesketch Framework
	// property of AdeptSites LLC
	// developed by Glen H. Barratt
	 

	class UserManager
	{

		// CLASS VARS
		static protected $db;

		protected $hashing_passwords = true;
		protected $hash_method = 'sha256';
	
		protected $use_salt = true;
		protected $salt_alias = 'ap_salt';
		protected $salt_length = 8;
		protected $salt_field = 'salt';
	
		protected $user_table;	
		protected $identifier_field = 'id';	
		protected $password_field = 'password_hash';	
		protected $username_field = 'username';	
		protected $session_key = 'user_id';
	
		protected $errors = array();

		protected $user_id;	

		// CLASS FUNCTIONS 
	
		public function __construct($db_passed=false, $user_table=false, $identifier_field=false)
		{
			global $db;

			if(!isset($_SESSION)) session_start();
			
			if(isset($db_passed) && is_object($db_passed)) self::$db = $db_passed;
			else if(isset($db) && is_object($db)) self::$db = $db;
			else 
			{
				die('You must have a database connection established in order to use the User Manager.');
			}

			if(!$user_table) $this->user_table = 'users'; // User table default
			else $this->user_table = $user_table;

			if(!$identifier_field) $this->identifier_field = 'id'; // User table unique identifier default
			else $this->identifier_field = $identifier_field;

		}// constructor


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
				INSERT INTO ".$this->user_table."
				(".implode(',', array_keys($user_data)).")
				VALUES
				(".implode(',', $user_data).")
			";
			//echo 'DEBUG sql <pre>'.$sql.'</pre>';

			$result = self::$db->query($sql);
			
			if (PEAR::isError($result)) 
			{
				$this->errors[] = 'There was an SQL error trying to insert user: '.$result->getMessage();
			}
			
		}//

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
				FROM '.$this->user_table."
				WHERE ".$this->username_field." = '".$username."'
			";

			$user_data = self::$db->queryRow($sql);

			if(PEAR::isError($user_data))
			{
				$this->errors[] = 'There was an SQL error '.$user_data->getMessage().$sql;
				//$this->errors[] = 'There was an SQL error '
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
				//echo 'DEBUG which is the hash of password_hash:'.$user_data['password_hash']."<br/>\n";
				//echo 'DEBUG and the salt of:'.$this->getSalt()."<br/>\n";
				//echo 'DEBUG does not match: '.$password."<br/>\n";A
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
