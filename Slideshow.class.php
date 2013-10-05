<?		

	// Slideshow
	// by Glen H. Barratt
	// ghbarratt@megasketch.com 
	//
	// requires: Thumbnailer and ContentBuilder	

	require_once 'ThumbnailGenerator.class.php';

	class Slideshow extends ThumbnailGenerator
	{

		private static $default_slideshows_directory; // = '/images/slideshows/';
		
		public $slideshow_web_path;

		public function __construct($slideshow_web_path, $thumbnail_sizes=false, $alias=false)
		{

			$this->slideshow_web_path = $slideshow_web_path;
				
			if(!$alias) $this->alias = substr($slideshow_web_path, strrpos($slideshow_web_path, '/', -2)+1);
			else $this->alias = $alias;

			// Now call the thumbnail generator
			parent::__construct($slideshow_web_path, $thumbnail_sizes);

		}// constructor


		public function getImageCount()
		{
			if(!$this->images) $this->createThumbnails();
			return count($this->images); 
		}// getImageCount


		public function getViewportWidth()
		{
			return $this->thumbnail_sizes['viewport']['width'];
		}// getViewportWidth


		public function getViewportHeight()
		{
			return $this->thumbnail_sizes['viewport']['height'];
		}// getViewportHeight


		public function getContent($template=false, $additional_replacements=false)
		{

			require_once 'ContentBuilder.class.php';

			if(!$template) $template = 'templates/slideshow.tpl';
			if(!$this->images) $this->createThumbnails();

			//echo 'DEBUG images <pre>';
			//print_r($this->images);
			//echo '</pre>';

			// This turned out to be neccessary
			foreach($this->images as &$i)
			{
				$i['slideshow_alias'] = $this->alias;
			}

			$replacements = array
			(
				'alias'                      => $this->alias,
				'images'                     => $this->images,
 			 	'image_count'                => count($this->images),
				'thumbnail_sizes'            => $this->thumbnail_sizes,
				'viewport_width'             => $this->thumbnail_sizes['viewport']['width'],
				'viewport_height'            => $this->thumbnail_sizes['viewport']['height']
				//'initial_image_alt' => $this->images[0]['alt'],
			);
			if(isset($this->images[0]))	$replacements['initial_image_web_filepath'] = $this->images[0]['thumbnail_web_filepaths']['viewport'];

			if($additional_replacements && is_array($additional_replacements)) $replacements = array_merge($replacements, $additional_replacements); 

			$cb = new ContentBuilder($template, $replacements);

			$content = $cb->getContent();

			return $content;

		}// getContent

	}// class Slideshow

?>
