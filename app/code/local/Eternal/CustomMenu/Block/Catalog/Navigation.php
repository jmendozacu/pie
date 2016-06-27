<?php

class Eternal_CustomMenu_Block_Catalog_Navigation extends Mage_Catalog_Block_Navigation
{
    const CUSTOM_BLOCK_TEMPLATE = "eternal_custommenu_%d";

    private $_productsCount = null;

    public function showHomeLink()
    {
        return Mage::getStoreConfig('eternal_custommenu/general/show_home_link');
    }
    
    public function showHomeIcon()
    {
        return Mage::getStoreConfig('eternal_custommenu/general/show_home_icon');
    }
    
    // get home icon
    public function getHomeIcon() 
    {
        $icon = Mage::getStoreConfig('eternal_custommenu/general/home_icon');
        if ($icon)
            return Mage::getBaseUrl('media') . 'eternal/custommenu/' . $icon;
        return Mage::getBaseUrl('media') . 'eternal/custommenu/icon-home.png';
    }
    
    // get custom block
    public function getCustomBlock() {
        $block_id = Mage::getStoreConfig('eternal_custommenu/custom/block');
        return $block_id;
    }
    
    // get custom links
    public function getCustomLinks() {
        $block_id = Mage::getStoreConfig('eternal_custommenu/custom/links');
        return $block_id;
    }
    
    // get custom mobile links
    public function getCustomMobileLinks() {
        $block_id = Mage::getStoreConfig('eternal_custommenu/custom/mobile_links');
        return $block_id;
    }

    public function drawCustomMenuMobileItem($category, $level = 0, $last = false)
    {
        if (!$category->getIsActive()) return '';
        $html = array();
        $id = $category->getId();
        // --- Sub Categories ---
        $activeChildren = $this->_getActiveChildren($category, $level);
        // --- class for active category ---
        $active = ''; if ($this->isCategoryActive($category)) $active = ' act';
        $hasSubMenu = count($activeChildren);
        $catUrl = $this->getCategoryUrl($category);
        $html[] = '<div id="menu-mobile-' . $id . '" class="menu-mobile level0' . $active . '">';
        $html[] = '<div class="parentMenu">';
        // --- Top Menu Item ---
        $html[] = '<a href="' . $catUrl .'">';
        $name = $this->escapeHtml($category->getName());
        if (Mage::getStoreConfig('eternal_custommenu/general/non_breaking_space')) {
            $name = str_replace(' ', '&nbsp;', $name);
        }
        $html[] = '<span>' . $name . '</span>';
        $html[] = '</a>';
        if ($hasSubMenu) {
            $html[] = '<span class="button" rel="submenu-mobile-' . $id . '" onclick="eternalSubMenuToggle(this, \'menu-mobile-' . $id . '\', \'submenu-mobile-' . $id . '\');">&nbsp;</span>';
        }
        $html[] = '</div>';
        // --- Add Popup block (hidden) ---
        if ($hasSubMenu) {
            // --- draw Sub Categories ---
            $html[] = '<div id="submenu-mobile-' . $id . '" rel="level' . $level . '" class="eternal-custom-menu-submenu" style="display: none;">';
            $html[] = $this->drawMobileMenuItem($activeChildren);
            $html[] = '<div class="clearBoth"></div>';
            $html[] = '</div>';
        }
        $html[] = '</div>';
        $html = implode("\n", $html);
        return $html;
    }

