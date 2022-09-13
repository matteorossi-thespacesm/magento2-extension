<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class SaveRecentCategory extends Description
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Category */
    protected $categoryHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon\Category $categoryHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->categoryHelper = $categoryHelper;
    }

    //########################################

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getPost('marketplace_id');
        $browseNodeId  = $this->getRequest()->getPost('browsenode_id');
        $categoryPath  = $this->getRequest()->getPost('category_path');

        if (!$marketplaceId || !$browseNodeId || !$categoryPath) {
            $this->setJsonContent(['result' => false]);
            return $this->getResult();
        }

        $this->categoryHelper->addRecent(
            $marketplaceId,
            $browseNodeId,
            $categoryPath
        );

        $this->setJsonContent(['result' => true]);
        return $this->getResult();
    }

    //########################################
}
