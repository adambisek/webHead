<?php


namespace WebHead;

use Nette;
use Nette\InvalidArgumentException;


/**
 * WebHead<br />
 * This renderable component is ultimate solution for valid and complete HTML headers.
 * Based on Header Control
 *
 * @author Ondřej Mirtes, Adam Bisek
 * @copyright (c) Ondřej Mirtes 2009, 2010, Adam Bisek 2013
 * @license MIT
 */
class Control extends Nette\Application\UI\Control
{

	/**
	 */
	const XML_PROLOG = '<?xml version="1.0" encoding="utf-8"?>';
	
	/**
	* doctypes
	*/
	const HTML_4 = self::HTML_4_STRICT; // backwards compatibility
	const HTML_4_STRICT = 'html4_strict';
	const HTML_4_TRANSITIONAL = 'html4_transitional';
	const HTML_4_FRAMESET = 'html4_frameset';

	const HTML_5 = 'html5';

	const XHTML_1 = self::XHTML_1_STRICT; //backwards compatibility
	const XHTML_1_STRICT = 'xhtml1_strict';
	const XHTML_1_TRANSITIONAL = 'xhtml1_transitional';
	const XHTML_1_FRAMESET = 'xhtml1_frameset';

	/**
	* languages
	*/
	const CZECH = 'cs';
	const SLOVAK = 'sk';
	const ENGLISH = 'en';
	const GERMAN = 'de';

	/**
	* content types
	*/
	const TEXT_HTML = 'text/html';
	const APPLICATION_XHTML = 'application/xhtml+xml';

	/**
	* common
	*/
	const PRESENTER_COMPONENT_NAME = 'webHead';


	/** @var Nette\Http\Request */
	private $httpResponse;

	/** @var string doctype */
	private $docType;

	/** @var bool whether doctype is XML compatible or not */
	private $xml;

	/** @var string document language */
	private $language;

	/** @var string document title */
	private $title;

	/** @var string title separator */
	private $titleSeparator;

	/** @var bool whether title should be rendered in reverse order or not */
	private $titlesReverseOrder = TRUE;

	/** @var array document hierarchical titles */
	private $titles = array();

	/** @var array header meta tags */
	private $metaTags = array();

	/** @var array header meta OG tags */
	private $ogTags = array();

	/** @var array header rss */
	private $rss = array();

	/** @var string document content type */
	private $contentType;

	/** @var bool whether XML content type should be forced or not */
	private $forceContentType;

	/** @var string path to favicon (without $basePath) */
	private $favicon;
	
	/** @var bool was headers sent? */
	private $headersSent = FALSE;
	
	/** @var AssetsCollector */
	private $assetsCollector;

	/** @var IRenderer */
	private $renderer;

	/** @var FiltersCompiler */
	private $filtersCompiler;

	/** @var array of callback */
	public $linkFormatCallback;


	/**
	 * @param \Nette\Http\Response $httpResponse
	 * @param \Nette\Application\UI\PresenterComponent $parent
	 * @param null $name
	 */
	public function __construct(Nette\Http\Response $httpResponse, Nette\Application\UI\PresenterComponent $parent = NULL)
	{
		parent::__construct($parent, self::PRESENTER_COMPONENT_NAME);
		$this->httpResponse = $httpResponse;
		$this->assetsCollector = new AssetsCollector($this);
		// sets defaults
		$this->setDocType(self::HTML_5);
		$this->setLanguage(self::CZECH);
		$this->setContentType(self::TEXT_HTML);
		$this->setRobots("index,follow");
		$this->setMetatag("googlebot", "snippet,noarchive");
	}


	/*
	 * Sets config array
	 */
	public function setConfig(array $config){
		if(isset($config['control'])){
			foreach($config['control'] as $k => $v){
				$m = 'set' . ucfirst($k);
				$this->$m($v);
			}
		}
		foreach($config['css'] as $k => $v){
			if(is_numeric($k)){ // indexed array
				$this->addCss($v);
			}else{ // assoc array - k = file, v = params
				$this->addCss($k, $v);
			}
		}
		foreach($config['js'] as $js){
			$this->addJs($js);
		}
		if(isset($config['compiler']) && !empty($config['compiler'])){
			$compiler = $this->getFiltersCompiler();
			if(!isset($config['compiler']['outputDir'])){
				throw new Nette\InvalidStateException("Output dir is not defined.");
			}elseif(!isset($config['compiler']['wwwPath'])){
				throw new Nette\InvalidStateException("Www path is not defined.");
			}
			$compiler->setOutputDir($config['compiler']['outputDir'], $config['compiler']['wwwPath']);
			if(isset($config['compiler']['filters'])){
				foreach((array) $config['compiler']['filters'] as $filter){
					$filter = is_string($filter) ? new $filter : $filter;
					$compiler->registerFilter($filter);
				}
			}
			if(isset($config['compiler']['joinFiles'])){
				$compiler->setJoinFiles($config['compiler']['joinFiles']);
			}
		}
	}
	


