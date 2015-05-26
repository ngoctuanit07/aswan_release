<?php
/**
*************************************************************************************
 Please Do not edit or add any code in this file without permission of bluezeal.in.
@Developed by bluezeal.in

Magento version 1.7.0.2                 CCAvenue Version 1.31
                              
Module Version. bz-1.0                 Module release: September 2012
**************************************************************************************
*/
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
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
 * @category   Mage
 * @package    Mage_ccavenuepay
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Ccavenuepay_Model_Source_Ccavenuepaytype
{
    
    public function getAllowedTypes()
    {
        return array();
    }

    public function toOptionArray()
    {
       
        $allowed = $this->getAllowedTypes();
        $options = array();

        foreach (Mage::getSingleton('ccavenuepay/config')->getCcavenuepayTypes() as $code => $name) {
            if (in_array($code, $allowed) || !count($allowed)) {
                $options[] = array(
                   'value' => $code,
                   'label' => $name
                );
            }
        }

        return $options;
    }
}