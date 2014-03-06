ka.Dialog = new Class({

    Binds: ['center', 'close', 'closeAnimated', 'checkResized'],
    Implements: [Events, Options],

    options: {
        content: '',
        minWidth: null,
        minHeight: null,
        maxHeight: null,
        maxWidth: null,
        width: null,
        height: null,

        cancelButton: true,
        applyButton: true,
        applyButtonLabel: '',
        withButtons: false,
        noBottom: false,

        absolute: false,
        fixed: false,

        autoDisplay: false,

        title: '',

        yOffset: 0,

        autoClose: false,
        destroyOnClose: true,

        animatedTransition: Fx.Transitions.Cubic.easeOut,
        animatedTransitionOut: Fx.Transitions.Cubic.easeIn,
        animatedDuration: 200
    },

    canClosed: true,

    initialize: function (pParent, pOptions) {
        if (!pParent) {
            pParent = ka.wm.getActiveWindow();
        }

        if (!pParent) {
            throw 'No parent found.';
        }

        this.lastFocusedElement = document.activeElement;
        this.container = instanceOf(pParent, ka.Window) ? pParent.toElement() : pParent;

        this.setOptions(pOptions);
        this.renderLayout();

        if (instanceOf(pParent, ka.Window)) {
            this.window = pParent;
            this.window.addEvent('resize', this.checkResized);
        } else {
            this.container.getDocument().getWindow().addEvent('resize', this.checkResized);
            if (!this.container.getDocument().hiddenCount) {
                this.container.getDocument().hiddenCount = 0;
            }
            this.container.getDocument().hiddenCount++;
            this.container.getDocument().body.addClass('hide-scrollbar');
        }

        if (this.options.autoClose) {
            this.overlay.addEvent('click', function(e){
                if (e.target === this.overlay) {
                    this.closeAnimated();
                }
            }.bind(this));

            this.checkClose = this.checkClose.bind(this);
            var body = this.container.getDocument().body;

            var span = new Element('span');
            span.cloneEvents(body);
            body.removeEvents();
            body.addEvent('keyup', this.checkClose);
            body.cloneEvents(span);
            span.destroy();
        }
    },

    checkResized: function() {
        if (this.isOpen()) {
            this.center();
        }
    },

    checkClose: function(e) {
        if (document.activeElement == document.body && 'esc' === e.key) {
            this.closeAnimated();
            return false;
        }
    },

    renderLayout: function () {
        this.overlay = new Element('div', {
            'class': 'ka-dialog-overlay'
        });

        if (this.options.autoDisplay) {
            this.overlay.inject(this.container);
        }

        this.overlay.kaDialog = this;

        this.main = new Element('div', {
            'class': 'ka-dialog selectable ka-scrolling'
        }).inject(this.overlay);

        this.content = new Element('div', {
            'class': 'ka-dialog-content'
        }).inject(this.main);

        if (typeOf(this.options.content) == 'string' && this.options.content) {
            this.content.set('text', this.options.content);
            this.content.addClass('ka-dialog-plain-content');
        } else if (typeOf(this.options.content) == 'element') {
            this.options.content.inject(this.content);
        }

        if (this.options.title) {
            new Element('h2', {
                text: this.options.title
            }).inject(this.content, 'top');
        }

        ['minWidth', 'maxWidth', 'minHeight', 'maxHeight', 'height', 'width'].each(function (item) {
            if (typeOf(this.options[item]) != 'null') {
                this.main.setStyle(item, this.options[item]);
            }
        }.bind(this));

        if (!this.options.noBottom) {
            this.bottom = new Element('div', {
                'class': 'ka-dialog-bottom ka-ActionBar'
            }).inject(this.main);
        }

        if (this.options.fixed) {
            this.overlay.addClass('ka-dialog-fixed');
        }

        if (this.options.absolute) {
            if (this.bottom) {
                this.bottom.addClass('ka-dialog-bottom-absolute');
            }
            this.content.addClass('ka-dialog-content-absolute');
            if (this.options.noBottom) {
                this.content.addClass('ka-dialog-content-no-bottom');
            }
        }

        if (this.options.withButtons && this.bottom) {
            if (this.options.cancelButton) {
                this.cancelButton = this
                    .addButton(t('Cancel'))
                    .addEvent('click', function () {
                        this.fireEvent('cancel');
                        this.closeAnimated(true);
                    }.bind(this));
            }

            if (this.options.applyButton) {
                this.applyButton = this
                    .addButton(this.options.applyButtonLabel || t('Apply'))
                    .addEvent('click', function () {
                        this.fireEvent('apply');
                        this.closeAnimated(true);
                    }.bind(this))
                    .setButtonStyle('blue');

                this.applyButton.focus();
            }
        }

        if (this.options.autoDisplay) {
            this.center(true);
        }
    },

    setStyle: function (p1, p2) {
        return this.main.setStyle(p1, p2);
    },

    setStyles: function (p1, p2) {
        return this.main.setStyles(p1, p2);
    },

    getCancelButton: function () {
        return this.cancelButton;
    },

    getApplyButton: function () {
        return this.applyButton;
    },

    getContentContainer: function () {
        return this.content;
    },

    setContent: function (pHtml) {
        this.getContentContainer().set('html', pHtml);
    },

    setText: function (pText) {
        this.getContentContainer().set('text', pText);
    },

    addButton: function (pTitle) {
        return new ka.Button(pTitle).inject(this.bottom);
    },

    closeAnimated: function (pInternal) {
        return this.close(pInternal, true);
    },

    /**
     * Cancels the (only) next closing call.
     *
     * Useful in the 'apply' event.
     */
    cancelClosing: function () {
        this.cancelNextClosing = true;
    },

    close: function (internal, animated) {
        if (this.cancelNextClosing) {
            delete this.cancelNextClosing;
            return;
        }

        if (internal) {
            this.main.fireEvent('preClose');
        }

        if (!this.canClosed) {
            return;
        }

        if (internal) {
            this.fireEvent('close');
        }

        if (this.window) {
            this.window.removeEvent('resize', this.center);
        } else {
            this.container.getDocument().getWindow().removeEvent('resize', this.center);
        }

        this.container.getDocument().hiddenCount--;
        if (this.container.getDocument().hiddenCount == 0) {
            this.container.getDocument().body.removeClass('hide-scrollbar');
        }

        if (this.options.autoClose) {
            this.container.getDocument().body.removeEvent('keyup', this.checkClose);
        }

        this.doClose(animated);
        if (!animated) {
            if (this.lastFocusedElement) {
                this.lastFocusedElement.focus();
            }
            this.fireEvent('closed');
        }
    },

    /**
     * @internal
     * @param {Boolean} pAnimated
     */
    doClose: function(pAnimated) {
        if (pAnimated) {
            var dsize = this.main.getSize();

            if (!this.fxOut) {
                this.fxOut = new Fx.Morph(this.main, {
                    transition: this.options.animatedTransitionOut,
                    duration: this.options.animatedDuration
                });
            }

            this.fxOut.addEvent('complete', function () {
                if (this.options.destroyOnClose) {
                    this.overlay.destroy();
                } else {
                    this.overlay.dispose();
                }
                if (this.lastFocusedElement) {
                    this.lastFocusedElement.focus();
                }
                this.fireEvent('postClose');
                this.fireEvent('closed');
            }.bind(this));

            this.fxOut.start({
                top: dsize.y * -1
            });
        } else {
            if (this.options.destroyOnClose) {
                this.overlay.destroy();
            } else {
                this.overlay.dispose();
            }
        }
    },

    /**
     * @param {Boolean} pCanClosed
     */
    setCanClosed: function (pCanClosed) {
        this.canClosed = pCanClosed;
    },

    getBottomContainer: function () {
        return this.bottom;
    },

    inject: function(){
        if (!this.overlay.getParent()) {
            this.overlay.inject(this.container);
        }
    },

    isOpen: function() {
        return !!this.overlay.getParent();
    },

    hide: function() {
        this.overlay.dispose();
    },

    show: function() {
        this.center(true);
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
        var dsize = this.main.getSize();

        var left = (size.x.toInt() / 2 - dsize.x.toInt() / 2);
        this.main.setStyle('left', left);

        if (pAnimated) {
            this.main.setStyle('top', dsize.y * -1);
            if (!this.fx) {
                this.fx = new Fx.Morph(this.main, {
                    transition: this.options.animatedTransition,
                    duration: this.options.animatedDuration
                });
            }
            this.fx.start({
                top: this.options.yOffset
            });
        } else {
            this.main.setStyle('top', this.options.yOffset);
        }
    },

    toElement: function () {
        return this.main;
    }

});