    public function drawCustomMenuItem($category, $level = 0, $last = false)
    {
        if (!$category->getIsActive()) return '';
        $html = array();
        $id = $category->getId();
        // --- Static Block ---
        $blockId = sprintf(self::CUSTOM_BLOCK_TEMPLATE, $id); // --- static block key
        #Mage::log($blockId);
        $collection = Mage::getModel('cms/block')->getCollection()
            ->addFieldToFilter('identifier', array(array('like' => $blockId . '_w%'), array('eq' => $blockId)))
            ->addFieldToFilter('is_active', 1);
        $blockId = $collection->getFirstItem()->getIdentifier();
        #Mage::log($blockId);
        $blockHtml = $this->getLayout()->createBlock('cms/block')->setBlockId($blockId)->toHtml();
        // --- Sub Categories ---
        $activeChildren = $this->_getActiveChildren($category, $level);
        // --- class for active category ---
        $active = ''; if ($this->isCategoryActive($category)) $active = ' act';
        // --- Popup functions for show ---
        $drawPopup = ($blockHtml || count($activeChildren));
        if ($drawPopup) {
            $html[] = '<div id="menu' . $id . '" class="menu' . $active . '" onmouseover="eternalShowMenuPopup(this, event, \'popup' . $id . '\');" onmouseout="eternalHideMenuPopup(this, event, \'popup' . $id . '\', \'menu' . $id . '\')">';
        } else {
            $html[] = '<div id="menu' . $id . '" class="menu' . $active . '">';
        }
        // --- Top Menu Item ---
        $html[] = '<div class="parentMenu">';
        if ($level == 0 && $drawPopup) {
            $html[] = '<a href="javascript:void(0);" rel="'.$this->getCategoryUrl($category).'">';
        } else {
            $html[] = '<a href="'.$this->getCategoryUrl($category).'">';
        }
        $name = $this->escapeHtml($category->getName());
        if (Mage::getStoreConfig('eternal_custommenu/general/non_breaking_space')) {
            $name = str_replace(' ', '&nbsp;', $name);
        }
        $html[] = '<span>' . $name . '</span>';
        $html[] = '</a>';
        $html[] = '</div>';
        $html[] = '</div>';
        // --- Add Popup block (hidden) ---
        if ($drawPopup) {
            // --- Popup function for hide ---
            $html[] = '<div id="popup' . $id . '" class="eternal-custom-menu-popup" onmouseout="eternalHideMenuPopup(this, event, \'popup' . $id . '\', \'menu' . $id . '\')" onmouseover="eternalPopupOver(this, event, \'popup' . $id . '\', \'menu' . $id . '\')">';
            // --- draw Sub Categories ---
            if (count($activeChildren)) {
                $columns = (int)Mage::getStoreConfig('eternal_custommenu/columns/count');
                $html[] = '<div class="block1">';
                $html[] = $this->drawColumns($activeChildren, $columns);
                $html[] = '<div class="clearBoth"></div>';
                $html[] = '</div>';
            }
            // --- draw Custom User Block ---
            if ($blockHtml) {
                $html[] = '<div id="' . $blockId . '" class="block2">';
                $html[] = $blockHtml;
                $html[] = '</div>';
            }
            $html[] = '</div>';
        }
        $html = implode("\n", $html);
        return $html;
    }
    
    public function drawCustomMenuBlock()
    {
        $blockIdsString = $this->getCustomBlock();
        $blockIds = explode(",", str_replace(" ", "", $blockIdsString));
        $block_html = '';
        foreach ($blockIds as $blockId) {
            if (!$blockId)
                continue;
        
            $html = array();
            $block = Mage::getModel('cms/block')->setStoreId(Mage::app()->getStore()->getId())->load($blockId);
            if (!$block) continue;
            
            $blockTitle = $block->getTitle();
            $blockContent = $this->getLayout()->createBlock('cms/block')->setBlockId($blockId)->toHtml();        
            
            if (!$blockTitle || !$blockContent) continue;
            
            $html[] = '<div id="menu' . $blockId . '" class="menu ' . $blockId . '" onmouseover="eternalShowMenuPopup(this, event, \'popup' . $blockId . '\');" onmouseout="eternalHideMenuPopup(this, event, \'popup' . $blockId . '\', \'menu' . $blockId . '\')">';
            $html[] = '<div class="parentMenu">';
            $html[] = '<a href="javascript:void(0);" rel="#">';
            if (Mage::getStoreConfig('eternal_custommenu/general/non_breaking_space')) {
                $blockTitle = str_replace(' ', '&nbsp;', $blockTitle);
            }
            $html[] = '<span>' . $blockTitle . '</span>';
            $html[] = '</a>';
            $html[] = '</div>';
            $html[] = '</div>';
            // --- Popup function for hide ---
            $html[] = '<div id="popup' . $blockId . '" class="eternal-custom-menu-popup" onmouseout="eternalHideMenuPopup(this, event, \'popup' . $blockId . '\', \'menu' . $blockId . '\')" onmouseover="eternalPopupOver(this, event, \'popup' . $blockId . '\', \'menu' . $blockId . '\')">';
            if ($blockContent) {
                $html[] = '<div id="' . $blockId . '" class="block2">';
                $html[] = $blockContent;
                $html[] = '</div>';
            }
            $html[] = '</div>';
            $html = implode("\n", $html);
            $block_html .= $html;
        }
        return $block_html;
    }
    
