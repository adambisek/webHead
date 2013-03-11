<?php

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	/*
	public function createComponentWebHead()
	{
		$control = new \WebHead\Control($this->context->httpResponse);
		$compiler = $control->getFiltersCompiler();
		$compiler->registerFilter(new \WebHead\Filters\CssMinifier);
		$compiler->setOutputDir(WWW_DIR . '/temp', $this->template->basePath . '/temp'); // directory to compile files
		$compiler->addFiles(Nette\Utils\Finder::findFiles("*")->from(WWW_DIR . '/css'));
		return $control;
	}
	*/


	public function createComponentWebHead()
	{
		$control = $this->context->webHead;
		$compiler = $control->getFiltersCompiler();
		$compiler->addFiles(Nette\Utils\Finder::findFiles("*")->from(WWW_DIR . '/css'));
		return $control;
	}


	public function afterRender()
	{
		Nette\Diagnostics\Debugger::barDump($this['webHead']);
	}

}
