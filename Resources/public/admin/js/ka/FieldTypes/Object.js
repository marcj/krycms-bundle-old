ka.FieldTypes.Object = new Class({

    Extends: ka.FieldAbstract,

    Statics: {
        asModel: true,
        options: {
            object: {
                label: 'Object key',
                desc: 'Example: Core:Node.',
                type: 'objectKey',
                required: true
            },
            'objectLabel': {
                needValue: 'object',
                label: t('Object label field (Optional)'),
                desc: t('The key of the field which should be used as label.')
            },
            asObjectUrl: {
                label: 'Value as object url',
                desc: 'Returns object:name://value instead of only the `value`.',
                type: 'checkbox',
                'default': false
            }
        },
        modelOptions: {
            object: {
                label: 'Object key',
                desc: 'Example: Core:Node.',
                type: 'objectKey',
                required: true
            },
            'objectLabel': {
                needValue: 'object',
                label: t('Object label field (Optional)'),
                desc: t('The key of the field which should be used as label.')
            },
            'objectRelation': {
                label: t('Relation'),
                needValue: 'object',
                type: 'select',
                required: true,
                items: {
                    'nTo1': 'Many to One (n-1)',
                    '1ToN': 'One to Many (1-n)',
                    '1To1': 'One to One (1-1)',
                    'nToM': 'Many to Many (n-n)'
                }
            },
            'objectRelationName': {
                label: t('Relation name'),
                desc: t('Example: for a nTo1 relation with the column name `categoryId` you should use here `category`.'),
                required: true
            },
            'objectRefRelationName': {
                label: t('Relation foreign name (Optional)'),
                desc: t('Default is the camelCased table name of this object. Use another if the foreign object/table has already a relation with this name.')
            },
            'objectRelationOnDelete': {
                label: t('OnDelete method (Optional)'),
                type: 'select',
                'default': 'cascade',
                items: ['cascade', 'setnull', 'restrict', 'none']
            },
            'objectRelationOnUpdate': {
                label: t('OnUpdate method (Optional)'),
                type: 'select',
                'default': 'cascade',
                items: ['cascade', 'setnull', 'restrict', 'none']
            }
        }
    },

    options: {
        object: null,
        objects: null,
        asObjectUrl: false,
        combobox: false
    },

    createLayout: function () {

        if (typeOf(this.options.object) == 'string') {
            this.options.objects = [this.options.object];
        }

        if (!this.options.objects || (typeOf(this.options.objects) == 'array' && this.options.objects.length == 0)) {
            //add all objects
            this.options.objects = [];

            Object.each(ka.settings.configs, function (config, key) {
                if (config.objects) {
                    Object.each(config.objects, function (object, objectKey) {
                        this.options.objects.push(key + '\\' + objectKey);
                    }.bind(this));
                }
            }.bind(this));
        }
        ;

        var definition = ka.getObjectDefinition(this.options.objects[0]);

        if (!definition) {
            this.fieldInstance.fieldPanel.set('text', t('Object not found %s').replace('%s', this.options.objects[0]));
            throw 'Object not found ' + this.options.objects[0];
        }

        if (definition.chooserFieldJavascriptClass) {

            var clazz = ka.getClass(definition.chooserFieldJavascriptClass);
            if (!clazz) {
                throw 'Can no load custom object field class "' + definition.chooserFieldJavascriptClass +
                    '" for object ' + this.options.objects[0];
            }

            this.customObj = new clazz(this.field, this.fieldInstance.fieldPanel, this);

            this.customObj.addEvent('change', function () {
                this.fireChange();
            }.bind(this));

            this.setValue = this.customObj.setValue.bind(this.customObj);
            this.getValue = this.customObj.getValue.bind(this.customObj);
            this.isOk = this.customObj.isEmpty.bind(this.customObj);
            this.highlight = this.customObj.highlight.bind(this.customObj);

        } else {

            if (this.options.objectRelation == 'nToM' || this.options.multi == 1) {
                this.renderChooserMulti(this.options.objects);
            } else {
                this.renderChooserSingle(this.options.objects);
            }
        }

    },

    renderObjectTableNoItems: function () {

        var tr = new Element('tr').inject(this.chooserTable.tableBody);
        new Element('td', {
            colspan: this.renderChooserColumns.length,
            style: 'text-align: center; color: gray; padding: 5px;',
            text: t('Empty')
        }).inject(tr);
    },

    renderObjectTable: function () {

        this.chooserTable.empty();

        this.objectTableLoaderQueue = {};

        if (!this.objectId || this.objectId.length == 0) {
            this.renderObjectTableNoItems();
        } else {
            Array.each(this.objectId, function (id) {

                var row = [];

                var placeHolder = new Element('span');
                row.include(placeHolder);

                if (typeOf(id) == 'object') {
                    id = ka.getObjectUrlId(this.options.object, id);
                }

                ka.getObjectLabel(ka.getObjectUrl(this.options.object, id), function (label) {
                    placeHolder.set('html', label);
                });

                var actionBar = new Element('div');

                var remoteIcon = new Element('a', {
                    'class': 'text-button-icon icon-remove-3',
                    href: 'javascript:;',
                    title: t('Remove')
                }).inject(actionBar);

                row.include(actionBar);

                var tr = this.chooserTable.addRow(row);
                remoteIcon.addEvent('click', function () {
                    tr.destroy();
                    this.updateThisValue();
                }.bind(this));

                tr.kaFieldObjectId = id;

            }.bind(this));
        }
    },

    updateThisValue: function () {

        var rows = this.chooserTable.getRows();

        this.objectId = [];
        Array.each(rows, function (row) {
            this.objectId.push(row.kaFieldObjectId);
        }.bind(this));

    },

    renderChooserSingle: function () {
        var table = new Element('table', {
            style: 'width: 100%', cellpadding: 0, cellspacing: 0
        }).inject(this.fieldInstance.fieldPanel);

        var tbody = new Element('tbody').inject(table);

        var tr = new Element('tr').inject(tbody);
        var leftTd = new Element('td').inject(tr);
        var rightTd = new Element('td', {width: '50px'}).inject(tr);

        this.field = new ka.Field({
            noWrapper: true
        }, leftTd)

        this.input = this.field.getFieldObject().input;
        this.input.addClass('ka-Input-text-disabled');
        this.input.disabled = true;

        if (this.options.combobox) {
            this.input.disabled = false;
            this.input.addEvent('focus', function () {
                this.input.removeClass('ka-Input-text-disabled');
                this._lastValue = this.input.value;

                if (this.objectId) {
                    this.lastObjectLabel = this.input.value;
                    this.lastObjectId = this.objectId;
                }
            }.bind(this));

            var checkChange = function () {
                if (this.input.value == this.lastObjectLabel) {
                    this.objectId = this.lastObjectId;
                    this.input.addClass('ka-Input-text-disabled');
                    return;
                }

                if (typeOf(this._lastValue) != 'null' && this.input.value != this._lastValue) {
                    //changed it, so we delete this.objectValue since its now a custom value
                    delete this.objectId;
                    this.input.removeClass('ka-Input-text-disabled');
                } else if (this.objectId) {
                    this.input.addClass('ka-Input-text-disabled');
                }
            }.bind(this);

            this.input.addEvent('keyup', checkChange);
            this.input.addEvent('change', checkChange);
            this.input.addEvent('blur', checkChange);
        }

        if (this.options.inputWidth) {
            this.input.setStyle('width', this.options.inputWidth);
        }

        var div = new Element('span').inject(this.fieldInstance.fieldPanel);

        var chooserParams = {
            onSelect: function (pUrl) {
                this.setValue(pUrl, true);
            }.bind(this),
            value: this.objectId,
            cookie: this.options.cookie,
            objects: this.options.objects,
            browserOptions: this.options.browserOptions
        };

        if (this.objectId) {
            chooserParams.value = this.objectId;
        }

        if (this.options.cookie) {
            chooserParams.cookie = this.options.cookie;
        }

        if (this.options.domain) {
            chooserParams.domain = this.options.domain;
        }

        var button = new ka.Button(t('Choose'))
            .addEvent('click', function () {
            if (this.options.designMode) {
                return;
            }
            ka.wm.openWindow('admin/backend/chooser', null, -1, chooserParams, true);
        }.bind(this))
        .inject(rightTd);

        this.setValue = function (pVal, pInternal) {
            if (typeOf(pVal) == 'null' || pVal === false || pVal === '' || !ka.getCroppedObjectId(pVal)) {
                this.objectId = '';
                this.input.value = '';
                this.input.title = '';
                return;
            }

            pVal = String.from(pVal);

            this.objectId = pVal;
            if ((typeOf(pVal) == 'string' && pVal.substr(0, 'object://'.length) != 'object://')) {
                this.objectId = 'object://' + ka.normalizeObjectKey(this.options.objects[0]) + '/' + ka.urlEncode(pVal);
            }

            ka.getObjectLabel(this.objectId, function (pLabel) {
                if (pLabel === false) {
                    this.input.removeClass('ka-Input-text-disabled');
                    if (!this.options.combobox) {
                        this.input.value = '[Not Found]: ' + pVal;
                    } else {
                        this.input.value = pVal;
                    }
                    delete this.objectId;
                } else {
                    this.input.value = pLabel;
                    this.input.addClass('ka-Input-text-disabled');
                }
            }.bind(this));

            this.input.title = ka.urlDecode(ka.getCroppedObjectId(pVal));
            if (pInternal) {
                this.fireChange();
            }
        };

        this.getValue = function () {
            if (!this.objectId) {
                return this.input.value;
            }

            var val = this.objectId;

            if (!this.options.asObjectUrl && typeOf(val) == 'string' && val.substr(0, 'object://'.length) == 'object://') {
                return ka.getCroppedObjectId(val);
            }
            return val;
        }
    },

    renderChooserMulti: function () {

        this.renderChooserColumns = [];

        this.objectDefinition = ka.getObjectDefinition(this.options.objects[0]);

        this.renderChooserColumns.include([""]);
        this.renderChooserColumns.include(["", 50]);

        this.chooserTable = new ka.Table(this.renderChooserColumns, {absolute: false, selectable: false});

        this.chooserTable.inject(this.fieldInstance.fieldPanel);
        this.renderObjectTableNoItems();

        //compatibility
        if (this.options.domain) {
            if (!this.options.browserOptions) {
                this.options.browserOptions = {};
            }
            if (!this.options.browserOptions.node) {
                this.options.browserOptions.node = {};
            }
            this.options.browserOptions.node.domain = this.options.domain;
        }

        var chooserParams = {
            onSelect: function (pId) {
                if (!this.objectId) {
                    this.objectId = [];
                }

                this.objectId.include(ka.getCroppedObjectId(pId));
                this.renderObjectTable();
            }.bind(this),
            value: this.objectId,
            cookie: this.options.cookie,
            objects: this.options.objects,
            browserOptions: this.options.browserOptions
        };

        if (this.objectId) {
            chooserParams.value = this.objectId;
        }

        if (this.options.cookie) {
            chooserParams.cookie = this.options.cookie;
        }

        if (this.options.domain) {
            chooserParams.domain = this.options.domain;
        }

        var button = new ka.Button([t('Add'), '#icon-plus-5']).addEvent('click', function () {

            if (this.options.designMode) {
                return;
            }
            ka.wm.open('admin/backend/chooser', chooserParams, -1, true);

        }.bind(this));

        this.actionBar = new Element('div', {
            'class': 'ka-ActionBar'
        }).inject(this.fieldInstance.fieldPanel);

        button.inject(this.actionBar);

        this.setValue = function (pVal) {

            this.objectId = pVal;

            if (!this.objectId) {
                this.objectId = [];
            }

            if (typeOf(this.objectId) != 'array') {
                this.objectId = [this.objectId];
            }

            this.renderObjectTable();

        }.bind(this);

        this.getValue = function () {
            return this.objectId;
        };

    }

});