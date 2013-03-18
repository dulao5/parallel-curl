Parallel Curl
=============

A high performance parallel http client . The PHP library using multi curl .

The Features:
 * parcall http call 
 * has a easly task iterator 
 * live connection
 * other curl features

Classes
=======

 * CurlMFetcher : curl multiple fetcher
 * CurlPool : a curl cache pool
 * CurlTaskList : a curl task iterator 
 * CurlTaskAbstract : a abstract class , your task class should extends it

Usage
=====

> php test/test.php test/urls.txt
> cat test/test.php

```php
//autoload
function __autoload($classname) {
	static $lib_path ;
	if(!$lib_path) { 
		$lib_path = dirname(__FILE__)."/../inc/";
	}
	include("$lib_path/$classname.php");
}

//define your task implement 
class MyCurlTask extends CurlTaskAbstract {
	protected function onFinished() {
		$info = curl_getinfo($this->_curl);
		$contents = curl_multi_getcontent($this->_curl);
		//do something
		fprintf(STDERR, "%s\ttask\t%s\tfinished\n", date("Y-m-d H:i:s.u"), $this->_url);
		//todo
		//you can implement a http content parser , or save to file , or other
	}
}

//load task list

$tasklist = new CurlTaskList();
$opts = array(
		CURLOPT_VERBOSE => 0 ,
		CURLOPT_CONNECTTIMEOUT => 1,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => 'gzip,deflate',
		);

foreach(array_filter(array_map('trim', file($argv[1]))) as $url) {
		$tasklist->push(new MyCurlTask($url, $opts));
}

//using curl multi featcher and run
$tunnels = 4;
$curlm = new CurlMFetcher($tasklist, $tunnels);
$curlm->setDebug(true);

//run
$curlm->run();
```
