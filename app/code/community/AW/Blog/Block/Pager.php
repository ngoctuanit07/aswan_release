<?php
/**
* aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/AW-LICENSE-ENTERPRISE.txt
 *
 * =================================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * =================================================================
 * This package designed for Magento ENTERPRISE edition
 * aheadWorks does not guarantee correct work of this extension
 * on any other Magento edition except Magento ENTERPRISE edition.
 * aheadWorks does not provide extension support in case of
 * incorrect edition usage.
 * =================================================================
 *
 * @category   AW
 * @package    AW_Blog
 * @version    1.1.1
 * @copyright  Copyright (c) 2010-2012 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/AW-LICENSE-ENTERPRISE.txt
 */


class AW_Blog_Block_Pager extends Mage_Core_Block_Template {

	protected $_collection = null;
	
	protected $_limit = null;
		
    //protected $_availableLimit = array((int) Mage::getStoreConfig('blog/blog/perpage')=>(int) Mage::getStoreConfig('blog/blog/perpage'));
    protected $_pageVarName    = 'page';
    protected $_limitVarName   = 'limit';
	
    public function getCollection() {

        if ($this->getData('collection')) {
            return $this->getData('collection');
        }

        $collection = Mage::getModel('blog/blog')->getCollection()
                ->addPresentFilter()
                ->addStoreFilter();

        $tagFilter = '';
        if ($tag = $this->getRequest()->getParam('tag')) {
            $collection->addTagFilter(urldecode($tag));
            $tagFilter = "/tag/{$tag}/";
        }

        $this->setTagFilter($tagFilter);

        Mage::getSingleton('blog/status')->addEnabledFilterToCollection($collection);

        if ($this->getCategoryMode()) {
            $collection->addCatFilter($this->getCatId());
        }
		
		
		
		if(Mage::helper('catalogsearch')->getQueryText()){
			$search_helper = Mage::helper('catalogsearch')->getQueryText();
		
			$stringHelper = Mage::helper('core/string');
		
			$words = $stringHelper->splitWords($search_helper, true, 10);
			
			$collection->addContentFilter($words);
		}
		if ((int) $this->getLimit()) {
            $collection->setPageSize($this->getLimit());
			
			$this->setFrameLength($this->getLimit());
        }
		
		$collection->setCurPage($this->getCurrentPage());

        $this->setData('collection', $collection);

        return $this->getData('collection');
    }

    public function getCurrentPage() {

        $currentPage = (int) $this->getRequest()->getParam('page');
        if (!$currentPage) {
            $currentPage = 1;
        }

        return $currentPage;
    }

    public function getPagesCount() {

        return ceil($this->getCollection()->count() / (int) Mage::getStoreConfig('blog/blog/perpage'));
    }
	
	public function getLastPageNum()
    {
        return $this->getCollection()->getLastPageNumber();
    }
	
	public function getLimit()
    {
        if ($this->_limit !== null) {
            return $this->_limit;
        }
        $limits = $this->getAvailableLimit();
        if ($limit = $this->getRequest()->getParam($this->getLimitVarName())) {
            if (isset($limits[$limit])) {
                return $limit;
            }
        }
        $limits = array_keys($limits);
        return $limits[0];
    }
	
	public function getAvailableLimit()
    {
		$limit = (int) Mage::getStoreConfig('blog/blog/perpage');
        return array($limit => $limit);
    }
	
	public function getLimitVarName()
    {
        return $this->_limitVarName;
    }
	
	/**
     * Setter for limit items per page
     *
     * @param int $limit
     * @return Mage_Page_Block_Html_Pager
     */
    public function setLimit($limit)
    {
        $this->_limit = $limit;
        return $this;
    }

    public function setCollection($collection)
    {
        $this->_collection = $collection
            ->setCurPage($this->getCurrentPage());
        // If not int - then not limit
        if ((int) $this->getLimit()) {
            $this->_collection->setPageSize($this->getLimit());
        }

        $this->_setFrameInitialized(false);

        return $this;
    }

    public function setPageVarName($varName)
    {
        $this->_pageVarName = $varName;
        return $this;
    }

    public function getPageVarName()
    {
        return $this->_pageVarName;
    }

    public function setShowPerPage($varName)
    {
        $this->_showPerPage=$varName;
        return $this;
    }

    public function getShowPerPage()
    {
        if(sizeof($this->getAvailableLimit())<=1) {
            return false;
        }
        return $this->_showPerPage;
    }

    public function setLimitVarName($varName)
    {
        $this->_limitVarName = $varName;
        return $this;
    }

