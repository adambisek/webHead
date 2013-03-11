<?php


namespace WebHead;

use Nette;


/*
 * @author Adam Bisek
 */
abstract class Filter extends Nette\Object implements IFilter
{

	final public function getSupportedTypes()
	{
		return (array) $this->supportedTypes;
	}


	public function isTypeSupported($type)
	{
		return in_array($type, $this->getSupportedTypes());
	}

}
