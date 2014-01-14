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

	/** @var bool */
	private $compiled = FALSE;

	/** @var bool */
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


	public function addFiles($files, $group = NULL)
	{
		if($files instanceof Nette\Utils\Finder){
			$files = iterator_to_array($files);
		}elseif(!is_array($files)){
			throw new Nette\InvalidArgumentException('Files must be an array.');
		}
		foreach($files as $file){
			$this->files[$file] = $group;
		}
		$this->compiled = FALSE; // need to recompile
		return $this;
	}


	public function registerFilter(IFilter $filter)
	{
		$this->filters[] = $filter;
		return $this;
	}


	public function setJoinFiles($join = TRUE)
	{
		$this->joinFiles = (bool) $join;
	}


	public function isFilesJoined()
	{
		return $this->joinFiles;
	}


	public function compile()
	{
		if($this->compiled){ // already compiled
			return;
		}
		$return = array();
		foreach($this->files as $file => $group){
			if(!is_file($file)){
				throw new FiltersCompilerException("File '$file' does not exists.");
			}

			$compiledFileName = $this->calculateFilename($file);
			$compiledFilePath = $this->outputDir . DIRECTORY_SEPARATOR . $compiledFileName;
			$compiledFilePath = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $compiledFilePath);
			$type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			if(is_file($compiledFilePath)){ // file already compiled, exists and is up to date
				$return[$file] = $this->wwwPath . '/' . $compiledFileName;
				continue;
			}

			$content = @file_get_contents($file);
			if($content === FALSE){
				throw new FiltersCompilerException("File '$file' is not readable.");
			}

			$compiled = FALSE;
			if($type === 'css'){
				$content = $this->fixCssPaths($file, $compiledFilePath, $content);
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

		if($this->joinFiles){
			$joined = array();
			foreach($return as $file => $wwwPath){
				$type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
				$joined[$type][$this->files[$file]][] = $file;
			}
			foreach($joined as $type => $groupFiles){
				foreach($groupFiles as $group => $files){
					$compiledFileName = '_joined-' . $type . '[' . $group . ']' . '-' . md5(implode("-", $files)) . '.' . $type;
					$compiledFilePath = $this->outputDir . DIRECTORY_SEPARATOR . $compiledFileName;
					if(is_file($compiledFilePath)){
						$return['joined'][$type][$group] = $this->wwwPath . '/' . $compiledFileName;
						continue;
					}

					$content = "";
					foreach($files as $file){
						$content .= '/* WH Filters Compiler ========= ' . basename($file) . ' =========== */' . "\n\n" . file_get_contents($file) . "\n\n";
					}
					file_put_contents("safe://$compiledFilePath", $content);
					$return['joined'][$type][$group] = $this->wwwPath . '/' . $compiledFileName;
				}
			}
		}

		$this->compiledFiles = $return;
		$this->compiled = TRUE;
	}


	public function getCompiledFiles()
	{
		$this->compile();
		return $this->compiledFiles;
	}


	public function getCompiledJoinedFiles()
	{
		$this->compile();
		return isset($this->compiledFiles['joined']) ? $this->compiledFiles['joined'] : array();
	}


	public function getCompiledFile($file)
	{
		if(!preg_match('#^\.?/?([^://].+)$#', $file, $match)){ // match relative path; if is absolute URL, return NULL
			return NULL;
		}
		$this->compile();
		$compiledFiles = $this->getCompiledFiles();
		if($compiledFiles === NULL){
			return NULL;
		}
		$file = $match[1];
		foreach($compiledFiles as $origFilePath => $compiledFile){
			$origFilePath = str_replace('\\', '/', $origFilePath); // standardize directory separator
			if(substr($origFilePath, strlen($origFilePath) - strlen($file)) === $file){ // relative path match od end of filesystem path
				return $compiledFile;
			}
		}
		return NULL;
	}



	/***************************************** backend ********************************************/

	private function calculateFilename($file)
	{
		$match = Nette\Utils\Strings::match(basename($file), '#^(.+)\.([^\.][a-zA-Z0-9]+)$#');
		return $match[1] . '-' . md5($file . filemtime($file)) . '.' . $match[2];
	}


	private function fixCssPaths($sourceFile, $compiledFile, $code)
	{
		if(!preg_match_all('#background(-image)?\s*:\s*[\#a-zA-Z0-9]*\s*url\(([^http:][^data:].+)\)#siU', $code, $matches)){
			return $code;
		}
		$relativePath  = $this->detectRelativePath($sourceFile, $compiledFile);

		foreach($matches[2] as $cssFile){
			$cssFile = str_replace(array("'", '"'), "", $cssFile);
			$absolutePath = str_replace('\\', "/", $relativePath) . "/" .  $cssFile;
			$absolutePath = str_replace("//", "/", $absolutePath);
			$absolutePath = $this->standardizePath($absolutePath);
			$code = str_replace($cssFile, $absolutePath, $code);
		}
		return $code;
	}


	private function detectRelativePath($sourceFile, $compiledFile)
	{
		$sourceFilePaths = explode(DIRECTORY_SEPARATOR, $sourceFile);
		$compiledFilePaths = explode(DIRECTORY_SEPARATOR, $compiledFile);
		$sharedPath = NULL;
		foreach($sourceFilePaths as $k => $v){
			$c = $compiledFilePaths[$k];
			if($c !== $v){
				$relativePath = str_repeat(DIRECTORY_SEPARATOR . "..", count(array_slice($compiledFilePaths, $k + 1)));
				$relativePath .= DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($sourceFilePaths, $k, count($sourceFilePaths) - $k - 1));
				return $relativePath;
			}
		}
		return NULL;
	}


	private function standardizePath($path)
	{
		$paths = explode("/", $path);
		array_shift($paths);
		foreach($paths as $k => $v){
			if($v === ".." && $k !== 0 && $paths[$k - 1] !== ".."){
				unset($paths[$k]);
				unset($paths[$k - 1]);
			}
		}
		return implode("/", $paths);
	}

}