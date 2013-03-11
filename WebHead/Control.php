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
	const HTML_4 = self::HTML_4_STRICT; //backwards compatibility
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

	/** @var string document content type */
	private $contentType;

	/** @var bool whether XML content type should be forced or not */
	private $forceContentType;

	/** @var string path to favicon (without $basePath) */
	private $favicon;
	
	/** @var bool was headers sent? */
	private $headersSent = FALSE;
	
	/** @var array */
	private $headElements = array();

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
		if(isset($config['compiler'])){
			if(!isset($config['compiler']['outputDir'])){
				throw new Nette\InvalidStateException("Output dir is not defined.");
			}elseif(!isset($config['compiler']['wwwPath'])){
				throw new Nette\InvalidStateException("Www path is not defined.");
			}
			$this->getFiltersCompiler()->setOutputDir($config['compiler']['outputDir'], $config['compiler']['wwwPath']);
			if(isset($config['compiler']['filters'])){
				foreach((array) $config['compiler']['filters'] as $filter){
					$filter = is_string($filter) ? new $filter : $filter;
					$this->getFiltersCompiler()->registerFilter($filter);
				}
			}
			foreach($config['css'] as $css){
				$this->addCss($css);
			}
			foreach($config['js'] as $js){
				$this->addJs($js);
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
	 * @param $css
	 */
	public function addCss($css, array $params = NULL)
	{
		$this->headElements[$css] = array(
			'type' => 'css',
			'file' => $css,
			'priority' => isset($params['priority']) ? (int) $params['priority'] : 0,
		) + (array) $params;
		return $this;
	}


	public function addJs($js, array $params = NULL)
	{
		$this->headElements[$js] = array(
			'type' => 'js',
			'file' => $js,
			'priority' => isset($params['priority']) ? (int) $params['priority'] : 0,
		) + (array) $params;
		return $this;
	}


	public function addRss($rss, array $params = NULL)
	{
		$this->headElements[$rss] = array(
			'type' => 'rss',
			'file' => $rss,
			'priority' => isset($params['priority']) ? (int) $params['priority'] : 0,
		) + (array) $params;
		return $this;
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


	/********** Specify Metatags **********/      

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
	* @param mixed $docType
	* @return mixed
	*/
	private function getDocTypeString($docType)
	{
		switch($docType){
			case self::HTML_4_STRICT:
				return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
			break;
			case self::HTML_4_TRANSITIONAL:
				return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
			break;
			case self::HTML_4_FRAMESET:
				return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">';
			break;
			case self::HTML_5:
				return '<!DOCTYPE html>';
			break;
			case self::XHTML_1_STRICT:
				return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
			break;
			case self::XHTML_1_TRANSITIONAL:
				return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
			break;
			case self::XHTML_1_FRAMESET:
				return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
			break;
			default:
				throw new Nette\InvalidStateException("Doctype $docType is not supported.");
		}
	}


	/**
	* Renders all
	*/
	public function render()
	{
		if(!$this->headersSent){
			$this->sendHeaders();
		}
		$this->renderBegin();
		$this->renderMetaTags();
		$this->renderElements();
		$this->renderEnd();
	}
	

	/**
	* Partially render: begin
	*/
	public function renderBegin()
	{
		if($this->isXml()){
			echo self::XML_PROLOG . "\n";
			echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$this->language.'" lang="'.$this->language.'">' . "\n";   
			echo $this->getDocTypeString($this->getDocType()) . "\n";
		}else{
			echo $this->getDocTypeString($this->getDocType()) . "\n";
			echo '<html>' . "\n"; 
		}
		echo "<head>\n";
	}


	/**
	 */
	public function renderMetaTags()
	{
		echo Nette\Utils\Html::el('meta')->addAttributes(array(
			'http-equiv' => 'Content-Language',
			'content' => $this->language,
		)) . "\n";
		echo Nette\Utils\Html::el('meta')->addAttributes(array(
			'http-equiv' => 'Content-Type',
			'content' => "$this->contentType; charset=utf-8",
		)) . "\n";
		echo Nette\Utils\Html::el('meta')->addAttributes(array(
			'http-equiv' => 'Content-Style-Type',
			'content' => 'text/css',
		)) . "\n";
		echo Nette\Utils\Html::el('meta')->addAttributes(array(
			'http-equiv' => 'Content-Script-Type',
			'content' => 'text/javascript',
		)) . "\n";
		echo "<title>{$this->getTitleString()}</title>" . "\n";
		if ($this->favicon !== NULL) {
			echo Nette\Utils\Html::el('link')->addAttributes(array(
				'rel' => 'shortcut icon',
				'href' => $this->favicon,
			)) . "\n";
		}
		foreach ($this->metaTags as $name => $content) {
			$this->renderElement('meta', array('name' => $name, 'content' => $content));
			echo "\n";
		}
	}


	/**
	 */
	public function renderElements()
	{
		$headElements = Utils::sortArrayByKey($this->headElements, 'priority');
		foreach($headElements as $el){
			$type = $el['type']; unset($el['type']);
			$this->renderElement($type, $el);
			echo "\n";
		}
	}
              
              
	/**
	* Partially render: end
	* 
	*/
	public function renderEnd() 
	{
		echo "</head>";
	}


	/**
	 * @param $type
	 * @param $args
	 * @internal
	 */
	public function renderElement($type, $args)
	{
		unset($args['priority']);
		if(in_array($type, array("css", "js", "rss"))){
			$file = $args['file']; unset($args['file']);
			$result = $this->formatCompiledFilesLink($file);
			if($result !== NULL){
				$file = $result;
			}
			foreach((array) $this->linkFormatCallback as $callback){
				if(is_callable($callback)){
					$result = call_user_func($callback, $type, $file, $args);
					if($result !== NULL){
						$file = $result;
					}
				}
			}
		}
		switch($type){
			case "css":
				echo Nette\Utils\Html::el('link')->addAttributes(array(
					'rel' => 'stylesheet',
					'type' => 'text/css',
					'href' => $file,
				) + (array) $args);
			break;
			case "js":
				echo Nette\Utils\Html::el('script')->addAttributes(array(
					'type' => 'text/javascript',
					'src' => $file,
				) + (array) $args);
			break;
			case "rss":
				echo Nette\Utils\Html::el('link')->addAttributes(array(
					'rel' => 'alternate',
					'type' => 'application/rss+xml',
					'href' => $file,
					'title' => 'rss',
				) + (array) $args);
			break;
			case "meta":
				echo Nette\Utils\Html::el('meta')->addAttributes(array(
					'name' => $args['name'],
					'content' => $args['content'],
				));
			break;
			default:
				throw new Nette\InvalidStateException("Unknown el type '$type'.");
		}
	}



	/********************************* Filters compiler ***********************************/

	private function formatCompiledFilesLink($file)
	{
		if($this->filtersCompiler === NULL){
			return NULL; // compiler not initilized
		}elseif(!preg_match('#^\.?/?([^://].+)$#', $file, $match)){ // match relative path
			return NULL;
		}
		$this->getFiltersCompiler()->compile();
		$compiledFiles = $this->getFiltersCompiler()->getCompiledFiles();
		if($compiledFiles === NULL){
			return NULL;
		}
		$file = $match[1];
		foreach($compiledFiles as $origFilePath => $compiledFile){
			$origFilePath = str_replace('\\', '/', $origFilePath); // standardize directory separator
			if(substr($origFilePath, strlen($origFilePath) - strlen($file)) === $file){ // relative path match od end of filesystem path
				return $compiledFile;
			}
		}
		return NULL;
	}


	private function createFiltersCompiler()
	{
		$compiler = new FiltersCompiler();
		return $compiler;
	}


	public function getFiltersCompiler()
	{
		if($this->filtersCompiler === NULL){
			$this->filtersCompiler = $this->createFiltersCompiler();
		}
		return $this->filtersCompiler;
	}

}