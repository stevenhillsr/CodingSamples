<?php
/**
 * Description of Adapter
 *
 * @author steven
 */
class App_Auth_SidedoorAdapter
    implements Zend_Auth_Adapter_Interface
{
    /**
     *
     * @var Model_User
     */
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }
    /**
     * We have the user already, just need to fudge authentication so hasIdentity works
     *
     * @return Zend_Auth_Result::SUCCESS
     */
    public function authenticate()
    {
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