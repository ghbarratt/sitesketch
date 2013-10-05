<?php


	function go_https()
	{
		if(isset($_SERVER))
		{
			if(!isset($_SERVER['HTTPS']) || !$_SERVER['HTTPS'])
			{
				header('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
				exit;
			}
		}
	}// go_https

?>
