<?php


namespace WebHead;

use Nette;


/**
 * @author Adam Bisek
 */
class Utils extends Nette\Object
{

	public static function getBasePath(Nette\Http\Request $httpRequest, $subdir = NULL)
	{
		return rtrim($httpRequest->url->basePath, '/') . '/' . $subdir;
	}

}