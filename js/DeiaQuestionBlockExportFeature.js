(function($) {
    $.pkp.classes.features.DeiaQuestionBlockExportFeature = function(gridHandler, options) {
        this.parent(gridHandler, options);
    };
    $.pkp.classes.Helper.inherits(
        $.pkp.classes.features.DeiaQuestionBlockExportFeature,
        $.pkp.classes.features.Feature
    );

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.isExporting = false;

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.init = function() {
        this.$exportButton_ = $('.pkp_linkaction_exportQuestionBlocks', this.getGridHtmlElement());
        this.$orderButton_ = $('.pkp_linkaction_orderItems', this.getGridHtmlElement());
        this.$finishControl_ = $('.deia_export_finish_controls', this.getGridHtmlElement());
        this.ensureSelectionInputs_();
        this.hideSelectionInputs_();
        this.$finishControl_.hide();
        this.bindExportButton_();
        this.bindOrderButton_();
        window.setTimeout(this.gridHandler.callbackWrapper(this.syncFinishControlPosition_, this), 0);
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.addFeatureHtml =
            function($gridElement, options) {
        if (options.exportFinishControls !== undefined) {
            $gridElement.find('table').last().after($(options.exportFinishControls).hide());
        }
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.addElement = function() {
        this.syncSelectionState_();
        return false;
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.replaceElement = function() {
        this.syncSelectionState_();
        return false;
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.syncSelectionState_ = function() {
        this.ensureSelectionInputs_();
        this.toggleSelectionInputs_(this.isExporting);

        if (this.isExporting) {
            this.toggleRowsExportMode_(true);
            this.toggleRowActions_(true);
        }
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.bindExportButton_ = function() {
        var clickHandler = this.gridHandler.callbackWrapper(this.startExportHandler_, this);
        this.$exportButton_.unbind('click').click(clickHandler).removeAttr('disabled');
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.bindOrderButton_ = function() {
        var self = this;

        this.$orderButton_.click(function(event) {
            if (self.isExporting) {
                event.preventDefault();
                event.stopImmediatePropagation();
                return false;
            }
        });
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.syncFinishControlPosition_ = function() {
        var $orderFinishControl = $('.order_finish_controls', this.getGridHtmlElement());

        if ($orderFinishControl.length) {
            $orderFinishControl.last().after(this.$finishControl_);
        }
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.startExportHandler_ = function() {
        if (this.isOrderingActive_()) {
            return false;
        }

        this.gridHandler.hideAllVisibleRowActions();
        this.toggleState_(true);
        return false;
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.isOrderingActive_ = function() {
        return $('.order_finish_controls', this.getGridHtmlElement()).is(':visible');
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.doneHandler_ = function() {
        var selectedIds = this.getSelectedIds_();

        if (!selectedIds.length) {
            alert(this.getOptions().noSelectionMessage);
            return false;
        }

        this.submitExport_(selectedIds);
        this.toggleState_(false);
        return false;
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.cancelHandler_ = function() {
        this.getSelectionInputs_().prop('checked', false);
        this.toggleState_(false);
        return false;
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.toggleState_ = function(isExporting) {
        this.isExporting = isExporting;
        this.toggleSelectionInputs_(isExporting);
        this.toggleFinishControl_(isExporting);
        this.toggleGridLinkActions_(isExporting);
        this.toggleExportButton_(isExporting);
        this.toggleOrderButton_(isExporting);
        this.toggleRowsExportMode_(isExporting);
        this.toggleRowActions_(isExporting);
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.toggleSelectionInputs_ = function(show) {
        if (show) {
            this.showSelectionInputs_();
        } else {
            this.hideSelectionInputs_();
        }
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.toggleRowsExportMode_ = function(show) {
        this.gridHandler.getRows().toggleClass('ordering', show);
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.showSelectionInputs_ = function() {
        this.getSelectionInputs_().show();
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.hideSelectionInputs_ = function() {
        this.getSelectionInputs_().hide();
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.getSelectionInputs_ = function() {
        return $('.deia_export_select', this.getGridHtmlElement());
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.ensureSelectionInputs_ = function() {
        var options = this.getOptions();

        this.gridHandler.getRows().each(function() {
            var $row = $(this),
                    matches = $row.attr('id').match(/-row-(.+)$/),
                    $rowActions = $row.find('div.row_actions').first();

            if (!matches || !$rowActions.length || $rowActions.find('.deia_export_select').length) {
                return;
            }

            $rowActions.prepend(
                    $('<input type="checkbox" class="deia_export_select" />')
                    .attr('name', options.selectName + '[]')
                    .val(matches[1])
                    .css({
                        'height': '15px',
                        'margin': '4px',
                        'width': '15px'
                    })
            );
        });
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.toggleRowActions_ = function(show) {
        var $rowActions = $('div.row_actions', this.getGridHtmlElement()),
                $actions = $rowActions.find('a');

        if (show) {
            $actions.addClass('pkp_helpers_display_none');
            this.gridHandler.showRowActionsDiv();
        } else {
            $actions.removeClass('pkp_helpers_display_none');
            this.gridHandler.hideRowActionsDiv();
        }
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.getSelectedIds_ = function() {
        var selectedIds = [];

        this.getSelectionInputs_().filter(':checked').each(function() {
            selectedIds.push($(this).val());
        });

        return selectedIds;
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.toggleFinishControl_ = function(show) {
        if (show) {
            this.bindFinishControls_();
            this.$finishControl_.slideDown(300);
        } else {
            this.unbindFinishControls_();
            this.$finishControl_.slideUp(300);
        }
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.bindFinishControls_ = function() {
        var doneHandler = this.gridHandler.callbackWrapper(this.doneHandler_, this),
                cancelHandler = this.gridHandler.callbackWrapper(this.cancelHandler_, this);

        this.$finishControl_.find('.saveButton').click(doneHandler);
        this.$finishControl_.find('.cancelFormButton').click(cancelHandler);
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.unbindFinishControls_ = function() {
        this.$finishControl_.find('.saveButton').unbind('click');
        this.$finishControl_.find('.cancelFormButton').unbind('click');
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.toggleGridLinkActions_ = function(disable) {
        var $gridLinkActions = $('.pkp_controllers_linkAction', this.getGridHtmlElement())
            .not(this.$exportButton_)
            .not(this.$finishControl_.find('*'));

        this.gridHandler.changeLinkActionsState(!disable, $gridLinkActions);
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.toggleExportButton_ = function(disable) {
        if (disable) {
            this.$exportButton_.unbind('click').attr('disabled', 'disabled');
        } else {
            this.bindExportButton_();
        }
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.toggleOrderButton_ = function(disable) {
        if (disable) {
            this.$orderButton_
                    .attr('disabled', 'disabled')
                    .addClass('deia_export_disabled_link')
                    .css('pointer-events', 'none');
        } else {
            this.$orderButton_
                    .removeAttr('disabled')
                    .removeClass('deia_export_disabled_link')
                    .css('pointer-events', '');
        }
    };

    $.pkp.classes.features.DeiaQuestionBlockExportFeature.prototype.submitExport_ = function(selectedIds) {
        var options = this.getOptions(),
                $form = $('<form method="post"></form>');

        $form.attr('action', options.exportUrl);
        $form.append($('<input type="hidden" name="csrfToken" />').val(options.csrfToken));

        $.each(selectedIds, function(index, blockId) {
            $form.append($('<input type="hidden" />').attr('name', options.selectName + '[]').val(blockId));
        });

        $('body').append($form);
        $form.submit();
        $form.remove();
    };
}(jQuery));
