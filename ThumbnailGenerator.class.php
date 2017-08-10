<?php		

	// Thumbnail Generator
	// by Glen H. Barratt of AdeptSites
	// Part of SiteSketch, a framework from AdeptSites
	// http://adeptsites.com
	// ghbarratt@adeptsites.com 


	class ThumbnailGenerator
	{

		private static $default_images_directory = 'images';

		// Example: /var/www/websites/littleharts/web/images/slideshows/ss1

		public $working_web_path; // /images/slideshows/ss1
		public $webroot_path; // /var/www/websites/littleharts/web/ usually = $_SERVER['DOCUMENT_ROOT']
		public $working_path; // the (full) path of the directory where we will make thumbnails (/var/www/websites/littleharts/web/images/slideshows/ss1)
		
		public $user_group = 'dev';
		public $set_user_group = true;

		protected $images;

		protected $thumbnail_sizes;

		// Example of what thumbnail_sizes could look like
		//protected $thumbnail_sizes = array
		//(
			//'small' => array
			//(	
				//'width'     => '80',
 			 	//'method'    => 'width'
			//),
			//'large' => array
			//(
				//'width'     => '800',
				//'height'    => '600',
 			 	//'method'    => 'max_both'
			//)
		//);


		public function __construct($working_web_path, $thumbnail_sizes=false)
		{

			if($working_web_path[strlen($working_web_path)-1]=='/') $working_web_path = substr($working_web_path, 0, strlen($working_web_path)-1);	
			if(!$this->resolveWorkingPath($working_web_path)) return false;
			else $this->working_web_path = $working_web_path;

			if(isset($thumbnail_sizes) && is_array($thumbnail_sizes)) $this->setThumbnailSizes($thumbnail_sizes);


		}// constructor


		public function resolveWorkingPath($working_web_path=false)
		{


			if(!$this->working_path || $this->working_web_path!=$working_web_path)
			{

				if(!isset($this->webroot_path) || !$this->webroot_path) $this->resolveWebrootPath();

				if($working_web_path[0]=='/') $working_web_path = substr($working_web_path, 1);

				$working_path = $this->webroot_path.'/'.$working_web_path;
				
				if(is_dir($working_path)) $this->working_path = $working_path;
				else return false;
				
			}

			return $this->working_path;

		}// resolveWorkingPath


		public function setThumbnailSizes($thumbnail_sizes=false)
		{

			if($thumbnail_sizes && is_array($thumbnail_sizes)) $this->thumbnail_sizes = $thumbnail_sizes;

			//foreach($this->thumbnail_sizes as &$ts)
			//{
				//if((!isset($ts['prefix']) || !$ts['prefix']) && (!isset($ts['postfix']) || !$ts['postfix']))
				//{
					//if((!isset($ts['directory']) || !$ts['directory']) && isset($ts['alias']) && $ts['alias']) $ts['directory'] = $ts['alias'];
					//if((!isset($ts['alias']) || !$ts['alias']) && isset($ts['directory']) && $ts['directory']) $ts['alias'] = $ts['directory'];
				//}
			//}

		}// setThumbnailSizes


		public function addThumbnailSize($thumbnail_size)
		{
			if(is_array($thumbnail_size) && isset($thumbnail_size['method']))
			{
				$this->thumbnailSizes = array_merge($this->thumbnail_sizes, $thumbnail_size);
				return true;
			}
			else return false;

		}// addThumbnailSize


		public function resolveWebrootPath()
		{

			if(!is_dir($this->webroot_path))
 			{
 				if(isset($_SERVER['DOCUMENT_ROOT']) && is_dir($_SERVER['DOCUMENT_ROOT'])) $this->webroot_path = $_SERVER['DOCUMENT_ROOT'];
			}

			//if(substr($this->webroot_path, strlen($this->webroot_path)-1)!='/') $this->webroot_path .= '/';
			
			return $this->webroot_path;

		}// resolveWebrootPath


		public function isImage($filepath)
		{
			
			$filename = substr($filepath, (strrpos($filepath, '/')+1));

			$extension = trim(strtolower(strrchr($filename, '.')));
			
			switch(strtolower($extension))
			{
				case '.jpg':
				case '.gif':
				case '.png':
				case '.bmp':
				case '.jpeg':
					return true;
				break;
				default:
				break;
			}

			//echo 'DEBUG '.$filepath." is not an image?<br/>\n";	
			return false;

		}// isImage


		public function isThumbnail($filepath)
		{

			$filename = substr($filepath, (strrpos($filepath, '/')+1));

			$answer = false;

			if(isset($this->thumbnail_sizes))
			{	
				foreach($this->thumbnail_sizes as $ts_alias=>$ts)
				{
					if
					(
						(isset($ts['prefix']) && $ts['prefix'] && strpos($filename, $ts['prefix'])!==false)
 						||
						(isset($ts['postfix']) && $ts['postfix'] && strpos($filename, $ts['postfix'])!==false)
						||
						(isset($ts['directory']) && $ts['directory'] && strpos($filepath, '/'.$ts['directory'].'/')!==false) 
						||
						(isset($ts_alias) && $ts_alias && strpos($filepath, '/'.$ts_alias.'/')!==false) 
					)	
					{
						echo 'DEBUG ts<pre>';
						print_r($ts);
						echo '</pre>';
						echo 'DEBUG filepath:'.$filepath."<br/>\n";
						$answer = true;
						break; // out of foreach ts
					}
				}
			}

			//echo 'DEBUG '.$filepath." is a thumbnail ? = ".$answer."<br/>\n";	
			return $answer;

		}// isThumbnail


		private function resolveImages($working_web_path=false, $do_sort=true)
		{
			
			if(!$this->images || !is_array($this->images) || ($working_web_path && $working_web_path!=$this->working_web_path))
			{

				$this->images = array();

				if(!$working_web_path && $this->working_web_path) $working_web_path = $this->working_web_path; 
				else return false;
				

				if(!$this->working_path || $this->working_web_path!=$working_web_path)
				{
					$this->resolveWorkingPath($working_web_path);
				}

				//echo 'DEBUG working_path:'.$this->working_path."<br/>\n";

				$filenames = array();
				$dp = opendir($this->working_path);
				while($filename = readdir($dp))
				{
					$web_filepath = $working_web_path.'/'.$filename;
					if
					(
						$this->isImage($filename) 
						&& 
						!$this->isThumbnail($web_filepath) 
						&& 
						is_file($this->webroot_path.$web_filepath)
					)
					{
						$filenames[] = $filename;	
					}	
				}
				closedir($dp);

				if(is_array($filenames) && count($filenames))
				{
					//print_r($filenames);
					if($do_sort) sort($filenames);
					//print_r($filenames);

					foreach($filenames as $filename)
 					{
	
						$web_filepath = $working_web_path.'/'.$filename;
						//echo 'DEBUG Trying '.$web_filepath."<br/>\n";
						$temp_image = array();
						if(is_file($this->webroot_path.$web_filepath))
						{
							$temp_image_data = @getimagesize($this->webroot_path.$web_filepath, $temp_extended_image_data);
						}
						$new_image_data = array();
						$new_image_data['web_path'] = $working_web_path;
						$new_image_data['filename'] = $filename;
						$new_image_data['width'] = $temp_image_data[0];
						$new_image_data['height'] = $temp_image_data[1];
						$new_image_data['type'] = $temp_image_data[2];
						if(isset($temp_image_data['bits'])) $new_image_data['bits'] = $temp_image_data['bits'];
						if(isset($temp_image_data['channels'])) $new_image_data['channels'] = $temp_image_data['channels'];
	
						//echo 'DEBUG image data <pre>';
						//print_r($temp_image_data);
						//echo '</pre>';
	
						$temp_image_data = array_merge($new_image_data, $temp_extended_image_data);
	
						foreach($temp_image_data as $tid_index=>$tid)
						{
							$temp_image[$tid_index] = $tid;
						}
	
						$this->images[] = $temp_image;
						//else echo 'DEBUG NOT A FILE? '.$this->webroot_path.$web_filepath."<br/>\n";
						//else echo 'NOT AN IMAGE OR IS A THUMBNAIL? '.$web_filepath."<br/>\n";
					}	
				}// filenames

			}// should make images array?

			return $this->images;

		}// resolveImages


		public function createThumbnail($original_filepath, $thumbnail_filepath, $options)
		{

			ini_set('memory_limit', '100M'); // Increase memory
			set_time_limit(3600); // Increase time limit (in seconds)

			if(is_string($options) && isset($this->thumbnail_sizes[$options])) $options = $this->thumbnail_sizes[$options];

			$original_size = getimagesize($original_filepath);

			//echo "DEBUG original_size<pre>";
			//print_r($original_size);
			//echo "</pre>";


			$original_width = $original_size[0];
			$original_height = $original_size[1];
			
			$adjusted_original_width = $original_size[0];
			$adjusted_original_height = $original_size[1];
			
			if(isset($options['width'])) $thumbnail_width = $options['width'];
			if(isset($options['height'])) $thumbnail_height = $options['height'];
			
			if($original_width < $original_height) $orientation = 'portrait';
			else $orientation = 'landscape';

			$fitting = false;
			$original_aspect_ratio = $original_width/$original_height; // larger is wider
			$ideal_thumbnail_aspect_ratio = $thumbnail_width/$thumbnail_height; // larger is wider
			if($original_aspect_ratio > $ideal_thumbnail_aspect_ratio) $fitting = 'wide'; // the original is wider than the ideal
			else if($original_aspect_ratio < $ideal_thumbnail_aspect_ratio) $fitting = 'tall'; // the original is taller than the ideal


			if(isset($options['method'])) $method = strtolower($options['method']);
			else $method = 'fit'; // default method
			
			$src_x = 0;
			$src_y = 0;

			// smaller and larger methods are the same as width and height methods, except determined by fitting
			if($method=='smaller')
			{
				if($fitting=='tall') $method = 'height';
				else $method = 'width';
			}
			else if($method=='larger')
			{
				if($fitting=='tall') $method = 'width';
				else $method = 'height';
			}

			if($method=='width')
			{
				$thumbnail_height = $thumbnail_width * $original_height/$original_width;
			}
			else if($method=='height')
			{
				$thumbnail_width = $thumbnail_height * $original_width/$original_height;
			}
			else if
			(
				($method=='fit' && $fitting=='tall')
				||
				($method=='crop' && $fitting=='wide')
			)
			{
				$adjusted_original_width = $original_height * $thumbnail_width/$thumbnail_height;
				$src_x = ($original_width-$adjusted_original_width)/2;
			}
			else if
			(
				($method=='crop' && $fitting=='tall')
				||	
				($method=='fit' && $fitting=='wide')
			)
			{
				$adjusted_original_height = $original_width * $thumbnail_height/$thumbnail_width;
				$src_y = ($original_height-$adjusted_original_height)/2;			
			}

			// Method "stretch" requires no param alterations


			$thumbnail_image = imagecreatetruecolor($thumbnail_width, $thumbnail_height);
			
			switch($original_size[2]) 
			{
				case 1: $original_image = imagecreatefromgif($original_filepath); break;
        case 2: $original_image = imagecreatefromjpeg($original_filepath); break;
        case 3: $original_image = imagecreatefrompng($original_filepath); break;
        default: $original_image = imagecreatefromjpeg($original_filepath); break;
			}

			if(isset($options['quality'])) $quality = $options['quality'];
			else $quality = 80;


			//echo 'DEBUG original_width: '.$original_width."<br/>\n";
			//echo 'DEBUG original_height: '.$original_height."<br/>\n";
			//echo 'DEBUG ideal_thumbnail_width: '.$ideal_thumbnail_width."<br/>\n";
			//echo 'DEBUG ideal_thumbnail_height: '.$ideal_thumbnail_height."<br/>\n";
			//echo 'DEBUG thumbnail_width: '.$thumbnail_width."<br/>\n";
			//echo 'DEBUG thumbnail_height: '.$thumbnail_height."<br/>\n";
			//echo 'DEBUG src_x: '.$src_x."<br/>\n";
			//echo 'DEBUG src_y: '.$src_y."<br/>\n";
			//exit();

			imagecopyresized($thumbnail_image, $original_image, 0, 0, $src_x, $src_y, $thumbnail_width, $thumbnail_height, $adjusted_original_width, $adjusted_original_height);
			
	
			if(imagejpeg($thumbnail_image, $thumbnail_filepath, $quality))
			{
				chmod($thumbnail_filepath, 0664);
				chgrp($thumbnail_filepath, $this->user_group);
				$result = true;
			}
			imagedestroy($thumbnail_image);
			imagedestroy($original_image);
			
			return $result;

		}// createThumbnail


		public function createThumbnails($force_creation=false)
		{

			if(!isset($this->images) || !is_array($this->images)) $this->resolveImages();

			//echo 'DEBUG images <pre>';
			//print_r($this->images);
			//echo '</pre>';

			if(!is_array($this->images)) return false;

			foreach($this->images as &$i)
			{
				foreach($this->thumbnail_sizes as $ts_alias=>$ts)
				{
					if
					(
						(isset($ts['prefix']) && $ts['prefix'])
						||
						(isset($ts['postfix']) && $ts['postfix'])
					)
					{
						$dot_pos = strrpos($i['filename'], '.');
						$filename_base = substr($i['filename'], 0, $dot_pos);
						$extension = substr($i['filename'], $dot_pos+1);
						$thumbnail_web_filepath = $i['web_path'].'/'.(isset($ts['prefix']) ? $ts['prefix'] : '').$Filename_base.(isset($ts['postfix']) ? $ts['postfix'] : '').'.'.$extension; 
					}
					else 
					{
						if(!is_dir($this->webroot_path.$i['web_path'].'/'.$ts_alias)) 
						{
							if(!mkdir($this->webroot_path.$i['web_path'].'/'.$ts_alias)) 
							{
								$username = exec('whoami');
								echo 'ERROR Could not create directory '.$this->webroot_path.$i['web_path'].'/'.$ts_alias.' as '.$username;
								return false;
							}
							//echo 'DEBUG Attempting to set permissions on folder: '.$this->webroot_path.$i['web_path'].'/'.$ts_alias."<br/>\n";
							$chmod_result = chmod($this->webroot_path.$i['web_path'].'/'.$ts_alias, 0775);
							if(!$chmod_result) echo 'DEBUG Attempt to set permissions was '.($chmod_result ? '' : 'un').'successful'."<br/>\n";
							$chgrp_result = chgrp($this->webroot_path.$i['web_path'].'/'.$ts_alias, $this->user_group);
							if(!$chgrp_result)
							{
								echo 'DEBUG Unable to set group to '.$this->user_group."<br/>\n";
								if($chmod_result) chmod($this->webroot_path.$i['web_path'].'/'.$ts_alias, 0777);
							}
						}
						$thumbnail_web_filepath = $i['web_path'].'/'.$ts_alias.'/'.$i['filename']; 
					}
					if($force_creation || !is_file($this->webroot_path.$thumbnail_web_filepath))
					{
						$this->createThumbnail($this->webroot_path.$i['web_path'].'/'.$i['filename'], $this->webroot_path.$thumbnail_web_filepath, $ts);
					}
					$i['thumbnail_web_filepaths'][$ts_alias] = $thumbnail_web_filepath;
				}
			}

		}// createThumbnails


		public function getContent($template=false, $additional_replacements=false)
		{
			return 'The Thumbnailer does not provide content';

			//if(!isset($this->images)) $this->resolveImages();
			//$this->createThumbnails();
			//echo 'DEBUG images: <pre>';
			//print_r($this->images);
			//echo '</pre>';

			//$images = $this->resolveImages();

			//echo 'DEBUG thumbnailer <pre>';
			//print_r($this);
			//echo '</pre>';

		}// getContent
		
		public function getImages()
		{
			if(!isset($this->images) || !is_array($this->images)) $this->resolveImages();
			return $this->images;
		}// getImages

	}// class Thumbnailer

?>
