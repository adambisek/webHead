<?php


namespace WebHead\Filters;

use Nette;
use WebHead;


class JsMinifier extends WebHead\Filter
{

	private static $libraryCheck = FALSE;

	protected $supportedTypes = array(
		'js',
	);


	public function __construct()
	{
		if(!self::$libraryCheck && !class_exists('JSMin', TRUE)){
			throw new Nette\InvalidStateException("JSMin library is not loaded.");
		}
		self::$libraryCheck = TRUE;
	}


	public function compile($content)
	{
		return \JSMin::minify($content);
	}

}
