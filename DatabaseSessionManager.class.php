<?php


class DatabaseSessionManager
{

	private static $db;
	private static $session_code;
	private static $sync_real_session;
	private static $data;
	private static $errors = array();
	private static $table_name = 'session';


	public function __construct($session_code=false, $sync_real_session=true)
	{

		global $db;

		self::$sync_real_session = $sync_real_session;

		if(!isset(self::$db) || !is_object(self::$db))
		{
			if(isset($db)) self::$db = $db;		
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


	public static function getSessionCode($session_id=null, $db_in=null)
	{
		
		global $db;

		if(!$db_in)
		{
			if(isset(self::$db)) $db_in = self::$db;
			else if(isset($db)) $db_in = $db;
		}
		if(!empty($session_id) && is_numeric($session_id))
		{
			$sql = "SELECT code FROM ".self::$table_name." WHERE id = ".(int)$session_id;
			$dbr = $db_in->query($sql);
			if(PEAR::isError($dbr))
			{
				self::$errors[] = $dbr->getMessage();
				return false;
			}
			else return $dbr->fetchOne();
		}
		else return self::$session_code;
	}// getSessionCode


	public function setSessionCode($session_code=false)
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


	public function sessionExistsWithCode($session_code=false, $db_in=false)
	{

		global $db;

		if(!$db_in)
		{
			if(isset(self::$db)) $db_in = self::$db;
			else if(isset($db)) $db_in = $db;
		}

		if(!$session_code && isset(self::$session_code) && self::$session_code) $session_code = self::$session_code;

		$sql =
		"	
			SELECT code FROM ".self::$table_name." WHERE code = '".$session_code."'
		";

		$dbr = $db_in->query($sql);
    if(PEAR::isError($dbr))
		{
			self::$errors[] = $dbr->getMessage();
			return false;
    }
		else return $dbr->fetchOne();

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
			//echo 'DEBUG reusing data because force is '.$force_query.'<br/>';
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

		//echo 'DEBUG db session data sql: '.$sql."\n";

		$dbr = self::$db->query($sql);
    if(PEAR::isError($dbr))
		{
			self::$errors[] = $dbr->getMessage();
			return false;
    }

		$data_string = $dbr->fetchOne();
		
		//echo 'DEBUG data_string: '.$data_string."<br/>\n";
		//echo 'DEBUG data <pre>';
		//print_r(unserialize($data_string));
		//echo '</pre>';

		$data = unserialize($data_string);

		if(self::$sync_real_session)
		{
			if(is_array($data)) $data = array_merge($_SESSION, $data);
			else $data = $_SESSION;
		}

		//echo 'DEBUG data <pre>';
		//print_r($data);
		//echo '</pre>';

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

		//echo 'DEBUG Attempting to remove: '.$key.' db: '.$write_to_database."<br/>\n";

		unset(self::$data[$key]);
		
		$sql = 'SELECT id FROM '.self::$table_name." WHERE code = ".self::$db->quote(self::$session_code);
		$dbr = self::$db->query($sql);
    if(PEAR::isError($dbr))
		{
			self::$errors[] = $dbr->getMessage();
			return false;
    }
		else $session_id = $dbr->fetchOne();	


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
						data = ".self::$db->quote(serialize(self::$data)).",
						updated = NOW(),
						last_ip = ".self::$db->quote($last_ip)."
					WHERE id = ".self::$db->quote($session_id)."
				";
		
				$dbr = self::$db->query($sql);
				
				//echo 'DEBUG Ran query: '.$sql."<br/>\n";

    		if(PEAR::isError($dbr))
				{
					self::$errors[] = $dbr->getMessage().' - SQL: '.$sql;
					//echo 'DEBUG Made it this far query: '.$sql.' but had error: '.$dbr->getMessage()."<br/>\n";
					return false;
    		}
				else return true;	
			}
		}
		return false;

	}// remove


	public function deleteSessionFromDatabase($session_id, $db_in=null)
	{

		global $db;

		if(!$db_in)
		{
			if(isset(self::$db)) $db_in = self::$db;
			else if(isset($db)) $db_in = $db;
		}

		$sql = 'SELECT id FROM '.self::$table_name.' WHERE id = '.(int)$session_id;
		$dbr = $db_in->query($sql);
		if(PEAR::isError($dbr))
		{
			self::$errors[] = $dbr->getMessage();
			return false;
		}
		else $found_id = $dbr->fetchOne();	

		if($session_id==$found_id)
		{
			$sql = 'DELETE FROM '.self::$table_name.' WHERE id = '.(int)$session_id;
			$dbr = $db_in->query($sql);
			if(PEAR::isError($dbr))
			{
				self::$errors[] = $dbr->getMessage().' - SQL: '.$sql;
				return false;
			}
			else return true;	
		}
		return false;

	}// deleteSessionFromDatabase


	public function set($key, $value, $write_to_database=true)
	{

		self::$data[$key] = $value;

		$sql = 'SELECT id FROM '.self::$table_name." WHERE code = ".self::$db->quote(self::$session_code);

		$dbr = self::$db->query($sql);
    if(PEAR::isError($dbr))
		{
			self::$errors[] = $dbr->getMessage();
			return false;
    }
		else $session_id = $dbr->fetchOne();	

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
						data = ".self::$db->quote(serialize(self::$data)).",
						updated = NOW(),
						last_ip = ".self::$db->quote($last_ip)."
					WHERE id = ".self::$db->quote($session_id)."
				";
			}
			else // no match
			{
				$sql =
				"
					INSERT INTO ".self::$table_name."
					(code, data, last_ip, created)
					VALUES
					(".self::$db->quote(self::$session_code).", ".self::$db->quote(serialize(self::$data)).', '.self::$db->quote($last_ip).", NOW())
				";
			}

			$dbr = self::$db->query($sql);

			//echo 'DEBUG set sql<pre>';
			//print_r($sql);
			//echo '</pre>';
	

			if(PEAR::isError($dbr))
			{
				self::$errors[] = $dbr->getMessage().' - SQL: '.$sql;
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

