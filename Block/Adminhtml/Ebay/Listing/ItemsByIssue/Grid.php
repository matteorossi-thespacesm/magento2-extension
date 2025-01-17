<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\ItemsByIssue;

use Ess\M2ePro\Block\Adminhtml\Tag\Switcher as TagSwitcher;
use Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite;
use Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\Collection as RelationCollection;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory */
    private $relationCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Tag */
    private $tagResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product */
    private $listingProductResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\CollectionFactory $relationCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product $listingProductResource,
        \Ess\M2ePro\Model\ResourceModel\Tag $tagResource,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);

        $this->tagResource = $tagResource;
        $this->relationCollectionFactory = $relationCollectionFactory;
        $this->listingProductResource = $listingProductResource;
    }

    /**
     * @ingeritdoc
     */
    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingItemsByIssueGrid');

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('total_items');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    /**
     * @ingeritdoc
     */
    protected function _prepareLayout()
    {
        $this->css->addFile('ebay/listing/itemsByIssue/grid.css');

        return parent::_prepareLayout();
    }

    /**
     * @inheridoc
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/ebay_listing/itemsByIssue', ['_current' => true]);
    }

    /**
     * @ingeritdoc
     */
    protected function _prepareCollection()
    {
        $collection = $this->relationCollectionFactory->create();

        $collection->getSelect()->join(
            ['tag' => $this->tagResource->getMainTable()],
            'main_table.tag_id = tag.id'
        );

        $collection->join(
            ['lp' => $this->listingProductResource->getMainTable()],
            'main_table.listing_product_id = lp.id'
        );

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'total_items' => new \Magento\Framework\DB\Sql\Expression('COUNT(*)'),
            'tag_nick' => 'tag.nick',
            'tag_id' => 'tag.id',
            'error_code' => 'tag.error_code',
        ]);
        $collection->getSelect()->where('tag.nick != ?', \Ess\M2ePro\Model\Tag::NICK_HAS_ERROR);
        $collection->getSelect()->group('main_table.tag_id');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @ingeritdoc
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'error_code',
            [
                'header' => __('Error Code'),
                'align' => 'left',
                'type' => 'text',
                'index' => 'error_code',
                'sortable' => false,
                'filter_index' => 'tag.nick',
                'filter_condition_callback' => [$this, 'callbackFilterErrorCode'],
                'column_css_class' => 'ebay-listing-by-issue-grid-column-setting',
            ]
        );

        $this->addColumn(
            'issue',
            [
                'header' => __('Issue'),
                'align' => 'left',
                'index' => 'issue',
                'type' => 'text',
                'sortable' => false,
                'filter' => false,
                'frame_callback' => [$this, 'callbackColumnIssue'],
            ]
        );

        $this->addColumn(
            'total_items',
            [
                'header' => __('Total Items'),
                'align' => 'right',
                'type' => 'number',
                'index' => 'total_items',
                'filter' => false,
                'frame_callback' => [$this, 'callbackTotalItems'],
                'column_css_class' => 'ebay-listing-by-issue-grid-column-setting',
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * @param \Ess\M2ePro\Model\ResourceModel\Tag\ListingProduct\Relation\Collection $collection
     * @param \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
     *
     * @return void
     */
    protected function callbackFilterErrorCode(RelationCollection $collection, Rewrite $column): void
    {
        if ($errorCode = $column->getFilter()->getValue()) {
            $collection->getSelect()->where('tag.error_code LIKE ?', '%' . $errorCode . '%');
        }
    }

    /**
     * @param string|null $value
     * @param \Ess\M2ePro\Model\Tag\ListingProduct\Relation $row
     * @param \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
     * @param bool $isExport
     *
     * @return string
     */
    public function callbackColumnIssue(
        ?string $value,
        \Ess\M2ePro\Model\Tag\ListingProduct\Relation $row,
        Rewrite $column,
        bool $isExport
    ): string {

        if ($row->getData('tag_nick') === \Ess\M2ePro\Model\Tag::NICK_EBAY_MISSING_ITEM_SPECIFIC) {
            return __('Missing item specific');
        }

        return __('N/A');
    }

    /**
     * @param string $value
     * @param \Ess\M2ePro\Model\Tag\ListingProduct\Relation $row
     * @param \Ess\M2ePro\Block\Adminhtml\Widget\Grid\Column\Extended\Rewrite $column
     * @param bool $isExport
     *
     * @return string
     */
    public function callbackTotalItems(
        string $value,
        \Ess\M2ePro\Model\Tag\ListingProduct\Relation $row,
        Rewrite $column,
        bool $isExport
    ): string {
        $url = $this->getUrl(
            '*/ebay_listing/allItems',
            [TagSwitcher::TAG_ID_REQUEST_PARAM_KEY => $row->getData('tag_id')]
        );

        return sprintf("<a href='%s'>%s</a>", $url, $row->getData('total_items'));
    }

    /**
     * @param \Ess\M2ePro\Model\Tag\ListingProduct\Relation $row
     *
     * @return false
     */
    public function getRowUrl($row)
    {
        return false;
    }
}
