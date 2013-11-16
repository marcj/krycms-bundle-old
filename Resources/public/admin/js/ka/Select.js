ka.Select = new Class({
    Implements: [Options, Events],

    Binds: ['addItemToChooser', 'checkScroll', 'search', 'actions', 'focus', 'blur', 'fireChange'],

    opened: false,
    value: null,

    /**
     * Items if we have fixed items.
     * @type {Object}
     */
    items: [],

    /**
     * Items which should not be visible.
     * @type {Object}
     */
    hideItems: {},

    /**
     * Items that are currently visible in the chooser.
     * @type {Object}
     */
    currentItems: [],
    duringFirstSelectLoading: false,

    a: {},
    enabled: true,

    searchValue: '',

    cachedObjectItems: {},

    objectFields: [],
    loaded: 0,
    maximumItemsReached: false,
    whileFetching: false,
    loaderId: 0,
    backupedTitle: false,

    labelTemplate: '{if kaSelectImage}' + '{var isVectorIcon = kaSelectImage.substr(0,1) == "#"} ' + '{if kaSelectImage && isVectorIcon}<span class="{kaSelectImage.substr(1)}">{/if}' + '{if kaSelectImage && !isVectorIcon}<img src="{kaSelectImage}" />{/if}' + '{/if}' + '{label}' + '{if kaSelectImage && isVectorIcon}</span>{/if}',

    options: {

        /**
         * Static items. You can pass a array of items or a object. In case of the object, the value-key is returned
         * as value. In case of the array, the actual label value is returned as value;
         *
         * Examples:
         *
         *  //take care with this one: JavaScript does not have a guarantee for the order. Use `objectItems` instead.
         *  items: {
         *    value: 'Label',
         *    value2: 'Label 2',
         *  }
         *
         *  items: [
         *     'Label', //will have value=0
         *     'Label 2' //will have value=1
         *  ]
         *
         * @var {Array|Object}
         */
        items: null, //array or object

        /**
         * Same as `items` but since object entries have in JavaScript no fixed order,
         * we can pass here a list of entries with a fixed order.
         *
         * Example:
         *
         *  objectItems: [
         *     {value: 'Label'},
         *     {value2: 'Label 2'},
         *  ]
         *
         * @var {Array}
         */
        objectItems: null,

        /**
         * Pass here the entry point path to your store.
         * You'll get as value always the raw id of your store. Not urlencoded as it is if you pass an object key.
         *
         * @var {String}
         */
        store: null, //string

        /**
         * If you pass an object, the REST entry point kryn/admin/object/<object>/ is called and you'll
         * get as value always an array containing the primary keys.
         *
         * @var {String}
         */
        object: false,

        /**
         * Use a other field as label as the default.
         *
         * @var {String}
         */
        objectLabel: null,

        /**
         * Requests more fields at the REST backend, so
         * you have more information iny our template/
         */
        objectFields: null,

        /**
         * The language. If the `object` is multi-language based, we filter
         * it by `objectLanguage` per default at the REST backend.
         *
         * @var {String}
         */
        objectLanguage: null,

        /**
         * More filter.
         *
         * {
         *    field1: 'filterByThis'
         * }
         *
         * @todo implement it
         *
         * @var {Object}
         */
        filter: {},

        /**
         * Whether to use a branch or not
         * kryn/admin/object/<object>/<objectBranch>:branch
         *
         * Contains the pk of the branch entry.
         *
         * Use true for the root.
         * Define `objectScope`, if the target object has multiple roots.
         *
         * @var {Boolean|String|Integer}
         */
        objectBranch: null,

        /**
         * The scope value, if you have objectBranch defined.
         *
         * @var {String|Integer}
         */
        objectScope: null,

        /**
         * Custom label template. Use `objectFields` if you use here more fields than
         * the default REST backend returns.
         *
         * @var {String}
         */
        labelTemplate: null,

        /**
         * Default items per load.
         *
         * @var {Number}
         */
        maxItemsPerLoad: 40, //number

        /**
         * Shows now border, background etc.
         *
         * @var {Boolean}
         */
        transparent: false,

        /**
         * @TODO
         *
         * @var {Boolean}
         */
        combobox: false,

        /**
         * @var {Boolean}
         */
        disabled: false,

        /**
         * Tries to select the first entry.
         *
         * @var {Boolean}
         */
        selectFirst: true,

        /**
         * Selects the first entry if the value is null.
         *
         * @var {Boolean}
         */
        selectFirstOnNull: true
    },

    initialize: function(pContainer, pOptions) {
        this.setOptions(pOptions);
        this.container = pContainer;

        this.createLayout();
        this.mapEvents();
        this.prepareOptions();

        if (this.options.selectFirst) {
            this.selectFirst(null, true);
        }

        if (this.options.disabled)
            this.setEnabled(false);

        if (this.options.transparent) {
            this.box.addClass('ka-Select-transparent');
        }

        this.fireEvent('ready');
    },

    createLayout: function() {
        this.box = new Element('a', {
            href: 'javascript: ;',
            'class': 'ka-normalize ka-Select-box ka-Select-box-active'
        }).addEvent('click', this.toggle.bind(this));

        this.box.instance = this;

        this.title = new Element('div', {
            'class': 'ka-Select-box-title'
        }).addEvent('mousedown', function(e) {
                e.preventDefault();
            }).inject(this.box);

        this.arrowBox = new Element('div', {
            'class': 'ka-Select-arrow icon-arrow-17'
        }).inject(this.box);

        this.chooser = new Element('div', {
            'class': 'ka-Select-chooser ka-normalize'
        });

        if (this.container) {
            this.box.inject(this.container);
        }
    },

    mapEvents: function() {
        this.box.addEvent('keydown', this.actions);
        this.box.addEvent('keyup', this.search);
        this.box.addEvent('focus', this.focus);
        this.box.addEvent('blur', function() {
            this.blur.delay(50, this);
        }.bind(this));

        this.chooser.addEvent('mousedown', function() {
            this.blockNextBlur = true;
        }.bind(this));

        this.chooser.addEvent('click', function(e) {
            if (!e || !(item = e.target)) {
                return;
            }
            if (!item.hasClass('ka-select-chooser-item') && !(item = item.getParent('.ka-select-chooser-item'))) {
                return;
            }

            this.chooseItem(item.kaSelectId, true);
            this.close(true);

        }.bind(this));

        this.chooser.addEvent('scroll', this.checkScroll);
    },

    prepareOptions: function() {
        if (this.options.items) {
            if (typeOf(this.options.items) == 'object') {
                Object.each(this.options.items, function(label, id) {
                    this.items.push({id: id, label: label});
                }.bind(this));
            }

            if (typeOf(this.options.items) == 'array') {
                if (this.options.itemsKey) {
                    if (this.options.itemsLabel) {
                        Array.each(this.options.items, function(item) {
                            this.items.push({id: item[this.options.itemsKey], label: item[this.options.itemsLabel]});
                        }.bind(this));
                    } else {
                        Array.each(this.options.items, function(item) {
                            this.items.push({id: item[this.options.itemsKey], label: item});
                        }.bind(this));
                    }
                } else {
                    Array.each(this.options.items, function(label, idx) {
                        this.items.push({id: idx, label: label});
                    }.bind(this));
                }
            }
        } else if (this.options.objectItems) {
            Array.each(this.options.objectItems, function(obj) {
                Object.each(obj, function(label, idx) {
                    this.items.push({id: idx, label: label});
                }.bind(this));
            }.bind(this));
        } else if (this.options.object) {
            this.options.object = ka.normalizeObjectKey(this.options.object);
            this.objectDefinition = ka.getObjectDefinition(this.options.object);

            var fields = [];
            if (this.options.objectFields) {
                fields = this.options.objectFields;
            } else if (this.options.objectLabel) {
                fields.push(this.options.objectLabel);
            }

            if (typeOf(fields) == 'string') {
                fields = fields.replace(/[^a-zA-Z0-9_]/g, '').split(',');
            }
            this.objectFields = fields;

        }
    },

    focus: function() {
    },

    blur: function() {
        if (this.blockNextBlur) {
            return this.blockNextBlur = false;
        }
        this.close();
    },

    loadObjectItems: function(pOffset, pCallback, pCount) {
        if (!pCount) {
            pCount = this.options.maxItemsPerLoad;
        }

        if (this.lastRq) {
            this.lastRq.cancel();
        }

        if (this.options.store) {
            var storePath = this.options.store;

            this.lastRq = new Request.JSON({url: _pathAdmin + storePath,
                noErrorReporting: ['NoAccessException'],
                onCancel: function() {
                    pCallback(false);
                },
                onComplete: function(response) {
                    if (response.error) {
                        //todo, handle error
                        return false;
                    } else {

                        var items = [];

                        if (null !== response.data) {
                            Object.each(response.data, function(item, id) {

                                items.push({
                                    id: id,
                                    label: item
                                });

                                this.cachedObjectItems[id] = item;

                            }.bind(this));
                        }

                        pCallback(items);
                    }
                }.bind(this)
            }).get({
                    //object: this.options.object,
                    offset: pOffset,
                    limit: pCount,
                    _lang: this.options.objectLanguage,
                    fields: this.objectFields ? this.objectFields.join(',') : null
                });

        }
        if (this.options.object) {

            this.lastRq = new Request.JSON({url: this.getObjectUrl(),
                noErrorReporting: ['NoAccessException'],
                onCancel: function() {
                    pCallback(false);
                },
                onComplete: function(response) {

                    if (response.error) {
                        //todo, handle error
                        return false;
                    } else {

                        var items = [];

                        if (null !== response.data) {
                            Array.each(response.data, function(item) {

                                var id = ka.getObjectUrlId(this.options.object, item);

                                if (this.hideOptions && this.hideOptions.contains(id)) {
                                    return;
                                }

                                items.push({
                                    id: id,
                                    label: item
                                });

                                this.cachedObjectItems[id] = item;

                            }.bind(this));
                        }

                        pCallback(items);
                    }
                }.bind(this)
            }).get({
                    //object: this.options.object,
                    offset: pOffset,
                    limit: pCount,
                    _lang: this.options.objectLanguage,
                    scope: this.options.objectScope,
                    fields: this.objectFields ? this.objectFields.join(',') : null
                });
        }
    },

    getObjectUrl: function() {
        var uri = _pathAdmin + 'admin/object/' + ka.urlEncode(this.options.object);

        if (this.options.objectBranch) {
            if (this.options.objectBranch === true) {
                uri += '/:branch';
            } else {
                uri += '/' + ka.urlEncode(this.options.objectBranch) + '/branch';
            }
        }

        return uri;
    },

    reset: function() {
        this.chooser.empty();
        this.maximumItemsReached = false;

        this.loaded = 0;
        this.currentItems = {};

        if (this.lastRq) {
            this.lastRq.cancel();
        }
    },

    checkScroll: function() {
        if (this.maximumItemsReached) {
            return;
        }
        if (this.whileFetching) {
            return;
        }

        var scrollPos = this.chooser.getScroll();
        var scrollMax = this.chooser.getScrollSize();
        var maxY = scrollMax.y - this.chooser.getSize().y;

        if (scrollPos.y + 10 < maxY) {
            return;
        }

        this.loadItems();
    },

    actions: function(pEvent) {
        if (pEvent.key == 'esc') {
            this.searchValue = '';
            this.close(true);
            pEvent.stopPropagation();
            return;
        }

        if (pEvent.key == 'enter' || pEvent.key == 'space' || pEvent.key == 'down' || pEvent.key == 'up') {
            var current = this.chooser.getElement('.ka-select-chooser-item-active');

            if (['down', 'up'].contains(pEvent.key)) {
                pEvent.stop();
            }

            if (pEvent.key == 'enter' || (this.searchValue.trim() == '' && pEvent.key == 'space')) {

                if (this.isOpen()) {
                    this.close(true);
                    if (current) {
                        this.chooseItem(current.kaSelectId, true);
                    }
                } else {
                    this.blockNextSearch = true;
                    this.open();
                }
                return;
            }

            if (pEvent.key == 'down') {
                if (!current) {
                    var first = this.chooser.getElement('.ka-select-chooser-item');
                    if (first) {
                        first.addClass('ka-select-chooser-item-active');
                    }
                } else {
                    current.removeClass('ka-select-chooser-item-active');
                    var next = current.getNext();
                    if (next) {
                        next.addClass('ka-select-chooser-item-active');
                    } else {
                        var first = this.chooser.getElement('.ka-select-chooser-item');
                        if (first) {
                            first.addClass('ka-select-chooser-item-active');
                        }
                    }
                }
            }

            if (pEvent.key == 'up') {
                if (!current) {
                    var last = this.chooser.getLast('.ka-select-chooser-item');
                    if (last) {
                        last.addClass('ka-select-chooser-item-active');
                    }
                } else {
                    current.removeClass('ka-select-chooser-item-active');
                    var previous = current.getPrevious();
                    if (previous) {
                        previous.addClass('ka-select-chooser-item-active');
                    } else {
                        var last = this.chooser.getLast('.ka-select-chooser-item');
                        if (last) {
                            last.addClass('ka-select-chooser-item-active');
                        }
                    }
                }
            }

            current = this.chooser.getElement('.ka-select-chooser-item-active');

            if (current) {
                var position = current.getPosition(this.chooser);
                var height = +current.getSize().y;

                if (position.y + height > this.chooser.getSize().y) {
                    this.chooser.scrollTo(this.chooser.getScroll().x, this.chooser.getScroll().y + (position.y - this.chooser.getSize().y) + height);
                }

                if (position.y < 0) {
                    this.chooser.scrollTo(this.chooser.getScroll().x, this.chooser.getScroll().y + (position.y));
                }
            }

            return;
        }
    },

    search: function(pEvent) {
        if (this.blockNextSearch) {
            return this.blockNextSearch = false;
        }

        if (['down', 'up', 'enter', 'tab'].contains(pEvent.key)) {
            return;
        }

        if ('backspace' === pEvent.key) {
            if ('' !== this.searchValue) {
                this.searchValue = this.searchValue.substr(0, this.searchValue.length - 1);
            }
        } else if (1 === pEvent.key.length) {
            this.searchValue += pEvent.key;
        }

        if (this.searchValue.trim() && !this.isOpen()) {
            this.open(true);
        }

        this.reset();
        this.loadItems();
    },

    loadItems: function() {
        if (this.lrct) {
            clearTimeout(this.lrct);
        }

        this.lrct = this._loadItems.delay(1, this);
    },

    _loadItems: function() {
        if (!this.box.hasClass('ka-Select-box-open')) {
            return false;
        }

        //this.chooser.empty();
        if (this.maximumItemsReached) {
            return this.displayChooser();
        }

        if (this.whileFetching) {
            return false;
        }

        this.whileFetching = true;

        //show small loader
        if (this.searchValue.trim()) {
            if (!this.title.inSearchMode) {
                this.backupedTitle = this.title.get('html');
            }

            this.title.set('text', this.searchValue);
            this.title.setStyle('color', 'gray');
            this.title.inSearchMode = true;

        } else if (this.backupedTitle !== false) {
            this.title.set('html', this.backupedTitle);
            this.title.setStyle('color');
            this.backupedTitle = false;
            this.title.inSearchMode = false;
        }

        this.lastLoader = new Element('a', {
            'text': t('Still loading ...'),
            style: 'display: none;'
        }).inject(this.chooser);

        this.lastLoaderGif = new Element('img');

        this.lastLoader.loaderId = this.loaderId++;

        var loaderId = this.lastLoader.loaderId;

        (function() {
            if (this.lastLoader && this.lastLoader.loaderId == loaderId) {
                this.lastLoader.setStyle('display', 'block');
                this.displayChooser();
            }
        }).delay(1000, this);

        this.dataProxy(this.loaded, function(pItems) {

            if (typeOf(pItems) == 'array') {

                Array.each(pItems, this.addItemToChooser);

                this.loaded += pItems.length;

                if (!pItems.length)//no items left
                {
                    this.maximumItemsReached = true;
                }
            }

            this.displayChooser();

            this.lastLoader.destroy();
            delete this.lastLoader;

            this.whileFetching = false;
            this.checkScroll();

        }.bind(this));

    },

    addItemToChooser: function(pItem) {
        var a;

        if (pItem.isSplit) {
            a = new Element('div', {
                html: pItem.label,
                'class': 'group'
            }).inject(this.chooser);

        } else {
            a = new Element('a', {
                'class': 'ka-select-chooser-item',
                html: this.renderLabel(pItem.label)
            });

            if (this.searchValue.trim()) {

                var regex = new RegExp('(' + ka.pregQuote(this.searchValue.trim()) + ')', 'gi');
                var match = a.get('text').match(regex);
                if (match) {
                    a.set('html', a.get('html').replace(regex, '<b>$1</b>'));
                } else {
                    a.destroy();
                    return false;
                }
            }

            a.inject(this.chooser);

            this.checkIfCurrentValue(pItem, a);

            a.kaSelectId = pItem.id;
            a.kaSelectItem = pItem;
            this.currentItems[pItem.id] = a;
        }

    },

    checkIfCurrentValue: function(pItem, pA) {
        if (pItem.id == this.value) {
            pA.addClass('icon-checkmark-6');
            pA.addClass('ka-select-chooser-item-selected');
        }
    },

    renderLabel: function(pData) {
        if (typeOf(pData) == 'null') {
            return '';
        }

        var data = pData;

        if (this.options.object && !this.options.labelTemplate) {
            //just return ka.getObjectLabel
            return ka.getObjectLabelByItem(this.options.object, data);
        }

        if (typeOf(data) == 'string') {
            data = {label: data};
        } else if (typeOf(data) == 'array') {
            //image
            data = {label: data[0], kaSelectImage: data[1]};
        }

        var template = this.labelTemplate;

        if (this.options.object && this.objectDefinition.labelTemplate) {
            template = this.objectDefinition.labelTemplate;
        }

        if (this.options.labelTemplate) {
            template = this.options.labelTemplate;
        }

        if (template == this.labelTemplate && this.options.object && this.objectFields.length > 0) {
            //we have no custom layout, but objectFields
            var label = [];
            Array.each(this.objectFields, function(field) {
                label.push(pData[field]);
            });
            data.label = label.join(', ');
        }

        if (!data.kaSelectImage) {
            data.kaSelectImage = '';
        }

        if (typeOf(data.label) == 'null') {
            data.label = '';
        }

        return mowla.fetch(template, data);
    },

    selectFirst: function(pOffset, pInternal) {
        this.duringFirstSelectLoading = true;

        if (!pOffset) {
            pOffset = 0;
        }

        this.dataProxy(pOffset, function(items) {
            this.duringFirstSelectLoading = false;

            if (items && items.length > 0) {
                var i = 0;
                for (i = 0; i < items.length; i++) {
                    var item = items[i];
                    if (item && !item.isSplit) {
                        if (null === this.value) {
                            this.chooseItem(item.id, pInternal);
                        }
                        this.fireEvent('firstItemLoaded', item.id);
                        this.fireEvent('selectFirst', item.id);
                        return;
                    }
                }
            }
        }.bind(this), pOffset + 5);
    },

    /**
     * Returns always max this.options.maxItemsPerLoad (20 default) items.
     *
     * @param {Integer}  pOffset
     * @param {Function} pCallback
     */
    dataProxy: function(pOffset, pCallback, pCount) {

        if (!pCount) {
            pCount = this.options.maxItemsPerLoad;
        }

        if (this.items.length > 0) {
            //we have static items
            var items = [];
            var i = pOffset - 1;

            while (++i >= 0) {

                if (i >= this.items.length) {
                    break;
                }
                if (items.length == pCount) {
                    break;
                }
                if (this.options.objectLanguage && this.items[i].lang != this.options.objectLanguage) {
                    continue;
                }

                if (this.hideOptions && this.hideOptions.contains(this.items[i].id)) {
                    continue;
                }

                items.push(this.items[i]);
            }

            pCallback(items);
        } else if (this.options.object || this.options.store) {
            //we have object items
            this.loadObjectItems(pOffset, pCallback, pCount);
        } else {
            pCallback(false);
        }

    },

    setEnabled: function(pEnabled) {
        if (this.enabled === pEnabled) {
            return;
        }

        this.enabled = pEnabled;

        if (this.enabled) {
            //add back all events
            if (this.$eventsBackuper) {
                this.box.cloneEvents(this.$eventsBackuper);
            }

            this.box.removeClass('ka-Select-disabled');
            delete this.$eventsBackuper;

        } else {
            this.$eventsBackuper = new Element('span');
            //backup all events and remove'm.
            this.$eventsBackuper.cloneEvents(this.box);
            this.box.removeEvents();

            this.box.addClass('ka-Select-disabled');
        }
    },

    inject: function(p, p2) {
        this.box.inject(p, p2);

        return this;
    },

    destroy: function() {
        this.chooser.destroy();
        this.box.destroy();
        this.chooser = null;
        this.box = null;
    },

    remove: function(pId) {
        var removed = null;
        Array.each(this.items, function(item) {
            if (null !== removed) return;

            if (item.id === pId) {
                var pos = this.items.indexOf(item);
                this.items.splice(pos, 1);
                removed = item.id;
                return false;
            }
        }.bind(this));

        if (removed === this.value) {
            this.selectFirst();
        }
    },

    addSplit: function(pLabel) {
        this.items.push({
            label: pLabel,
            isSplit: true
        });

        this.loadItems();
    },

    showOption: function(pId) {
        if (!this.hideOptions) {
            this.hideOptions = [];
        }
        this.hideOptions.push(pId);
    },

    hideOption: function(pId) {
        if (!this.hideOptions) {
            return;
        }
        var idx = this.hideOptions.indexOf(pId);
        if (idx === -1) {
            return;
        }
        this.hideOptions.splice(idx, 1);
    },

    addImage: function(pId, pLabel, pImage, pPos) {
        return this.add(pId, [pLabel, pImage], pPos);
    },

    /**
     * Adds a item to the static list.
     *
     * @param {String} pId
     * @param {Mixed}  pLabel String or array ['label', 'imageSrcOr#Class']
     * @param {int}    pPos   Starts with 0
     */
    add: function(pId, pLabel, pPos) {
        if (pPos == 'top') {
            this.items.splice(0, 1, {id: pId, label: pLabel});
        } else if (pPos > 0) {
            this.items.splice(pPos, 1, {id: pId, label: pLabel});
        } else {
            this.items.push({id: pId, label: pLabel});
        }

        if (typeOf(this.value) == 'null' && this.options.selectFirst) {
            this.chooseItem(pId);
        }

        return this.loadItems();
    },

    setLabel: function(pId, pLabel) {

        var i = 0, max = this.items.length;
        do {
            if (this.items[i].id == pId) {
                this.items[i].label = pLabel;
                break;
            }
        } while (++i && i < max);

        if (this.value == pId) {
            this.title.set('html', pLabel);
            this.chooseItem(pId);
        }

    },

    setStyle: function(p, p2) {
        this.box.setStyle(p, p2);
        return this;
    },

    empty: function() {
        this.items = [];
        this.value = null;
        this.title.set('html', '');
        this.chooser.empty();
    },

    getLabel: function(pId, pCallback) {

        var data;
        if (this.items.length > 0) {
            //search for i
            for (var i = this.items.length - 1; i >= 0; i--) {
                if (pId == this.items[i].id) {
                    data = this.items[i];
                    break;
                }
            }
            pCallback(data);
        } else if (this.options.object || this.options.store) {
            //maybe in objectcache?
            if (this.cachedObjectItems[pId]) {
                item = this.cachedObjectItems[pId];
                pCallback({
                    id: pId,
                    label: item
                });
            } else {
                //we need a request
                if (this.lastLabelRequest) {
                    this.lastLabelRequest.cancel();
                }

                if (this.options.store) {

                    this.lastLabelRequest = new Request.JSON({
                        url: _pathAdmin + this.options.store + '/' + ka.urlEncode(pId),
                        onComplete: function(response) {

                            if (!response.error) {

                                if (response.data === false) {
                                    return pCallback(false);
                                }

                                pCallback({
                                    id: pId,
                                    label: response.data
                                });
                            }
                        }.bind(this)
                    }).get({
                            fields: this.objectFields.join(',')
                        });

                } else if (this.options.object) {
                    this.lastLabelRequest = new Request.JSON({
                        url: _pathAdmin + 'admin/object/' + ka.urlEncode(this.options.object) + '/' + ka.urlEncode(pId),
                        onComplete: function(response) {

                            if (!response.error) {

                                if (!response.data) {
                                    return pCallback(false);
                                }

                                var id = ka.getObjectUrlId(this.options.object, response.data);
                                pCallback({
                                    id: id,
                                    label: response.data
                                });
                            }
                        }.bind(this)
                    }).get({
                            fields: this.objectFields.join(',')
                        });
                }
            }
        }
    },

    chooseItem: function(pValue, pInternal) {
        this.setValue(pValue, pInternal);
    },

    setValue: function(pValue, pInternal) {
        this.value = pValue;

        if (this.options.object && typeOf(this.value) == 'object') {
            this.value = ka.getObjectUrlId(this.options.object, this.value);
        }

        if (typeOf(this.value) == 'null' || null === this.value) {
            if (!this.options.selectFirstOnNull) {
                return this.title.set('text', '');
            } else {
                return this.selectFirst(null, pInternal);
            }
        }

        this.getLabel(this.value, function(item) {
            if (typeOf(item) != 'null' && item !== false) {
                this.title.set('html', this.renderLabel(item.label));
            } else {
                this.title.set('text', '');
            }
        }.bind(this));

        if (pInternal) {
            this.fireChange();
        }

    },

    fireChange: function() {
        this.fireEvent('change', this.getValue());
    },

    getValue: function() {
        if (this.options.object) {
            return ka.getObjectId(this.options.object + '/' + this.value);
        }

        return this.value;
    },

    toggle: function() {
        if (this.chooser.getParent()) {
            this.close(true);
        } else {
            this.open();
        }
    },

    close: function(pInternal) {
        this.chooser.dispose();
        this.box.removeClass('ka-Select-box-open');
        this.reset();

        if (this.backupedTitle !== false) {
            this.title.set('html', this.backupedTitle);
            this.backupedTitle = false;
        }

        if (this.lastOverlay) {
            this.lastOverlay.close();
            delete this.lastOverlay;
        }

        this.title.setStyle('color');
        this.title.inSearchMode = false;
    },

    isOpen: function() {
        return this.box.hasClass('ka-Select-box-open');
    },

    open: function(pWithoutLoad) {
        if (!this.enabled) {
            return;
        }

        if (this.box.getParent('.kwindow-win-titleGroups')) {
            this.chooser.addClass('ka-Select-darker');
        } else {
            this.chooser.removeClass('ka-Select-darker');
        }

        this.box.addClass('ka-Select-box-open');

        this.searchValue = '';

        if (this.lastRq) {
            this.lastRq.cancel();
        }

        if (pWithoutLoad !== true) {
            this.loadItems();
        }
    },

    displayChooser: function() {
        if (!this.lastOverlay) {
            this.lastOverlay = ka.openDialog({
                element: this.chooser,
                target: this.box,
                onClose: function() {
                    this.close(true);
                }.bind(this),
                offset: {y: -1}
            });
        } else {
            this.lastOverlay.updatePosition();
        }

        if (this.borderLine) {
            this.borderLine.destroy();
        }

        this.box.removeClass('ka-Select-withBorderLine');

        var csize = this.chooser.getSize();
        var bsize = this.box.getSize();

        if (bsize.x < csize.x) {

            var diff = csize.x - bsize.x;

            this.borderLine = new Element('div', {
                'class': 'ka-Select-borderline',
                styles: {
                    width: diff
                }
            }).inject(this.chooser);

            this.box.addClass('ka-Select-withBorderLine');
        } else if (bsize.x - csize.x < 4 && bsize.x - csize.x >= 0) {
            this.box.addClass('ka-Select-withBorderLine');
        }

        if (window.getSize().y < csize.y) {
            this.chooser.setStyle('height', window.getSize().y);
        }

    },

    toElement: function() {
        return this.box;
    }

});
