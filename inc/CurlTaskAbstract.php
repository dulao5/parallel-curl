<?php

abstract class CurlTaskAbstract {

	protected $_url;
	protected $_opts;

	protected $_curl;

	public function __construct($url, $opts = array()) {
		$this->_url = $url;
		$this->_opts = $opts;
	}

	public function __toString() {return "Task: " . $this->_url;}

	public function getCurlHandler(){
		if(empty($this->_curl)) {
			return $this->genCurlHandler();
		}
		else {
			return $this->_curl;
		}
	}

	protected function genCurlHandler() {
		$curl_pool = CurlPool::instance();

		return $this->_curl 
				= $curl_pool->requestCurlHandler(
								$this->_url , 
								$this->_opts ) ;
	}

	public function finish() {
		$this->onFinished();
		$curl_pool = CurlPool::instance();
		$curl_pool->releaseCurlHandler($this->_curl, $this->_url);
	}

	//todo & parse result & analyze stat info
	abstract protected function onFinished() ;
}
