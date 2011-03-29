<?php

class CurlTaskList extends BasicIterator {

	public function __construct() {
		parent::__construct();
	}

	public function push($task) {
		$i = count($this->_arr);
		$this->_arr[$i] = $task;
	}

}

