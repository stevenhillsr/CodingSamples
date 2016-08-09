<?php
// $Id: jproxy.rest.inc, v 0.1 2015/07/21 10:07:00 steven.hill Exp $

/**
 * @file
 * REST services class for connecting to JPROXY rest services
 *
 */
class jproxyRest
{
  protected $_secret = 'mys3cr3tk3y';
  protected $_baseurl = 'https://example.com/apis';
  protected $_method = 'post';
  protected $_format = 'json';
  protected $_http_auth = '';
  protected $_service;
  protected $_module;
  protected $_action;
  protected $_request = array();


  public function getSecret() { return $this->_secret; }
  public function setSecret($val) { $this->_secret = $val; }

  public function getBaseurl() { return $this->_baseurl; }
  public function setBaseurl($val) { $this->_baseurl = $val; }

  public function getMethod() { return $this->_method; }
  public function setMethod($val) { $this->_method = $val; }

  public function getFormat() { return $this->_format; }
  public function setFormat($val) { $this->_format = $val; }

  public function getHttpAuth() { return $this->_http_auth; }
  public function setHttpAuth($val) { $this->_http_auth = $val; }

  public function getService() { return $this->_service; }
  public function setService($val) { $this->_service = $val; }

  public function getModule() { return $this->_module; }
  public function setModule($val) { $this->_module = $val; }

  public function getAction() { return $this->_action; }
  public function setAction($val) { $this->_action = $val; }

  public function getRequestKey($key) { return $this->_request[$key]; }
  public function setRequestKey($key, $val) { $this->_request[$key] = $val; }

  public function call() {
    $auth    = $this->_signArgs($this->_request, $this->_secret);
    $this->setRequestKey('auth',$auth);
    $headers = array('Content-Type' => 'application/x-www-form-urlencoded');

    if ($this->_http_auth != "") {
        $headers['Authorization'] = "Basic " . base64_encode($this->_http_auth);
    }

    $method  = strtoupper($this->_method);
    $format  = strtoupper($this->_format);
    $url     = $this->_baseurl;

    if ($this->_service != "") {
        $url .= "/$this->_service";
    }
    if ($this->_module != "") {
        $url .= "/$this->_module";
    }
    if ($this->_action != "") {
      $url .= "/$this->_action";
    }

    if ($this->getRequestKey("format") == "") {
      $this->setRequestKey("format",$format);
    }

    $method = 'POST';

    $options = array();
    $options["headers"] = $headers;
    $options["method"] = $method;
    $options["timeout"] = 60;
    $options["data"] = http_build_query($this->_request,'','&');

  //  error_log("jproxy.rest call - url: ${url} -- options: " . print_r($options,1));

    $content = drupal_http_request($url, $options);

  //  error_log("REST call result code: " . $content->code);
  //  error_log("BACK FROM REST CALL with content of type: " . gettype($content) . " with value of: " . print_r($content,TRUE));

    if (isset($_GET['debug'])) {
      echo "<div style='border: 1px black solid;'><p><pre>";
      echo "REST parameters: <br>";
      echo "url: " . $url . "<br>";
      echo "headers: " . print_r($headers,true) . "<br>";
      echo "method: " . strtoupper($method) . "<br>";
      echo "format: " . strtoupper($format) . "<br>";
      echo "request: " . print_r($this->_request,true) . "<br>";
      echo "raw results: " . print_r($content,true) . "<br>";
      echo "</pre></p></div>";
    }

    // extract the data from the response object
    if ($content->data) {
      $data = $content->data;
    } else {
      $data['NODATA'] = 'No data found for request: ' . $url . http_build_query($this->_request,'','&');
    }

    $callSuceeded = false;

//  error_log("REST call format: " . $format);
//  error_log("REST call data: " . print_r($data,TRUE));

    // check the data based on format - unpack data into content -- REPURPOSE $content
    //   default is to just set content to data
    $content = $data;
    switch (strtoupper($format)) {
        case "XML" :
                if ('<?xml' == substr($data, 0, 5)) {
                    $content = new SimpleXMLElement($data);
                    $callSuceeded = true;
                }
                break;
        case "JSON" :
//                error_log("jproxy.rest - call - checking response is json: ");
                $decoded_json_array = json_decode($data,1);
                $json_exit_code = json_last_error();
                if ($json_exit_code == JSON_ERROR_NONE) {
//                    error_log("         it appears to be json: " . print_r($decoded_json_array,1));
                    $content = $decoded_json_array;
                    $callSuceeded = true;
                } else {
                  error_log("           it appears to be invalid json - error code: ${json_exit_code}");
                }
                break;
        case "HTML" :
                // just asking for html block from some service assuming it will just be inserted into a page
                // and therefore, anything goes...  let assignment of data to content stand...
                $callSuceeded = true;
                break;
        default:
                // nothing to do, callSuceeded is false...
    }

    if ($callSuceeded) {
        if (isset($_GET['debug'])) {
            echo "<br><br><div style='border: 1px black solid;'><p>CONTENT OF REST CALL:<br><br><pre>";
            print_r($content);
            echo "</pre></p></div>";
        }
    } else {
        error_log("jproxy.rest call - url: ${url} -- options: " . print_r($options,1));
        error_log('Web service call failed with these results: ' . print_r($data,  true));
        drupal_set_message('It appears the web service call failed, Please try again soon and if it continues to fail notify the web adminisrator.');
    }
    return $content;
  }

  private function _signArgs($args, $secret) {

		ksort($args);
    	$a = '';
    	foreach($args as $k => $v)
      	{
         	$a .= $k . $v;
    	}
   		return md5($secret.$a);
	}

  public function getRestServer() {
    $make_absolute = array('absolute' => TRUE);
    return url("apis", $make_absolute);
  }
}
?>
