<?php
/**
 * Authentication Adapter for AutoWall
 *    authenticates against the users table
 *
 * @dependency Application_Model_Users::authenticate()
 *
 * @author Steven Hill Sr ( steven.hill.sr at gmail com )
 */

class App_Auth_Adapter
    implements Zend_Auth_Adapter_Interface
{
    const NOT_FOUND_MESSAGE = "Account not found";
    const BAD_PW_MESSAGE = "Password is invalid";

    /**
     *
     * @var Model_User
     */
    protected $user;

    /**
     *
     * @var string
     */
    protected $username;

    /**
     *
     * @var string
     */
    protected $password;

/**
 * App_Auth_Adapter provides AutoWall application specific authentication
 *
 *@param username
 *@param password
 *@return Auth_Adapter object
 */
    public function __construct($username , $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        try
        {
            $this->user = Application_Model_Users::authenticate($this->username, $this->password);
        }
        catch (Exception $e)
        {
            if ($e->getMessage() == Application_Model_Users::WRONG_PW)
                return $this->result(Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID, self::BAD_PW_MESSAGE);
            if ($e->getMessage() == Application_Model_Users::NOT_FOUND)
                return $this->result(Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND, self::NOT_FOUND_MESSAGE);
        }
//        error_log('Adaptor - authenticate - Success - user: ' . print_r($this->user,TRUE));
        return $this->result(Zend_Auth_Result::SUCCESS);
    }

    /**
     * Factory for Zend_Auth_Result
     *
     *@param integer    The Result code, see Zend_Auth_Result
     *@param mixed      The Message, can be a string or array
     *@return Zend_Auth_Result
     */
    public function result($code, $messages = array()) {
        if (!is_array($messages)) {
            $messages = array($messages);
        }

        return new Zend_Auth_Result(
            $code,
            $this->user,
            $messages
        );
    }
}