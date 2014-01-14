<?php

namespace WebHead;


use Nette;
use Nette\Latte;
use Nette\Utils\PhpGenerator;


/*
* @author Adam Bisek
*/
class Macro extends Latte\Macros\MacroSet implements Latte\IMacro
{

	public $nodes = array();

	/** @internal */
	public $macroNodeOpened = NULL; // NULL = not yet, TRUE = opened, FALSE = already closed


	/**
	 * @param \Nette\Latte\Parser $parser
	 */
	public static function install(Latte\Compiler $compiler)
	{
		$me = new static($compiler);
		$class = get_called_class();

		$open = function(Latte\MacroNode $node) use ($me, $class){
			$me->macroNodeOpened = TRUE;
			return $class.'::headArgs($presenter, array('.$node->args.'));' . "\n" .
				$class.'::renderHeadBegin($presenter);' . "\n";
		};
		$close = function(Latte\MacroNode $node) use ($me, $class){
			$me->macroNodeOpened = FALSE;
			return $class.'::renderHeadEnd($presenter);' . "\n";
		};
		$me->addMacro('webHead', $open, $close);

		$cb = function (Latte\MacroNode $node) use ($me, $class){
			$me->nodes[] = $node;
		};
		$me->addMacro('css', $cb);
		$me->addMacro('js', $cb);
		$me->addMacro('rss', $cb);
		$me->addMacro('meta', $cb);
		$me->addMacro('og', $cb);

		$me->addMacro('webHead:begin', $class.'::getWebHeadComponent($presenter)->render("begin");');
		$me->addMacro('webHead:metaTags', $class.'::getWebHeadComponent($presenter)->render("metaTags");');
		$me->addMacro('webHead:elements', $class.'::getWebHeadComponent($presenter)->render("elements");');
		$me->addMacro('webHead:end', $class.'::getWebHeadComponent($presenter)->render("end");');

		$me->addMacro('webHead:setTitle', $class.'::getWebHeadComponent($presenter)->setTitle(%node.args);');
		$me->addMacro('webHead:addTitle', $class.'::getWebHeadComponent($presenter)->addTitle(%node.args);');
		$me->addMacro('webHead:addKeywords', $class.'::getWebHeadComponent($presenter)->addKeywords(%node.args);');
	}
	


	/********************* interface Nette\Latte\IMacro **********************/

	/**
	 * Initializes before template parsing.
	 * @return void
	 */
	public function initialize()
	{
	}


	/**
	 * Finishes template parsing.
	 * @return array(prolog, epilog)
	 */
	public function finalize()
	{
		$code = array();
		foreach($this->nodes as $node){
			$name = $node->name;
			$args = $this->unFormatMacroArgs($node->args);
			switch($name){
				case 'meta':
					$code[] = get_called_class().'::getWebHeadComponent($presenter)->setMetaTag("'.$args[0].'", "'.$args[1].'");';
				break;
				case 'og':
					$code[] = get_called_class().'::getWebHeadComponent($presenter)->addOgTag("'.$args[0].'", "'.$args[1].'");';
				break;
				default:
					$file = $args[0]; array_shift($args);
					$args = $this->argsToArray($args);
					$code[] = get_called_class().'::getWebHeadComponent($presenter)->add'.ucfirst($name).'("'.$file.'", array('.$args.'));';
			}
		}
		return array(implode("\n", $code), '');
	}



	/********************** Called from Generated code ***********************/

	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 * @param array $args
	 */
	public static function headArgs(Nette\Application\UI\Presenter $presenter, array $args)
	{
		$webHead = static::getWebHeadComponent($presenter);
		$properties = array('docType', 'contentType', 'language', 'title', 'titles', 'titleSeparator', 'titlesReverseOrder');
		foreach($args as $k => $v){
			if(!in_array($k, $properties)){
				throw new Nette\InvalidStateException("Unknown arg '$k'.");
			}
			$m = 'set' . ucfirst($k);
			$webHead->$m($v);
		}
	}
	
	
	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 */
	public static function renderHeadBegin(Nette\Application\UI\Presenter $presenter)
	{
		$webHead = static::getWebHeadComponent($presenter);
		$webHead->render('begin');
		$webHead->render('metaTags');
	}


	/**
	 * @param \Nette\Application\UI\Presenter $presenter
	 */
	public static function renderHeadEnd(Nette\Application\UI\Presenter $presenter)
	{
		$webHead = static::getWebHeadComponent($presenter);
		$webHead->render('elements');
		$webHead->render('end');
	}


	/**
	 * @param \Nette\ComponentModel\Container $component
	 */
	public static function getWebHeadComponent(Nette\ComponentModel\Container $component)
	{
		if (!$component->getComponent(Control::PRESENTER_COMPONENT_NAME, FALSE)) {
			throw new Nette\InvalidStateException('You have to register ' . __NAMESPACE__ . ' as presenter component named ' . Control::PRESENTER_COMPONENT_NAME . '.');
		}
		return $component->getComponent(Control::PRESENTER_COMPONENT_NAME);
	}


	/**
	 * @internal
	 */
	public function unFormatMacroArgs($args)
	{
		$arr = explode(",", $args);
		$args = array();
		foreach($arr as $v){
			$expl = explode("=>", $v);
			if(count($expl) === 2){ // associative array
				$expl[0] = trim($expl[0], ' \'"');
				$expl[1] = trim($expl[1], ' \'"');
				$args[$expl[0]] = $expl[1];
			}else{
				$args[] = trim($v, ' \'"');
			}
		}
		return $args;
	}


	/**
	 * @internal
	 */
	public function argsToArray(array $args)
	{
		array_walk($args, function(&$val, $key){
			if(!is_numeric($key)){ // if indexed array, do not use keys
				$val = "'$key' => '$val'";
			}else{
				$val = "'$val'";
			}
		});
		$args = implode(", ", $args);
		return $args;
	}

}