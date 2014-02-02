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

        this.main.addClass('ka-dialog-system');
    },

    /**
     * Position the dialog to the correct position.
     *
     * @param {Boolean} animated position the dialog out of the viewport and animate it into it.
     */
    center: function (animated) {
        if (!this.overlay.getParent()) {
            this.overlay.inject(this.container);
        }

        this.main.setStyles({
            left: 0,
            minWidth: null,
            top: -100,
            bottom: 0
        });

        this.showDialogContainer();

        setTimeout(function(){
            if (Modernizr.csstransforms && Modernizr.csstransitions) {
                var styles = {
                    opacity: 1
                };
                styles[Modernizr.prefixed('transform')] = 'translate(0px, 100px)';
                this.main.setStyles(styles);
            } else {
                this.main.morph({
                    'top': 0
                });
            }
        }.bind(this, 50));
    },

    getContainer: function() {
        return ka.getAdminInterface().getDialogContainer()
    },

    doClose: function(animated) {
        if (this.options.destroyOnClose) {
            this.overlay.destroy();
        } else {
            this.overlay.dispose();
        }

        if (!this.getContainer().getChildren().length) {
            this.hideDialogContainer();
        }

        this.fireEvent('close');
    },

    showDialogContainer: function() {
        if (!this.getContainer().hasClass('ka-main-dialog-container-visible')) {
            this.getContainer().addClass('ka-main-dialog-container-visible');
        }
    },

    hideDialogContainer: function() {
        this.getContainer().removeClass('ka-main-dialog-container-visible');
    }
});