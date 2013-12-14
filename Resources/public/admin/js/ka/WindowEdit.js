ka.WindowEdit = new Class({
    Implements: [Events, Options],
    Binds: ['showVersions'],

    inline: false,

    options: {
        saveLabel: ''
    },

    fieldToTabOIndex: {}, //index fieldkey to main-tabid
    winParams: {}, //copy of pWin.params in constructor

    initialize: function(pWin, pContainer) {
        this.win = pWin;

        this.winParams = Object.clone(this.win.getParameter());

        if (!this.winParams.item && this.winParams.values) {
            this.winParams.item = this.winParams.values;
        } //compatibility

        if (!this.windowAdd && !this.winParams.item) {
            this.win.alert('No item given. A edit object window can not be called directly.', function() {
                this.win.close();
            }.bind(this));
            return;
        }

        if (!pContainer) {
            this.container = this.win.content;
            this.container.setStyle('overflow', 'visible');
        } else {
            this.inline = true;
            this.container = pContainer;
        }

        this.container.empty();

        this.bCheckClose = this.checkClose.bind(this);
        this.bCheckTabFieldWidth = this.checkTabFieldWidth.bind(this);

        this.win.addEvent('close', this.bCheckClose);
        this.win.addEvent('resize', this.bCheckTabFieldWidth);

        if (this.win.getEntryPoint()) {
            this.load();
        }
    },

    getContentContainer: function() {
        return this.container;
    },

    destroy: function() {
        this.win.removeEvent('close', this.bCheckClose);
        this.win.removeEvent('resize', this.bCheckTabFieldWidth);

        if (this.languageTip) {
            this.languageTip.stop();
            delete this.languageTip;
        }

        delete this.tabPane;

        Object.each(this._buttons, function(button, id) {
            button.stopTip();
        });

        if (this.topTabGroup) {
            this.topTabGroup.destroy();
        }

        if (this.actionGroup) {
            this.actionGroup.destroy();
            delete this.actionGroup;
        }

        if (this.actionBar) {
            this.actionBar.destroy();
            delete this.actionBar;
        }

        if (this.versioningSelect) {
            this.versioningSelect.destroy();
        }

        if (this.languageSelect) {
            this.languageSelect.destroy();
        }

        delete this.versioningSelect;
        delete this.languageSelect;

        this.container.empty();

    },

    getModule: function() {
        if (!this.module) {
            if (this.getEntryPoint().indexOf('/') > 0) {
                this.module = this.getEntryPoint().substr(0, this.getEntryPoint().indexOf('/'));
            } else {
                this.module = this.getEntryPoint();
            }
        }
        return this.module;
    },

    getEntryPoint: function() {
        var restPoint = this.win.getEntryPoint();
        if (restPoint.substr(restPoint.length - 1) == '/') {
            restPoint = restPoint.substr(0, restPoint.length - 1);
        }
        return restPoint;
    },

    load: function() {

        this.container.set('html', '<div style="text-align: center; padding: 50px; color: silver">' + t('Loading definition ...') + '</div>');

        new Request.JSON({url: _pathAdmin + this.getEntryPoint() + '/', noCache: true, onComplete: function(pResponse) {

            if (!pResponse.error && pResponse.data && pResponse.data._isClassDefinition) {
                this.render(pResponse.data);
            } else {
                this.container.set('html', '<div style="text-align: center; padding: 50px; color: red">' + t('Failed. No correct class definition returned. %s').replace('%s', 'admin/' + this.getEntryPoint() + '?_method=options') + '</div>');
            }

        }.bind(this)}).post({_method: 'options'});
    },

    generateItemParams: function(pVersion) {
        var req = {};

        if (pVersion) {
            req.version = pVersion;
        }

        if (this.winParams && this.winParams.item) {
            this.classProperties.primary.each(function(prim) {
                req[ prim ] = this.winParams.item[prim];
            }.bind(this));
        }

        return req;
    },

    loadItem: function() {
        if (!this.classProperties) {
            this.selectItem = true;
            return;
        }

        var id = ka.getObjectUrlId(this.classProperties['object'], this.winParams.item);

        if (this.lastRq) {
            this.lastRq.cancel();
        }

        this.win.setLoading(true, null, this.container.getCoordinates(this.win));

        this.lastRq = new Request.JSON({url: _pathAdmin + this.getEntryPoint() + '/' + id,
            noCache: true, onComplete: function(res) {
                this._loadItem(res.data);
            }.bind(this)}).get({withAcl: true});
    },

    _loadItem: function(pItem) {
        this.item = pItem;

        this.setValue(pItem, true);
        this.saveBtn.setEnabled(pItem._editable);
        this.hideNotEditableFields(pItem._notEditable);

        this.renderVersionItems();

        this.win.setLoading(false);
        this.fireEvent('load', pItem);

        this.ritem = this.retrieveData(true);
    },

    hideNotEditableFields: function(fields) {
        this.fieldForm.showAll();

        if (fields && 'array' === typeOf(fields)) {
            Array.each(fields, function(field) {
                this.fieldForm.hideField(field);
            }.bind(this));
        }
    },

    setValue: function(pValue, pInternal) {

        pValue = pValue || {};

        this.fieldForm.setValue(pValue, pInternal);

        if (this.getTitleValue()) {
            this.win.setTitle(this.getTitleValue());
        }

        if (this.languageSelect && this.languageSelect.getValue() != pValue.lang) {
            this.languageSelect.setValue(pValue.lang);
            this.changeLanguage();
        }
    },

    /**
     * Returns the vlaue of the field for the window title.
     * @return {String}
     */
    getTitleValue: function() {

        var value = this.fieldForm.getValue();

        var titleField = this.classProperties.titleField;
        if (!this.classProperties.titleField) {
            Object.each(this.fieldForm.getFieldDefinitions(), function(field, fieldId) {
                if (field.type != 'tab' && field.type != 'childrenSwitcher') {
                    if (!titleField) {
                        titleField = fieldId;
                    }
                }
            });
        }

        if (!this.fieldForm.getFieldDefinition(titleField)) {
            logger(tf('Field %s ($titleField) for the window title does not exists in the $fields variable', titleField));
        }

        if (titleField && this.fields[titleField]) {

            var value = ka.getObjectFieldLabel(value, this.fieldForm.getFieldDefinition(titleField), titleField, this.classProperties['object']);
            return value;
        }
        return '';
    },

    renderPreviews: function() {

        if (!this.classProperties.previewPlugins) {
            return;
        }

        //this.previewBtn;

        this.previewBox = new Element('div', {
            'class': 'ka-Select-chooser'
        });

        this.previewBox.addEvent('click', function(e) {
            e.stop();
        });

        this.previewBox.inject(this.win.getTitleGroupContainer());

        this.previewBox.setStyle('display', 'none');

        //this.classProperties.previewPlugins

        document.body.addEvent('click', this.closePreviewBox.bind(this));

        if (!this.classProperties.previewPluginPages) {
            return;
        }

        Object.each(this.classProperties.previewPlugins, function(item, pluginId) {

            var title = ka.getConfig(this.getModule()).plugins[pluginId][0];

            new Element('div', {
                html: title,
                href: 'javascript:;',
                style: 'font-weight:bold; padding: 3px; padding-left: 15px;'
            }).inject(this.previewBox);

            var index = pluginId;
            if (pluginId.indexOf('/') === -1) {
                index = this.getModule() + '/' + pluginId;
            }

            Object.each(this.classProperties.previewPluginPages[index], function(pages, domain_id) {

                Object.each(pages, function(page, page_id) {

                    var domain = ka.getDomain(domain_id);
                    if (domain) {
                        new Element('a', {
                            html: '<span style="color: gray">[' + domain.lang + ']</span> ' + page.path,
                            style: 'padding-left: 21px',
                            href: 'javascript:;'
                        }).addEvent('click', this.doPreview.bind(this, page_id, index)).inject(this.previewBox);
                    }

                }.bind(this));

            }.bind(this));

        }.bind(this));

    },

    preview: function(e) {
        this.togglePreviewBox(e);
    },

    doPreview: function(pPageRsn, pPluginId) {
        this.closePreviewBox();

        if (this.lastPreviewWin) {
            this.lastPreviewWin.close();
        }

        var url = this.previewUrls[pPluginId][pPageRsn];

        if (this.versioningSelect.getValue() != '-') {
            url += '?kryn_framework_version_id=' + this.versioningSelect.getValue() + '&kryn_framework_code=' + pPluginId;
        }

        this.lastPreviewWin = window.open(url, '_blank');

    },

    setPreviewValue: function() {
        this.closePreviewBox();
    },

    closePreviewBox: function() {
        this.previewBoxOpened = false;
        this.previewBox.setStyle('display', 'none');
    },

    togglePreviewBox: function(e) {

        if (this.previewBoxOpened == true) {
            this.closePreviewBox();
        } else {
            if (e && e.stop) {
                document.body.fireEvent('click');
                e.stop();
            }
            this.openPreviewBox();
        }
    },

    openPreviewBox: function() {

        this.previewBox.setStyle('display', 'block');

        this.previewBox.position({
            relativeTo: this.previewBtn,
            position: 'bottomRight',
            edge: 'upperRight'
        });

        var pos = this.previewBox.getPosition();
        var size = this.previewBox.getSize();

        var bsize = window.getSize($('desktop'));

        if (size.y + pos.y > bsize.y) {
            this.previewBox.setStyle('height', bsize.y - pos.y - 10);
        }

        this.previewBoxOpened = true;
    },

    loadVersions: function() {

        var req = this.generateItemParams();
        new Request.JSON({url: _pathAdmin + this.getEntryPoint() + '/', noCache: true, onComplete: function(res) {

            if (res && res.data.versions) {
                this.item.versions = res.data.versions;
                this.renderVersionItems();
            }

        }.bind(this)}).get(req);

    },

    renderVersionItems: function() {
        if (this.classProperties.versioning != true) {
            return;
        }

        this.versioningSelect.empty();
        this.versioningSelect.chooser.setStyle('width', 210);
        this.versioningSelect.add('-', _('-- LIVE --'));

        /*new Element('option', {
         text: _('-- LIVE --'),
         value: ''
         }).inject( this.versioningSelect );*/

        if (typeOf(this.item.versions) == 'array') {
            this.item.versions.each(function(version, id) {
                this.versioningSelect.add(version.version, version.title);
            }.bind(this));
        }

        if (this.item.version) {
            this.versioningSelect.setValue(this.item.version);
        }

    },

    render: function(pValues) {
        this.classProperties = pValues;

        this.container.empty();

        this.win.setLoading(true, null, {left: 265});

        this.fields = {};

        this.renderVersions();

        this.renderPreviews();

        this.renderActionBar();

        //this.renderMultilanguage();

        this.renderFields();

        this.fireEvent('render');

        if (this.winParams) {
            this.loadItem();
        }
    },

    renderFields: function() {

        if (this.classProperties.fields && typeOf(this.classProperties.fields) != 'array') {

            this.form = new Element('div', {
                'class': 'ka-windowEdit-form'
            }).inject(this.getContentContainer(), 'top');

            if (this.classProperties.layout) {
                this.form.set('html', this.classProperties.layout);
            }

            this.tabPane = new ka.TabPane(this.form, true);

            this.fieldForm = new ka.FieldForm(this.form, this.classProperties.fields, {
                firstLevelTabPane: this.tabPane
            }, {win: this.win});

            this.fields = this.fieldForm.getFields();

            this._buttons = this.fieldForm.getTabButtons();

            if (this.fieldForm.firstLevelTabBar) {
                this.topTabGroup = this.fieldForm.firstLevelTabBar.buttonGroup;
            }

        }

        //generate index, fieldkey => main-tabid
        Object.each(this.classProperties.fields, function(item, key) {
            if (item.type == 'tab') {
                this.setFieldToTabIdIndex(item.children, key);
            }
        }.bind(this));

        //generate index, fieldkey => main-tabid
        //@obsolete
        Object.each(this.classProperties.tabFields, function(items, key) {
            this.setFieldToTabIdIndex(items, key);
        }.bind(this));

    },

    setFieldToTabIdIndex: function(childs, tabId) {
        Object.each(childs, function(item, key) {
            this.fieldToTabOIndex[key] = tabId;
            if (item.children) {
                this.setFieldToTabIdIndex(item.children, tabId);
            }
        }.bind(this));
    },

    renderVersions: function() {
        if (this.classProperties.versioning == true) {
            var versioningSelectRight = 5;
            if (this.languageSelect) {
                versioningSelectRight = 150;
            }

            this.versioningSelect = new ka.Select(this.win.getTitleGroupContainer());
            this.versioningSelect.setStyle('width', 120);

            this.versioningSelect.addEvent('change', this.changeVersion.bind(this));
        }
    },

    /*    renderMultilanguage: function () {

     if (this.classProperties.multiLanguage) {

     if (this.classProperties.asNested) {
     return false;
     }

     this.win.extendHead();

     this.languageSelect = new ka.Select();
     this.languageSelect.inject(this.saveBtn, 'before');
     this.languageSelect.setStyle('width', 120);

     this.languageSelect.addEvent('change', this.changeLanguage.bind(this));

     this.languageSelect.add('', t('-- Please Select --'));

     Object.each(ka.settings.langs, function (lang, id) {

     this.languageSelect.add(id, lang.langtitle + ' (' + lang.title + ', ' + id + ')');

     }.bind(this));

     if (this.winParams && this.winParams.item) {
     this.languageSelect.setValue(this.winParams.item.lang);
     }

     }

     },*/

    changeVersion: function() {
        var value = this.versioningSelect.getValue();
        if (value == '-') {
            value = null;
        }

        this.loadItem(value);
    },

    changeLanguage: function() {
        Object.each(this.fields, function(item, fieldId) {

            if (item.field.type == 'select' && item.field.multiLanguage) {
                item.field.lang = this.languageSelect.getValue();
                item.renderItems();
            }
        }.bind(this));

        if (this.languageTip && this.languageSelect.getValue() != '') {
            this.languageTip.stop();
            delete this.languageTip;
        }
    },

    changeTab: function(pTab) {
        this.currentTab = pTab;
        Object.each(this._buttons, function(button, id) {
            button.setPressed(false);
            this._panes[ id ].setStyle('display', 'none');
        }.bind(this));
        this._panes[ pTab ].setStyle('display', 'block');
        this._buttons[ pTab ].setPressed(true);

        this._buttons[ pTab ].stopTip();
    },

    reset: function() {
        this.setValue(this.item, true);
    },

    remove: function() {
        this.win.confirm(tf('Really delete %s?', this.getTitleValue()), function(answer) {

            this.win.setLoading(true, null, this.container.getCoordinates(this.win));
            var itemPk = ka.getObjectUrlId(this.classProperties['object'], this.winParams.item);

            this.lastDeleteRq = new Request.JSON({url: _pathAdmin + this.getEntryPoint() + '/',
                onComplete: function(pResponse) {
                    this.win.setLoading(false);
                    this.fireEvent('remove', this.winParams.item);
                    ka.getAdminInterface().objectChanged(this.classProperties['object']);
                    this.destroy();
                    this.win.close();
                }.bind(this)}).post({_method: 'delete', pk: itemPk});

        }.bind(this));
    },

    renderActionBar: function(container) {
        var container = this.win.getSidebar();

        this.actionGroup = new ka.ButtonGroup(container);
        this.saveBtn = this.actionGroup.addButton(t('Save'), '#icon-checkmark-6', function() {
            this.save();
        }.bind(this));

        if (this.win.isInline()) {
            this.closeBtn = this.actionGroup.addButton(t('Close'), '#icon-cancel', function() {
                this.checkClose();
            }.bind(this));
        }

        this.saveBtn.setButtonStyle('blue')

        this.removeBtn = this.actionGroup.addButton(t('Remove'), ka.mediaPath(this.classProperties.removeIcon), this.remove.bind(this));
        this.removeBtn.setButtonStyle('red');

        this.resetBtn = this.actionGroup.addButton(t('Reset'), '#icon-escape', this.reset.bind(this));

        //        if (this.classProperties.workspace) {
        //            this.showVersionsBtn = this.actionBarGroup1.addButton(t('Versions'), '#icon-history', this.showVersions);
        //        }

        if (true) {
            this.previewBtn = this.actionGroup.addButton(t('Preview'), '#icon-eye');
        }

        this.checkTabFieldWidth();
    },

    showVersions: function() {

        //for now, we use a dialog

        var dialog = this.win.newDialog();

        new ka.ObjectVersionGraph(dialog.content, {
            object: ka.getObjectUrlId(this.classProperties['object'], this.winParams.item)
        });

    },

    checkTabFieldWidth: function() {

        if (!this.topTabGroup) {
            return;
        }

        if (!this.cachedTabItems) {
            this.cachedTabItems = document.id(this.topTabGroup).getElements('a');
        }

        var actionsMaxLeftPos = 5;
        if (this.versioningSelect) {
            actionsMaxLeftPos += document.id(this.versioningSelect).getSize().x + 10
        }

        if (this.languageSelect) {
            actionsMaxLeftPos += document.id(this.languageSelect).getSize().x + 10
        }

        var actionNaviWidth = this.actionsNavi ? document.id(this.actionsNavi).getSize().x : 0;

        var fieldsMaxWidth = this.win.titleGroups.getSize().x - actionNaviWidth - 17 - 20 - (actionsMaxLeftPos + document.id(this.topTabGroup).getPosition(this.win.titleGroups).x);

        if (this.tooMuchTabFieldsButton) {
            this.tooMuchTabFieldsButton.destroy();
        }

        this.cachedTabItems.removeClass('ka-tabGroup-item-last');
        this.cachedTabItems.inject(document.hiddenElement);
        this.cachedTabItems[0].inject(document.id(this.topTabGroup));
        var curWidth = this.cachedTabItems[0].getSize().x;

        var itemCount = this.cachedTabItems.length - 1;

        if (!this.overhangingItemsContainer) {
            this.overhangingItemsContainer = new Element('div', {'class': 'ka-windowEdit-overhangingItemsContainer'});
        }

        var removeTooMuchTabFieldsButton = false, atLeastOneItemMoved = false;

        this.cachedTabItems.each(function(button, id) {
            if (id == 0) {
                return;
            }

            curWidth += button.getSize().x;
            if ((curWidth < fieldsMaxWidth && id < itemCount) || (id == itemCount && curWidth < fieldsMaxWidth + 20)) {
                button.inject(document.id(this.topTabGroup));
            } else {
                atLeastOneItemMoved = true;
                button.inject(this.overhangingItemsContainer);
            }

        }.bind(this));

        this.cachedTabItems.getLast().addClass('ka-tabGroup-item-last');

        if (atLeastOneItemMoved) {

            this.tooMuchTabFieldsButton = new Element('a', {
                'class': 'ka-tabGroup-item ka-tabGroup-item-last'
            }).inject(document.id(this.topTabGroup));

            new Element('img', {
                src: _path + 'bundles/kryncms/admin/images/ka.mainmenu-additional.png',
                style: 'left: 1px; top: 6px;'
            }).inject(this.tooMuchTabFieldsButton);

            this.tooMuchTabFieldsButton.addEvent('click', function() {
                if (!this.overhangingItemsContainer.getParent()) {
                    this.overhangingItemsContainer.inject(this.win.border);
                    ka.openDialog({
                        element: this.overhangingItemsContainer,
                        target: this.tooMuchTabFieldsButton,
                        offset: {y: 0, x: 1}
                    });

                    /*ka.openDialog({
                     element: this.chooser,
                     target: this.box,
                     onClose: this.close.bind(this)
                     });*/
                }
            }.bind(this));

        } else {

            this.cachedTabItems.getLast().addClass('ka-tabGroup-item-last');
        }

    },

    removeTooltip: function() {
        this.stopTip();
        this.removeEvent('click', this.removeTooltip);
    },

    /**
     *
     * @param [pWithoutEmptyCheck]
     * @param [patch]
     * @returns {*}
     */
    retrieveData: function(pWithoutEmptyCheck, patch) {
        if (!pWithoutEmptyCheck && !this.fieldForm.checkValid()) {
            var invalidFields = this.fieldForm.getInvalidFields();

            Object.each(invalidFields, function(item, fieldId) {

                var properTabKey = this.fieldToTabOIndex[fieldId];
                if (!properTabKey) {
                    return;
                }
                var tabButton = this.fields[properTabKey];

                if (tabButton && !tabButton.isPressed()) {

                    tabButton.startTip(t('Invalid input!'));
                    tabButton.toolTip.loader.set('src', _path + 'bundles/kryncms/admin/images/icons/error.png');
                    tabButton.toolTip.loader.setStyle('position', 'relative');
                    tabButton.toolTip.loader.setStyle('top', '-2px');
                    document.id(tabButton.toolTip).setStyle('top', document.id(tabButton.toolTip).getStyle('top').toInt() + 2);

                    tabButton.addEvent('click', this.removeTooltip);
                } else {
                    tabButton.stopTip();
                }

                item.highlight();
            }.bind(this));

            return false;
        }

        var req = this.fieldForm.getValue(null, patch);

        if (this.languageSelect) {
            if (!pWithoutEmptyCheck && this.languageSelect.getValue() == '') {

                if (!this.languageTip) {
                    this.languageTip = new ka.Tooltip(this.languageSelect, _('Please fill!'), null, null, _path + 'bundles/kryncms/admin/images/icons/error.png');
                }
                this.languageTip.show();

                return false;
            } else if (!pWithoutEmptyCheck && this.languageTip) {
                this.languageTip.stop();
            }
            req['lang'] = this.languageSelect.getValue();
        }

        return req;

    },

    hasUnsavedChanges: function() {
        if (!this.ritem) {
            return false;
        }

        var currentData = this.retrieveData(true);
        if (!currentData) {
            return true;
        }

        return JSON.encode(currentData) == JSON.encode(this.ritem) ? false : true;
    },

    checkClose: function() {
        var hasUnsaved = this.hasUnsavedChanges();

        if (hasUnsaved) {
            this.win.interruptClose = true;
            this.win._confirm(t('There are unsaved data. Want to continue?'), function(pAccepted) {
                if (pAccepted) {
                    this.win.close();
                }
            }.bind(this));
        } else {
            this.win.close();
        }
    },

    /**
     *
     * @param [patch] Default is false|null
     * @returns {{}}
     */
    buildRequest: function(patch) {
        var req = {};

        var data = this.retrieveData(null, patch);

        if (!data) {
            return;
        }

        this.ritem = data;

        if (this.winParams.item) {
            req = Object.merge(this.winParams.item, data);
        } else {
            req = data;
        }

        return data;
    },

    save: function(pClose) {

        if (this.lastSaveRq) {
            this.lastSaveRq.cancel();
        }

        var request = this.buildRequest(this.classProperties.usePatch);

        var method = this.classProperties.usePatch ? 'patch' : 'put';

        if (typeOf(request) != 'null') {

            this.saveBtn.startLoading(t('Saving ...'));

            var objectId = ka.getObjectUrlId(this.classProperties['object'], this.winParams.item);

            this.lastSaveRq = new Request.JSON({url: _pathAdmin + this.getEntryPoint() + '/' + objectId + '?_method=' + method,
                noErrorReporting: [
                    'Kryn\\CmsBundle\\Exceptions\\Rest\\ValidationFailedException',
                    'DuplicateKeysException',
                    'ObjectItemNotModified'
                ],
                noCache: true,
                onProgress: function(event) {
                    this.saveBtn.setProgress(parseInt(event.loaded / event.total * 100));
                }.bind(this),
                onFailure: function() {
                    this.saveBtn.failedLoading();
                }.bind(this),
                onComplete: function(res) {

                    console.log(res);
                    if (res && res.error == 'RouteNotFoundException') {
                        this.saveBtn.failedLoading();
                        return this.win.alert(t('RouteNotFoundException. You setup probably the wrong `editEntrypoint`'));
                    }

                    if (res && res.error == 'Kryn\\CmsBundle\\Exceptions\\Rest\\ValidationFailedException') {
                        this.saveBtn.failedLoading(t('Validation failed'));
                        return this.win.alert('Todo, show the filed etc.');
                    }

                    if (res && res.error == 'DuplicateKeysException') {
                        this.win.alert(t('Duplicate keys. Please change the values of marked fields.'));

                        Array.each(res.fields, function(field) {
                            if (this.fields[field]) {
                                this.fields[field].showInvalid();
                            }
                        }.bind(this));

                        this.saveBtn.failedLoading();
                        return;
                    }

                    if (!res) {
                        this.saveBtn.failedLoading();
                    }

                    if (typeOf(res.data) == 'object') {
                        this.winParams.item = res.data; //our new primary keys
                    } else {
                        this.winParams.item = ka.getObject(this.classProperties['object'], request); //maybe we changed some pk
                    }

                    this.saveBtn.stopTip(t('Saved'));

                    if (this.classProperties.loadSettingsAfterSave == true) {
                        ka.loadSettings();
                    }

                    this.fireEvent('save', [request, res]);
                    ka.getAdminInterface().objectChanged(this.classProperties['object']);

                    if ((!pClose || this.inline ) && this.classProperties.versioning == true) {
                        this.loadVersions();
                    }

                    if (this.win.isInline()) {
                        this.win.close();
                    }
                }.bind(this)}).post(request);
        }
    }
});