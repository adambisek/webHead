<?php


namespace WebHead;

use Nette;


/**
 * @author Adam Bisek
 */
class Utils extends Nette\Object
{

	public static function sortArrayByKey($arr, $column, $direction = 'asc')
	{
		$direction = (strtolower($direction) === 'asc' ? \SORT_ASC : \SORT_DESC);
		$sortCol = array();
		foreach ($arr as $key => $val) {
			$sortCol[$key] = $val[$column];
		}
		array_multisort($sortCol, $direction, $arr);
		return $arr;
	}


	public static function getBasePath(Nette\Http\Request $httpRequest, $subdir = NULL)
	{
		return rtrim($httpRequest->url->basePath, '/') . '/' . $subdir;
	}
	
}