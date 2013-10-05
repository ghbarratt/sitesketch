<?php


	class ContentBuilder
	{

		/// VARS ///	
		static $template_extension = 'tpl';
		static $template_directory='templates';
		static $end_of_line = "\n";
		static $indentation_unit = "\t";
		static $singularize_plural_magic_keys = false;

		static $site_path;
		static $web_directory = 'web';
		static $site_web_path;
		static $sitesketch_path;

		public $line_prefix = '';
		public $replacements;

		public $remove_leftover_tags = true;
		public $use_smart_indentation = false; // faster

		protected $template;
		protected $content;

		protected $has_placed_replacements = false;
		protected static $tag_bang = '!';
		protected static $tag_prefix = '~';
		protected static $tag_postfix = '~';
		protected $eval_open_tag = '~eval(';
		protected $eval_close_tag = ')eval~';
		
		protected static $tag_open_prefix = '~';
		protected static $tag_open_postfix = '{';
		protected static $tag_close_prefix = '}';
		protected static $tag_close_postfix = '~';
		
		protected $errors = array();


		/// FUNCTIONS ///
		public function __construct($template='', $replacements=false, $template_directory=false, $line_prefix=false, $indentation_unit=false, $tag_prefix=false, $tag_postfix=false)
		{

			return $this->reset($template, $replacements, $template_directory, $line_prefix, $indentation_unit, $tag_prefix, $tag_postfix);

		}// constructor

		
		public function reset($template='', $replacements=false, $template_directory=false, $line_prefix=false, $indentation_unit=false, $tag_prefix=false, $tag_postfix=false)
		{

			$this->setTemplate($template);
			if($replacements) $this->replacements = $replacements;
			if($template_directory) self::$template_directory = $template_directory;
			if($line_prefix) $this->line_prefix = $line_prefix;
			if($indentation_unit) $this->indentation_unit = $indentation_unit;
			
			return true;

		}// reset

	

		static public function getSitesketchPath()
		{
			if(self::$sitesketch_path) return self::$sitesketch_path;

			// What is the directory of THIS file?
			self::$sitesketch_path = dirname(__FILE__);
	
			//echo 'DEBUG sitesketch_path: '.self::$sitesketch_path."<br/>";

			return self::$sitesketch_path;
		}// getSitesketchPath

	
		static public function getSitePath()
		{

			if(isset(self::$site_path) && strlen(self::$site_path)) return self::$site_path;
			
			// Attempt to use the first web directory on current path, otherwise use the web ("document") root
			$web_pos = strrpos($_SERVER['SCRIPT_FILENAME'], self::$web_directory);
			if($web_pos!==false) self::$site_web_path = substr($_SERVER['SCRIPT_FILENAME'], 0, $web_pos+strlen(self::$web_directory));
			else if(isset($_SERVER['DOCUMENT_ROOT'])) self::$site_web_path = $_SERVER['DOCUMENT_ROOT'];

			// Usually site path is one directory up from the site web path	
			self::$site_path = dirname(self::$site_web_path); 
			
			//echo 'DEBUG site path: '.self::$site_path."<br/>";
			
			return self::$site_path;

		}// getSitePath
		

		static public function getTemplateExtension()
		{
			return self::$template_extension;
		}// getTemplateExtension


		public function setTemplate($template)
		{
		
			if(!$template) return false;
			
			$template = str_replace('.'.self::$template_extension.'.'.self::$template_extension, '.'.self::$template_extension, $template);
	
			$this->has_placed_replacements = false;

			$filepaths_to_try = array
			(
				$template,
				self::getSitePath().'/'.$template,
				self::getSitePath().'/'.self::$template_directory.'/'.$template,
				self::getSitesketchPath().'/'.$template,
				self::getSitesketchPath().'/'.self::$template_directory.'/'.$template
			);

			$template_found = false;
			foreach($filepaths_to_try as $fi=>$filepath)
			{
				
				//echo 'DEBUG text: '.self::$template_extension."<br/>";
				if(is_file($filepath)) 
				{
					$this->template = $filepath;
					$template_found = true;
					//echo 'DEBUG '.$fi.'. '.$filepath." FOUND! <br/>\n";
				}
				//else echo 'DEBUG '.$fi.'. '.$filepath." NOT FOUND <br/>\n";
			}

			if(!$template_found) $this->errors[] = 'The template file: "'.$template.'" could not be found. Path is: '.get_include_path(); 

			$this->content = null;

		}// setTemplate
		

		public function useSmartIndentation($use=true)
		{
			$this->use_smart_indentation = $use;
		}// userSmartIndetation


		public function adjustIndentation($amount = false)
		{

			if ($amount > 0)
 			{
				$this->line_prefix = str_pad($this->line_prefix, $amount, $this->indentation_unit, STR_PAD_LEFT);
			}
			else if ($amount < 0)
			{
				$this->line_prefix = str_replace($this->indentation_unit, '', $this->line_prefix, $amount);
			}
			else return preg_match_all('/'.$this->indentation_unit.'/',$this->line_prefix);
			
		}// adjustIndentation


		public function adjustLinePrefix($amount)
		{
			return $this->adjustIndentation($amount);
		}// adjustLinePrefix alias for adjustIndentation


		public function setReplacements($replacements)
		{
			$this->replacements = $replacements;
		}// setReplacements


		public function addReplacements($replacements)
		{
			$this->replacements = array_merge($replacements, $this->replacements);
		}// addReplacements


		public function addReplacement($tag, $value)
		{
			$this->replacements = array_merge(array($tag=>$value), $this->replacements);
		}// addReplacement


		public function getTemplateContent($template=false)
		{
			if(!$template) $template = $this->template;

			// Determine if the template param is a filename,
			$is_filename = false;
			
			// Does the template have a template extension in it and are there no < ? Then is must be a filename
			if(strpos($template, '.'.self::$template_extension) && strpos($template, '<')===false) $is_filename = true; 

			if($is_filename) return $this->loadTemplateContent($template);
			else return $template;

		}// getTemplateContent


		public function getTemplateWithReplacementsContent($template=false, $replacements=false)
		{
			
			
			if(!$template) $template = $this->template;
			
			$template_content = $this->getTemplateContent($template);
			
			if(!$replacements || !is_array($replacements))
			{
				if(isset($this->replacements) && is_array($this->replacements)) $replacements = $this->replacements; 
				else $replacements = array();
			}
			//if(!$tag_prefix) $tag_prefix = self::$tag_prefix;
			//if(!$tag_postfix) $tag_postfix = self::$tag_postfix;
			
			//echo 'DEBUG Entered getTemplateWithReplacementContent with template: <pre>';
			//print_r($template);
			//echo '</pre>';
 		 	//echo 'AND	replacements: <pre>';
			//print_r($replacements);
			//echo "</pre><br/>\n";

			//echo 'DEBUG replacements <pre>';
			//print_r($replacements);
			//echo '</pre>';


			$content = $template_content;

			// Find regular tags
			$pattern = '/'.self::$tag_open_prefix.'('.self::$tag_bang.'?[A-z0-9,_]+)'.self::$tag_open_postfix.'/';
			//echo 'DEBUG pattern:'.$pattern."<br/>\n";
			$match_count = preg_match_all($pattern, $content, $matches);	

			if($match_count)
			{
				$embed_tags = $matches[1];
				//echo 'DEBUG NEW embed_tag: '.implode(', ', $matches[1]).'<br/>';

				//echo 'DEBUG embed_tags <pre>';
				//print_r($embed_tags);
				//echo '</pre>';
			}
			else $embed_tags = array();

			$leftover_embed_tags = array();

			//echo 'DEBUG The template content:<pre>'.$template_content.'</pre>';

			if(isset($replacements) && is_array($replacements))
			{

				foreach($embed_tags as $embed_tag_index=>$tag)
				{
	
					$no_bang_tag = $tag;

					if(strpos($tag, self::$tag_bang)!==false)
					{
						// Dealing with an embed tag with a bang

						$no_bang_tag = substr($tag, strlen(self::$tag_bang));
						//echo 'DEBUG Currently considering embed tag:'.$no_bang_tag."<br/>\n";

						if
						(
							!isset($replacements[$no_bang_tag])
							||
							!$replacements[$no_bang_tag]
						)
						{
							//echo 'YES - because the tag is missing or false we need to fill in<br/>';
							$replacements[$tag] = $replacements;
							//echo 'DEBUG <pre>';
							//print_r($replacements);
							//echo '</pre>';
						}
					}
					else // no bang
					{
						if($this->remove_leftover_tags && !in_array($tag, array_keys($replacements)))
						{
							$replacements[$tag] = '';
						}
					}


					if(in_array($no_bang_tag, array_keys($replacements)))
					{
						$value = false;
						if(isset($replacements[$tag])) $value = $replacements[$tag];
						
						
						// if we are dealing with an array (we should be), merge the original replacements to each element
						if(is_array($value))
						{
							foreach($value as $vi=>$vv)
							{
								if(is_array($vv)) $value[$vi] = array_merge($replacements, $vv);
								else 
								{
									$original_vv = $vv;
									$vv = $replacements;
									$vv[] = $original_vv;
								}
							}
						}

						//echo 'DEBUG found an embed no_bang_tag:'.$no_bang_tag."<br/>\n";
						// Here we recursively call this function with the inside of the no_bang_tag(s) that match(es)

						// What do the no_bang_tags look like?
						$open_tag = self::$tag_open_prefix.$tag.self::$tag_open_postfix;
						$close_tag = self::$tag_close_prefix.$tag.self::$tag_close_postfix;
						//echo 'DEBUG Now searching through content:<pre>';
 					 	//print_r($content);
						//echo '</pre> for open_tag: '.$open_tag." and close_tag: ".$close_tag."</pre><br/>\n";

						$offset = 0;
						$cut_start = strpos($content, $open_tag, $offset);
						while($offset<strlen($content) && $cut_start!==false)
						{
							$offset = $cut_start+strlen($open_tag);
							$cut_end = strpos($content, $close_tag, $offset);
							if($cut_end!==false)
							{
								$offset = $cut_end+strlen($close_tag);
								$embed_template = substr($content, ($cut_start+strlen($open_tag)), ($cut_end-($cut_start+strlen($open_tag))));




								//echo 'DEBUG Now attempting to fill template: <pre>'.$embed_template."\nwith:\n";
								//print_r($value);
								//echo "</pre><br/>\n";
									
								$filled_template = '';
								
								$content_to_replace = substr($content, $cut_start, ($cut_end+strlen($close_tag)-$cut_start));
								//echo 'DEBUG Here is the content to replace: <pre>'.$content_to_replace."</pre><br/>\n";
	
								if(is_array($value))
								{
									$is_array_numeric = true;
									// How to test if array is numeric?
									foreach(array_keys($value) as $key) if(!is_numeric($key)) $is_array_numeric = false;
									
									if($is_array_numeric)
									{
										//echo 'DEBUG array is numeric so we might need to fill the embed template multiple times';
										//echo 'DEBUG <pre>';
										//print_r($value);
										//echo '</pre>';
										reset($value);
										$first_key = key($value);
										if(!empty($value[$first_key]) && !is_array($value[$first_key]))
										{
											if(self::$singularize_plural_magic_keys) $new_key = self::singularize($tag);
											else $new_key = $tag;
											$new_value = array();
											foreach($value as $temp_index=>$embed_replacements)
											{
												$new_value[$temp_index] = array($new_key=>$embed_replacements);
											}
											$value = $new_value;
											unset($new_value);
										}
										foreach($value as $temp_index=>$embed_replacements)
										{
											if(!array_key_exists('index', $embed_replacements)) $embed_replacements['index'] = $temp_index;

											$filled_template .= $this->getTemplateWithReplacementsContent($embed_template, $embed_replacements);
										}	
									}
									else $filled_template = $this->getTemplateWithReplacementsContent($embed_template, $value);
								}
								else // the value associated is not an array
								{
									//echo 'DEBUG the value for '.$tag.' is not an array: '.$value."<br/>\n";
									if($value)
									{
										// Use the whole current set of replacements
										$filled_template = $this->getTemplateWithReplacementsContent($embed_template, $replacements);
									}
								}

								//echo 'DEBUG Here is the filled embed template: <pre>'.$filled_template."</pre><br/>\n";
									
								$content = str_replace($content_to_replace, $filled_template, $content);

							}// closing embed tag found	
	
							if($offset<strlen($content)) $cut_start = strpos($content, $open_tag, $offset);
							else $cut_start = false;

						}// while finding opening and closing embed tags

					}
					else $leftover_embed_tags[] = $tag;
				}	


				// Now do the regular tags ...
				$pattern = '/'.self::$tag_prefix.'([A-z0-9,_]+)'.self::$tag_postfix.'/';
		
				//echo 'DEBUG pattern:'.$pattern."<br/>\n";

				$match_count = preg_match_all($pattern, $content, $matches);	

				if($match_count)
				{
					$replacement_tags = $matches[1];
					//echo 'DEBUG embed_tags <pre>';
					//print_r($embed_tags);
					//echo '</pre>';
				}
				else $replacement_tags = array();

				foreach ($replacement_tags as $tag)
				{
					if($this->remove_leftover_tags && (!isset($replacements[$tag]) || !in_array($tag, array_keys($replacements)) || $replacements[$tag]===null))
					{
						//echo 'DEBUG tag: '.$tag." is being set to blank <br/>";
						$replacements[$tag] = '';
					}
					//else echo 'DEBUG tag: '.$tag." is being set to ".$replacements[$tag]."<br/>";


					if(isset($replacements[$tag]))
					{
						$value = $replacements[$tag];

						//echo 'DEBUG Attempting to replace tag:'.$tag.' with '.$value."<br/>\n";
						//echo 'DEBUG The template content is <pre>';
						//print_r($template_content);
						//echo '</pre>';
						if(is_array($value)) 
						{
							$new_value = '';

							if(count($value))
							{
								$temp_keys = array_keys($value);
								if(is_array($value[$temp_keys[0]])) foreach($value as $v) $new_value .= $v;
							}
							else $new_value = implode(', ', $value);
							$value = $new_value;
						}
							
						if(substr_count($value, self::$end_of_line))
						{
							$delete_end_characters = array(' ', "\n", "\r", "\t");
							$cutoff_pos = strlen($value)-1; 
							if(strlen(trim($value)))
							{
								while(in_array(substr($value,$cutoff_pos,1), $delete_end_characters)) 
								{
									//echo 'DEBUG while in_array('.substr($value,$cutoff_pos,1).', '.$delete_end_characters.')';
									$cutoff_pos--;
								}
								$value = substr($value,0,$cutoff_pos+1);
							}
							
							if($this->use_smart_indentation)
							{
								$offset = 0;
								while($offset<strlen($content) && $cut_start = strpos($content, $tag, $offset))
								{
									$offset = $cut_start+strlen($tag);
									
									$indentation_end = $cut_start-1;
									if($indentation_end>0)
									{
										$indentation_offset = -(strlen($content)-($indentation_end));

										$indentation_start = strrpos($content, self::$end_of_line, $indentation_offset)+strlen(self::$end_of_line);
								
										$indentation_material = substr($content, $indentation_start, ($indentation_end-$indentation_start));
										//echo 'DEBUG indentation_material: '.$indentation_material."<br/>\n";
	
										$indentation_level = substr_count($indentation_material, self::$indentation_unit);
									
										//echo 'DEBUG the indentation_level*strlen(unit)='.$indentation_level*strlen(self::$indentation_unit).' == strlen(material)='.strlen($indentation_material)." ?<br/>\n";
										if($indentation_level*strlen(self::$indentation_unit)==strlen($indentation_material))
										{
											//echo 'DEBUG the indentation_level for tag '.$tag.' is '.$indentation_level."<br/>\n";
											// Add indentation to every line except the first
											$value = str_replace(self::$end_of_line, self::$end_of_line.$indentation_material, $value);
										}
									}
								}									
							}	
						}
						
						$content = str_replace(self::$tag_prefix.$tag.self::$tag_postfix, $value, $content);	
						
						//echo 'DEBUG The end result is <pre>';
						//print_r($content);
						//echo '</pre>';
					}
				}
			}
			//else echo "DEBUG Replacements is not an array or is blank!<br/>\n";


			//foreach($leftover_embed_tags as $tag)
			//{
				//echo 'DEBUG This was leftover: '.$tag."<br/>\n";
			//}
			
			// Find eval tags
			$pattern = '/'.str_replace(array('(',')'), array('\(','\)'), $this->eval_open_tag).'(.+?)'.str_replace(array('(',')'), array('\(','\)'), $this->eval_close_tag).'/';
			//echo 'DEBUG pattern for eval <pre>'.$pattern."</pre><br/>\n";
			$match_count = preg_match_all($pattern, $content, $matches);	


			//echo 'DEBUG Match count: <pre>';
			//print_r($match_count);
			//echo '</pre>';

			if($match_count)
			{
				$to_replace = $matches[0];
				$to_evaluate = $matches[1];
				
				//echo 'DEBUG matches <pre>';
				//print_r($matches);
				//echo '</pre>';

				foreach($to_evaluate as $index=>$te)
				{
					$te = $this->getTemplateWithReplacementsContent($te, $replacements);

					//echo 'DEBUG replacements <pre>';
					//print_r($replacements);
					//echo '</pre>';
					
					// Now replace tags on the "expression" inside the eval tags
					//echo 'DEBUG te '.$te."<br/>\n";

					$temp_result = '';
					eval('$temp_result = ('.$te.');');

					// Now evaluate the expression
					$content = str_replace($to_replace[$index], $temp_result, $content);
				}
			}
			else $eval_tags = array();


			$this->has_placed_replacements = true;

			return $content;

		}// getTemplateWithReplacementsContent


		public function getContent($template=false, $replacements=false)
		{

			//echo "DEBUG Entered getContent with replacements <pre>";
			//print_r($replacements);
			//echo '<pre>';

			if(isset($this->content) && strlen($this->content) && !$template && !$replacements) return $this->content;

			$needs_reset = false;

			if(!$template) $template = $this->template;
			else $needs_reset = true;

			if(!$replacements) $replacements = $this->replacements;
			else $needs_reset = true;
			
			//echo 'DEBUG template at this point is: '.$this->template;

			if($needs_reset) 
			{
				$this->reset($template, $replacements);
				$template = $this->template;
				$replacements = $this->replacements;
			}

			$content = $this->getTemplateWithReplacementsContent($template, $replacements);

			if(!isset($this->content) || !$this->content) $this->content = $content;

			//echo 'DEBUG content with replacements is:<br/>'.$content;
			return $content;

		}// getContent

		
		public function loadTemplateContent($filepath, $template_directory=false)
		{

			// Fix the filename?
			if(!is_file($filepath))
			{
				if(!$template_directory) $template_directory = self::$template_directory;
				if (is_file(($template_directory ? $template_directory.'/' : '').$filepath)) $filepath = ($template_directory ? $template_directory.'/' : '').$filepath;
			}

			return file_get_contents($filepath, FILE_USE_INCLUDE_PATH);
		
		}// loadTemplateContent


		public function hasErrors()
		{
			return count($this->errors);
		}// hasErrors


		public function getErrors()
		{
			return $this->errors;
		}// getErrors


		public function render($template=false, $replacements=false)
		{
			echo $this->getContent($template, $replacements);
		}// render

		public function write()
		{
			$this->render();
		}// write (alias for render)


		// TODO Move this function out of this class and into a locale class
		/**
		* Singularizes English nouns.  Credit: http://www.kavoir.com/2011/04/php-class-converting-plural-to-singular-or-vice-versa-in-english.html
		*
		* @access public
		* @static
		* @param  string $word  English noun to singularize
		* @return string Singular noun.
		*/
		public static function singularize($word)
		{
			$singular = array 
			(
				'/(quiz)zes$/i' => '\1',
				'/(matr)ices$/i' => '\1ix',
				'/(vert|ind)ices$/i' => '\1ex',
				'/^(ox)en/i' => '\1',
				'/(alias|status)es$/i' => '\1',
				'/([octop|vir])i$/i' => '\1us',
				'/(cris|ax|test)es$/i' => '\1is',
				'/(shoe)s$/i' => '\1',
				'/(o)es$/i' => '\1',
				'/(bus)es$/i' => '\1',
				'/([m|l])ice$/i' => '\1ouse',
				'/(x|ch|ss|sh)es$/i' => '\1',
				'/(m)ovies$/i' => '\1ovie',
				'/(s)eries$/i' => '\1eries',
				'/([^aeiouy]|qu)ies$/i' => '\1y',
				'/([lr])ves$/i' => '\1f',
				'/(tive)s$/i' => '\1',
				'/(hive)s$/i' => '\1',
				'/([^f])ves$/i' => '\1fe',
				'/(^analy)ses$/i' => '\1sis',
				'/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
				'/([ti])a$/i' => '\1um',
				'/(n)ews$/i' => '\1ews',
				'/s$/i' => '',
			);
		
			$uncountable = array('equipment', 'information', 'rice', 'money', 'species', 'series', 'fish', 'sheep');
		
			$irregular = array(
				'person' => 'people',
				'man' => 'men',
				'child' => 'children',
				'sex' => 'sexes',
				'move' => 'moves')
			;
		
			$lowercased_word = strtolower($word);
			foreach ($uncountable as $_uncountable)
			{
				if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable)
				{
					return $word;
				}
			}
		
			foreach($irregular as $_plural=> $_singular)
			{
				if(preg_match('/('.$_singular.')$/i', $word, $arr)) 
				{
					return preg_replace('/('.$_singular.')$/i', substr($arr[0],0,1).substr($_plural,1), $word);
				}
			}

			foreach ($singular as $rule => $replacement) {
				if (preg_match($rule, $word)) {
					return preg_replace($rule, $replacement, $word);
				}
			}

			return $word;

		}// singularize


	}// class ContentBuilder

?>
