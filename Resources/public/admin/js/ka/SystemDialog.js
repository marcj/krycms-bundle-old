ka.SystemDialog = new Class({

    Extends: ka.Dialog,

    initialize: function(pParent, pOptions) {
        pParent = ka.getAdminInterface().getDialogContainer();
        this.closeExistingDialog();
        this.parent(pParent, pOptions);
    },

    closeExistingDialog: function() {
        var lastDialog = ka.getAdminInterface().getDialogContainer().getElement('.ka-dialog-overlay');
        if (lastDialog && lastDialog.kaDialog) {
            lastDialog.kaDialog.hide();
        }
    },

    renderLayout: function () {
        this.parent();

        new Element('a', {
            title: t('Close'),
            href: 'javascript:void(0)',
            'class': 'ka-SystemDialog-closer icon-cancel-8'
        })
            .addEvent('click', function(){
                this.close();
            }.bind(this))
            .inject(this.main);
    },

    /**
     * Position the dialog to the correct position.
     *
     * @param {Boolean} pAnimated position the dialog out of the viewport and animate it into it.
     */
    center: function (pAnimated) {
        if (!this.overlay.getParent()) {
            this.overlay.inject(this.container);
        }

        var size = this.container.getSize();

        this.main.setStyles({
            left: 0,
            minWidth: null,
            top: 0,
            bottom: 0
        });

        ka.adminInterface.hideAppContainer();
    },

    doClose: function(pAnimated) {
        if (this.options.destroyOnClose) {
            this.overlay.destroy();
        } else {
            this.overlay.dispose();
        }

        ka.adminInterface.showAppContainer();
    }
});