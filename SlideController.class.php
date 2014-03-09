<?php

class SlideController 
{
	
	public static $web_directory = 'web';
	public static $site_path;
	public static $slides_webpath = '/images/slides/';

	private static $dbh;	
	private static $cb;	
	private static $thumbnail_directory = 'thumbnail';
	private static $thumbnail_size_alias = 'thumbnail';
	private static $possible_extensions = array('.jpg', '.gif', '.png', '.jpeg', '.JPG', '.GIF', '.PNG');
	private static $change_group = 'dev';

	static private $debugging = false; //true;
	private static $thumbnail_sizes = array();

	private $slides_subpath;
	private $template = 'slide_controls.tpl';

	private $require_processing_for_render = false;
	private $processed = false;

	private $data;
	
	private $errors;



	public function __construct($dbh_in=false, $cb=false)
	{

		global $dbh;


		if(!isset(self::$site_path) || !self::$site_path)
		{
			require_once 'ContentBuilder.class.php';	
			self::$site_path = ContentBuilder::getSitePath();
		}

		if($dbh_in) self::$dbh = $dbh_in;
		else if(isset($dbh) && $dbh) self::$dbh = $dbh;
		
		if($cb) self::$cb = $cb;
		else 
		{
			require_once 'ContentBuilder.class.php';
			self::$cb = new ContentBuilder();
		}

	}// constructor


	public static function getSlidesPath()
	{
		if(!isset(self::$site_path) || !self::$site_path)
		{
			require_once 'ContentBuilder.class.php';	
			self::$site_path = ContentBuilder::getSitePath();
		}
		return self::$site_path.DIRECTORY_SEPARATOR.self::$web_directory.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'slides';
	}// getSlidePath



	public function getSlideCollections($params=false)
	{

		$slides_path = self::getSlidesPath();
		//echo 'DEBUG slide path: '.$slides_path."\n";
		
		// What are the slide collections? - the directories under the slides_path should tell us
		$slide_collection_aliases = glob($slides_path.DIRECTORY_SEPARATOR.'*');

		// Clean off the path
		foreach($slide_collection_aliases as &$sca) $sca = str_replace($slides_path.DIRECTORY_SEPARATOR, '', $sca);


		$slide_collections = array();

		// Now for each of these slide collections, scan the directories to find
		foreach($slide_collection_aliases as &$sca)
		{
			$slides = glob($slides_path.DIRECTORY_SEPARATOR.$sca.DIRECTORY_SEPARATOR.'*');
			// Clean off the path
			foreach($slides as $si=>&$s)
			{
				$s = str_replace($slides_path.DIRECTORY_SEPARATOR.$sca.DIRECTORY_SEPARATOR, '', $s);

				// Thumbnails are a special exception
				if($s=='thumbnail') unset($slides[$si]);
			}
			$slide_collections[$sca] = $slides;
		}

		return $slide_collections;

	}// getSlideCollections


	public function getSlidesSubpath()
	{

		if(!$this->slides_subpath) $this->slides_subpath = str_replace('index.php','',$_SERVER['PHP_SELF']);
		if($this->slides_subpath[strlen($this->slides_subpath)-1]==DIRECTORY_SEPARATOR) $this->slides_subpath = substr($this->slides_subpath,0,strlen($this->slides_subpath)-1);
		if($this->slides_subpath && $this->slides_subpath[0]==DIRECTORY_SEPARATOR) $this->slides_subpath = substr($this->slides_subpath,1);
		//echo 'DEBUG slides_subpath: '.$this->slides_subpath."\n<br/>\n";
		return $this->slides_subpath;

	}// getSlidesSubpath