	/********************************* Document ************************************/

	/**
	*/
	protected function sendHeaders()
	{
		if($this->headersSent){
			throw new Nette\InvalidStateException("Header already sent.");
		}
		$httpResponse = $this->httpResponse;
		if ($this->docType == self::XHTML_1_STRICT && $this->contentType == self::APPLICATION_XHTML && $this->forceContentType) {
			$contentType = self::APPLICATION_XHTML;
			$httpResponse->setHeader('Vary', 'Accept'); // due to proxy, to avoid force download
			$httpResponse->setContentType($contentType, 'utf-8');
		} else {
			$contentType = $this->contentType = self::TEXT_HTML;
			$httpResponse->setContentType($contentType, 'utf-8');
		}
		$this->headersSent = TRUE;
	}
	

	/**
	* @param mixed $docType
	*/
	public function setDocType($docType)
	{
		if($docType == self::HTML_4_STRICT || $docType == self::HTML_4_TRANSITIONAL || $docType == self::HTML_4_FRAMESET || $docType == self::HTML_5 ||
		$docType == self::XHTML_1_STRICT || $docType == self::XHTML_1_TRANSITIONAL || $docType == self::XHTML_1_FRAMESET){
			$this->docType = $docType;
			$this->xml = ($docType == self::XHTML_1_STRICT || $docType == self::XHTML_1_TRANSITIONAL || $docType == self::XHTML_1_FRAMESET);
		}else{
			throw new InvalidArgumentException("Doctype $docType is not supported.");
		}
		return $this;
	}


	/**
	* @param mixed $contentType
	* @param mixed $force
	*/
	public function setContentType($contentType, $force = FALSE)
	{
		if ($contentType == self::APPLICATION_XHTML && $this->docType != self::XHTML_1_STRICT && $this->docType != self::XHTML_1_TRANSITIONAL && $this->docType != self::XHTML_1_FRAMESET){
			throw new \InvalidArgumentException("Cannot send $contentType type with non-XML doctype.");
		}
		if($contentType == self::TEXT_HTML || $contentType == self::APPLICATION_XHTML) {
			$this->contentType = $contentType;
		}else{
			throw new \InvalidArgumentException("Content type $contentType is not supported.");
		}
		$this->forceContentType = (bool) $force;
		return $this;
	}

	/**
	*/
	public function getContentType() 
	{
		return $this->contentType;
	}


	/**
	*/
	public function getDocType() 
	{
		return $this->docType;
	}


	/**
	*/
	public function isXml() 
	{
		return $this->xml;
	}


	/**
	*/
	public function isContentTypeForced() 
	{
		return (bool) $this->forceContentType;
	}



	/********************************* Language ************************************/    

	/**
	* @param mixed $language
	*/
	public function setLanguage($language) 
	{
		$this->language = $language;
		return $this;
	}


	/**
	*/
	public function getLanguage() 
	{
		return $this->language;
	}



	/********************************* Document title ************************************/    

	/**
	* @param mixed $title
	*/
	public function setTitle($title) 
	{
		if($title == ''){ // intentionally ==
			throw new InvalidArgumentException("Title must be non-empty string.");
		}
		$this->title = $title;
		return $this;
	}
	

	/**
	* @param mixed $index
	*/
	public function getTitle($index = 0)
	{
		if (count($this->titles) == 0){
			return $this->title;
		}elseif(count($this->titles)-1-$index < 0){
			return $this->getTitle();
		}else{
			return $this->titles[count($this->titles)-1-$index];
		}
	}


	/**
	* @param mixed $title
	*/
	public function addTitle($title) 
	{
		if($title == ''){ // intentionally ==
			throw new InvalidArgumentException("Title must be non-empty string.");
		}
		$this->titles[] = $title;
		return $this;
	}

	
	/**
	*/
	public function getTitles() 
	{
		return $this->titles;
	}
	

	/**
	* @param mixed $separator
	*/
	public function setTitleSeparator($separator)
	{
		$this->titleSeparator = $separator;
		return $this;
	}

	
	/**
	*/
	public function getTitleSeparator() 
	{
		return $this->titleSeparator;
	}

	
	/**
	* @param mixed $reverseOrder
	*/
	public function setTitlesReverseOrder($reverseOrder = TRUE)
	{
		$this->titlesReverseOrder = (bool) $reverseOrder;
		return $this;
	}
	

	/**
	*/
	public function isTitlesOrderReversed()
	{
		return (bool) $this->titlesReverseOrder;
	}


	/**
	*/
	public function getTitleString()
	{
		if($this->titles){
			if($this->title == NULL){ // intentionally ==
				throw new Nette\InvalidStateException("No main title setted.");
			}
			$titles = $this->titles;
			if(!$this->titlesReverseOrder){
				array_unshift($titles, $this->title);
			}else{
				$titles = array_reverse($titles);
				array_push($titles, $this->title);
			}
			return implode($this->titleSeparator, $titles);
		}else{
			return $this->title;
		}
	}