    public function drawCustomMenuLinks()
    {
        $blockId = $this->getCustomLinks();
        if (!$blockId) return;
        
        $html = array();
        $block = Mage::getModel('cms/block')->setStoreId(Mage::app()->getStore()->getId())->load($blockId);
        if (!$block) return;
        
        return $this->getLayout()->createBlock('cms/block')->setBlockId($blockId)->toHtml();
    }
    
    public function drawCustomMobileMenuLinks()
    {
        $blockId = $this->getCustomMobileLinks();
        if (!$blockId) return;
        
        $html = array();
        $block = Mage::getModel('cms/block')->setStoreId(Mage::app()->getStore()->getId())->load($blockId);
        if (!$block) return;
        
        return $this->getLayout()->createBlock('cms/block')->setBlockId($blockId)->toHtml();
    }

    public function drawCustomNarrowMenuItem($category, $level = 0, $last = false)
    {
        if (!$category->getIsActive()) return '';
        $html = array();
        $id = $category->getId();
        // --- Sub Categories ---
        $activeChildren = $this->_getActiveChildren($category, $level);
        // --- class for active category ---
        $active = ''; 
        if ($this->isCategoryActive($category)) $active = ' act';
        $hasChilds = count($activeChildren);
        if ($level == 0)
            $active .= ' level-top';
        if ($hasChilds) {
            $html[] = '<li class="level' . $level . " " . $active . ' parent">';
        } else {
            $html[] = '<li class="level' . $level . " " . $active . '">';
        }
        // --- Top Menu Item ---
        if ($level == 0) {
            $html[] = '<a class="level-top" href="'.$this->getCategoryUrl($category).'">';
        } else {
            $html[] = '<a href="'.$this->getCategoryUrl($category).'">';
        }
        $name = $this->escapeHtml($category->getName());
        if (Mage::getStoreConfig('eternal_custommenu/general/non_breaking_space')) {
            $name = str_replace(' ', '&nbsp;', $name);
        }
        $html[] = '<span>' . $name . '</span>';
        $html[] = '</a>';
        // --- Add child categories (hidden) ---
        if ($hasChilds) {
            $html[] = '<ul class="level'.$level.'">';
            foreach ($activeChildren as $child) {
                $html[] = $this->drawCustomNarrowMenuItem($child, $level + 1);
            }
            $html[] = '</ul>';
        }
        $html[] = '</li>';
        $html = implode("\n", $html);
        return $html;
    }

    public function drawMobileMenuItem($children, $level = 1)
    {
        $keyCurrent = $this->getCurrentCategory()->getId();
        $html = '';
        foreach ($children as $child) {
            if (is_object($child) && $child->getIsActive()) {
                // --- class for active category ---
                $active = '';
                $id = $child->getId();
                $activeChildren = $this->_getActiveChildren($child, $level);
                if ($this->isCategoryActive($child)) {
                    $active = ' actParent';
                    if ($id == $keyCurrent) $active = ' act';
                }
                $html.= '<div id="menu-mobile-' . $id . '" class="itemMenu level' . $level . $active . '">';
                // --- format category name ---
                $name = $this->escapeHtml($child->getName());
                if (Mage::getStoreConfig('eternal_custommenu/general/non_breaking_space')) $name = str_replace(' ', '&nbsp;', $name);
                $html.= '<div class="parentMenu">';
                $html.= '<a class="itemMenuName level' . $level . '" href="' . $this->getCategoryUrl($child) . '"><span>' . $name . '</span></a>';
                if (count($activeChildren) > 0) {
                    $html.= '<span class="button" rel="submenu-mobile-' . $id . '" onclick="eternalSubMenuToggle(this, \'menu-mobile-' . $id . '\', \'submenu-mobile-' . $id . '\');">&nbsp;</span>';
                }
                $html.= '</div>';
                if (count($activeChildren) > 0) {
                    $html.= '<div id="submenu-mobile-' . $id . '" rel="level' . $level . '" class="eternal-custom-menu-submenu level' . $level . '" style="display: none;">';
                    $html.= $this->drawMobileMenuItem($activeChildren, $level + 1);
                    $html.= '<div class="clearBoth"></div>';
                    $html.= '</div>';
                }
                $html.= '</div>';
            }
        }
        return $html;
    }

