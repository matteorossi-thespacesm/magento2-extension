<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Any usage is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template\Shipping;

/**
 * Class \Ess\M2ePro\Model\Amazon\Template\Shipping\Source
 */
class Source extends \Ess\M2ePro\Model\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Magento\Product $magentoProduct
     */
    private $magentoProduct = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Shipping $shippingTemplateModel
     */
    private $shippingTemplateModel = null;

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     *
     * @return $this
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $this->magentoProduct = $magentoProduct;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        return $this->magentoProduct;
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Amazon\Template\Shipping $instance
     *
     * @return $this
     */
    public function setShippingTemplate(\Ess\M2ePro\Model\Amazon\Template\Shipping $instance)
    {
        $this->shippingTemplateModel = $instance;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Shipping
     */
    public function getShippingTemplate()
    {
        return $this->shippingTemplateModel;
    }

    //########################################

    /**
     * @return string
     */
    public function getTemplateName()
    {
        $result = '';

        switch ($this->getShippingTemplate()->getTemplateNameMode()) {
            case \Ess\M2ePro\Model\Amazon\Template\Shipping::TEMPLATE_NAME_VALUE:
                $result = $this->getShippingTemplate()->getTemplateNameValue();
                break;

            case \Ess\M2ePro\Model\Amazon\Template\Shipping::TEMPLATE_NAME_ATTRIBUTE:
                $result = $this->getMagentoProduct()->getAttributeValue(
                    $this->getShippingTemplate()->getTemplateNameAttribute()
                );
                break;
        }

        return $result;
    }

    //########################################
}
