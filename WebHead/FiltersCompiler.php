<?php


namespace WebHead;

use Nette;


/*
 * @author Adam Bisek
 */
class FiltersCompiler extends Nette\Object
{

	/** @var string */
	private $outputDir;

	/** @var string */
	private $wwwPath;

	/** @var array */
	private $files = array();

	/** @var Filter[] */
	private $filters = array();

	/** @var array */
	private $compiledFiles;

	/** @var string */
	private $cssFilesSubDir = 'css-files';

	/** @var bool @todo */
	private $joinFiles = FALSE;


	public function setOutputDir($dir, $wwwPath)
	{
		if(!is_dir($dir)){
			throw new Nette\InvalidArgumentException("Directory '$dir' is not valid valid directory.");
		}
		$this->outputDir = (string) $dir;
		$this->wwwPath = (string) $wwwPath;
		return $this;
	}


	public function addFiles($files)
	{
		if($files instanceof Nette\Utils\Finder){
			$files = array_keys(iterator_to_array($files));
		}elseif($files instanceof \Traversable){
			$files = iterator_to_array($files);
		}elseif(!is_array($files)){
			throw new Nette\InvalidArgumentException('Files must be an array or Traversable.');
		}
		$this->files = $files;
		return $this;
	}


	public function registerFilter(IFilter $filter)
	{
		$this->filters[] = $filter;
		return $this;
	}


	public function compile()
	{
		if(empty($this->filters)){
			throw new Nette\InvalidStateException('No filters registered.');
		}
		if($this->compiledFiles !== NULL){
			return;
		}
		$return = array();
		foreach($this->files as $file){
			$compiledFileName = $this->calculateFilename($file);
			$compiledFilePath = $this->outputDir . DIRECTORY_SEPARATOR . $compiledFileName;
			$type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			if(is_file($compiledFilePath)){ // file already compiled, exists and is up to date
				$return[$file] = $this->wwwPath . '/' . $compiledFileName;
				continue;
			}

			$content = file_get_contents($file);
			$compiled = FALSE;
			if($type === 'css'){
				$content = $this->extractCssLinkedFiles($file, $content);
				$compiled = TRUE;
			}
			foreach($this->filters as $filter){
				if(!$filter->isTypeSupported($type)){
					continue;
				}
				$content = $filter->compile($content);
				$compiled = TRUE;
			}
			if($compiled){
				file_put_contents("safe://$compiledFilePath", $content);
				$return[$file] = $this->wwwPath . '/' . $compiledFileName;
			}
		}
		$this->compiledFiles = $return;
	}


	public function getCompiledFiles()
	{
		return $this->compiledFiles;
	}


	private function calculateFilename($file)
	{
		$match = Nette\Utils\Strings::match(basename($file), '#^(.+)\.([^\.][a-zA-Z0-9]+)$#');
		return $match[1] . '-' . md5($file . filemtime($file)) . '.' . $match[2];
	}


	private function extractCssLinkedFiles($file, $code)
	{
		$compileDir = $this->outputDir . DIRECTORY_SEPARATOR . $this->cssFilesSubDir;
		if(!is_dir($compileDir)){ // check, because on win, if folder doesnt exists php writes to closest existing parent folder!
			$result = @mkdir($compileDir);
			if(!$result){
				throw new Nette\InvalidStateException("Dir '$compileDir' does not exists and cannot be created.");
			}
		}

		if(!preg_match_all('#background(-image)?\s*:\s*[\#a-zA-Z0-9]*\s*url\(([^data].+)\)#', $code, $matches)){
			return $code;
		}
		$replacements = array();
		$basedir = dirname($file);
		$cssFiles = array_map(function($s){ return trim($s, ' \'"'); }, $matches[2]);
		foreach($cssFiles as $cssFile){
			if(preg_match('#^data:#', $cssFile)){
				continue;
			}
			$basename = basename($cssFile);
			$match = Nette\Utils\Strings::match($basename, '#^(.+)\.([^\.][a-zA-Z0-9]+)$#');
			$basename = $match[1] . '-' . md5($file) . '.' . $match[2];
			$compiledFile = $compileDir  . DIRECTORY_SEPARATOR . $basename;
			$cssFilePath = $basedir . DIRECTORY_SEPARATOR . $cssFile;
			if(is_file($cssFilePath)){
				copy($cssFilePath, $compiledFile);
			}
			$replacements[$cssFile] = "$this->cssFilesSubDir/$basename";
		}
		$code = strtr($code, $replacements);
		return $code;
	}

}