	/********************************* Head Elements ************************************/

	/**
	 */
	public function addCss($css, array $params = NULL)
	{
		$this->assetsCollector->addCss($css, $params);
		return $this;
	}


	/**
	 */
	public function addJs($js, array $params = NULL)
	{
		$this->assetsCollector->addJs($js, $params);
		return $this;
	}


	/**
	 */
	public function addRss($rss)
	{
		$this->rss[$rss] = array(
			'type' => 'rss',
			'file' => $rss,
		);
		return $this;
	}


	/**
	 */
	public function getRss()
	{
		return $this->rss;
	}



	/********************************* Favicon ************************************/    

	/**
	* @param mixed $filename
	*/
	public function setFavicon($filename) 
	{
		$this->favicon = $filename;
		return $this;
	}


	/**
	*/
	public function getFavicon() 
	{
		return $this->favicon;
	}


	/********************************* Metatags ************************************/   

	/**
	* @param mixed $name
	* @param mixed $value
	*/
	public function setMetaTag($name, $value) 
	{
		$this->metaTags[$name] = $value;
		return $this;
	}

	
	/**
	* @param mixed $name
	*/
	public function getMetaTag($name) 
	{
		return isset($this->metaTags[$name]) ? $this->metaTags[$name] : NULL;
	}

	
	/**
	*/
	public function getMetaTags()
	{
		return $this->metaTags;
	}


	/********** Specific Metatags **********/

	/**
	* @param mixed $author
	*/
	public function setAuthor($author) 
	{
		$this->setMetaTag('author', $author);
		return $this;
	}


	/**
	*/
	public function getAuthor() 
	{
		return $this->getMetaTag('author');
	}


	/**
	* @param mixed $description
	*/
	public function setDescription($description) 
	{
		$this->setMetaTag('description', $description);
		return $this;
	}


	/**
	*/
	public function getDescription() 
	{
		return $this->getMetaTag('description');
	}


	/**
	 */
	public function addOgTag($name, $content)
	{
		$this->ogTags[$name] = $content;
	}


	/**
	 */
	public function getOgTags()
	{
		return $this->ogTags;
	}


	/**
	* @param mixed $keywords
	*/
	public function addKeywords($keywords) 
	{
		if($keywords instanceof \Traversable){
			$keywords = iterator_to_array($keywords);
		}
		if(is_array($keywords)){
			$keywords = implode(', ', $keywords);
		}elseif(is_scalar($keywords)){
                  $keywords = (string) $keywords;
		}else{
			throw new Nette\InvalidArgumentException('Type of keywords argument is not supported.');
		}
		// here is keywords in string
		if($this->keywords){
			$this->setMetaTag('keywords', $this->getKeywords() . ', ' . $keywords);
		}else{
			$this->setMetaTag('keywords', $keywords);
		}
		return $this;
	}

	
	/**
	*/
	public function getKeywords() 
	{
		return $this->getMetaTag('keywords');
	}


	/**
	* @param mixed $robots
	*/
	public function setRobots($robots) 
	{
		$this->setMetaTag('robots', $robots);
		return $this;
	}


	/**
	*/
	public function getRobots() 
	{
		return $this->getMetaTag('robots');
	}



	/********************************* Rendering ************************************/

	/**
	 */
	public function setRenderer(IRenderer $renderer)
	{
		$this->renderer = $renderer;
		return $this;
	}


	/**
	 */
	final public function getRenderer()
	{
		if ($this->renderer === NULL) {
			$this->renderer = new Renderers\DefaultRenderer;
		}
		return $this->renderer;
	}


	/**
	 */
	public function render()
	{
		if(!$this->headersSent){
			$this->sendHeaders();
		}
		$args = func_get_args();
		array_unshift($args, $this);
		echo call_user_func_array(array($this->getRenderer(), 'render'), $args);
	}


	/**
	 */
	public function __toString()
	{
		try {
			return $this->getRenderer()->render($this);
		} catch (\Exception $e) {
			if (func_get_args() && func_get_arg(0)) {
				throw $e;
			} else {
				trigger_error("Exception in " . __METHOD__ . "(): {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", E_USER_ERROR);
			}
		}
	}



	/********************************* Assets Collector ***********************************/

	/**
	 */
	public function getAssetsCollector()
	{
		return $this->assetsCollector;
	}



	/********************************* Filters compiler ***********************************/

	public function setFiltersCompiler(FiltersCompiler $filtersCompiler)
	{
		$this->filtersCompiler = $filtersCompiler;
	}


	public function getFiltersCompiler()
	{
		if($this->filtersCompiler === NULL){
			$this->setFiltersCompiler($this->createFiltersCompiler());
		}
		return $this->filtersCompiler;
	}


	public function hasFiltersCompiler()
	{
		return (bool) $this->filtersCompiler !== NULL;
	}


	private function createFiltersCompiler()
	{
		return new FiltersCompiler();
	}

}