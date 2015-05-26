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
 * @package     Mage_SalesRule
 * @copyright   Copyright (c) 2012 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://www.magentocommerce.com/license/enterprise-edition
 */


/**
 * Sales Rules resource collection model
 *
 * @category    Mage
 * @package     Mage_SalesRule
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_SalesRule_Model_Resource_Rule_Collection extends Mage_Rule_Model_Resource_Rule_Collection_Abstract
{
    /**
     * Store associated with rule entities information map
     *
     * @var array
     */
    protected $_associatedEntitiesMap = array(
        'website' => array(
            'associations_table' => 'salesrule/website',
            'rule_id_field'      => 'rule_id',
            'entity_id_field'    => 'website_id'
        ),
        'customer_group' => array(
            'associations_table' => 'salesrule/customer_group',
            'rule_id_field'      => 'rule_id',
            'entity_id_field'    => 'customer_group_id'
        )
    );

    /**
     * Set resource model and determine field mapping
     */
    protected function _construct()
    {
        $this->_init('salesrule/rule');
        $this->_map['fields']['rule_id'] = 'main_table.rule_id';
    }

    /**
     * Filter collection by specified website, customer group, coupon code, date.
     * Filter collection to use only active rules.
     * Involved sorting by sort_order column.
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @param string|null $now
     * @use $this->addWebsiteGroupDateFilter()
     *
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    public function setValidationFilter($websiteId, $customerGroupId, $couponCode = '', $now = null)
    {
        if (!$this->getFlag('validation_filter')) {

            /* We need to overwrite joinLeft if coupon is applied */
            $this->getSelect()->reset();
            parent::_initSelect();

            $this->addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now);
            $select = $this->getSelect();
			
            if (strlen($couponCode)) {
                // Created a virtual table to filter coupon code records before join
                $subSelect = $this->getConnection()->select()
                    ->from(array('rule_coupons_filtred' => $this->getTable('salesrule/coupon')), '*')
                    ->where('code = ?', $couponCode);
                
                /*$select->joinLeft(
                    array('rule_coupons' => $this->getTable('salesrule/coupon')),
                    'main_table.rule_id = rule_coupons.rule_id ',
                    array('code', 'usage_limit', 'usage_per_customer', 'expiration_date', 'is_primary', 'created_at')
                );*/
                
                // Provided filtered virtual table instead of actual table
                $select->joinLeft(
                    array('rule_coupons' => $subSelect),
                    'main_table.rule_id = rule_coupons.rule_id ',
                    array('code', 'usage_limit', 'usage_per_customer', 'expiration_date', 'is_primary', 'created_at')
                );
            $select->where('(main_table.coupon_type = ? ', Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON)
                ->orWhere('(main_table.coupon_type = ? AND rule_coupons.type = 0',
                    Mage_SalesRule_Model_Rule::COUPON_TYPE_AUTO)
                ->orWhere('main_table.coupon_type = ? AND main_table.use_auto_generation = 1 ' .
                    'AND rule_coupons.type = 1', Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
                /*->orWhere('main_table.coupon_type = ? AND main_table.use_auto_generation = 0 ' .
                    'AND rule_coupons.type = 0)', Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
                ->where('rule_coupons.code = ?)', $couponCode);*/
                ->orWhere('main_table.coupon_type = ? AND main_table.use_auto_generation = 0 ' .
                    'AND rule_coupons.type = 0))', Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC);
            } else {
                $this->addFieldToFilter('main_table.coupon_type', Mage_SalesRule_Model_Rule::COUPON_TYPE_NO_COUPON);
            }
            $this->setOrder('sort_order', self::SORT_ORDER_ASC);
            $this->setFlag('validation_filter', true);
        }
        //echo($this->getSelect());
        //die();
        return $this;
    }

    /**
     * Filter collection by website(s), customer group(s) and date.
     * Filter collection to only active rules.
     * Sorting is not involved
     *
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string|null $now
     * @use $this->addWebsiteFilter()
     *
     * @return Mage_SalesRule_Model_Mysql4_Rule_Collection
     */
    public function addWebsiteGroupDateFilter($websiteId, $customerGroupId, $now = null)
    {
        if (!$this->getFlag('website_group_date_filter')) {
            if (is_null($now)) {
                $now = Mage::getModel('core/date')->date('Y-m-d');
            }
            
            // Removed because we have single store.
            //$this->addWebsiteFilter($websiteId);

            $entityInfo = $this->_getAssociatedEntityInfo('customer_group');
            $connection = $this->getConnection();
			
			/* coupon code date is checked here to skip the promotion expiry date in case the coupon is set which has a different expiry */
			
			$_coupon_code = Mage::getSingleton('checkout/session')->getQuote()->getCouponCode();
			
			$use_promotion_expiry = true;
			
			if(isset($_coupon_code)){
				$coupon_object = Mage::getModel("salesrule/coupon")->loadByCode($_coupon_code);
			
				$coupon_data = $coupon_object->getData();
				
				if(!empty($coupon_data)){
					$coupon_expiry = $coupon_object->getExpirationDate();
					
					$current_date = date("Y-m-d H:i:s", Mage::getModel('core/date')->timestamp(time()));
					
					if($coupon_expiry >= $current_date){
						$use_promotion_expiry = false;
					}
				}
			}
			
			/* coupon code date is checked here to skip the promotion expiry date in case the coupon is set which has a different expiry */
			
			if($use_promotion_expiry){
				$this->getSelect()
                ->joinInner(
                    array('customer_group_ids' => $this->getTable($entityInfo['associations_table'])),
                    $connection->quoteInto(
                        'main_table.' . $entityInfo['rule_id_field']
                            . ' = customer_group_ids.' . $entityInfo['rule_id_field']
                            . ' AND customer_group_ids.' . $entityInfo['entity_id_field'] . ' = ?',
                        (int)$customerGroupId
                    ),
                    array()
                )
                ->where('from_date is null or from_date <= ?', $now)
                ->where('to_date is null or to_date >= ?', $now);
			}else{
				$this->getSelect()
                ->joinInner(
                    array('customer_group_ids' => $this->getTable($entityInfo['associations_table'])),
                    $connection->quoteInto(
                        'main_table.' . $entityInfo['rule_id_field']
                            . ' = customer_group_ids.' . $entityInfo['rule_id_field']
                            . ' AND customer_group_ids.' . $entityInfo['entity_id_field'] . ' = ?',
                        (int)$customerGroupId
                    ),
                    array()
                )
                ->where('from_date is null or from_date <= ?', $now)
                ->where("((to_date is null or to_date >= ?) AND (coupon_type=1)) OR (coupon_type!=1)", $now);
			}
			
            $this->addIsActiveFilter();

            $this->setFlag('website_group_date_filter', true);
        }

        return $this;
    }

    /**
     * Add primary coupon to collection
     *
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    public function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()
            ->joinLeft(
                array('rule_coupons' => $this->getTable('salesrule/coupon')),
                'main_table.rule_id = rule_coupons.rule_id',
                array('code', 'usage_limit', 'expiration_date', 'is_primary', 'created_at')
            );
        return $this;
    }

    /**
     * Find product attribute in conditions or actions
     *
     * @param string $attributeCode
     *
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    public function addAttributeInConditionFilter($attributeCode)
    {
        $match = sprintf('%%%s%%', substr(serialize(array('attribute' => $attributeCode)), 5, -1));
        $field = $this->_getMappedField('conditions_serialized');
        $cCond = $this->_getConditionSql($field, array('like' => $match));
        $field = $this->_getMappedField('actions_serialized');
        $aCond = $this->_getConditionSql($field, array('like' => $match));

        $this->getSelect()->where(sprintf('(%s OR %s)', $cCond, $aCond), null, Varien_Db_Select::TYPE_CONDITION);

        return $this;
    }

    /**
     * Excludes price rules with generated specific coupon codes from collection
     *
     * @return Mage_SalesRule_Model_Resource_Rule_Collection
     */
    public function addAllowedSalesRulesFilter()
    {
        $this->addFieldToFilter(
            'main_table.use_auto_generation',
            array('neq' => 1)
        );

        return $this;
    }
}