<?php
/**
 * Plugin that runs at beginning of route process
 *     to log activity for the user for this date
 *
 *
 * @author Steven Hill Sr ( steven.hill.sr at gmail com )
 */

class App_Controller_Plugin_UserActivity extends Zend_Controller_Plugin_Abstract
{
  public function routeStartup(Zend_Controller_Request_Abstract $request) {
    $config = Zend_Registry::get('config');

    if (empty($config->activityLogged)) {
      $uActivity = new Application_Model_UsersActvity();
      $uActivity->user_id = $config->loggedInUserId;
      $uActivity->ip_address = $this->getRequest()->getClientIp();
      $uActivity->last_active_on = date('Y-m-d H:i:s');
      $uActivity->save();
      $config->activityLogged = 1;
      Zend_Registry::set('config', $config);
    }
  }
}
?>