	public function getThumbnails($subpath=null)
	{
		
		if(!$subpath && $this->slides_subpath) $subpath = $this->slides_subpath;
		if(!$subpath) $subpath = $this->getSlidesSubpath();

		$slides_path = self::getSlidesPath();
	
	
		// What are the slide collections? - the directories under the slides_path should tell us
		$thumbnail_candidates = glob($slides_path.DIRECTORY_SEPARATOR.$subpath.DIRECTORY_SEPARATOR.self::$thumbnail_directory.DIRECTORY_SEPARATOR.'*');


		$thumbnails = array();

		// Clean out directories
		// Clean off the slide path
		foreach($thumbnail_candidates as &$tc) 
		{
			$new_thumbnail = false;
			foreach(self::$possible_extensions as $pe)
			{
				if(stripos($tc,$pe)!==false)
				{
					$new_thumbnail = array
					(
						'filename' => str_replace($slides_path.DIRECTORY_SEPARATOR.$subpath.DIRECTORY_SEPARATOR.self::$thumbnail_directory.DIRECTORY_SEPARATOR, '', $tc),
						'thumbnail_server_filepath' => $tc,
					);
					$new_thumbnail['thumbnail_web_filepath'] = self::$slides_webpath.($this->slides_subpath ? $this->slides_subpath.DIRECTORY_SEPARATOR : '').self::$thumbnail_size_alias.DIRECTORY_SEPARATOR.$new_thumbnail['filename'];
					$new_thumbnail['original_web_filepath'] = self::$slides_webpath.($this->slides_subpath ? $this->slides_subpath.DIRECTORY_SEPARATOR : '').$new_thumbnail['filename'];
					break;
				}
			}
			if($new_thumbnail) $thumbnails[] = $new_thumbnail;
		}

		//echo 'DEBUG There are '.count($thumbnails)." thumbnails: <br/>\n";
		//print_r($thumbnails);
		
		return $thumbnails;

	}// getThumbnails


	public function getSlides($subpath=null)
	{

		
		if(!$subpath && $this->slides_subpath) $subpath = $this->slides_subpath;
		if(!$subpath) $subpath = $this->getSlidesSubpath();

		$this->slides_subpath = $subpath;	

		$slides_path = self::getSlidesPath();
		
		// What are the slide collections? - the directories under the slides_path should tell us
		$slide_candidates = glob($slides_path.DIRECTORY_SEPARATOR.$subpath.DIRECTORY_SEPARATOR.'*');

		$slides = array();

		// Clean out directories
		// Clean off the slide path
		foreach($slide_candidates as &$sc) 
		{
			foreach(self::$possible_extensions as $pe)
			{
				if(stripos($sc,$pe)!==false)
				{
					$slides[] = str_replace($slides_path.DIRECTORY_SEPARATOR.$subpath.DIRECTORY_SEPARATOR, '', $sc);
					break;
				}
			}
		}

		return $slides;

	}// getSlides


	public function setTemplate($template)
	{
		$this->template = $template;
	}// setTemplate


	public function getContent($template=false, $replacements=false)
	{

		// Check if the first parameter is actually just the collection alias
		$pos = stripos($template, ContentBuilder::getTemplateExtension());
		if($pos===false || $pos!==(strlen($template)-strlen(ContentBuilder::getTemplateExtension())))
		{
			//echo 'DEBUG This is NOT a template? '.$template.' Cause pos: '.$pos.' != '.(strlen($template)-strlen(ContentBuilder::getTemplateExtension()))."\n";
			$collection_alias = $template;
			$template = $this->template; //.= '.'.ContentBuilder::getTemplateExtension();
		}
		else $collection_alias = substr($template, 0, -strlen(ContentBuilder::getTemplateExtension()));
		
		//echo 'DEBUG collection alias: '.$collection_alias."\n";
		// We need to know the collection alias in order to determine the set of slides (aka the "slide collection") to work with
	
		if(!$template) $template = $this->template;

		$slides = $this->getSlides($this->slides_subpath);	

		//echo 'DEBUG slides<pre>';	
		//print_r($slides);
		//echo '</pre>';	
	
		if(count($slides)>1) 
		{
			// We need to make the slide paths webroot-relative
			if(self::$slides_webpath)
			{
				foreach($slides as &$s)
				{
					$s = self::$slides_webpath.($this->slides_subpath ? $this->slides_subpath.DIRECTORY_SEPARATOR : '').$s;
				}
			}

			$replacements['slides'] = $slides;

			return self::$cb->getContent($template, $replacements);
		}
		else return false;

	}// getContent


	public function getErrors()
	{
		return $this->errors;
	}// getErrors


}// class


?>
