<?php

/**
 * This helper tracks the user's browsing trail into the system allowing a return through the same path
 *
 * Basically, you use it to start recording where you go so that as you return, you can step backwards through the same path
 * 
 * So, lets say there are two paths to a list of items (../listOfItems), one from /pointA and one from /somewhereElse/pointB
 * Suppose you go to PointA, in pointA you would issue
 *   $trail = new App_Controller_Action_Helper_Trail('/pointA');
 * 
 * this will start a trail at '/pointA' if one does not already exist, otherwise it adds a marker for this point in the trail
 * 
 * and later in that code, possibly when returning from the form, you could issue
 * 
 *   $trail->goback(); // this will find the marker (path - url - whatever you want to call it) to return to
 * 
 * or perhaps you follow a different link to another location where you would
 * 
 * It is inspired by the History Helper by Jani Hartikainen <firstname at codeutopia net>
 *
 * @copyright 2011 Steven Hill Sr <www.circle-r-development.com>
 * @author Steven Hill Sr ( steven.hill.sr at gmail com )
 */
class App_Controller_Action_Helper_Trail extends Zend_Controller_Action_Helper_Abstract 
{
	/**
	 * @var Zend_Session_Namespace
	 */
	private $_namespace;
	
	/**
	 * @param string $marker [required] The identifier record the referring url for
	 * @param string $url [required] The referring url, where to go back to from this marker
	 */
	public function __construct($marker, $url)
	{
		$this->_initSession($marker, $url);
	}
	
	/**
	 * Initialize the trail from session
	 */
	private function _initSession($marker, $url)
	{
		$this->_namespace = new Zend_Session_Namespace('App_Controller_Action_Helper_Trail');
		
		if(!is_array($this->_namespace->trail))
		{
			$this->_namespace->trail = array();
    }
    $this->setMarker($marker,$url);
	}
	
	/**
	 * Unconditionally change the url in a marker
	 *
	 * @param int $marker [required] what level are we setting marker for, will check if it exists, store if not, trim to that level if it does
	 * @param int $url   [required] the url to add to the trail, allowing this to be back out of through here
	 */
	public function setMarker($marker,$url)
	{
    if (empty($this->_namespace->trail[$marker])) {
  		$this->_namespace->trail[$marker] = $url;
    }
	}
	
	private function clearMarker($marker)
	{
  	unset($this->_namespace->trail[$marker]);
  }

	/**
	 * Redirects the browser back to url stored at marker
	 *
	 * @param string $marker Storage marker holding url to redirect to
	 */
	public function goBack($marker)
	{
    $url = $this->_namespace->trail[$marker];
    if (count($this->_namespace->trail) == 0) {
      unset($this->_namespace->trail);
    } else {
      $this->clearMarker($marker);
    }
//    error_log('Helper_Trail - goBack: ' . print_r($this->getArray(),TRUE));
		Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')
		                                   ->setPrependBase(false)
		                                   ->gotoUrl($url);
	}

	/**
	 * Returns an URL from trail
	 *
	 * @param string $marker Get the url stored at marker
	 * @return string
	 */
	public function getPreviousUrl($marker)
	{
		return $this->_namespace->trail[$marker];
	}
	
	/**
	 * Return all previous URLs
   * 
   * Utiltiy function allowing the array to be retrieved for inspection if there are issues
	 *
	 * @return array
	 */
	public function getArray()
	{
		return $this->_namespace->trail;
	}
	
	public function getName()
	{
		return 'Trail';
	}
}