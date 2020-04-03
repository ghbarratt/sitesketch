<?php


class DatabaseSessionManager
{

	private static $dbh;
	private static $session_code;
	private static $sync_real_session;
	private static $data;
	private static $errors = array();
	private static $table_name = 'session';


	public function __construct($dbh, $session_code=false, $sync_real_session=true)
	{

		self::$sync_real_session = $sync_real_session;

		if(!isset(self::$dbh) || !is_object(self::$dbh))
		{
			if(isset($dbh)) self::$dbh = $dbh;		
			else 
			{
				self::$errors[] = 'Database object is not set';
				return false;
			}
		}

		if(self::$sync_real_session) $result = session_start();
		else $result = true;
	
		if($session_code) self::setSessionCode($session_code);
		else if(!self::$session_code && self::$sync_real_session) self::$session_code = session_id();
		else if(!self::$session_code && isset($_COOKIE['PHPSESSID'])) self::$session_code = $_COOKIE['PHPSESSID'];

		if(!self::$session_code) 
		{
			self::$errors[] = 'Unable to get session code (PHPSESSID) from browser (cookies). Are cookies turned off?';
			return false;
		}

		return $result;
		
	}// construct


	public static function getSessionCode($session_id=null, $dbh_in=null)
	{
		
		if(!$dbh_in)
		{
			if(isset(self::$dbh)) $dbh_in = self::$dbh;
		}
		if(!empty($session_id) && is_numeric($session_id))
		{
			$sql = "SELECT code FROM ".self::$table_name." WHERE id = ".(int)$session_id;
			try {
				$sth = $dbh_in->prepare($sql);
				$sth->execute();
				$result = $sth->fetch();
				return $result['code'];
			}
			catch (PDOException $e)
			{
				self::$errors[] = $e->getMessage();
				return false;
			}
		}
		else return self::$session_code;
	}// getSessionCode


	public static function setSessionCode($session_code=false)
	{

		if(self::$sync_real_session) 
		{
			if(!$session_code)
			{
				$result = session_regenerate_id(true); 
				if($result) $session_code = session_id();
				$result = true;
			}
			else $result = session_id($session_code);
		}
		else $result = true;

		setcookie('PHPSESSID', $session_code, 0, '/'); // important to set path to /
		self::$session_code = $session_code;
	
		return $result;			

	}// setSessionCode


	public static function sessionExistsWithCode($session_code=false, $dbh_in=false)
	{

		if(!$dbh_in)
		{
			if(isset(self::$dbh)) $dbh_in = self::$dbh;
		}

		if(!$session_code && isset(self::$session_code) && self::$session_code) $session_code = self::$session_code;

		$sql =
		"	
			SELECT code FROM ".self::$table_name." WHERE code = '".$session_code."'
		";
		try {
			$sth = $dbh_in->prepare($sql);
			$sth->execute();
			$result = $sth->fetch();
			return $result['code'];
		} 
		catch (PDOException $e)
		{
			self::$errors[] = $e->getMessage();
			return false;
		}

	}// sessionExistsWithCode


	public function renewData()
	{
		self::$data = self::getData(false, true);	
	}// renewData


	public function getData($session_code=false, $force_query=false)
	{

		if(!$session_code && self::$session_code) $session_code = self::$session_code;
		if
		(
			!$force_query && $session_code==self::$session_code && isset(self::$data) && is_array(self::$data)&& count(self::$data)) 
		{
			return self::$data;
		}
		//echo 'DEBUG force: '.$force_query."<br/>\n";
		//echo 'DEBUG Querying database for data with session_code: '.$session_code.' when self: '.self::$session_code.'<br/>';

		$sql =
		"	
			SELECT data
			FROM ".self::$table_name."
			WHERE code = '".$session_code."'
		";

		try {
			$sth = self::$dbh->prepare($sql);
			$sth->execute();
			$result = $sth->fetch();
			$data_string = $result['data'];
		} 
		catch (PDOException $e)
		{
			self::$errors[] = $e->getMessage();
			return false;
		}

		$data = unserialize($data_string);

		if(self::$sync_real_session)
		{
			if(is_array($data)) $data = array_merge($_SESSION, $data);
			else $data = $_SESSION;
		}

		if($session_code==self::$session_code) self::$data = $data;
		$data['session_code'] = $session_code;

		return $data;

	}// getData


