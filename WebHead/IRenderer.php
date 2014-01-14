<?php


namespace WebHead;


interface IRenderer {

	public function render(Control $control, $mode = NULL);

} 