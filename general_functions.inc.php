<?php

function isValidIP($ip)
{
	if(!empty($ip) && ip2long($ip)!=-1)
	{
		$reserved_ips = array 
		(
			array('0.0.0.0','2.255.255.255'),
			array('10.0.0.0','10.255.255.255'),
			array('127.0.0.0','127.255.255.255'),
			array('169.254.0.0','169.254.255.255'),
			array('172.16.0.0','172.31.255.255'),
			array('192.0.2.0','192.0.2.255'),
			array('192.168.0.0','192.168.255.255'),
			array('255.255.255.0','255.255.255.255')
		);
		foreach($reserved_ips as $r) 
		{
			$min = ip2long($r[0]);
			$max = ip2long($r[1]);
			if((ip2long($ip)>=$min) && (ip2long($ip)<=$max)) return false;
		}
		return true;
	}
	else return false;
}// isValidIP


function getIP() 
{
 
	if(!empty($_SERVER['HTTP_CLIENT_IP']) && isValidIP($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER["HTTP_CLIENT_IP"];

	if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		foreach(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $ip)
		{
	 		if(isValidIP(trim($ip))) return $ip;
		}
	}	

	if(!empty($_SERVER['HTTP_X_FORWARDED']) && isValidIP($_SERVER['HTTP_X_FORWARDED'])) return $_SERVER['HTTP_X_FORWARDED'];
	elseif (!empty($_SERVER['HTTP_FORWARDED_FOR']) && isValidIP($_SERVER['HTTP_FORWARDED_FOR'])) return $_SERVER['HTTP_FORWARDED_FOR'];
	elseif (!empty($_SERVER['HTTP_FORWARDED']) && isValidIP($_SERVER['HTTP_FORWARDED'])) return $_SERVER['HTTP_FORWARDED'];
	elseif (!empty($_SERVER['HTTP_X_FORWARDED']) && isValidIP($_SERVER['HTTP_X_FORWARDED'])) return $_SERVER['HTTP_X_FORWARDED'];
	else return $_SERVER['REMOTE_ADDR'];
	
}// getIP


function forceHTTPS()
{
	if(isset($_SERVER))
	{
		if(!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS'])!='on')
		{
			header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
			exit;
		}
	}
}// forceHTTPS


function getPageAliasUsingURL()
{
	$url = $_SERVER['SCRIPT_NAME'] ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];	
	$alias = str_replace('index.php', '', $url);
	// Remove first /
	while(substr($alias, 0, 1)=='/') $alias = substr($alias, 1);
	// Remove lasst /
	while(substr($alias, strlen($alias)-1, 1)=='/') $alias = substr($alias, 0, strlen($alias)-1);
	// Just take what comes after the LAST /
	if(strpos($alias, '/')!==false) $alias = substr($alias, strrpos($alias, '/')+1);
	return $alias;
}// getPageAliasUsingURL


function isEmailValid($email) 
{
	if(preg_match('/^([a-zA-Z0-9])+([a-zA-Z0-9\._-]).*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/', $email))
	{
		list($username, $domain) = split('@', $email);
		if(!checkdnsrr($domain,'MX')) return false;
	}
	return true;
}// isEmailValid




