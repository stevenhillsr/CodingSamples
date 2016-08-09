<?php
/**
 * Plugin that runs at beginning of dispatch loop
 *     to select up to 2 display add images from those weighted images
 *     - 0 if there are none, random selection if more than 2
 *
 *
 * @author Steven Hill Sr ( steven.hill.sr at gmail com )
 */

class App_Controller_Plugin_AdsSetup extends Zend_Controller_Plugin_Abstract
{
  public function dispatchLoopStartup (Zend_Controller_Request_Abstract $request)
  {
    $controllerName = $request->getControllerName();
    $actionName = $request->getActionName();

    $config = Zend_Registry::get('config');

    $user = false;
    if ($config->loggedInUserId != 0) {
      $user = Application_Model_UsersTable::getInstance()->find($config->loggedInUserId);
      if ($user) {
        $dealer_id = $user->getPrimaryDealerId();
      } else {
        $dealer_id = 0;
      }
    } else {
      $dealer_id = 0;
    }
    if ($controllerName == 'dealer' && $actionName == 'view') {
      $dealer_id = $request->getParam('id',$dealer_id);
    }

    $adsArray = array();
    
    $db = Zend_Registry::get('config')->awdbAdaptor;

    $sql  = "SELECT a.display_ad_id, a.weight ";
    $sql .= "  FROM dealer_display_ad a ";
    if (! $user) {
      $sql .= "  JOIN display_ad d on a.display_ad_id = d.id";
    }
    $sql .= " WHERE a.dealer_id = " . $db->quote($dealer_id);
    
    if (! $user) {
      $sql .= "  AND d.public_ad = 1";
    }

    $adsArray = $db->fetchAll($sql);

//    error_log('AdsSetup - display_ad results: ' . print_r($adsArray,TRUE));
    
    $adsArrayCount = count($adsArray);
    
    switch ($adsArrayCount) {
      case 0:  $config->leftAdImgId  = 0;
               $config->rightAdImgId = 0;
               break;
      case 1:  $config->leftAdImgId  = $adsArray[0]['display_ad_id'];
               $config->rightAdImgId = 0;
               break;
      case 2:  $config->leftAdImgId  = $adsArray[0]['display_ad_id'];
               $config->rightAdImgId = $adsArray[1]['display_ad_id'];
               break;
      default: $leftRandom = 0;
               $rightRandom = 0;
               $extendedAdsArray = array();
               foreach ($adsArray as $display_ad) {
                 for ($index = 0; $index < $display_ad['weight']; $index++) {
                   $extendedAdsArray[] = $display_ad['display_ad_id'];
                 }
               }
//    error_log('AdsSetup - extrnded Ads array: ' . print_r($extendedAdsArray,TRUE));
               $extCount = count($extendedAdsArray);
               $randomCount = $extCount - 1;
               while ($leftRandom == $rightRandom) {
                 $leftRandom  = rand(0,$randomCount);
                 $rightRandom = rand(0,$randomCount);
               }

               $config->leftAdImgId  = $extendedAdsArray[$leftRandom];
               $config->rightAdImgId = $extendedAdsArray[$rightRandom];
               break;
    }
    Zend_Registry::set('config', $config);

  }
}
?>
