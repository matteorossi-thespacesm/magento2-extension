define([
    'M2ePro/Plugin/Messages',
    'M2ePro/Listing/View/Grid',
    'M2ePro/Listing/Moving',
    'M2ePro/Listing/Mapping',
    'M2ePro/Amazon/Listing/View/Action',
    'M2ePro/Amazon/Listing/View/Fulfillment',
    'M2ePro/Amazon/Listing/Product/Search',
    'M2ePro/Amazon/Listing/Product/Template/Description',
    'M2ePro/Amazon/Listing/Product/Template/Shipping',
    'M2ePro/Amazon/Listing/Product/Template/ProductTaxCode',
    'M2ePro/Amazon/Listing/Product/Variation/Manage',
    'M2ePro/Amazon/Listing/Transferring'
], function (MessageObj) {

    window.AmazonListingViewGrid = Class.create(ListingViewGrid, {

        // ---------------------------------------

        getLogViewUrl: function (rowId) {
            var idField = M2ePro.php.constant('\\Ess\\M2ePro\\Block\\Adminhtml\\Log\\Listing\\Product\\AbstractGrid::LISTING_PRODUCT_ID_FIELD');

            var params = {};
            params[idField] = rowId;

            return M2ePro.url.get('amazon_log_listing_product/index', params);
        },

        // ---------------------------------------

        getMaxProductsInPart: function()
        {
            return 10;
        },

        // ---------------------------------------

        prepareActions: function($super)
        {
            this.actionHandler = new AmazonListingViewAction(this);

            this.actions = {
                listAction: this.actionHandler.listAction.bind(this.actionHandler),
                relistAction: this.actionHandler.relistAction.bind(this.actionHandler),
                reviseAction: this.actionHandler.reviseAction.bind(this.actionHandler),
                stopAction: this.actionHandler.stopAction.bind(this.actionHandler),
                stopAndRemoveAction: this.actionHandler.stopAndRemoveAction.bind(this.actionHandler),
                previewItemsAction: this.actionHandler.previewItemsAction.bind(this.actionHandler)
            };

            this.movingHandler = new ListingMoving(this);
            this.mappingHandler = new ListingMapping(this, 'amazon');
            this.productSearchHandler = new AmazonListingProductSearch(this);

            this.templateDescriptionHandler    = new AmazonListingProductTemplateDescription(this);
            this.templateShippingHandler       = new AmazonListingProductTemplateShipping(this);
            this.templateProductTaxCodeHandler = new AmazonListingProductTemplateProductTaxCode(this);

            this.variationProductManageHandler = new AmazonListingProductVariationManage(this);
            this.fulfillmentHandler = new AmazonListingViewFulfillment(this);

            this.actions = Object.extend(this.actions, {
                duplicateAction: this.duplicateProducts.bind(this),
                transferringAction: this.transferring.bind(this),
                movingAction: this.movingHandler.run.bind(this.movingHandler),
                deleteAndRemoveAction: this.actionHandler.deleteAndRemoveAction.bind(this.actionHandler),

                assignTemplateDescriptionIdAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.templateDescriptionHandler.validateProductsForTemplateDescriptionAssign(id)
                }).bind(this),
                unassignTemplateDescriptionIdAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.templateDescriptionHandler.unassignFromTemplateDescription(id)
                }).bind(this),

                assignTemplateShippingIdAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.templateShippingHandler.openPopUp(id)
                }).bind(this),
                unassignTemplateShippingIdAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.templateShippingHandler.unassign(id)
                }).bind(this),

                assignTemplateProductTaxCodeIdAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.templateProductTaxCodeHandler.openPopUp(id)
                }).bind(this),
                unassignTemplateProductTaxCodeIdAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.templateProductTaxCodeHandler.unassign(id)
                }).bind(this),

                switchToAfnAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.fulfillmentHandler.switchToAFN(id);
                }).bind(this),
                switchToMfnAction: (function(id) {
                    id = id || this.getSelectedProductsString();
                    this.fulfillmentHandler.switchToMFN(id);
                }).bind(this),

                assignGeneralIdAction: (function() {
                    this.productSearchHandler.searchGeneralIdAuto(this.getSelectedProductsString())
                }).bind(this),
                newGeneralIdAction: (function() {
                    this.productSearchHandler.addNewGeneralId(this.getSelectedProductsString())
                }).bind(this),
                unassignGeneralIdAction: (function() {
                    this.productSearchHandler.unmapFromGeneralId(this.getSelectedProductsString())
                }).bind(this),

                remapProductAction: function(id) {
                    this.mappingHandler.openPopUp(id, null, this.listingId);
                }.bind(this),
            });
        },

        // ---------------------------------------

        tryToMove: function (listingId) {
            this.movingHandler.submit(listingId, this.onSuccess)
        },

        onSuccess: function () {
            this.unselectAllAndReload();
        },

        // ---------------------------------------

        duplicateProducts: function () {
            this.scrollPageToTop();
            MessageObj.clear();

            new Ajax.Request(M2ePro.url.get('amazon_listing/duplicateProducts'), {
                method: 'post',
                parameters: {
                    ids: this.getSelectedProductsString()
                },
                onSuccess: (function (transport) {

                    try {
                        var response = transport.responseText.evalJSON();

                        MessageObj['add' + response.type[0].toUpperCase() + response.type.slice(1)](response.message);

                        if (response.type != 'error') {
                            this.unselectAllAndReload();
                        }

                    } catch (e) {
                        MessageObj.addError('Internal Error.');
                    }
                }).bind(this)
            });
        },

        transferring: function(id)
        {
            this.selectedProductsIds = id ? [id] : this.getSelectedProductsArray();
            this.unselectAll();

            window.AmazonListingTransferringObj.popupShow(this.selectedProductsIds);
        },

        // ---------------------------------------

        unassignTemplateDescriptionIdActionConfrim: function (id)
        {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        self.templateDescriptionHandler.unassignFromTemplateDescription(id);
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        // ---------------------------------------

        unassignTemplateShippingIdActionConfrim: function (id)
        {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        self.templateShippingHandler.unassign(id);
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        unassignTemplateProductTaxCodeIdActionConfrim: function (id)
        {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        self.templateProductTaxCodeHandler.unassign(id);
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        }

        // ---------------------------------------
    });

});
