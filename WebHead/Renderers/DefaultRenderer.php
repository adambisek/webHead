<?php


namespace WebHead\Renderers;

use Nette;
use WebHead;
use WebHead\Control;


class DefaultRenderer extends Nette\Object implements WebHead\IRenderer
{

	/** @var Control */
	private $control;


	public function render(Control $control, $mode = NULL)
	{
		if ($this->control !== $control) {
			$this->control = $control;
		}

		if (!$mode || $mode === 'begin') {
			$this->renderBegin();
		}
		if (!$mode || $mode === 'metaTags') {
			$this->renderMetaTags();
		}
		if (!$mode || $mode === 'elements') {
			$this->renderElements();
		}
		if (!$mode || $mode === 'end') {
			$this->renderEnd();
		}
	}


	public function renderBegin()
	{
		if($this->control->isXml()){
			echo self::XML_PROLOG . "\n";
			echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="' . $this->control->language . '" lang="' . $this->control->language . '">' . "\n";
			echo $this->getDocTypeString($this->control->getDocType()) . "\n";
		}else{
			echo $this->getDocTypeString($this->control->getDocType()) . "\n";
			echo '<html>' . "\n";
		}
		echo "<head>\n";
	}


	public function renderEnd()
	{
		echo "</head>";
	}


	public function renderMetaTags()
	{
		echo Nette\Utils\Html::el('meta')->addAttributes(array(
			'http-equiv' => 'Content-Language',
			'content' => $this->control->getLanguage(),
		)) . "\n";
		echo Nette\Utils\Html::el('meta')->addAttributes(array(
			'http-equiv' => 'Content-Type',
			'content' => $this->control->getContentType() . "; charset=utf-8",
		)) . "\n";
		echo Nette\Utils\Html::el('meta')->addAttributes(array(
			'http-equiv' => 'Content-Style-Type',
			'content' => 'text/css',
		)) . "\n";
		echo Nette\Utils\Html::el('meta')->addAttributes(array(
			'http-equiv' => 'Content-Script-Type',
			'content' => 'text/javascript',
		)) . "\n";
		echo "<title>" . $this->control->getTitleString() . "</title>" . "\n";
		if ($this->control->getFavicon() !== NULL) {
			echo Nette\Utils\Html::el('link')->addAttributes(array(
				'rel' => 'shortcut icon',
				'href' => $this->control->getFavicon(),
			)) . "\n";
		}
		foreach ($this->control->getMetaTags() as $name => $content) {
			$this->renderElement('meta', array('name' => $name, 'content' => $content));
			echo "\n";
		}
		foreach ($this->control->getOgTags() as $name => $content) {
			$this->renderElement('meta', array('property' => 'og:' . $name, 'content' => $content));
			echo "\n";
		}
		foreach ($this->control->getRss() as $file => $args) {
			$this->renderElement('rss', $file);
			echo "\n";
		}
	}


	/**
	 */
	public function renderElements()
	{
		$this->control->getAssetsCollector()->prepare();
		foreach($this->control->getAssetsCollector()->getAll() as $asset){
			$this->renderElement($asset->type, $asset);
			echo "\n";
		}
	}


	/**
	 * @param $type
	 * @param $args
	 * @internal
	 */
	public function renderElement($type, $args)
	{
		if(isset($args['file'])){
			if(in_array($type, array('css', 'js'))){
				foreach((array) $this->control->linkFormatCallback as $callback){
					if(!is_callable($callback)){
						throw new Nette\InvalidStateException("Callback is not callable.");
					}
					$args['file'] = call_user_func($callback, $type, $args['file'], $args) ?: $args['file'];
				}
			}
			$file = $args['file'];
		}

		unset($args['priority'], $args['file'], $args['type'], $args['compiled']);
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
					'name' => isset($args['name']) ? $args['name'] : NULL,
					'property' => isset($args['property']) ? $args['property'] : NULL,
					'content' => $args['content'],
				));
			break;
			default:
				throw new Nette\InvalidStateException("Unknown el type '$type'.");
		}
	}


	private function getDocTypeString($docType)
	{
		switch($docType){
			case Control::HTML_4_STRICT:
				return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
				break;
			case Control::HTML_4_TRANSITIONAL:
				return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
				break;
			case Control::HTML_4_FRAMESET:
				return '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">';
				break;
			case Control::HTML_5:
				return '<!DOCTYPE html>';
				break;
			case Control::XHTML_1_STRICT:
				return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
				break;
			case Control::XHTML_1_TRANSITIONAL:
				return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
				break;
			case Control::XHTML_1_FRAMESET:
				return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
				break;
			default:
				throw new Nette\InvalidStateException("Doctype $docType is not supported.");
		}
	}

} 