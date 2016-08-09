<?php
/**
 * Provides default functionality/properties for Controllers
 *
 * @dependency Application_Model_AuthUser::getIdentity()
 *
 * @author Steven Hill Sr ( steven.hill.sr at gmail com )
 */

class App_Controller_Action extends Zend_Controller_Action
{

  protected $_currentUserId;
  protected $_currentUserRoleId;
  protected $_currentUserCustomerId;
  protected $_currentUserDealerIds;

  protected $_activityLogged = FALSE;

  public function init() {
    $currentUser                 = Application_Model_AuthUser::getIdentity();
    if ($currentUser) {
      $this->_currentUserId         = $currentUser->id;
      $this->_currentUserRoleId     = $currentUser->employee_role;
      $this->_currentUserDealerIds  = $currentUser->getDealerIds();
    } else {
      $this->_currentUserId         = 0;
      $this->_currentUserRoleId     = 0;
      $this->_currentUserDealerIds  = array();
    }
  }

}
?>
