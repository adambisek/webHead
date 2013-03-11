<?php //netteCache[01]000378a:2:{s:4:"time";s:21:"0.35051500 1362872627";s:9:"callbacks";a:2:{i:0;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:9:"checkFile";}i:1;s:56:"D:\webserver\WebHead\sandbox\app\templates\@layout.latte";i:2;i:1362872619;}i:1;a:3:{i:0;a:2:{i:0;s:19:"Nette\Caching\Cache";i:1;s:10:"checkConst";}i:1;s:25:"Nette\Framework::REVISION";i:2;s:30:"6a33aa6 released on 2012-10-01";}}}?><?php

// source file: D:\webserver\WebHead\sandbox\app\templates\@layout.latte

?><?php
// prolog Nette\Latte\Macros\CoreMacros
list($_l, $_g) = Nette\Latte\Macros\CoreMacros::initRuntime($template, '69e7os85wt')
;
// prolog Nette\Latte\Macros\UIMacros

// snippets support
if (!empty($_control->snippetMode)) {
	return Nette\Latte\Macros\UIMacros::renderSnippets($_control, $_l, get_defined_vars());
}

// prolog WebHead\Macro
WebHead\Macro::getWebHeadComponent($presenter)->addCss("css/before.css", array('media' => 'screen'));
WebHead\Macro::getWebHeadComponent($presenter)->addCss("css/after.css", array('media' => 'screen'));

//
// main template
//
 WebHead\Macro::getWebHeadComponent($presenter)->setTitle('Website title'); WebHead\Macro::headArgs($presenter, array('lang' => 'cs', 'titleSep' => '|'));
WebHead\Macro::renderHeadBegin($presenter);
 WebHead\Macro::getWebHeadComponent($presenter)->renderElement("meta", array('name' => 'robots', 'content' => 'nofollow')); echo "\n";
 WebHead\Macro::getWebHeadComponent($presenter)->renderElement("css", array('media' => 'screen', 'file' => 'css/screen.css')); echo "\n";
 WebHead\Macro::getWebHeadComponent($presenter)->renderElement("css", array('media' => 'print', 'file' => 'css/print.css')); echo "\n" ?>
	
	<script src="http://code.jquery.com/jquery-1.8.1.min.js"></script>
	<script src="<?php echo htmlSpecialChars($basePath) ?>/js/netteForms.js"></script>

	<!--[if lt IE 9]>
		<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
<?php WebHead\Macro::renderHeadEnd($presenter) ?>
<body>
	<script> document.body.className+=' js' </script>

<?php $iterations = 0; foreach ($flashes as $flash): ?>	<div class="flash <?php echo htmlSpecialChars($flash->type) ?>
"><?php echo Nette\Templating\Helpers::escapeHtml($flash->message, ENT_NOQUOTES) ?></div>
<?php $iterations++; endforeach ?>

<?php Nette\Latte\Macros\UIMacros::callBlock($_l, 'content', $template->getParameters()) ?>
</body>
</html>
