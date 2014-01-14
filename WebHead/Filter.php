<?php


namespace WebHead;

use Nette;


/*
 * @author Adam Bisek
 */
abstract class Filter extends Nette\Object implements IFilter
{

	private $supportedTypesChecked = FALSE;


	final public function getSupportedTypes()
	{
		if($this->supportedTypesChecked === FALSE){
			if(!is_array($this->supportedTypes)){
				$class = get_class($this);
				$type = gettype($this->supportedTypes);
				throw new Nette\InvalidStateException("Property $class::supportedTypes must be an array, but is $type.");
			}
			$this->supportedTypesChecked = TRUE;
		}
		return $this->supportedTypes;
	}


	public function isTypeSupported($type)
	{
		return in_array($type, $this->getSupportedTypes());
	}

}
