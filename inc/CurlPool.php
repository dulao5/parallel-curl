<?php

class CurlPool {
	
	protected static $_instance;

	protected $_idle;
	protected $_using;
	protected $_pool_map;

	CONST HANDLER_USED_COUNT_LIMIT = 100;

	private function __construct() {
		$this->_idle = array();
		$this->_using = array();
		$this->_pool_map = array();
	}

	public function __destruct() {
		foreach($this->_pool_map as $id => $obj) {
				$this->freeCurlHandler($obj);
			}
	}

	public static function instance() {
		if(empty(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __clone() {
		trigger_error('Clone is not allowed.', E_USER_ERROR);
	}

	public function requestCurlHandler($url, $opts = array()) {
		$pool_key = $this->getPoolKey($url, $opts);

		$obj = $this->popIdle($pool_key);
		if(!$obj) {
			$obj = $this->genHandlerObject($pool_key);
		}
		$this->pushUsing($obj);
		return $this->resetCurlHandler($obj['handler'], $url, $opts);
	}

	public function releaseCurlHandler($curl) {
		$index = (int) $curl;
		$pool_key = $this->_pool_map[$index]['pool_key'];
		assert(!empty($pool_key));

		$obj = $this->popUsing($pool_key, $curl);
		assert(!empty($obj));

		//unset handle if count > limit
		if($obj['count'] >= self::HANDLER_USED_COUNT_LIMIT)  {
			$this->freeCurlHandler($obj);
		}
		else {
			$this->pushIdle($obj);
		}
	}

	protected function freeCurlHandler($obj) {
		$index = (int) $obj['handler'];
		curl_close($obj['handler']);
		unset($this->_pool_map[$index]);
	}

	protected function resetCurlHandler($curl, $url, $opts) {
		//FIXME: php not support curl_easy_reset

		curl_setopt($curl, CURLOPT_URL, $url);

		curl_setopt_array($curl, $opts);

		return $curl;
	}


	protected function genHandlerObject($pool_key) {
		$obj = array(
				'handler' => curl_init() ,
				'count' => 0 ,
				'pool_key' => $pool_key
			);
		
		$index = (int) $obj['handler'];

		$this->_pool_map[$index] = $obj;
		$objref = & $obj;
		return $objref;
	}

	protected function popIdle($pool_key) {
		if(isset($this->_idle[$pool_key])) {
			if(count($this->_idle[$pool_key])) {
				return array_pop($this->_idle[$pool_key]);
			}
		}

		return null;
	}

	protected function pushIdle($obj) {
		$pool_key = $obj['pool_key'];
		$index = (int) $obj['handler'];

		if(!isset($this->_idle[$pool_key])) {
			$this->_idle[$pool_key] = array();
		}

		$this->_idle[$pool_key][$index] = $obj;
	}

	protected function popUsing($pool_key, $curl) {
		$index = (int) $curl;
		$obj = $this->_using[$pool_key][$index];

		unset($this->_using[$pool_key][$index]);
		return $obj;
	}

	protected function pushUsing($obj) {
		$pool_key = $obj['pool_key'];
		$index = (int) $obj['handler'];

		$obj['count'] ++;

		if(!isset($this->_using[$pool_key])) {
			$this->_using[$pool_key] = array();
		}

		$this->_using[$pool_key][$index] = $obj;
	}

	protected function getPoolKey($url, $opts) {
		//杯具, PHP无法支持curl_easy_reset, 也无opts的默认值完整资料
		//还想利用 live connections特性的安全做法:
			//   只能用HOST和opts-keys标记不同的CURL-POOL
		$opt_keys = array_keys($opts);
		$info = parse_url($url);
		return @$info['host'] . ":" . @$info['port'] 
			."#"
			. join(',', array_keys($opts));
	}
}
