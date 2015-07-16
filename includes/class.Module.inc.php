<?php
 
class Module {
	private $name;
	private $order;
	private $content; // html

	public function __construct($name, $order, $content) {
		$this->$name = $name;
		$this->$order = $order;
		$this->$content = $content;
	}
	
	public function getName() {
		return $this->$name;
	}

	public function getOrder() {
		return $this->$order;
	}

	public function getContent() {
		return $this->$content;
	}
}

?>