    public function setAvailableLimit(array $limits)
    {
        $this->_availableLimit = $limits;
    }

    public function getFirstNum()
    {
        $collection = $this->getCollection();
        return $collection->getPageSize()*($collection->getCurPage()-1)+1;
    }

    public function getLastNum()
    {
        $collection = $this->getCollection();
        return $collection->getPageSize()*($collection->getCurPage()-1)+$collection->count();
    }

    public function getTotalNum()
    {
        return $this->getCollection()->getSize();
    }

    public function isFirstPage()
    {
        return $this->getCollection()->getCurPage() == 1;
    }

    public function isLastPage()
    {
        return $this->getCollection()->getCurPage() >= $this->getLastPageNum();
    }

    public function isLimitCurrent($limit)
    {
        return $limit == $this->getLimit();
    }

    public function isPageCurrent($page)
    {
        return $page == $this->getCurrentPage();
    }

    public function getPages()
    {
        $collection = $this->getCollection();

        $pages = array();
        if ($collection->getLastPageNumber() <= $this->_displayPages) {
            $pages = range(1, $collection->getLastPageNumber());
        }
        else {
            $half = ceil($this->_displayPages / 2);
            if ($collection->getCurPage() >= $half && $collection->getCurPage() <= $collection->getLastPageNumber() - $half) {
                $start  = ($collection->getCurPage() - $half) + 1;
                $finish = ($start + $this->_displayPages) - 1;
            }
            elseif ($collection->getCurPage() < $half) {
                $start  = 1;
                $finish = $this->_displayPages;
            }
            elseif ($collection->getCurPage() > ($collection->getLastPageNumber() - $half)) {
                $finish = $collection->getLastPageNumber();
                $start  = $finish - $this->_displayPages + 1;
            }

            $pages = range($start, $finish);
        }
        return $pages;
    }

    public function getFirstPageUrl()
    {
        return $this->getPageUrl(1);
    }

    public function getPreviousPageUrl()
    {
        return $this->getPageUrl($this->getCollection()->getCurPage(-1));
    }

    public function getNextPageUrl()
    {
        return $this->getPageUrl($this->getCollection()->getCurPage(+1));
    }

    public function getLastPageUrl()
    {
        return $this->getPageUrl($this->getCollection()->getLastPageNumber());
    }

    public function getPageUrl($page)
    {
        return $this->getPagerUrl(array($this->getPageVarName()=>$page));
    }

    public function getLimitUrl($limit)
    {
        return $this->getPagerUrl(array($this->getLimitVarName()=>$limit));
    }

    public function getPagerUrl($params=array())
    {
        $urlParams = array();
        $urlParams['_current']  = true;
        $urlParams['_escape']   = true;
        $urlParams['_use_rewrite']   = true;
        $urlParams['_query']    = $params;
        return $this->getUrl('*/*/*', $urlParams);
    }

    /**
     * Getter for $_frameStart
     *
     * @return int
     */
    public function getFrameStart()
    {
        $this->_initFrame();
        return $this->_frameStart;
    }

    /**
     * Getter for $_frameEnd
     *
     * @return int
     */
    public function getFrameEnd()
    {
        $this->_initFrame();
        return $this->_frameEnd;
    }

    /**
     * Return array of pages in frame
     *
     * @return array
     */
    public function getFramePages()
    {
        $start = $this->getFrameStart();
        $end = $this->getFrameEnd();
        return range($start, $end);
    }

    /**
     * Return page number of Previous jump
     *
     * @return int
     */
    public function getPreviousJumpPage()
    {
        if (!$this->getJump()) {
            return null;
        }
        $frameStart = $this->getFrameStart();
        if ($frameStart - 1 > 1) {
            return max(2, $frameStart - $this->getJump());
        }

        return null;
    }

    /**
     * Prepare URL for Previous Jump
     *
     * @return string
     */
    public function getPreviousJumpUrl()
    {
        return $this->getPageUrl($this->getPreviousJumpPage());
    }

    /**
     * Return page number of Next jump
     *
     * @return int
     */
    public function getNextJumpPage()
    {
        if (!$this->getJump()) {
            return null;
        }
        $frameEnd = $this->getFrameEnd();
        if ($this->getLastPageNum() - $frameEnd > 1) {
            return min($this->getLastPageNum() - 1, $frameEnd + $this->getJump());
        }

        return null;
    }

    /**
     * Prepare URL for Next Jump
     *
     * @return string
     */
    public function getNextJumpUrl()
    {
        return $this->getPageUrl($this->getNextJumpPage());
    }

