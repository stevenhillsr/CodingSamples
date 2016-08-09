<?php
// $Id: jproxy_cua.class.inc, v 0.1 2015/07/21 12:27:00 steven.hill Exp $

/**
 * @file
 * jproxy Current User Account class for the user's account
 *
 */

global $user;

$cp = dirname(drupal_get_path('module','jproxy_cua'));

require_once($cp . '/jproxy.rest.inc');


class jproxyCua
{
    /**
    *
    * @var array The collection of all CUA related data
    */
    protected static $_cua;

    /**
    *
    * @var string session variable
    */
    protected $sessionId;

    /**
    *
    * @var int the Drupal user id
    */
    protected $userId;

    /**
    *
    * @var int the JPROXY user id
    */
    protected $id;


    /**
    *
    * @var array the JPROXY user properties
    */
    protected $properties = array();

    /**
     * Singleton implementation for class
     *
     * @return self
     */
    public static function getInstance()
    {
        if ( ! isset(self::$_cua)) {
    //        error_log("CUA - getInstance: Not already present, call the constructor...");
            self::$_cua = new self();
    //    } else {
    //        error_log("CUA - getInstance: Already present, simply return it");
        }
        return self::$_cua;
    }

    /**
     * Private constructor - use getInstance
     */
    private function  __construct()
    {
        global $user;

        $this->sessionId = session_id();

        if (isset($_SESSION['old_session_id'])) {
            if ($_SESSION['old_session_id'] != $this->sessionId) {
                $this->resetSessionId();
            }
        }
        $_SESSION['old_session_id'] = $this->sessionId;

        if ($user) {
            $this->userId = $user->uid;
        } else {
            $this->userId = 0;
        }
        $pdc = @$_COOKIE['playDrupalConnector'];
        if ($pdc == null) { $pdc = "-2"; }
//        error_log("currentUserInfo - pdc: " . $pdc);
        $this->userId = $pdc;
        $this->getCuaInfo();
    }

    public function setUserId($v) {
        $this->userId = $v;
    }

    public function getUserId() {
        return $this->userId;
    }

    public function getProperties() {
        return $this->properties;
    }

    public function __get($key) {
        if (array_key_exists($key,$this->properties)) {
            return $this->properties[$key];
        } else {
            return null;
        }
    }

    public function getCuaInfo() {
        $rest = new jproxyRest;

        // the following jproxy_get_* functions are in the jproxy.fuctions.inc file 
        // located in the sites/default folder and included in settings.php 

        $rest->setHttpAuth(jproxy_get_http_auth());

        $rest->setSecret(jproxy_get_jproxy_webservice_secretkey());
        $rest->setBaseurl(jproxy_get_rest_server());
        $rest->setService('');
        $rest->setModule('rest');
        $rest->setAction('getCurrentUser');

        // standard filters
        $rest->setRequestKey('apikey', jproxy_get_jproxy_webservice_accesskey());
        $rest->setRequestKey('format', 'json');
        $rest->setRequestKey('user_id', $this->userId);
     //   $rest->setRequestKey('session_id', $this->sessionId);

        $rest->setMethod('post');


        $rest->setMethod('get');

    //    error_log("Checking method: " . $rest->getMethod());


        $content = $rest->call();

    //    error_log("BACK FROM REST CALL with content of type: " . gettype($content) . " with value of: $content");
        if (is_array($content)) {
            $this->_parseUserInfo($content);
        }
//        error_log("leaving getCuaInfo");

    }

    public function _parseUserInfo($userinfo) {
//        error_log("parsing User Info: " . print_r($userinfo,1));
        foreach ($userinfo as $k => $v) {
            $this->properties[$k] = $v;
        }
    }

    public function updateCuaInfo() {
        $rest = new jproxyRest;

        $rest->setHttpAuth(jproxy_get_http_auth());

        $rest->setSecret(jproxy_get_jproxy_webservice_secretkey());
        $rest->setBaseurl(jproxy_get_rest_server());
        $rest->setService('');
        $rest->setModule('rest');
        $rest->setAction('update-user-info');

        // standard filters
        $rest->setRequestKey('apikey', jproxy_get_jproxy_webservice_accesskey());
        $rest->setRequestKey('format', 'xml');
        $rest->setRequestKey('user_id', $this->userId);
        $rest->setRequestKey('session_id', $this->sessionId);
        $rest->setRequestKey('userinfo', $this->properties);

        $content = $rest->call();

        if ('<?xml' == substr($content, 0, 5)) {
            $content = new SimpleXMLElement($content);

//            error_log('Calling _parseUserInfo from updateCmaUserInfo....');
            $this->_parseUserInfo($content->data->userinfo);

            if (isset($_GET['debug'])) {
                echo "<br><br><div style='border: 1px black solid;'><p>CONTENT OF REST CALL:<br><br><pre>";
                print_r($content);
                echo "</pre></p></div>";
            }
        }
        else
        {
            error_log('WEB SERVICE CALL FAILED with these results: ' . print_r($content,  true));
            drupal_set_message('It appears the web service call failed, Please try again soon and if it continues to fail notify the web adminisrator.');
        }

    }

    public function resetSessionId() {
        $rest = new jproxyRest;

        $rest->setHttpAuth(jproxy_get_http_auth());

        $rest->setSecret(jproxy_get_jproxy_webservice_secretkey());
        $rest->setBaseurl(jproxy_get_rest_server());
        $rest->setService('');
        $rest->setModule('rest');
        $rest->setAction('reset-session-id');

        // standard filters
        $rest->setRequestKey('apikey', jproxy_get_jproxy_webservice_accesskey());
        $rest->setRequestKey('format', 'xml');
        $rest->setRequestKey('old_session_id', $_SESSION['old_session_id']);
        $rest->setRequestKey('session_id', $this->sessionId);

        $rest->setMethod('post');

        $content = $rest->call();

//        error_log('results of cma rest call: ' . print_r($content,  true));

        if ('<?xml' == substr($content, 0, 5)) {
//            error_log('It seems that $content[\'NODATA\'] is set');
            $content = new SimpleXMLElement($content);

            if ($content->status->code == "fail") {
                error_log ("Unable to migrate session to logged in user");
            }
        }
        else
        {
            error_log('Web service call failed with these results: ' . print_r($content,  true));
            drupal_set_message('It appears the web service call failed, Please try again soon and if it continues to fail notify the web adminisrator.');
        }
    }

}