<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Stop;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\Stop\Requester
 */
class Requester extends \Ess\M2ePro\Model\Walmart\Connector\Product\Requester
{
    //########################################

    public function getCommand()
    {
        return ['product', 'update', 'entities'];
    }

    //########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_STOP;
    }

    protected function getLockIdentifier()
    {
        $identifier = parent::getLockIdentifier();

        if (!empty($this->params['remove'])) {
            $identifier .= '_and_remove';
        }

        return $identifier;
    }

    protected function getLogsAction()
    {
        return !empty($this->params['remove']) ?
            \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_AND_REMOVE_PRODUCT :
            \Ess\M2ePro\Model\Listing\Log::ACTION_STOP_PRODUCT_ON_COMPONENT;
    }

    //########################################

    protected function validateListingProduct()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        $parentListingProduct = null;

        if ($variationManager->isRelationChildType()) {
            $parentListingProduct = $variationManager->getTypeModel()->getParentListingProduct();
        }

        $validator = $this->getValidatorObject();

        $validationResult = $validator->validate();

        if (!$validationResult && $this->listingProduct->isDeleted()) {
            if ($parentListingProduct !== null) {
                $parentListingProduct->load($parentListingProduct->getId());

                /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartParentListingProduct */
                $walmartParentListingProduct = $parentListingProduct->getChildObject();
                $walmartParentListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
            }

            return false;
        }

        foreach ($validator->getMessages() as $messageData) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData($messageData['text'], $messageData['type']);

            $this->storeLogMessage($message);
        }

        return $validationResult;
    }

    //########################################

    protected function validateAndProcessParentListingProduct()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->listingProduct->getChildObject();

        if (!$walmartListingProduct->getVariationManager()->isRelationParentType()) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\Listing\Product[] $childListingsProducts */
        $childListingsProducts = $walmartListingProduct->getVariationManager()
            ->getTypeModel()
            ->getChildListingsProducts();

        $filteredByStatusChildListingProducts = $this->filterChildListingProductsByStatus($childListingsProducts);
        $filteredByStatusNotLockedChildListingProducts = $this->filterLockedChildListingProducts(
            $filteredByStatusChildListingProducts
        );

        if (empty($this->params['remove']) && empty($filteredByStatusNotLockedChildListingProducts)) {
            $this->listingProduct->setData('no_child_for_processing', true);
            return false;
        }

        $notLockedChildListingProducts = $this->filterLockedChildListingProducts($childListingsProducts);

        if (count($childListingsProducts) != count($notLockedChildListingProducts)) {
            $this->listingProduct->setData('child_locked', true);
            return false;
        }

        if (!empty($this->params['remove'])) {
            $walmartListingProduct->getVariationManager()->switchModeToAnother();

            $this->listingProduct->addData([
                'status' => \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED,
            ]);
            $this->listingProduct->save();
            $this->getProcessingRunner()->stop();

            foreach ($childListingsProducts as $childListingProduct) {
                if ($childListingProduct->isNotListed() ||
                    $childListingProduct->isStopped() ||
                    $childListingProduct->isBlocked()
                ) {
                    $childListingProduct->delete();
                }
            }

            $this->listingProduct->delete();
        }

        if (empty($filteredByStatusNotLockedChildListingProducts)) {
            return true;
        }

        $childListingsProductsIds = [];
        foreach ($filteredByStatusNotLockedChildListingProducts as $listingProduct) {
            $childListingsProductsIds[] = $listingProduct->getId();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('id', ['in' => $childListingsProductsIds]);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $processChildListingsProducts */
        $processChildListingsProducts = $listingProductCollection->getItems();
        if (empty($processChildListingsProducts)) {
            return true;
        }

        foreach ($processChildListingsProducts as $childListingProduct) {
            // @codingStandardsIgnoreStart
            $processingRunner = $this->modelFactory->getObject('Walmart_Connector_Product_ProcessingRunner');
            $processingRunner->setParams(
                [
                    'listing_product_id' => $childListingProduct->getId(),
                    'configurator'       => $this->listingProduct->getActionConfigurator()->getSerializedData(),
                    'action_type'        => $this->getActionType(),
                    'lock_identifier'    => $this->getLockIdentifier(),
                    'requester_params'   => array_merge($this->params, ['is_parent_action' => true]),
                    'group_hash'         => $this->listingProduct->getProcessingAction()->getGroupHash(),
                ]
            );
            $processingRunner->start();
            // @codingStandardsIgnoreEnd
        }

        return true;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterChildListingProductsByStatus(array $listingProducts)
    {
        $resultListingProducts = [];

        foreach ($listingProducts as $id => $childListingProduct) {
            if ((!$childListingProduct->isListed() || !$childListingProduct->isStoppable()) &&
                empty($this->params['remove'])
            ) {
                continue;
            }

            $resultListingProducts[] = $childListingProduct;
        }

        return $resultListingProducts;
    }

    //########################################
}
