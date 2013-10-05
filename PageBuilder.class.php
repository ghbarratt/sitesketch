<?php

	// PageBuilder class - builds pages
	// 
	// part of the Sitesketch Framework
	// property of AdeptSites
	// developed by Glen H. Barratt
	 
	require_once 'ContentBuilder.class.php';


	class PageBuilder 
	{

		// CLASS VARS

		public $alias;
		public $site_alias;
		public $site_title;
		public $title = '';
		public $type = '';

		protected $doctype_alias;
		protected $uri;
		
		protected $header_content;
		protected $footer_content;
		protected $body_content;
		protected $icon;

		protected $templates;
		protected $stylesheets;
		protected $scripts;
		protected $metas;
		protected $head_extra;
		protected $body_attributes;

		protected $errors = array();

		protected $cb;
		
		protected $replacements = array();

		protected $description;
		protected $keywords;


		// CLASS FUNCTIONS 
	
		public function __construct($alias=false, $replacements=false, $type=false, $title=false)
		{

			$this->cb = new ContentBuilder();
			if($title) $this->title = $title;
			else $title = ucwords(str_replace('_', ' ', $alias));
			$this->reset($alias, $replacements, $type, $title);

		}// constructor


		public function reset($alias=false, $replacements=false, $type=false, $title=false)
		{

			global $site_alias;
			global $site_title;
			global $site_data;

			
			if($alias) $this->alias = $alias;
			if(empty($this->site_alias))
			{
				if(!empty($site_alias)) $this->site_alias = $site_alias; 
				else if(!empty($site_data) && !empty($site_data['alias'])) $this->site_alias = $site_data['alias'];
			}
			
			if(isset($site_title)) $this->site_title = $site_title; 
			else if(!empty($site_data) && !empty($site_data['title'])) $title = $site_data['title'];
			else if(isset($this->site_alias) && $this->site_alias[0]!='_') $this->site_title = ucwords(str_replace('_', ' ', $this->site_alias));


			if($title!==false) $this->title = $title;
			else if(isset($this->alias) && $this->alias[0]!='_') $this->title = ucwords(str_replace('_', ' ', $this->alias));
			else 
			{
				$url = $_SERVER['REQUEST_URI'];
				if(strpos($url,'.')!==false) $url = substr($url, 0, strpos($url, '.')); 
				while($url[0]=='/') $url = substr($url, 1); 
				while($url[strlen($url)-1]=='/') $url = substr($url, 0, strlen($url)-1); 
				$this->title = ucwords(str_replace(array('/', '_'), array(' - ', ' '), parse_url($url, PHP_URL_PATH)));
			}


			if(!$this->alias) $this->alias = str_replace(array(' - ',' '), '_', strtolower($this->title));

			//echo 'DEBUG site_title: '.$this->site_title.' title: '.$this->title.' and alias: '.$alias."<br/>\n";

			if(isset($this->site_title) && $this->site_title && isset($this->title) && $this->title) $this->title = $this->site_title.' - '.$this->title;
			else if((!isset($this->title) || !$this->title) && isset($this->site_title) && $this->site_title) $this->title = $this->site_title;  

			if($replacements && is_array($replacements)) $this->replacements = $replacements;
			
			$this->scripts = false;
			$this->stylesheets = false;
			$this->templates = array();
			if($type) $this->setType($type);
			else $this->type = false;

			if
			(
				!empty($site_data) && 
				(
					!empty($site_data['stylesheets'])
					||
					!empty($site_data['stylesheet'])
				)
			) 
			{
				if(!empty($site_data['stylesheets']))
				{
					if(is_array($site_data['stylesheets']))
					{
						$this->stylesheets = array_merge($this->stylesheets, $site_data['stylesheets']);
					}
					else $this->stylesheets[] = array('href'=>$site_data['stylesheets']);
				}
				if(!empty($site_data['stylesheet'])) $this->stylesheets[] = array('href'=>$site_data['stylesheet']);
			}

			if(!$this->stylesheets)
			{
				$document_root_path = $_SERVER['DOCUMENT_ROOT'];
				$attempt_to_add_css = array('/css/global.css', '/css/primary.css');
				if(isset($this->site_alias) && $this->site_alias) $attempt_to_add_css[] = '/css/'.$this->site_alias.'.css';
				if(isset($this->type) && $this->type) $attempt_to_add_css[] = '/css/'.$this->type.'.css';
				if(isset($this->alias) && $this->type) $attempt_to_add_css[] = '/css/'.$this->alias.'.css';
	
				foreach($attempt_to_add_css as $ac)
				{
					if(is_file($document_root_path.$ac)) $this->stylesheets[] = array('href'=>$ac);
				}
			}

			$this->header_content = false;
			$this->footer_content = false;
			$this->body_content = false;
			
		}// reset


		private function setSiteAlias($site_alias=false)
		{
			
			if($site_alias) $this->site_alias = $site_alias;
			else
			{
				$this->site_alias = str_replace('www.','',substr($_SERVER['HTTP_HOST'], 0, strrpos($_SERVER['HTTP_HOST'], '.')));
			}

			return $this->site_alias;
						
		}// setSiteAlias
		

		public function setType($type)
		{
			$this->type = $type;

			// if the type css is not present, add it
			if(!$this->hasStylesheet('/css/'.$this->type.'.css')) 
			{
				if(!is_array($this->stylesheets)) $this->stylesheets = array();
				$this->stylesheets = array_merge($this->stylesheets, array(0=>array('href'=>'/css/'.$this->type.'.css')));
			}

		}// setType


		public function setTitle($title)
		{
			$this->title = $title;
		}// setTitle


		public function setDescription($description)
		{
			$this->description = $description;
		}// setDescription


		public function hasStylesheet($href)
		{
			if(!isset($this->stylesheets) || !is_array($this->stylesheets) || !count($this->stylesheets)) return false;
			foreach($this->stylesheets as $stylesheet)
			{
				if((is_array($stylesheet) && $stylesheet['href']==$href) || $stylesheet==$href) return true; 
			}

			return false;

		}// hasStylesheet


		public function useSmartIndentation($setting=true)
		{
		
			return $this->cb->useSmartIndentation($setting);
		
		}// useSmartIndentation


		private function getMetas($alias=false, $description=false, $keywords=false, $content_type=false)
		{

			global $site_keywords;
			global $site_description;

			if(!$alias) $alias = $this->getAlias();
			if(!$description && isset($this->description)) $description = $this->description;
			if(!$description && isset($site_description)) $description = $site_description;

			if(!$keywords)
			{
				if(isset($this->keywords)) $keywords = $this->keywords;
			}

			if(isset($site_keywords)) 
			{
				if(is_array($keywords)) $keywords = array_merge($keywords, $site_keywords);
				else $keywords = $site_keywords;
			}

			if(!$content_type) $content_type = $this->getContentType();

			$metas = array();
			if($content_type) $metas[] = array('attribute'=>'http-equiv', 'attribute_value'=>'content-type', 'content'=>$content_type);
			
			if($description) $metas[] = array('attribute'=>'name', 'attribute_value'=>'description', 'content'=>$description);
			
			if($keywords) 
			{
				if(is_array($keywords))
				{
					$keywords_string = implode(', ', $keywords);
				}
				else $keywords_string = $keywords;

				$metas[] = array('attribute'=>'name', 'attribute_value'=>'keywords', 'content'=>$keywords_string);
			}

			return $metas;

		}// getMetas


		private function getHeadExtra($alias=false)
		{
			
			if(!$alias) $alias = $this->getAlias();

			// TODO? Head extra for certain aliases?

			$head_extra = '';
			if(isset($this->head_extra)) $head_extra = $this->head_extra;

			return $head_extra;

		}// getHeadExtra


		public function setIcon($icon)
		{
			$this->icon = $icon;
		}// setIcon


		public function getIcon()
		{
			global $site_data;

			if($this->icon) return $this->icon;
			else if(!empty($site_data) && !empty($site_data['icon'])) $this->icon = $site_data['icon'];
			else if(is_file($_SERVER['DOCUMENT_ROOT'].'/favicon.ico'))
			{
				$this->icon = '/favicon.ico';
				return $this->icon;
			} 
			else return false;			
		}


		public function setKeywords($keywords)
		{
			$this->keywords = $keywords;
		}// setKeywords


		public function setHeadExtra($head_extra)
		{
			$this->head_extra = $head_extra;
		}// setHeadExtra


		public function setBodyAttributes($body_attributes)
		{
			$this->body_attributes = $body_attributes;
		}// setBodyAttributes


		public function getDefaultReplacements()
		{
			
			return array
			(
				'alias' => $this->getAlias(),
				'type' => $this->type,
				'doctype_alias' => $this->doctype_alias,
				'this_year' => date('Y')
			);

		}// getDefaultReplacements


		public function getReplacements()
		{
			return array_merge($this->getDefaultReplacements(), $this->replacements);
		}// getReplacements


		public function addReplacements($replacements)
		{
			if(!is_array($this->replacements)) $this->replacements = array();
			$this->replacements = array_merge($replacements, $this->replacements);
		}// addReplacements


		public function addStylesheets($stylesheets)
		{
			if(!is_array($this->stylesheets)) $this->stylesheets = array();
			if(!is_array($stylesheets) && is_string($stylesheets)) return $this->addStylesheet($stylesheets);
			foreach($stylesheets as $s)
			{
				if(!is_array($s) && is_string($s)) $this->stylesheets[] = array('href'=>$s);
				else $this->stylesheets[] = $s;
			}
		}// addStylesheets


		public function addScripts($scripts)
		{

			//echo 'DEBUG scripts <pre>';
			//print_r($scripts);
			//echo '</pre>';

			if(!is_array($this->scripts)) $this->scripts = array();
		
			if(!is_array($scripts) && is_string($scripts)) return $this->addScript($scripts);
			else 
			{	
				foreach($scripts as $s)
				{
					if(!is_array($s) && is_string($s)) $this->scripts[] = array('src'=>$s, 'type'=>'text/javascript');
					else $this->scripts[] = $s;
				}
			}
	

		}// addScripts


		public function addReplacement($tag, $value)
		{
			$this->replacements = array_merge(array($tag=>$value), $this->replacements);
		}// addReplacement


		public function addStylesheet($href)
		{
			if(!isset($this->stylesheets) || !is_array($this->stylesheets)) $this->stylesheets = array();
			$this->stylesheets = array_merge(array(0=>array('href'=>$href)), $this->stylesheets);
		}// addStylesheet

		public function removeStylesheet($href)
		{
			$result = false;
			if(!$this->hasStylesheet($href)) return false;
			else 
			{
				foreach($this->stylesheets as $hi=>$s)
				{
					
					if($s['href']==$href)
					{
						unset($this->stylesheets[$hi]);
						$result = true;
					}
				}
			}
			return $result;	
		}// removeStylesheet


		public function addScript($src, $type='text/javascript')
		{
			if(!is_array($this->scripts)) $this->scripts = array();

			if(!is_array($src) && is_string($src)) $this->scripts[] = array('src'=>$src, 'type'=>$type);
			else $this->scripts[] = $src;
		}// addScript


		public function setDoctypeAlias($doctype_alias)
		{
			$this->doctype_alias = $doctype_alias;
			
			return $this->doctype_alias;
		
		}// setDoctypeAlias


		public function setDoctype($doctype_alias)
		{
			return $this->setDoctypeAlias($doctype_alias);
		}// setDoctype alias for setDoctypeAlias


		public function getDoctypeAlias()
		{
			if($this->doctype_alias) return $this->doctype_alias;
			else return 'xhtml1_transitional';
		}// getDoctypeAlias


		public function getContentType($doctype_alias=false)
		{
			if(!$doctype_alias) $doctype_alias = $this->getDoctypeAlias();
		
			switch($doctype_alias)
			{
				case 'html':
				case 'html_transitional':
				case 'html_loose':
				case 'html_4':
				case 'html_4_transitional':
				case 'html_4_loose':
				case 'html_4.01':
				case 'html_4.01_transitional':
				case 'html_4.01_loose':
				case 'html4':
				case 'html4_transitional':
				case 'html4_loose':
				case 'html4.01':
				case 'html4.01_transitional':
				case 'html4.01_loose':
					$content_type = 'text/html; charset=iso-8859-1';				
				break;
				default:
					$content_type = 'text/html; charset=utf-8';	
				break;
			}

			return $content_type;

		}// getContentType

		public function getDTDContent($alias=false, $doctype_alias=false)
		{

			require_once 'ContentBuilder.class.php';
			
			if(!$alias) $alias = $this->getAlias();
			if(!$doctype_alias) $doctype_alias = $this->getDoctypeAlias();

			switch ($doctype_alias)
			{
				case 'html_transitional':
				case 'html_loose':
				case 'html_4':
				case 'html_4_transitional':
				case 'html_4_loose':
				case 'html_4.01':
				case 'html_4.01_transitional':
				case 'html_4.01_loose':
				case 'html4':
				case 'html4_transitional':
				case 'html4_loose':
				case 'html4.01':
				case 'html4.01_transitional':
				case 'html4.01_loose':
					$access = ' PUBLIC';
					$declaration = ' "-//W3C//DTD HTML 4.01 Transitional//EN"';
					$link = ' "http://www.w3.org/TR/html4/loose.dtd"';
					$this->doctype_alias = 'html4_transitional';
				break;
				case 'html_strict':
				case 'html_4_strict':
				case 'html_4.01_strict':
				case 'html4_strict':
				case 'html4.01_strict':
					$access = ' PUBLIC';
					$declaration = ' "-//W3C//DTD HTML 4.01//EN"';
					$link = ' "http://www.w3.org/TR/html4/strict.dtd"';
					$this->doctype_alias = 'html4_strict';
				break;
				case 'xhtml_strict':
				case 'xhtml_1_strict':
				case 'xhtml_1.0_strict':
				case 'xhtml1_strict':
				case 'xhtml1.0_strict':
					$access = ' PUBLIC';
					$declaration = ' "-//W3C//DTD XHTML 1.0 Strict//EN"';
					$link = ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"';
					$this->doctype_alias = 'xhtml1_strict';
				break;
				case 'xhtml_transitional':
				case 'xhtml_1_transitional':
				case 'xhtml_1.0_transitional':
				case 'xhtml1_transitional':
				case 'xhtml1.0_transitional':
					$access = ' PUBLIC';
					$declaration = ' "-//W3C//DTD XHTML 1.0 Transitional//EN"';
					$link = ' "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';
					$this->doctype_alias = 'xhtml1_transitional';
				break;
				default: // default is HTML 5
					$access = '';
					$declaration = '';
					$link = '';
					$this->doctype_alias = 'html5';
				break;
			}


			if(isset($this->templates['dtd'])) $template = $this->templates['dtd'];
			else $template = ContentBuilder::$template_directory.'/dtd.'.ContentBuilder::$template_extension;

			$this->templates['dtd'] = $template;
			$this->cb->reset($template);
			$this->cb->setReplacements(array('access'=>$access, 'declaration'=>$declaration, 'link'=>$link));
			if($this->cb->hasErrors()) 
			{
				$this->errors = array_merge($this->errors, $this->cb->getErrors());
				return false;
			}
			else return $this->cb->getContent();

			return false;

		}// getDTDContent
	

		public function getScripts($scripts=false)
		{

			global $site_scripts;

			if(isset($this->scripts) && is_array($this->scripts) && count($this->scripts)) $scripts = $this->scripts;

			// Place the site scripts in first
			if(isset($site_scripts) && is_array($site_scripts) && count($site_scripts)) $scripts = array_merge($site_scripts, (is_array($scripts) ? $scripts : array()));

			if(is_array($scripts))
			{
				// Standardize the scripts array format
				foreach($scripts as &$script)
				{
					if(!is_array($script) && strpos($script, '.'))
					{
						$script_src = $script;
						$script = array();
						$script['src'] = $script_src;
						$script['type'] = 'text/javascript';
					}
					else // is array
					{
						if(!isset($script['type']) || !$script['type']) $script['type'] = 'text/javascript';
					}
				}
			}

			return $scripts;

		}// getScripts


		public function getHeadContent($alias=false, $stylesheets=false, $scripts=false, $title=false, $metas=false, $head_extra=false)
		{

			global $site_head_extra;

			if(!$alias) $alias = $this->getAlias();

			if(!$title && isset($this->title) && $this->title) $title = $this->title;

			if(!$stylesheets && isset($this->stylesheets) && is_array($this->stylesheets) && count($this->stylesheets)) $stylesheets = $this->stylesheets;
			
			if(!$scripts) $scripts = $this->getScripts();
 		 
			if(!$metas) $metas = $this->getMetas($alias);
			
			if(isset($this->head_extra)) $head_extra .= $this->getHeadExtra($alias);
			if(!empty($site_data) && !empty($site_data['head_extra'])) $head_extra .= $site_data['head_extra'];
			else if(isset($site_head_extra)) $head_extra .= $site_head_extra; // check site global

			$template = ContentBuilder::$template_directory.'/head.'.ContentBuilder::$template_extension;
			if(isset($this->templates['head'])) $template = $this->templates['head'];

			$replacements = array();
			
			if($title) $replacements['title'] = $title;

	
			$icon = $this->getIcon();
			if($icon) $replacements['icon'] = $icon;
			
			if($stylesheets) $replacements['stylesheets'] = $stylesheets;
			if($scripts) $replacements['scripts'] = $scripts;
			if($metas) $replacements['metas'] = $metas;	
			if($head_extra) $replacements['extra'] = $head_extra;	

			return $this->cb->getContent($template, $replacements);

		}// getHeadContent


		public function hasType()
		{
			if(isset($this->type) && strlen($this->type)) return $this->type;
			else return false;
		}// hasType


		public function getHeaderContent($alias=false, $template=false)
		{

			global $site_path;
			
			if(!isset($site_path)) $site_path = ContentBuilder::getSitePath();

			if($this->header_content) return $this->header_content;

			if(!$alias) $alias = $this->getAlias();
			if(!$template) $template = $site_path.ContentBuilder::$template_directory.'/'.($this->hasType() ? $this->type.'_' : '').'header.'.ContentBuilder::$template_extension;

			if(is_file($template))
			{
				$replacements = $this->getReplacements();

				$this->cb->reset($template, $replacements);

				$this->header_content = $this->cb->getContent();
			}

			return $this->header_content;

		}// getHeaderContent


		public function getFooterContent($alias=false, $template=false)
		{

			global $site_path;
			
			if(!isset($site_path)) $site_path = ContentBuilder::getSitePath();

			if(isset($this->footer_content) && strlen($this->footer_content)) return $this->footer_content;
			
			if(!$alias) $alias = $this->getAlias();
			if(!$template) $template = $site_path.ContentBuilder::$template_directory.'/'.($this->hasType() ? $this->type.'_' : '').'footer.'.ContentBuilder::$template_extension;
			

			if(is_file($template))
			{
				$replacements = $this->getReplacements();

				$this->cb->reset($template, $replacements);

				$this->footer_content = $this->cb->getContent();
			}

			return $this->footer_content;

		}// getFooterContent


		public function getBodyContent($alias=false, $attributes='')
		{
			
			if(!$alias) $alias = $this->getAlias();

			if($this->body_content) return $this->body_content;

			$replacements = $this->getReplacements();

			if(isset($this->body_attributes)) $attributes .= $this->body_attributes;

			if($this->hasType()) $inside_template = ContentBuilder::$template_directory.'/'.$this->type.'.'.ContentBuilder::$template_extension;
			else $inside_template = ContentBuilder::$template_directory.'/'.$alias.'.'.ContentBuilder::$template_extension;

			$this->cb->reset($inside_template, $replacements);

			$normal_content = $this->cb->getContent();

			$body_inside_content = $this->getHeaderContent($alias).$normal_content.$this->getFooterContent($alias);

			$replacements = array_merge(array('attributes'=>$attributes, 'inside'=>$body_inside_content), $replacements);			

			$this->cb->reset(ContentBuilder::$template_directory.'/body.'.ContentBuilder::$template_extension, $replacements);

			$this->body_content = $this->cb->getContent();

			return $this->body_content;

		}// getBodyContent


		public function getPageContent($alias=false,$attributes='')
		{
			if(!$alias) $alias = $this->getAlias();
			if(!$this->doctype_alias) $this->doctype_alias = $this->getDoctypeAlias();

			//$page_inside_content = $this->getHeadContent($alias).$this->getBodyContent($alias);

			$replacements = array
			(
				'dtd' => $this->getDTDContent($alias),
				'attributes' => '',
				'head' => $this->getHeadContent($alias),
				'body' => $this->getBodyContent($alias)
			);

			if(stripos($this->doctype_alias, 'xhtml')!==false) $replacements['attributes'] .= 'xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"';

			$replacements = array_merge($replacements, $this->getReplacements());			
			
			$this->cb->reset(ContentBuilder::$template_directory.'/page.'.ContentBuilder::$template_extension, $replacements);

			$content = $this->cb->getContent();

			return $content;

		}// getPageContent

		public function getHTMLContent($alias=false, $attributes='')
		{
			return $this->getPageContent($alias, $attributes);
		}// getHTMLContent alias for getPageContent



		public function getDomain()
		{
			if($this->domain) return $this->domain;
			
			if((isset($_SERVER['HTTP_HOST']))&&($_SERVER['HTTP_HOST']!='')) $domain = $_SERVER['HTTP_HOST'];
			else $domain = $_SERVER['SERVER_NAME'];
		}// getDomain

		
		public function getAlias()
 		{
			
			if($this->alias) return $this->alias;

			$alias = self::getAliasUsingURL();
			
			//echo 'DEBUG cutting alias with '.$start_position.' and '.$end_position;

			if(!$alias) $alias = '_';

			return $alias;

		}// getAlias	


		public function getContent($alias=false)
		{

			if (!$alias) $alias = $this->getAlias();

			$content = $this->getPageContent($alias);

			if(count($this->errors))
			{
				$content .= '<ul class="errors">';
				foreach($this->errors as $error_message)
				{
					$content .= '<li>'.$error_message.'</li>';
				}
				$content .= '</ul>';
			}

			return $content;

		}// getContent
		
		
		public function render($alias=false)
		{

			if (!$alias) $alias = $this->getAlias();
			echo $this->getContent($alias);

		}// render


		public static function getAliasUsingURL($url=false)
		{
			if(!$url) 
			{
				$url = $_SERVER['SCRIPT_NAME'] ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];	
				if(!$url) 
				{
					$uri_parts = parse_url($_SERVER['REQUEST_URI']);
					$url = $uri_parts['path'];
				}
			}
			$alias = str_replace('index.php', '', $url);
			// Remove first /
			while(substr($alias, 0, 1)=='/') $alias = substr($alias, 1);
			// Remove lasst /
			while(substr($alias, strlen($alias)-1, 1)=='/') $alias = substr($alias, 0, strlen($alias)-1);
			// Just take what comes after the LAST /
			if(strpos($alias, '/')!==false) $alias = substr($alias, strrpos($alias, '/')+1);
			return $alias;
		}// getPageAliasUsingURL
	

	}// class PageBuilder


?>