	public function get($key)
	{
		
		if((!isset(self::$data) || !is_array(self::$data)) && isset(self::$session_code)) self::$data = self::getData();

		if(isset(self::$data[$key]))
		return self::$data[$key];

		return false;

	}// get


	public function remove($key, $write_to_database=true)
	{

		unset(self::$data[$key]);
		
		$sql = 'SELECT id FROM '.self::$table_name." WHERE code = ".self::$dbh->quote(self::$session_code);
		try {
			$sth = self::$dbh->prepare($sql);
			$sth->execute();
			$result = $sth->fetch();	
			$session_id = $result['id'];
		}
		catch (PDOException $e)
		{
			self::$errors[] = $e->getMessage();
			return false;
		}


		$last_ip = $_SERVER['REMOTE_ADDR']; 

		if(self::$sync_real_session)
		{
			unset($_SESSION[$key]);			
		}

		if($write_to_database)
		{
			if($session_id)
			{
				$sql = 
				"
					UPDATE ".self::$table_name."
					SET 
						data = ".self::$dbh->quote(serialize(self::$data)).",
						updated = NOW(),
						last_ip = ".self::$dbh->quote($last_ip)."
					WHERE id = ".self::$dbh->quote($session_id)."
				";
				try {
					$sth = self::$dbh->prepare($sql);
					$sth->execute();
				} 
				catch (PDOException $e)
				{
					self::$errors[] = $e->getMessage().' - SQL: '.$sql;
					return false;
    				}
				return true;	
			}
		}
		return false;

	}// remove


	public function deleteSessionFromDatabase($session_id, $dbh_in=null)
	{

		if(!$dbh_in)
		{
			if(isset(self::$dbh)) $dbh_in = self::$dbh;
		}

		$sql = 'SELECT id FROM '.self::$table_name.' WHERE id = '.(int)$session_id;
		try {
			$sth = $dbh_in->prepare($sql);
			$sth->execute();
			$result = $sth->fetch();	
			$found_id = $result['id'];
		}
		catch (PDOException $e)
		{
			self::$errors[] = $e->getMessage();
			return false;
		}

		if($session_id==$found_id)
		{
			$sql = 'DELETE FROM '.self::$table_name.' WHERE id = '.(int)$session_id;
			try {
				$sth = $dbh_in->prepare($sql);
				$sth->execute();
			}
			catch (PDOException $e)
			{
				self::$errors[] = $e->getMessage().' - SQL: '.$sql;
				return false;
			}
			return true;	
		}
		return false;

	}// deleteSessionFromDatabase


	public function set($key, $value, $write_to_database=true)
	{

		self::$data[$key] = $value;

		$sql = 'SELECT id FROM '.self::$table_name." WHERE code = ".self::$dbh->quote(self::$session_code);

		try {
			$sth = self::$dbh->prepare($sql);
			$sth->execute();
			$result = $sth->fetch();
			$session_id = $result['id'];
		}
		catch (PDOException $e)
		{
			self::$errors[] = $e->getMessage();
			return false;
		}

		$last_ip = $_SERVER['REMOTE_ADDR']; 

		if(self::$sync_real_session)
		{
			$_SESSION[$key] = $value;			
		}

		if($write_to_database)
		{
			if($session_id)
			{
				$sql = 
				"
					UPDATE ".self::$table_name."
					SET 
						data = ".self::$dbh->quote(serialize(self::$data)).",
						updated = NOW(),
						last_ip = ".self::$dbh->quote($last_ip)."
					WHERE id = ".self::$dbh->quote($session_id)."
				";
			}
			else // no match
			{
				$sql =
				"
					INSERT INTO ".self::$table_name."
					(code, data, last_ip, created)
					VALUES
					(".self::$dbh->quote(self::$session_code).", ".self::$dbh->quote(serialize(self::$data)).', '.self::$dbh->quote($last_ip).", NOW())
				";
			}

			try {
				$sth = self::$dbh->prepare($sql);
				$sth->execute();
			}
			catch (PDOException $e)
			{
				self::$errors[] = $e->getMessage().' - SQL: '.$sql;
				return false;
			}
		}

		return true;	

	}// set

	
	public function getErrors()
	{
		return self::$errors;
	}// getErrors


}// class DatabaseSessionManager