    public function drawMenuItem($children, $level = 1)
    {
        $html = '<div class="itemMenu level' . $level . '">';
        $keyCurrent = $this->getCurrentCategory()->getId();
        foreach ($children as $child)
        {
            if (is_object($child) && $child->getIsActive())
            {
                // --- class for active category ---
                $active = '';
                if ($this->isCategoryActive($child))
                {
                    $active = ' actParent';
                    if ($child->getId() == $keyCurrent) $active = ' act';
                }
                // --- format category name ---
                $name = $this->escapeHtml($child->getName());
                if (Mage::getStoreConfig('eternal_custommenu/general/non_breaking_space'))
                    $name = str_replace(' ', '&nbsp;', $name);
                $html.= '<a class="itemMenuName level' . $level . $active . '" href="' . $this->getCategoryUrl($child) . '"><span>' . $name . '</span></a>';
                $activeChildren = $this->_getActiveChildren($child, $level);
                if (count($activeChildren) > 0)
                {
                    $html.= '<div class="itemSubMenu level' . $level . '">';
                    $html.= $this->drawMenuItem($activeChildren, $level + 1);
                    $html.= '</div>';
                }
            }
        }
        $html.= '</div>';
        return $html;
    }

    public function drawColumns($children, $columns = 1)
    {
        $html = '';
        // --- explode by columns ---
        if ($columns < 1) $columns = 1;
        $chunks = $this->_explodeByColumns($children, $columns);
        // --- draw columns ---
        $lastColumnNumber = count($chunks);
        $i = 1;
        foreach ($chunks as $key => $value)
        {
            if (!count($value)) continue;
            $class = '';
            if ($i == 1) $class.= ' first';
            if ($i == $lastColumnNumber) $class.= ' last';
            if ($i % 2) $class.= ' odd'; else $class.= ' even';
            $html.= '<div class="column' . $class . '">';
            $html.= $this->drawMenuItem($value, 1);
            $html.= '</div>';
            $i++;
        }
        return $html;
    }

    protected function _getActiveChildren($parent, $level)
    {
        $activeChildren = array();
        // --- check level ---
        $maxLevel = (int)Mage::getStoreConfig('eternal_custommenu/general/max_level');
        if ($maxLevel > 0)
        {
            if ($level >= ($maxLevel - 1)) return $activeChildren;
        }
        // --- / check level ---
        if (Mage::helper('catalog/category_flat')->isEnabled())
        {
            $children = $parent->getChildrenNodes();
            $childrenCount = count($children);
        }
        else
        {
            $children = $parent->getChildren();
            $childrenCount = $children->count();
        }
        $hasChildren = $children && $childrenCount;
        if ($hasChildren)
        {
            foreach ($children as $child)
            {
                if ($this->_isCategoryDisplayed($child))
                {
                    array_push($activeChildren, $child);
                }
            }
        }
        return $activeChildren;
    }

    private function _isCategoryDisplayed(&$child)
    {
        if (!$child->getIsActive()) return false;
        // === check products count ===
        // --- get collection info ---
        if (!Mage::getStoreConfig('eternal_custommenu/general/display_empty_categories'))
        {
            $data = $this->_getProductsCountData();
            // --- check by id ---
            $id = $child->getId();
            #Mage::log($id); Mage::log($data);
            if (!isset($data[$id]) || !$data[$id]['product_count']) return false;
        }
        // === / check products count ===
        return true;
    }

