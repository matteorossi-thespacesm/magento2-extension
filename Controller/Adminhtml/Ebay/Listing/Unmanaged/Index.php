<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Unmanaged;

class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    public function execute()
    {
        if ($this->getRequest()->getQuery('ajax')) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Unmanaged\Grid::class)
            );

            return $this->getResult();
        }

        $this->addContent(
            $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Unmanaged::class)
        );
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('All Unmanaged Items'));
        $this->setPageHelpLink('x/FP8UB');

        return $this->getResult();
    }
}
