ka.Content = new Class({
    Extends: ka.Base,
    Binds: ['onOver', 'onOut', 'remove', 'fireChange'],

    drop: null,

    contentObject: null,
    currentType: null,
    currentTemplate: null,

    boxId: null,
    sortableId: null,

    contentContainer: null,

    initialize: function(pContent, pContainer, pDrop) {
        this.drop = pDrop;
        this.renderLayout(pContainer);
        this.setValue(pContent);

        this.clipboardListener = this.showPasteState.bind(this);
        window.addEvent('clipboard', this.clipboardListener);
    },

    getSlot: function() {
        return this.main.getParent('.ka-slot').kaSlotInstance;
    },

    /**
     * @returns {ka.Editor}
     */
    getEditor: function() {
        return this.getSlot().getEditor();
    },

    setBoxId: function(boxId) {
        this.boxId = boxId;
    },

    setSortId: function(sortableId) {
        this.sortId = sortableId;
    },

    getBoxId: function() {
        return this.boxId;
    },

    getSortId: function() {
        return this.sortId;
    },

    /**
     * Returns the actual contentType from ka.ContentTypes.*
     *
     * @returns {ka.ContentAbstract}
     */
    getContentObject: function() {
        return this.contentObject;
    },

    renderLayout: function(container) {
        this.main = new Element('div', {
            'class': 'ka-content '
        }).inject(container);

        this.main.addListener('dragstart', function(e) {
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('application/json', JSON.encode(this.getValue()));
            //this.main.set('draggable');
        }.bind(this));

        this.main.addListener('dragend', function(e) {
            if ('move' === e.dataTransfer.dropEffect) {
                this.destroy();
            }
        }.bind(this));

        this.main.kaContentInstance = this;

        this.actionBar = new Element('div', {
            'class': 'ka-normalize ka-content-actionBar'
        }).inject(this.main);

        this.addActionBarItems();
    },

    destroy: function() {
        if (this === this.getEditor().getContentField().getSelected()) {
            this.getEditor().getContentField().deselect();
        }
        this.getContentObject().destroy();
        this.main.destroy();
        window.removeEvent('clipboard', this.clipboardListener);
    },

    fireChange: function() {
        this.updateUI();
        this.fireEvent('change');
    },

    addActionBarItems: function() {

        var moveBtn = new Element('span', {
            html: '&#xe0c6;',
            'class': 'icon ka-content-actionBar-move',
            title: t('Move content')
        }).inject(this.actionBar);

        moveBtn.addEvent('mouseover', function() {
            this.main.set('draggable', true);
        }.bind(this));

        moveBtn.addEvent('mouseout', function() {
            this.main.set('draggable');
        }.bind(this));

        new Element('a', {
            href: 'javascript: ;',
            title: t('Copy content'),
            'class': 'icon-copy'
        }).addEvent('click', function(e) {
                e.stop();
                ka.setClipboard('Content', 'content', this.getValue());
            }.bind(this)).inject(this.actionBar);

        this.actionBarItemVisibility = new Element('a', {
            href: 'javascript: ;',
            title: t('Hide/Show'),
            'class': 'icon-eye'
        }).addEvent('click', function(e) {
                e.stop();
                this.toggleVisibility();
            }.bind(this)).inject(this.actionBar);

        this.actionBarItemPaste = new Element('a', {
            href: 'javascript: ;',
            title: t('Paste content'),
            'class': 'icon-arrow-down-11'
        }).addEvent('click', function(e) {
                e.stop();
                if (ka.isClipboard('content')) {
                    var value = ka.getClipboard().value;
                    if (value.id) {
                        delete value.id;
                    }
                    var content = this.getSlot().addContent(value);
                    content.inject(this, 'after');
                }
            }.bind(this)).inject(this.actionBar);

        new Element('a', {
            html: '&#xe26b;',
            href: 'javascript: ;',
            title: t('Remove content'),
            'class': 'icon'
        }).addEvent('click', function(e) {
                e.stop();
                this.remove();
            }.bind(this)).inject(this.actionBar);

    },

    showVisibilityState: function() {
        var opacity = this.value.hide ? 0.5 : null;

        this.actionBarItemVisibility.setStyle('opacity', opacity);
        this.main.getChildren().setStyle('opacity', opacity);
        this.actionBar.setStyle('opacity', null);
    },

    showPasteState: function() {
        var opacity = !ka.isClipboard('content') ? 0.5 : null;
        this.actionBarItemPaste.setStyle('opacity', opacity);
    },

    toggleVisibility: function() {
        this.value.hide = !this.value.hide;

        this.updateUI();
    },

    remove: function() {
        this.getEditor().deselect();
        this.main.destroy();
    },

    /**
     *
     * @returns {Element}
     */
    getContentContainer: function() {
        return this.contentContainer;
    },

    /**
     *
     * @param {Boolean} visible
     * @param {ka.ProgressWatch} progressWatch
     */
    setPreview: function(visible, progressWatch) {
        if (!this.getContentObject().isPreviewPossible()) {
            return false;
        }

        this.preview = visible;
        this.updateUI();
    },

    /**
     * @param {ka.ProgressWatch} progressWatch
     */
    loadPreview: function(progressWatch) {
        delete this.currentTemplate;

        if (this.lastRq) {
            this.lastRq.cancel();
        }

        var req = Object.toQueryString({
            template: this.value.template,
            type: this.value.type,
            nodeId: this.getEditor().getNodeId(),
            domainId: this.getEditor().getDomainId()
        });

        this.getEditor().deactivateLinks(this.main);
        this.lastRq = new Request.JSON({url: _pathAdmin + 'admin/content/preview?' + req, noCache: true,
            onFailure: function() {
                if (progressWatch) {
                    progressWatch.error();
                }
            },
            onComplete: function(pResponse) {
                this.actionBar.dispose();
                this.main.empty();

                this.main.set('html', pResponse.data);
                this.actionBar.inject(this.main, 'top');

                if (progressWatch) {
                    progressWatch.done();
                }
            }.bind(this)}).post({
                content: this.value.content
            });
    },

    /**
     * @param {ka.ProgressWatch} progressWatch
     */
    loadTemplate: function(progressWatch) {
        if (null !== this.currentTemplate && this.currentTemplate == this.value.template) {
            return;
        }

        if (this.lastRq) {
            this.lastRq.cancel();
        }

        this.getEditor().activateLinks(this.main);
        this.lastRq = new Request.JSON({url: _pathAdmin + 'admin/content/template', noCache: true,
            onFailure: function() {
                progressWatch.error();
            },
            onComplete: function(pResponse) {
                this.actionBar.dispose();
                this.main.setStyle('height', this.main.getSize().y);

                if (this.contentObject) {
                    this.contentObject.destroy();
                }
                this.main.empty();
                this.main.set('html', pResponse.data);

                this.contentContainer = this.main.getElement('.ka-content-container') || new Element('div').inject(this.main);

                delete this.contentObject;
                this.currentTemplate = this.value.template;
                this.actionBar.inject(this.main, 'top');

                this.setValue(this.value);

                this.main.setStyle.delay(50, this.main, ['height']);
            }.bind(this)}).get({
                template: this.value.template,
                type: this.value.type
            });
    },

    focus: function() {
        if (this.contentObject) {
            this.contentObject.focus();
            this.nextFocus = false;
        } else {
            this.nextFocus = true;
        }
    },

    /**
     * @param {ka.ProgressWatch} progressWatch
     *
     * @returns {*}
     */
    getValue: function(progressWatch) {
        if (this.selected) {
            this.value = this.value || {};

            this.value.template = this.template.getValue();
        }

        if (this.contentObject) {
            this.value.content = this.contentObject.getValue();
        }

        if (progressWatch) {
            var content = this.value;

            var self = this;
            progressWatch.addEvent('preDone', function(sp) {
                self.value.content = sp.getValue();
                self.value.boxId = self.getBoxId();
                self.value.sort = self.getSortId();
                this.setValue(self.value);
            });

            if (this.contentObject && progressWatch) {
                this.contentObject.save(progressWatch);
            }
        }

        return this.value;
    },

    loadInspector: function() {
        var inspectorContainer = this.getEditor().getContentField().getInspectorContainer();

        inspectorContainer.empty();

        this.template = new ka.Field({
            label: t('Content Layout'),
            width: 'auto',
            type: 'contentTemplate',
            inputWidth: '100%'
        }, inspectorContainer);

        this.template.addEvent('change', function() {
            return this.updateUI();
        }.bind(this));

        this.template.setValue(this.value.template || null);

        this.inspectorContainer = new Element('div', {
            style: 'padding-top: 5px;'
        }).inject(inspectorContainer);
    },

    updateUI: function() {
        this.value = this.getValue();

        this.showVisibilityState();
        this.showPasteState();

        if (this.preview) {
            this.loadPreview();
        } else {
            this.loadTemplate();
        }
    },

    setSelected: function(selected) {
        if (selected) {
            this.main.addClass('ka-content-selected');
            this.loadInspector();
            if (this.contentObject && this.contentObject.selected) {
                this.contentObject.selected(this.inspectorContainer);
            }
        } else {
            this.main.removeClass('ka-content-selected');
            if (this.contentObject && this.contentObject.deselected) {
                this.contentObject.deselected();
            }
            if (this.inspectorContainer) {
                this.inspectorContainer.destroy();
            }
        }
        this.selected = selected;
    },

    setValue: function(pValue) {
        this.value = pValue;

        if (!this.currentType || !this.contentObject || pValue.type != this.currentType || !this.currentTemplate || this.currentTemplate != pValue.template) {

            if (null === this.currentTemplate || this.currentTemplate != pValue.template) {
                return this.loadTemplate(pValue);
            }

            if (!ka.ContentTypes) {
                throw 'No ka.ContentTypes loaded.';
            }

            var clazz = ka.ContentTypes[pValue.type] || ka.ContentTypes[pValue.type.capitalize()];
            if (clazz) {
                this.contentObject = new clazz(this, this.options);
            } else {
                console.error(tf('ka.ContentType `%s` not found.', pValue.type));
            }

            this.contentObject.addEvent('change', this.fireChange);

            if (this.nextFocus) {
                this.focus();
            }
            this.currentType = pValue.type;
        }
//
        this.showVisibilityState();
        this.showPasteState();

        this.contentObject.setValue(pValue.content);

        if (this.selected) {
            this.setSelected(true);
        }
    }

});