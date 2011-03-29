<?php

class BasicIterator implements Countable , ArrayAccess , Iterator {
	protected $_arr = array();
	protected $_pos = 0;

	public function __construct() {
		$this->_arr = array();
		$this->_pos = 0;
	}

	//Countable interface
	public function count() {
		return count($this->_arr);
	}

	//Iterator interface
	
	public function next() {
		++$this->_pos;
		return $this->current();
	}
	public function valid() {
		return isset($this->_arr[$this->_pos]);
	}

	public function current() {
		return @$this->_arr[$this->_pos];
	}

	public function rewind() {
		$this->_pos = 0;
	}

	public function key() {
		return $this->_pos;
	}

	//array access interface
	
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->_arr[] = $value;
		} else {
			$this->_arr[$offset] = $value;
		}
	}
	public function offsetExists($offset) {
		return isset($this->_arr[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->_arr[$offset]);
	}
	public function offsetGet($offset) {
		return isset($this->_arr[$offset]) ? $this->_arr[$offset] : null;
	}

}