    /**
     * Getter for $_frameLength
     *
     * @return int
     */
    public function getFrameLength()
    {
        return $this->_frameLength;
    }

    /**
     * Getter for $_jump
     *
     * @return int
     */
    public function getJump()
    {
        return $this->_jump;
    }

    /**
     * Setter for $_frameLength
     *
     * @param int $frame
     * @return Mage_Page_Block_Html_Pager
     */
    public function setFrameLength($frame)
    {
        $frame = abs(intval($frame));
        if ($frame == 0) {
            $frame = $this->_frameLength;
        }
        if ($this->getFrameLength() != $frame) {
            $this->_setFrameInitialized(false);
            $this->_frameLength = $frame;
        }

        return $this;
    }

    /**
     * Setter for $_jump
     *
     * @param int $jump
     * @return Mage_Page_Block_Html_Pager
     */
    public function setJump($jump)
    {
        $jump = abs(intval($jump));
        if ($this->getJump() != $jump) {
            $this->_setFrameInitialized(false);
            $this->_jump = $jump;
        }

        return $this;
    }

    /**
     * Whether to show first page in pagination or not
     *
     * @return bool
     */
    public function canShowFirst()
    {
        return $this->getJump() > 1 && $this->getFrameStart() > 1;
    }

    /**
     * Whether to show last page in pagination or not
     *
     * @return bool
     */
    public function canShowLast()
    {
        return $this->getJump() > 1 && $this->getFrameEnd() < $this->getLastPageNum();
    }

    /**
     * Whether to show link to Previous Jump
     *
     * @return bool
     */
    public function canShowPreviousJump()
    {
        return $this->getPreviousJumpPage() !== null;
    }

    /**
     * Whether to show link to Next Jump
     *
     * @return bool
     */
    public function canShowNextJump()
    {
        return $this->getNextJumpPage() !== null;
    }

    /**
     * Initialize frame data, such as frame start, frame start etc.
     *
     * @return Mage_Page_Block_Html_Pager
     */
    protected function _initFrame()
    {
        if (!$this->isFrameInitialized()) {
            $start = 0;
            $end = 0;

            $collection = $this->getCollection();
            if ($collection->getLastPageNumber() <= $this->getFrameLength()) {
                $start = 1;
                $end = $collection->getLastPageNumber();
            }
            else {
                $half = ceil($this->getFrameLength() / 2);
                if ($collection->getCurPage() >= $half && $collection->getCurPage() <= $collection->getLastPageNumber() - $half) {
                    $start  = ($collection->getCurPage() - $half) + 1;
                    $end = ($start + $this->getFrameLength()) - 1;
                }
                elseif ($collection->getCurPage() < $half) {
                    $start  = 1;
                    $end = $this->getFrameLength();
                }
                elseif ($collection->getCurPage() > ($collection->getLastPageNumber() - $half)) {
                    $end = $collection->getLastPageNumber();
                    $start  = $end - $this->getFrameLength() + 1;
                }
            }
            $this->_frameStart = $start;
            $this->_frameEnd = $end;

            $this->_setFrameInitialized(true);
        }

        return $this;
    }

    /**
     * Setter for flag _frameInitialized
     *
     * @param bool $flag
     * @return Mage_Page_Block_Html_Pager
     */
    protected function _setFrameInitialized($flag)
    {
        $this->_frameInitialized = (bool)$flag;
        return $this;
    }

    /**
     * Check if frame data was initialized
     *
     * @return Mage_Page_Block_Html_Pager
     */
    public function isFrameInitialized()
    {
        return $this->_frameInitialized;
    }

    /**
     * Getter for alternative text for Previous link in pagination frame
     *
     * @return string
     */
    public function getAnchorTextForPrevious()
    {
        return Mage::getStoreConfig('design/pagination/anchor_text_for_previous');
    }

    /**
     * Getter for alternative text for Next link in pagination frame
     *
     * @return string
     */
    public function getAnchorTextForNext()
    {
        return Mage::getStoreConfig('design/pagination/anchor_text_for_next');
    }

    /**
     * Set whether output of the pager is mandatory
     *
     * @param bool $isRequired
     * @return Mage_Page_Block_Html_Pager
     */
    public function setIsOutputRequired($isRequired)
    {
        $this->_outputRequired = (bool)$isRequired;
        return $this;
    }

    /**
     * Determine whether the pagination should be eventually rendered
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->_outputRequired || $this->getTotalNum() > $this->getLimit()) {
            return parent::_toHtml();
        }
        return '';
    }

}