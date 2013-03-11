WebHead for Nette Framework
===============

Author: Adam Bisek (adam.bisek@gmail.com)
License: MIT

===============

Requirements:

- Nette Framework (2.0.x or in future 2.1.x)

===============

Todos:

- support OG tags and similar things
- support files joining (js and css files)

===============

Configuring:

This renderable component generates header of html page
and can compile js a and files using compiler and filters

There are 3 ways how to configure it.
All ways can be combined!

1. Using Nette\Config\CompilerExtension

	boostrap.php

	WebHead\CompilerExtension::install($configurator);

	config.neon (after extension install there is new section webHead)

	webHead:
		control:
			title: My webpage title
			author: John Doe

		compiler:
			outputDir: %wwwDir%/myDir
			wwwPath: /myDir
			filters:
				- WebHead\Filters\CssMinifier

		js: [file.js] # these files will be compiled

		css: [screen.css] # these files will be compiled
		
		
2. Via presenter component factory in presenter (usually BasePresenter)

	public function createComponentWebHead()
	{
		$control = new \WebHead\Control($this->context->httpResponse);
		$compiler = $control->getFiltersCompiler();
		$compiler->registerFilter(new \WebHead\Filters\CssMinifier);
		$compiler->setOutputDir(WWW_DIR . '/temp', $this->template->basePath . '/temp'); // directory to compile files
		$compiler->addFiles(Nette\Utils\Finder::findFiles("*")->from(WWW_DIR . '/css')); // these files will be compiled
		return $control;
	}
	
	
3. Via template macros

	{webHead:setTitle 'Website title'}
	{webHead:addTitle 'Page title'}
	{webHead:addKeywords 'keyword'}
	{css 'screen.css'}
	{js 'script.js'}


===============

Rendering:	
	
Example of component rendering in a template:

{webHead}{/webHead}


Customized rendering using CSS, Javascript and RSS parameters (replaces all CSS, JS and RSS settings):

template.latte

{css css/before.css, media => "screen"}
{webHead:setTitle 'Website title'}
{webHead 'lang' => 'cs', 'titleSep' => '|'}
	{meta robots, nofollow}
	{css css/screen.css, media => "screen"}
	{css css/print.css, media => "print"}
	
	<script src="http://code.jquery.com/jquery-1.8.1.min.js"></script>
	<script src="{$basePath}/js/netteForms.js"></script>

	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
{/webHead}
{css css/after.css, media => "screen"}

===============