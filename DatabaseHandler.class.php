<?php

class DatabaseHandler
{

	/// VARS ///
	static private $type_default = 'pear_mdb2';

	private $dsn;
	private $type = 'pear_mdb2';
	private $db;
	private $last_sql;
	private $errors = array();

	
	/// FUNCTIONS ///
	public function __construct($dsn, $type=false, $options=false)
	{

		return $this->connect($dsn, $type, $options);

	}// constructor


	public function connect($dsn, $type=false, $options=false)
	{

		// Currently there are only two options: pear_mdb2 and php_native_mysql

		$connected = false;

		if($type=='php_native_mysql')
		{
			$this->db = mysql_connect($dsn->hostspec, $dsn->username, $dsn->password);
			if($this->db)
			{
				if($dsn->database) mysql_select_db($dsn->database, $this->db);
				$this->dsn = $dsn;
				$connected = true;
			}
		}
		else if($type=='pear_mdb2')
		{
			require_once 'MDB2.php';

			$this->db =& MDB2::factory($dsn, $options);
			if(PEAR::isError($this->db))
			{
  			$this->errors[] = $this->db->getMessage();
			}
			else
			{
				$connected = true;
				$this->db->setFetchMode(MDB2_FETCHMODE_ASSOC);
			}

		}
		
		if(!$connected)
		{
			$errors[] = 'Unable to connect to database using type: '.$type;
			return false;
		}
		else return $this->db;

	}// connect


	public function getDatabase()
	{

		if(isset($this->db)) return $this->db;

		$this->errors[] = 'You must first initialize the database connection';
		return false;

	}// getDatabase
	

	public function getDB()
	{
		return $this->getDatabase();
	}// getDB alias for getDatabase

	
	public function query($sql)
	{
		$this->last_sql = $sql;
		if($this->type=='pear_mdb2')
		{
			return $this->db->query($sql);
		}
	}// query


	public function isError($dbr)
	{
		if($this->type=='pear_mdb2') return PEAR::isError($dbr);
		else return true;
	}// isError


	public function fetchAll($dbr, $sql=false)
	{
		if(!$sql) $sql = $this->last_sql;
		if($this->type=='pear_mdb2') return $dbr->fetchAll($sql);
		else return false;
	}// fetchall	


	public function quote($value)
	{
		if($this->type=='pear_mdb2') return self::$db->quote($value);
		else return false;
	}// quote


	public function exec($sql)
	{
		if($this->type=='pear_mdb2') return self::$db->exec($sql);
		else return false;
	}// exec


	public function getMessage()
	{
		return self::$db->getMessage();
	}// getMessage


	public function LastInsertID($table=NULL, $field=NULL)
	{
		
		if($this->type=='pear_mdb2') return self::$db->lastInsertID($table, $field);
		else return false;

	}// getLastInsertID


	public function getErrors()
	{
		return $this->errors;
	}// getErrors


}// class DatabaseHandler

