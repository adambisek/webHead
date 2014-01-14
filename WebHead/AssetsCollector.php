<?php

namespace WebHead;

use Nette;


class AssetsCollector extends Nette\Object
{

	private $control;

	private $assets;


	public function __construct(Control $control)
	{
		$this->control = $control;
	}


	public function addCss($css, array $params = NULL)
	{
		$params['media'] = isset($params['media']) ? $params['media'] : 'screen';
		$this->assets[$css] = array(
			'type' => 'css',
			'file' => $css,
			'priority' => isset($params['priority']) ? (int) $params['priority'] : 0,
		) + (array) $params;
	}


	public function addJs($js, array $params = NULL)
	{
		$this->assets[$js] = array(
			'type' => 'js',
			'file' => $js,
			'priority' => isset($params['priority']) ? (int) $params['priority'] : 0,
		) + (array) $params;
	}


	public function getCss()
	{
		$css = $this->filterType('css', $this->assets);
		$css = $this->sort($css);
		$return = array();
		foreach($css as $asset){
			$return[$asset['media']][] = $asset['file'];
		}
		return $this->formatResult($return);
	}


	public function getJs()
	{
		$js = $this->filterType('js', $this->assets);
		$js = $this->sort($js);
		$return = array();
		foreach($js as $asset){
			$return[] = $asset['file'];
		}
		return $this->formatResult($return);
	}


	public function getAll()
	{
		$assets = $this->assets;
		$assets = $this->sort($assets);
		return $this->formatResult($assets);
	}


	public function prepare()
	{
		$filtersCompiler = $this->getFiltersCompiler();
		if($filtersCompiler){
			$filtersCompiler->addFiles(iterator_to_array($this->getJs()));
			foreach($this->getCss() as $media => $files){
				$filtersCompiler->addFiles(iterator_to_array($files), $media);
			}
			if($filtersCompiler->isFilesJoined()){
				$compiledFiles = $filtersCompiler->getCompiledJoinedFiles();
				$this->assets = array();
				foreach($compiledFiles as $type => $groupFiles){
					foreach($groupFiles as $group => $file){
						if($type === 'css'){
							$this->addCss($file, array('media' => $group));
						}elseif($type === 'js'){
							$this->addJs($file);
						}
					}
				}
			}else{
				$compiledFiles = $filtersCompiler->getCompiledFiles();
				foreach($this->assets as & $asset){
					$file = $asset['file'];
					if(isset($compiledFiles[$file])){
						$asset['file'] = $compiledFiles[$file];
						$asset['compiled'] = TRUE;
					}
				}
			}
		}
	}


	private function getFiltersCompiler()
	{
		return $this->control->hasFiltersCompiler() ? $this->control->getFiltersCompiler() : NULL;
	}


	private function filterType($type, $assets)
	{
		$return = array();
		foreach($assets as $asset){
			if($asset['type'] !== $type){
				continue;
			}
			$return[] = $asset;
		}
		return $return;
	}


	private function sort($assets)
	{
		$sortedAssets = array();
		// sort into right order
		foreach($assets as $asset){
			$sortedAssets[$asset['priority']][] = $asset;
		}
		$return = array();
		foreach(array_reverse($sortedAssets) as $assets){
			foreach($assets as $asset){
				$return[] = $asset;
			}
		}
		return $return;
	}


	private function formatResult($assets)
	{
		return Nette\ArrayHash::from($assets);
	}

} 