    private function _getProductsCountData()
    {
        if (is_null($this->_productsCount))
        {
            $collection = Mage::getModel('catalog/category')->getCollection();
            $storeId = Mage::app()->getStore()->getId();
            /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection */
            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('is_active')
                ->setProductStoreId($storeId)
                ->setLoadProductCount(true)
                ->setStoreId($storeId);
            $productsCount = array();
            foreach($collection as $cat)
            {
                $productsCount[$cat->getId()] = array(
                    'name' => $cat->getName(),
                    'product_count' => $cat->getProductCount(),
                );
            }
            #Mage::log($productsCount);
            $this->_productsCount = $productsCount;
        }
        return $this->_productsCount;
    }

    private function _explodeByColumns($target, $num)
    {
        if ((int)Mage::getStoreConfig('eternal_custommenu/columns/divided_horizontally')) {
            $target = self::_explodeArrayByColumnsHorisontal($target, $num);
        } else {
            $target = self::_explodeArrayByColumnsVertical($target, $num);
        }
        #return $target;
        if ((int)Mage::getStoreConfig('eternal_custommenu/columns/integrate') && count($target))
        {
            // --- combine consistently numerically small column ---
            // --- 1. calc length of each column ---
            $max = 0; $columnsLength = array();
            foreach ($target as $key => $child)
            {
                $count = 0;
                $this->_countChild($child, 1, $count);
                if ($max < $count) $max = $count;
                $columnsLength[$key] = $count;
            }
            // --- 2. merge small columns with next ---
            $xColumns = array(); $column = array(); $cnt = 0;
            $xColumnsLength = array(); $k = 0;
            foreach ($columnsLength as $key => $count)
            {
                $cnt+= $count;
                if ($cnt > $max && count($column))
                {
                    $xColumns[$k] = $column;
                    $xColumnsLength[$k] = $cnt - $count;
                    $k++; $column = array(); $cnt = $count;
                }
                $column = array_merge($column, $target[$key]);
            }
            $xColumns[$k] = $column;
            $xColumnsLength[$k] = $cnt - $count;
            // --- 3. integrate columns of one element ---
            $target = $xColumns; $xColumns = array(); $nextKey = -1;
            if ($max > 1 && count($target) > 1)
            {
                foreach($target as $key => $column)
                {
                    if ($key == $nextKey) continue;
                    if ($xColumnsLength[$key] == 1)
                    {
                        // --- merge with next column ---
                        $nextKey = $key + 1;
                        if (isset($target[$nextKey]) && count($target[$nextKey]))
                        {
                            $xColumns[] = array_merge($column, $target[$nextKey]);
                            continue;
                        }
                    }
                    $xColumns[] = $column;
                }
                $target = $xColumns;
            }
        }
        return $target;
    }

    private function _countChild($children, $level, &$count)
    {
        foreach ($children as $child)
        {
            if ($child->getIsActive())
            {
                $count++; $activeChildren = $this->_getActiveChildren($child, $level);
                if (count($activeChildren) > 0) $this->_countChild($activeChildren, $level + 1, $count);
            }
        }
    }

    private static function _explodeArrayByColumnsHorisontal($list, $num)
    {
        if ($num <= 0) return array($list);
        $partition = array();
        $partition = array_pad($partition, $num, array());
        $i = 0;
        foreach ($list as $key => $value) {
            $partition[$i][$key] = $value;
            if (++$i == $num) $i = 0;
        }
        return $partition;
    }

    private static function _explodeArrayByColumnsVertical($list, $num)
    {
        if ($num <= 0) return array($list);
        $listlen = count($list);
        $partlen = floor($listlen / $num);
        $partrem = $listlen % $num;
        $partition = array();
        $mark = 0;
        for ($column = 0; $column < $num; $column++) {
            $incr = ($column < $partrem) ? $partlen + 1 : $partlen;
            $partition[$column] = array_slice($list, $mark, $incr);
            $mark += $incr;
        }
        return $partition;
    }
}