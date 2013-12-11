ka.WindowAdd = new Class({
    Extends: ka.WindowEdit,

    initialize: function(pWin, pContainer) {
        this.windowAdd = true;
        this.parent(pWin, pContainer);
    },

    loadItem: function() {

        //ist in render() am ende also lösche unnötigen balast
        this.win.setLoading(false);

        this.removeBtn.hide();

        if (this.previewBtn) {
            this.previewBtn.hide();
        }
        if (this.showVersionsBtn) {
            this.showVersionsBtn.hide();
        }
        if (this.resetBtn) {
            this.resetBtn.hide();
        }

        this.ritem = this.retrieveData(true);

        this.openAddItem();

        var first = this.getContentContainer().getElement('input[type=text]');
        if (first && first.focus) {
            first.focus();
        }
    },

    /**
     * Opens a first step overlay, that points then to the actual form if only nestedAddWithPositionSelection is set.
     * If addMultiple is set, we use $addMultipleFields $addMultipleFixedFields for the insertion and ignore the
     * actual form.
     * If nothing is set, this method does nothing.
     *
     */
    openAddItem: function() {

        if ((this.classProperties.asNested && this.classProperties.nestedAddWithPositionSelection) || this.classProperties.addMultiple) {

            //show dialog with
            this.createNewFirstDialog();

            if (this.tabPane) {
                this.tabPane.hide();
            }

            if (this.classProperties.addMultiple) {

                if (this.classProperties.nestedAddWithPositionSelection) {
                    this.addItemMultiAddLayout = new ka.LayoutHorizontal(this.addDialogFieldContainer, {
                        columns: [null, this.classProperties.addMultipleFieldContainerWidth]
                    });

                    new ka.LayoutSplitter(this.addItemMultiAddLayout.getColumn(2), 'left');

                    this.addDialogLayoutPositionChooser = this.addNestedObjectPositionChooser(this.addItemMultiAddLayout.getColumn(1));

                    this.addDialogFieldContainerNested = new Element('div', {
                        'style': 'position: absolute; left: 6px; top: 0; right: 0; bottom: 0; overflow: auto;'
                    }).inject(this.addItemMultiAddLayout.getColumn(2));
                    this.populateAddMultipleForm(this.addDialogFieldContainerNested);
                } else {
                    this.populateAddMultipleForm(this.addDialogFieldContainer);
                }

            } else {

                this.addDialogFieldContainer.setStyle('position', 'relative');
                this.addDialogLayoutPositionChooser = this.addNestedObjectPositionChooser(this.addDialogFieldContainer);

            }

            if (this.addDialogLayoutPositionChooser) {

                this.addDialogLayoutPositionChooser.addEvent('positionChoose', function(pDom, pDirection, pItem, pChooser, pTree) {

                    this.addItemToAdd = {
                        position: pDirection == 'after' ? 'next' : 'first',
                        pk: ka.getObjectPk(pDom.objectKey, pItem),
                        objectKey: pDom.objectKey,
                        tree: pTree
                    };

                    this.checkAddItemForm();
                }.bind(this));

            }

            //            if (!this.classProperties.addMultiple && this.classProperties.nestedAddWithPositionSelection) {

            //                this.openAddItemNextButton = new ka.Button(tc('addNestedObjectChoosePositionDialog', t('Next')))
            //                    .inject(this.openAddItemPageBottom);
            //
            //                this.openAddItemNextButton.setButtonStyle('blue');
            //                this.openAddItemNextButton.setEnabled(false);

            //            } else

            //            if (this.classProperties.addMultiple) {
            //
            //                this.openAddItemSaveButton = new ka.Button(tc('addMultipleItems', t('Add')))
            //                    .addEvent('click', function () {
            //                        this.multipleAdd();
            //                    }.bind(this))
            //                    .inject(this.openAddItemPageBottom);
            //
            //                this.openAddItemSaveButton.setButtonStyle('blue');
            //                //this.openAddItemSaveButton.setEnabled(false);
            //            }

            this.renderSelectPositionText();

        }
    },

    multipleAdd: function(pClose) {

        if (!this.addMultipleFieldForm.checkValid()) {
            return false;
        }

        var request = this.addMultipleFieldForm.getValue();

        this.saveBtn.startLaggedTip(t('Still adding ...'));
        if (this.lastAddRq) {
            this.lastAddRq.cancel();
        }

        request._multiple = true;

        if (this.addItemToAdd) {
            request._position = this.addItemToAdd.position;
            request._pk = this.addItemToAdd.pk;
            request._targetObjectKey = this.addItemToAdd.objectKey;
        }

        this.lastAddRq = new Request.JSON({url: _pathAdmin + this.getEntryPoint() + '/:multiple',
            noErrorReporting: ['DuplicateKeysException', 'ObjectItemNotModified'],
            noCache: true, onComplete: function(pResponse) {

                if (pResponse.error == 'DuplicateKeysException') {
                    this.win._alert(t('Duplicate keys. Please change the values of marked fields.'));

                    Array.each(pResponse.fields, function(field) {
                        if (this.fields[field]) {
                            this.fields[field].showInvalid();
                        }
                    }.bind(this));

                    this.saveBtn.stopTip(t('Failed'));
                    return;
                }

                if (pResponse.error == 'FieldCanNotBeEmptyException') {
                    this.saveBtn.stopTip(t('Failed'));
                    return;
                }

                this.winParams.item = pResponse.data[0]; //our new primary keys for the first item

                this.saveBtn.stopTip(t('Saved'));

                if (!pClose && this.saveNoClose) {
                    this.saveNoClose.stopTip(t('Done'));
                }

                if (this.classProperties.loadSettingsAfterSave == true) {
                    ka.loadSettings();
                }

                var args = [request, pResponse];
                if (this.addItemToAdd) {
                    args.push(this.addItemToAdd.tree);
                }
                this.fireEvent('addMultiple', args);

                window.fireEvent('softReload', this.win.getEntryPoint());

                if (pClose) {
                    this.win.close();
                }

            }.bind(this)}).post(request);

    },

    renderSelectPositionText: function() {

        if (this.classProperties.nestedAddWithPositionSelection) {
            this.selectPositionText = new Element('div', {
                text: t('Select the position of your new entries!'),
                style: 'position: absolute; top: 0px; left: 5px; color: gray;'
            }).inject(this.openAddItemPageBottom);

            this.addDialogLayoutPositionChooser.addEvent('positionChoose', function() {
                this.selectPositionText.setStyle('display', 'none');
            }.bind(this));

            this.addDialogLayoutPositionChooser.addEvent('positionChoose', function() {
                this.selectPositionText.setStyle('display', 'none');
            }.bind(this));
        }

    },

    checkAddItemForm: function() {
        var valid = true;

        if (!this.addItemToAdd) {
            valid = false;
        }

        if (this.classProperties.addMultiple) {

            if (this.addMultipleFieldForm && !this.addMultipleFieldForm.isValid()) {
                valid = false;
            }

            if (this.saveBtn) {
                this.saveBtn.setEnabled(valid);
            }
        }
    },

    populateAddMultipleForm: function(pContainer) {

        var fields = {};

        if (typeOf(this.classProperties.addMultipleFixedFields) == 'object' && Object.getLength(this.classProperties.addMultipleFixedFields) > 0) {

            Object.each(this.classProperties.addMultipleFixedFields, function(item, key) {
                fields[key] = item;
            });

        }

        if (typeOf(this.classProperties.addMultipleFields) == 'object' && Object.getLength(this.classProperties.addMultipleFields) > 0) {

            fields._items = {
                label: t('Values per entry'),
                type: 'array',
                width: 'auto',
                withOrder: true,
                startWith: 1,
                columns: [],
                fields: {}
            };

            Object.each(this.classProperties.addMultipleFields, function(item, key) {

                var column = {};
                column.label = item.label;
                column.desc = item.desc;
                column.width = item.width;

                if (item.required && (typeOf(item.withAsteriskIfRequired) == 'null' || item.withAsteriskIfRequired)) {
                    column.label += '*';
                }

                fields._items.columns.push(column);

                fields._items.fields[key] = item;
            });

        }

        this.addMultipleFieldForm = new ka.FieldForm(pContainer, fields, {
            onChange: this.checkAddItemForm.bind(this)
        });

    },

    createNewFirstDialog: function() {

        this.addNestedAddPage = new Element('div', {
            'class': 'ka-windowEdit-form-addDialog'
        }).inject(this.container);

        this.addDialogLayout = new ka.LayoutVertical(this.addNestedAddPage, {
            rows: [40, null],
            gridLayout: true
        });

        this.addDialogFieldContainer = this.addDialogLayout.getContentRow(2);

        this.openAddItemPageBottom = new Element('div', {
            'class': 'kwindow-win-buttonBar'
        }).inject(this.addDialogLayout.getContentRow(1));

    },

    addNestedObjectPositionChooser: function(pContainer) {
        var objectOptions = {};
        var fieldObject;

        objectOptions.type = 'tree';
        objectOptions.object = this.classProperties.object;
        objectOptions.scopeChooser = false;
        objectOptions.noWrapper = true;
        objectOptions.selectable = false;
        objectOptions.moveable = this.classProperties.nestedMoveable;

        var lastSelected;

        var choosePosition = function(pChooser, pDom, pDirection, pItem, pTree) {
            if (lastSelected) {
                lastSelected.removeClass('ka-objectTree-positionChooser-item-active');
            }

            lastSelected = pChooser;
            lastSelected.addClass('ka-objectTree-positionChooser-item-active');

            fieldObject.fireEvent('positionChoose', [pDom, pDirection, pItem, pChooser, pTree]);
        };

        var addChooser = function(pDom, pDirection, pItem, pTree) {
            var div;

            if (pDirection != 'into') {
                if (pDom.childrenContainer.insertedAddChooserAfter) {
                    return;
                }
                div = new Element('div', {
                    styles: {
                        paddingLeft: pDom.getStyle('padding-left').toInt() + 18
                    }
                }).inject(pDom.childrenContainer, pDirection);
                pDom.childrenContainer.insertedAddChooserAfter = true;
            } else {
                if (pDom.insertedAddChooser) {
                    return;
                }
                div = pDom.span;
                pDom.insertedAddChooser = true;
            }

            var a = new Element('a', {
                html: ' <------ &nbsp;&nbsp;',
                'class': 'ka-objectTree-positionChooser-item',
                href: 'javascript:;',
                style: 'text-decoration: none;'
            }).addEvent('click',function() {
                    choosePosition(this, pDom, pDirection, pItem, pTree);
                }).inject(div);

            new Element('span', {
                'class': 'ka-objectTree-positionChooser-item-text',
                text: pDirection == 'into' ? tc('addNestedObjectChoosePositionDialog', 'Into this!') : tc('addNestedObjectChoosePositionDialog', 'Add here!')
            }).inject(a);

            return div;
        }

        objectOptions.onChildrenLoaded = function(pItem, pDom, pTree) {

            if (pDom.childrenContainer) {
                var children = pDom.childrenContainer.getChildren('.ka-objectTree-item');
                if (children.length > 0) {
                    pDom.childrenContainer.getChildren('.ka-objectTree-item').each(function(item) {
                        addChooser(item, 'after', item.objectEntry, pTree);
                        addChooser(item, 'into', item.objectEntry, pTree);
                    });
                }
            }

            addChooser(pDom, 'into', pDom.objectEntry, pTree);

        }.bind(this);

        if (this.languageSelect) {
            objectOptions.scopeLanguage = this.languageSelect.getValue();
        }

        var treeContainer = new Element('div', {
            style: 'position: absolute; left: 0; right: 0; top: 0; bottom: 0; overflow: auto;'
        }).inject(pContainer);

        fieldObject = new ka.Field(objectOptions, treeContainer);
        return fieldObject;

    },

    nestedItemSelected: function(pItem, pDom) {
        //pDom.objectKey
        //pDom.id

        if (pDom.objectKey == this.classProperties.object) {

            if (_this.classProperties.edit) {

                ka.entrypoint.open(ka.entrypoint.getRelative(_this.win.getEntryPoint(), _this.classProperties.editEntrypoint), {
                    item: pItem.values
                }, this);

            }

        } else if (this.classProperties.nestedRootEdit) {
            var entryPoint = ka.entrypoint.getRelative(this.win.getEntryPoint(), this.classProperties.nestedRootEditEntrypoint);
            ka.entrypoint.open(entryPoint);
        }

    },

    save: function(pClose) {

        if (this.lastSaveRq) {
            this.lastSaveRq.cancel();
        }

        if (this.classProperties.addMultiple) {
            return this.multipleAdd();
        }

        var request = this.buildRequest();

        if (typeOf(request) != 'null') {

            this.saveBtn.startTip(t('Adding ...'));

            this.lastSaveRq = new Request.JSON({url: _pathAdmin + this.getEntryPoint()+'/',
                noErrorReporting: ['DuplicateKeysException', 'ObjectItemNotModified'],
                noCache: true,
                onError: function() {
                    this.saveBtn.stopTip(t('Failed'));
                }.bind(this),
                onComplete: function(res) {
                    if (res.error == 'RouteNotFoundException') {
                        return this.win.alert(t('RouteNotFoundException. You setup probably the wrong `editEntrypoint`'));
                    }

                    if (res.error == 'DuplicateKeysException') {
                        this.win._alert(t('Duplicate keys. Please change the values of marked fields.'));

                        Array.each(res.fields, function(field) {
                            if (this.fields[field]) {
                                this.fields[field].showInvalid();
                            }
                        }.bind(this));

                        this.saveBtn.stopTip(t('Failed'));
                        return;
                    }

                    if (typeOf(res.data) == 'object') {
                        this.winParams.item = res.data; //our new primary keys
                    } else {
                        this.winParams.item = ka.getObjectPk(this.classProperties['object'], request); //maybe we changed some pk
                    }

                    this.saveBtn.stopTip(t('Added'));

                    if (this.classProperties.loadSettingsAfterSave == true) {
                        ka.loadSettings();
                    }

                    this.fireEvent('add', [request, res]);

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
