<?php

class CurlMFetcher {

	CONST DEFAULT_TUNNELS = 10;
	CONST DEFAULT_SELECT_TIMEOUT = 1;

	protected $_curlm = null;
	protected $_current_tunnels = 0;
	protected $_tunnels = 0;
	protected $_select_timeout = 0;

	protected $_tasklist = null;

	protected $_debug = false;

	public function __construct($task_list, 
								$tunnels = self::DEFAULT_TUNNELS , 
								$select_timeout = self::DEFAULT_SELECT_TIMEOUT) {
		$this->_tasklist = $task_list ;
		$this->_curlm = curl_multi_init();
		$this->_tunnels = $tunnels;
		$this->_select_timeout = $select_timeout;
	}

	public function __destruct() {
		curl_multi_close($this->_curlm);
	}

	public function run() {
		$this->loadTasks( $this->_tunnels - $this->_current_tunnels);

		while($this->_current_tunnels) {
			if($this->_debug) echo date("Y-m-d H:i:s.u")."\t".__FUNCTION__."\twhile({$this->_current_tunnels})\n";

			curl_multi_select($this->_curlm);

			//perform
			while(
				($r = curl_multi_exec($this->_curlm, $running)) 
				== CURLM_CALL_MULTI_PERFORM
			){
				if($this->_debug) echo date("Y-m-d H:i:s.u")."\t".__FUNCTION__."\tmulti_exec running:$running\n";
			}

			//try .  //process finished tunnels
			$need_load = false;
			while($inf = curl_multi_info_read($this->_curlm)) {
				$this->removeTask($inf);
				$need_load = true;
			}
			if($need_load) {
				$loaded = $this->loadTasks( $this->_tunnels - $this->_current_tunnels );
				//perform new tasks
				while( $loaded && 
					($r = curl_multi_exec($this->_curlm, $running)) 
					== CURLM_CALL_MULTI_PERFORM
				){
					if($this->_debug) echo date("Y-m-d H:i:s.u")."\t".__FUNCTION__."\tmulti_exec after loaded\n";
				}
			}
		}//while end
	}

	public function setDebug($flag) {
		$this->_debug = $flag;
	}

	protected function loadTasks($count) {
		
		if($this->_debug) echo date("Y-m-d H:i:s.u")."\t".__FUNCTION__."\t[$count] = {$this->_tunnels} - {$this->_current_tunnels}\n";

		for($i=0; $i<$count; $i++) {
			if(! $this->loadTask() ) {
				break;
			}
		}
		return $i;
	}

	protected function loadTask() {
			static $begin;
			if(!$begin) {
				$this->_tasklist->rewind();
				$task = $this->_tasklist->current();
				$begin = true;
			}
			else {
				$task = $this->_tasklist->next();
			}

			if($this->_debug) echo date("Y-m-d H:i:s.u")."\t".__FUNCTION__."\t[$task]\n";

			if(!$task) return false;
			
			$curl = $task->getCurlHandler();
			curl_multi_add_handle($this->_curlm , $curl);

			$this->_taskmap[ (int) $curl ] = $task;
			$this->_current_tunnels ++;

			return true;
	}


	protected function removeTask($inf) {
			$curl = $inf['handle'];
			$task = $this->_taskmap[(int)$curl];
			$task->finish();
			if($this->_debug) echo date("Y-m-d H:i:s.u")."\t".__FUNCTION__."\t[$task]\n";

			curl_multi_remove_handle($this->_curlm , $curl);
			unset( $this->_taskmap[ (int) $curl ] );
			$this->_current_tunnels --;

			return true;
	}

}
