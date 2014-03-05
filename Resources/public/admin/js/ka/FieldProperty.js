ka.FieldProperty = new Class({
    Implements: [Events, Options],

    Binds: ['fireChange', 'openProperties', 'applyFieldProperties'],

    kaFields: {
        key: {
            label: t('Key'),
            modifier: 'trim',
            required: true
        },

        label: {
            label: t('Label'),
            type: 'text'
        },
        'type': {
            label: t('Type'),
            type: 'select'
        },
        width: {
            label: t('Width'),
            desc: t('Use a px value or a % value. Example: 25%, 50, 35px')
        },
        primaryKey: {
            needValue: ['text', 'password', 'number', 'checkbox', 'select', 'date', 'object', 'datetime', 'file',
                'folder', 'page'],
            againstField: 'type',
            label: t('Primary key'),
            'default': false,
            type: 'checkbox'
        },
        autoIncrement: {
            label: 'Auto increment?',
            desc: t('If no value is assigned the value will be increased by each insertion.'),
            type: 'checkbox',
            'default': false,
            needValue: 'number',
            againstField: 'type'
        },
        __optional__: {
            label: t('Optional'),
            cookieStorage: 'ka.FieldProperty.__optional__',
            type: 'childrenSwitcher',
            children: {
                desc: {
                    label: t('Description'),
                    type: 'text'
                },
                required: {
                    label: t('Required field?'),
                    type: 'checkbox',
                    'default': false
                },
                inputWidth: {
                    label: t('Input element width'),
                    needValue: ['text', 'number', 'password', 'object', 'file', 'folder', 'page', 'domain', 'datetime',
                        'date'],
                    againstField: 'type',
                    type: 'text'
                },
                inputHeight: {
                    label: t('Input element height'),
                    needValue: ['textarea', 'codemirror'],
                    againstField: 'type',
                    type: 'text'
                },
                maxlength: {
                    label: t('Max length'),
                    needValue: ['text', 'number', 'password'],
                    againstField: 'type',
                    type: 'text'
                },
                target: {
                    label: t('Inject to target'),
                    desc: t('If your tab has a own layout.'),
                    type: 'text'
                },
                'needValue': {
                    label: tc('kaFieldTable', 'Visibility condition'),
                    desc: t("Shows this field only, if the field defined below or the parent field has the defined value. String, JSON notation for arrays and objects, /regex/ or 'javascript:(value=='foo'||value.substr(0,4)=='lala')'")
                },
                againstField: {
                    label: tc('kaFieldTable', 'Visibility condition target field'),
                    desc: t("Define the key of another field if the condition should not against the parent. Use JSON notation for arrays and objects. String or Array")
                },
                'default': {
                    againstField: 'type',
                    type: 'text',
                    label: t('Default value. Use JSON notation for arrays and objects.')
                },
                'requiredRegex': {
                    needValue: ['text', 'password', 'number', 'checkbox', 'select', 'date', 'datetime', 'file', 'folder'
                    ],
                    againstField: 'type',
                    type: 'text',
                    label: t('Required value as regular expression.'),
                    desc: t('Example of an email-check: /^[^@]+@[^@]+/')
                },
                tableItem: {
                    label: t('Acts as a table item'),
                    desc: t('Injects instead of a DIV a TR element.'),
                    type: 'checkbox',
                    'default': false
                },
                noWrapper: {
                    label: t('No wrapper. Removes all around the field itself.'),
                    desc: t('Injects only the pure UI of the defined type.'),
                    type: 'checkbox',
                    'default': false
                }
            }
        }
    },

    options: {
        addLabel: t('Add'),
        asModel: false, //renders 'modelOptions' of ka.Fields instead of 'options' if available. Includes ORM specific stuff.
        asFrameworkColumn: false, //for column definition, with width field. renders all fields of ka.LabelTypes.
        asFrameworkSearch: false, //Remove some option fields, like 'visibility condition', 'required', etc
        withoutChildren: false, //deactivate children?
        tableItemLabelWidth: 330,
        allTableItems: true,
        withActions: true,

        withWidth: false, //is enabled if asFrameworkColumn is active. otherwise you can enable it here manually.

        fieldTypes: false, //if as array defined, we only have types which are in this list
        fieldTypesBlacklist: false, //if as array defined, we only have types which are not in this list

        keyModifier: '',

        asTableItem: true,

        noActAsTableField: false, //Remove the field 'Acts as a table item'
        arrayKey: false //allows key like foo[bar], foo[barsen], foo[bar][sen]
    },

    childDiv: false,
    main: false,
    definition: {},

    children: [], //instances of ka.FieldProperty

    initialize: function (pKey, pDefinition, pContainer, pOptions, pWin) {

        this.setOptions(pOptions);
        this.win = pWin;
        this.key = pKey;
        this.container = pContainer;

        this.prepareFields();
        this._createLayout();

        this.setValue(pKey, pDefinition);
    },

    prepareFields: function () {

        this.kaFields = Object.clone(this.kaFields);

        var items = {}, children = {}, fields, options;

        var sourceFields = this.options.asFrameworkColumn ? ka.LabelTypes : ka.FieldTypes;

        this.fieldOptionsMap = {};

        Object.each(sourceFields, function(field, key){
            if (this.options.asModel && !field.asModel) return;
            items[key.lcfirst()] = field.label || key;

            if (field.options || field.modelOptions) {
                fields = {};
                this.fieldOptionsMap[key.lcfirst()] = [];

                options = field.options;
                if (this.options.asModel && field.modelOptions) {
                    options = Object.merge(options, field.modelOptions);
                }

                var extractOptions = function(optionsToExtract) {
                    Object.each(optionsToExtract, function(option, optionKey) {
                        if ('function' === typeOf(option)) {
                            fields[optionKey] = option();
                        } else {
                            fields[optionKey] = option;
                        }
                        this.fieldOptionsMap[key.lcfirst()].push(optionKey);
                        if ('fieldForm' !== option.type && option.children) {
                            extractOptions(option.children);
                        }
                    }.bind(this));
                }.bind(this);

                extractOptions(options);

                children['options.' + key.lcfirst()] = {
                    type: 'fieldForm',
                    fields: options,
                    noWrapper: true,
                    needValue: key.lcfirst(),
                    allTableItems: this.options.allTableItems
                }
            }
        }.bind(this));

        this.kaFields.type = {
            label: t('Type'),
            type: 'select',
            items: items,
            children: children
        };

        if (!this.options.asModel) {
            delete this.kaFields.primaryKey;
            delete this.kaFields.autoIncrement;
        }

        if (this.options.noActAsTableField) {
            delete this.kaFields.__optional__.children.tableItem;
        }

        if (this.options.asFrameworkColumn) {
            delete this.kaFields.__optional__;
            this.kaFields.type.label = t('Label type');
        } else if (!this.options.withWidth) {
            delete this.kaFields.width;
        }

        if (typeOf(this.options.fieldTypes) === 'string') {
            this.options.fieldTypes = this.options.fieldTypes.replace(' ', '').split(',');
        }
        if (typeOf(this.options.fieldTypesBlacklist) === 'string') {
            this.options.fieldTypesBlacklist = this.options.fieldTypesBlacklist.replace(' ', '').split(',');
        }

        if (typeOf(this.options.fieldTypes) == 'array') {
            Object.each(this.kaFields.type.items, function (def, key) {
                if (!this.options.fieldTypes.contains(key)) {
                    delete this.kaFields.type.items[key];
                }
            }.bind(this));
        }

        if (typeOf(this.options.fieldTypesBlacklist) == 'array') {
            Array.each(this.options.fieldTypesBlacklist, function (key) {
                delete this.kaFields.type.items[key];
            }.bind(this));
        }
    },

    _createLayout: function () {
        var count = this.container.getElements('.ka-fieldProperty-item').length + 1;

        if (this.options.asTableItem) {
            this.main = new Element('tr', {
                'class': 'ka-fieldProperty-item'
            }).inject(this.container);

            this.main.store('ka.FieldProperty', this);

            this.tdLabel = new Element('td').inject(this.main);

            this.iKey = new ka.Field({
                type: 'text',
                modifier: this.options.keyModifier,
                noWrapper: true
            }, this.tdLabel);

            delete this.kaFields.key;

            this.iKey.setValue(this.key ? this.key : 'property_' + count);

            if (this.options.asFrameworkColumn || this.options.withWidth) {
                this.tdWidth = new Element('td', {width: 80}).inject(this.main);
                var width = Object.clone(this.kaFields.width);
                width.noWrapper = true;
                this.widthField = new ka.Field(width, this.tdWidth);

                this.widthField.setValue(this.definition && this.definition.width ? this.definition.width : '');
            }

            this.tdType = new Element('td', {width: 150}).inject(this.main);

            var field = Object.clone(this.kaFields.type);
            delete field.children;

            field.noWrapper = true;
            this.typeField = new ka.Field(field, this.tdType);

            this.typeField.setValue(this.definition && this.definition.type ? this.definition.type : 'text');

            this.tdProperties = new Element('td', {width: 150}).inject(this.main);

            this.propertiesButton = new ka.Button(t('Properties'))
                .addEvent('click', this.openProperties)
                .inject(this.tdProperties);

            this.actionContainer = new Element('td', {
                width: 80
            }).inject(this.main);

        } else {
            //non tr/td
            this.main = new Element('div', {
                'class': 'ka-fieldProperty-item'
            }).inject(this.container);

            this.main.store('ka.FieldProperty', this);

            this.fieldObject = new ka.FieldForm(this.main, this.kaFields, {
                allTableItems: this.options.allTableItems,
                tableItemLabelWidth: this.options.tableItemLabelWidth,
                withEmptyFields: false
            }, {win: this.win});

            this.fieldObject.setValue(this.definition);

            this.fieldObject.addEvent('change', this.fireChange);
        }

        if (!this.options.withoutChildren) {

            new Element('a', {
                style: "cursor: pointer; font-family: 'icomoon'; padding: 0px 5px;",
                title: _('Add children'),
                html: '&#xe109;'
            })
                .addEvent('click', this.addChild.bind(this, '', {}))
                .inject(this.actionContainer);
        }

        if (this.options.withActions) {

            new Element('a', {
                style: "cursor: pointer; font-family: 'icomoon'; padding: 0px 5px;",
                title: _('Remove'),
                html: '&#xe26b;'
            })
                .addEvent('click', function () {
                    this.win._confirm(t('Really delete?'), function (ok) {
                        if (ok) {
                            this.fireEvent('delete');
                            this.removeEvents('change');
                            this.main.destroy();
                            if (this.childContainer) {
                                this.childContainer.destroy();
                            }
                        }
                    }.bind(this));
                }.bind(this))
                .inject(this.actionContainer);

            new Element('a', {
                style: "cursor: pointer; font-family: 'icomoon'; padding: 0px 2px;",
                title: t('Move up'),
                html: '&#xe2ca;'
            })
                .addEvent('click', function () {

                    var previous = this.main.getPrevious('.ka-fieldProperty-item');
                    if (!previous) {
                        return;
                    }
                    this.main.inject(previous, 'before');

                    if (this.childContainer) {
                        this.childContainer.inject(this.main, 'after');
                    }

                }.bind(this))
                .inject(this.actionContainer);

            new Element('a', {
                style: "cursor: pointer; font-family: 'icomoon'; padding: 0px 2px;",
                title: t('Move down'),
                html: '&#xe2cc;'
            })
                .addEvent('click', function () {

                    var next = this.main.getNext('.ka-fieldProperty-item');
                    if (!next) {
                        return;
                    }
                    this.main.inject(next.childContainer || next, 'after');

                    if (this.childContainer) {
                        this.childContainer.inject(this.main, 'after');
                    }

                }.bind(this))
                .inject(this.actionContainer);

        }

    },

    openProperties: function () {
        this.dialog = new ka.Dialog(this.win, {
            absolute: true,
            minWidth: '90%',
            minHeight: '90%'
        });

        /*if (!this.options.withTableDefinition) {
         var headerInfo = new Element('div', {
         text: t('Surround the key above with __ and __ (double underscore) to define a field which acts only as a user interface item and does not appear in the result.'),
         style: 'color: gray',
         'class': 'ka-fieldTable-key-info'
         }).inject(this.header);
         }*/

        var main = new Element('div', {'class': 'ka-fieldTable-definition'}).inject(this.dialog.content);

        var fieldContainer;

        new Element('h2', {
            text: tf('Field: %s', this.iKey.getValue())
        }).inject(main);

        if (this.options.allTableItems) {
            var table = new Element('table', {
                width: '100%'
            }).inject(main);

            fieldContainer = new Element('tbody').inject(table);
        } else {
            fieldContainer = main;
        }

        this.saveBtn = new ka.Button(t('Apply'));

        this.fieldObject = new ka.FieldForm(fieldContainer, this.kaFields, {
            allTableItems: this.options.allTableItems,
            tableItemLabelWidth: this.options.tableItemLabelWidth,
            saveButton: this.saveBtn,
            withEmptyFields: false
        });

        this.fieldObject.setValue(this.definition);

        this.fieldObject.getField('type').setValue(this.typeField.getValue(), true);

        if (this.options.asFrameworkColumn || this.options.withWidth) {
            this.fieldObject.getField('width').setValue(this.widthField.getValue(), true);
        }

        new ka.Button(t('Cancel'))
            .addEvent('click', function () {
                this.dialog.closeAnimated();
            }.bind(this))
            .inject(this.dialog.bottom);

        this.saveBtn.addEvent('click', function () {
                if (!this.fieldObject.checkValid()) {
                    return;
                }

                this.definition = this.fieldObject.getValue();
                this.typeField.setValue(this.definition.type);
                if (this.options.asFrameworkColumn || this.options.withWidth) {
                    this.widthField.setValue(this.definition.width);
                }

                this.fireChange();
                this.dialog.close();
            }.bind(this))
            .setButtonStyle('blue')
            .inject(this.dialog.bottom);

        this.dialog.center(true);
    },

    fireChange: function () {
        this.fireEvent('change');
    },

    addChild: function (pKey, pDefinition) {

        if (!this.childContainer) {

            this.childContainer = new Element('tr').inject(this.main, 'after');
            this.main.childContainer = this.childContainer;

            this.childTd = new Element('td', {
                colspan: this.main.getChildren().length
            }).inject(this.childContainer);

            this.childDiv = new Element('div', {
                style: 'margin-left: 25px'
            }).inject(this.childTd);

            this.childContainer = new Element('table', {
                width: '100%'
            }).inject(this.childDiv);

        }

        new ka.FieldProperty(pKey, pDefinition, this.childContainer, this.options, this.win);
    },

    getValue: function () {
        var key;

        if (this.options.asTableItem) {
            key = this.iKey.getValue();
            var type = this.typeField.getValue();

            if (!key) {
                return;
            }

            this.definition.type = type;
        } else {
            this.definition = this.fieldObject.getValue();
            key = this.definition.key;
        }

        var property = this.definition;

        Object.each(property, function (pval, pkey) {

            if (typeOf(pval) != 'string') {
                return;
            }

            var newItem = false;

            try {
                //check if json array
                if (pval.substr(0, 1) == '[' && pval.substr(pval.length - 1) == ']' &&
                    pval.substr(0, 2) != '[[' && pval.substr(pval.length - 2) != ']]') {
                    newItem = JSON.decode(pval);
                }

                //check if json object
                if (pval.substr(0, 1) == '{' && pval.substr(pval.length - 1, 1) == '}') {
                    newItem = JSON.decode(pval);
                }

            } catch (e) {
            }

            if (newItem) {
                property[pkey] = newItem;
            }

        }.bind(this));

        if (!this.options.withoutChildren && this.childContainer) {
            property.children = {};

            this.childContainer.getChildren('tr.ka-fieldProperty-item').each(function (child) {
                var fieldProperty = child.retrieve('ka.FieldProperty');
                var value = fieldProperty.getValue();
                property.children[value.key] = value.definition;
            });

            if (Object.getLength(property.children) === 0) {
                delete property.children;
            }
        }

        if (property.options){
            if (property.options[property.type]) {
                property.options = property.options[property.type];
            } else {
                delete property.options;
            }
        }

        return {
            key: key,
            definition: property
        };
    },

    normalizeValues: function(pDefinition) {
        if (pDefinition.type == 'select' && pDefinition.tableItems) {
            if (typeOf(pDefinition.tableItems) == 'object') {
                pDefinition.items = Object.clone(pDefinition.tableItems);
            }

            if (typeOf(pDefinition.tableItems) == 'array') {
                pDefinition.items = Array.clone(pDefinition.tableItems);
            }

            delete pDefinition.tableItems;
        }

        if (typeOf(pDefinition.items) == 'array') {
            var first = pDefinition.items[0];
            if (typeOf(first) == 'object') {
                var newItems = {};
                Array.each(pDefinition.items, function (item) {
                    newItems[ item[pDefinition.table_key] ] = item[pDefinition.table_label];
                });
                pDefinition.items = newItems;
            }
        }

        if (!pDefinition.type) {
            pDefinition.type = 'text';
        }

        var options = {};
        var normalize = function(map){
            Object.each(map, function(fields, type) {
                options[type] = {};
                Array.each(fields, function(field) {
                    var value = pDefinition[field];
                    if (pDefinition.options && pDefinition.options[field]) {
                        value = pDefinition.options[field];
                    }
                    options[type][field] = value;
                });
            });
        };

        normalize(this.fieldOptionsMap);

        pDefinition.options = options;
    },

    setValue: function (key, definition) {
        this.definition = Object.clone(definition || {});
        //console.log('setValue', key, definition);
        this.normalizeValues(this.definition);

        if (this.options.asTableItem) {
            this.iKey.setValue(key);
            this.typeField.setValue(this.definition.type);

            if (this.options.asFrameworkColumn || this.options.withWidth) {
                this.widthField.setValue(this.definition.width);
            }

        } else {
            this.fieldObject.setValue(this.definition);
        }

        delete this.children;

        this.children = [];
        if (this.childDiv) {
            this.childDiv.empty();
        }

        if (!this.options.withoutChildren) {
            if (this.definition.children) {
                Object.each(this.definition.children, function (definition, key) {

                    this.addChild(key, definition);

                }.bind(this));
            }
        }

        this.fireEvent('set');
    }

});