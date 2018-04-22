<?php
namespace agraddy\base;
use agraddy\base\Type;

class CPT {
	public $config = [];
	public $fields = [];

	function __construct() {
	}

	function config($key, $value) {
		$this->config[$key] = $value;
	}

	function create($singular, $plural, $cap = 'manage_options', $args = []) {
		return new Type($this->config['key'], $singular, $plural, $cap, $args);
	}
}

?>
