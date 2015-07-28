<?php
 
class BasicModule {
	private $name;
	private $order;

	public function __construct($name, $order) {
		$this->name = $name;
		$this->order = $order;
	}

	public function getName() {
		return $this->name;
	}

	public function getOrder() {
		return $this->order;
	}

	public function getContent() {
		return '';
	}
}

?>