<?php
/**
 * Magento Enterprise Edition
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magento Enterprise Edition License
 * that is bundled with this package in the file LICENSE_EE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.magentocommerce.com/license/enterprise-edition
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Page
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */

/**
 * Html page block
 *
 * @category   Mage
 * @package    Mage_Page
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class FCM_Page_Block_Html_Footer extends Mage_Core_Block_Template
{

    protected $_copyright;

    protected function _construct()
    {
         $cache = Mage::app()->getCacheInstance();
         $cache->banUse('full_page');
        /*$this->addData(array(
            'cache_lifetime'=> false,
            'cache_tags'    => array(Mage_Core_Model_Store::CACHE_TAG, Mage_Cms_Model_Block::CACHE_TAG)
        ));*/
    }

    /**
     * Get cache key informative items
     *
     * @return array
     */
    public function getCacheKeyInfo()
    {
        return array(
            'PAGE_FOOTER',
            Mage::app()->getStore()->getId(),
            (int)Mage::app()->getStore()->isCurrentlySecure(),
            Mage::getDesign()->getPackageName(),
            Mage::getDesign()->getTheme('template'),
            Mage::getSingleton('customer/session')->isLoggedIn()
        );
    }

    public function setCopyright($copyright)
    {
        $this->_copyright = $copyright;
        return $this;
    }

    public function getCopyright()
    {
        if (!$this->_copyright) {
            $this->_copyright = Mage::getStoreConfig('design/footer/copyright');
        }

        return $this->_copyright;
    }

    /**
     * Retrieve child block HTML, sorted by default
     *
     * @param   string $name
     * @param   boolean $useCache
     * @return  string
     */
    public function getChildHtml($name='', $useCache=true, $sorted=true)
    {
        return parent::getChildHtml($name, $useCache, $sorted);
    }
	
	/*
	public function canShowFBLoginPopup(){
		//below lines of code NOT working on certain browsers like IE7 and sometimes on Chrome, Opera
		//$visitorData = Mage::getSingleton('core/session')->getVisitorData();/////not working on certain browsers like IE7,
		//$curSessId = $visitorData['session_id'];
		
		$curSessId = session_id();
		
		$routeName = Mage::app()->getFrontController()->getRequest()->getRouteName();
		$identifier = Mage::getSingleton('cms/page')->getIdentifier();
		
		if($identifier=='home' && $routeName=='cms'){
			//If current page is home page then only show the fb login popup
			if(Mage::getSingleton('customer/session')->isLoggedIn()){
				//Customer is logged-in
				return false;
			}else{
				//Customer NOT logged-in
				//Check cookie, if set, dont show else show
				if (isset($_SESSION['fb_session_id'])) {
					return false;
				}else{
					//$cookiePath = Mage::getModel('core/cookie')->getPath();
					//setcookie("fb_session_id", $curSessId, 0, $cookiePath);
					$_SESSION['fb_session_id'] = 1;
					return true;
				}
			}
		}
	}*/
}