<?php


namespace WebHead\Filters;

use Nette;
use WebHead;


class CssMinifier extends WebHead\Filter
{

	private static $libraryCheck = FALSE;

	protected $supportedTypes = array(
		'css',
	);


	public function __construct()
	{
		if(!self::$libraryCheck && !class_exists('CssMin', TRUE)){
			throw new Nette\InvalidStateException("CssMin library is not loaded.");
		}
		self::$libraryCheck = TRUE;
	}


	public function compile($content)
	{
		return \CssMin::minify($content);
	}

}
