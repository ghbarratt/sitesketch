<?php

	function getContentFromFile($filename, $line_prefix='')
	{
		
		$file_text = getTextFromFile($filename);	

		$content = '';

		//$content .= "<!-- FILE (".$filename.") CONTENT STARTS HERE -->\n";
		
		$file_lines = explode("\n",$file_text);
		foreach($file_lines as $Line) $content .= $line_prefix.$Line."\n";


		$replacements = array
		(
			'elevated' => array
			(	
				'start_tag' => '<span type="elevated">',
				'end_tag' => '</span>',
				'function' => 'wrapElevatedContent'
			),
			'quote' => array
			(	
				'start_tag' => '<q>',
				'end_tag' => '</q>',
				'function' => 'wrapQuoteContent'
			),
		);

		foreach($replacements as $r)
		{

			// Go through the content and replace tags!
			$start_position = strpos($content, $r['start_tag']);

			while ($start_position!=false) 
			{
				$end_position=strpos($content,$r['end_tag'],$start_position);
				if ($end_position!=false)
				{
					$to_replace = substr($content,$start_position,(($end_position+strlen($r['end_tag']))-($start_position)));
					$inside_content = substr($content,$start_position+strlen($r['start_tag']),(($end_position)-($start_position+strlen($r['start_tag']))));
					$replace_with = $r['function']($inside_content,$line_prefix);
					$content = str_replace($to_replace, $replace_with, $content);		
					//$inside_content = '';
				}
				$start_position = false;
				$start_position = strpos($content,$r['start_tag']);
			}	
		}

		//$content .= "<!-- FILE (".$filename.") CONTENT ENDS HERE -->\n";

		return $content;
	}// getContentFromFile


 	function getTextFromFile($filename,$Debugging=false)
	{
		$filename = $filename;
		
		$FileHandler = fopen($filename, 'r') or $file_text = "ERROR: fopen could not open ".$filename.".\n";
		if($FileHandler)
		{
			$file_text = fread($FileHandler, filesize($filename));
			fclose($FileHandler);
		}

		return $file_text;

	}// getTextFromFile


	function adjustLinePrefix($amount=0)
	{
		global $line_prefix;
	
		if(!isset($line_prefix)) $line_prefix = "\t\t\t\t";

		if(!is_int($amount))return false;

		if($amount<0)
		{
			$before = $line_prefix;
			// ASSUMES LinePrefix peiece is one character only
			$line_prefix = substr($line_prefix,abs($amount));
			//echo "HI! str replace here amount: ".$amount."=".abs($amount)."\nBEFORE: '".$before."'\nAFTER:  '".$line_prefix."'\n";
		}
		else 
		{
			for($i=0; $i<$amount; $i++)
			{
				$line_prefix = "\t".$line_prefix;
			}
		}
		
		return $line_prefix;
	}// adjustLinePrefix


	function getButtonContent($alias, $id, $link, $alt='', $extra='')
	{

		global $line_prefix;

		if(!isset($line_prefix)) $line_prefix = "\t\t\t\t";

		//$content .= "<!-- ".strtoupper($alias)." BUTTON STARTS HERE -->\n";

		$content .= $line_prefix."<a href=\"".$link."\">\n";
		$content .= $line_prefix."\t<img src=\"/images/button_".$alias.'_up.gif" alt="'.$alt.'" id="'.$id.'" name="'.$id.'" onmouseover="changeImage(\"".$id.'\',\'/images/button_\"".$alias.'_hover.gif\');" onmouseout="changeImage(\"".$id.'\',\'/images/button_'.$alias.'_up.gif\');" onmousedown="changeImage(\"".$id.'\',\'/images/button_'.$alias.'_down.gif\');" onmouseup="changeImage(\"".$id.'\',\'/images/button_'.$alias.'_hover.gif\');"'.$Extra.">\n";
		$content .= $line_prefix."</a>\n";
		//$content .= "<!-- ".strtoupper($alias)." BUTTON ENDS HERE -->\n";

		return $content;
	}// getButtonContent

	

?>
