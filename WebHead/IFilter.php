<?php


namespace WebHead;


interface IFilter
{

	public function compile($content);

	public function getSupportedTypes();

	public function isTypeSupported($type);

}
