ka.ContentTypes = ka.ContentTypes || {};

ka.ContentTypes.Plugin = new Class({

    Extends: ka.ContentAbstract,

    Statics: {
        icon: 'icon-cube-2',
        label: 'Plugin'
    },

    options: {

    },

    createLayout: function () {
        this.main = new Element('div', {
            'class': 'ka-normalize ka-content-plugin'
        }).inject(this.getContentInstance());

        this.iconDiv = new Element('div', {
            'class': 'ka-content-inner-icon icon-cube-2'
        }).inject(this.main);

        this.inner = new Element('div', {
            'class': 'ka-content-inner ka-normalize'
        }).inject(this.main);

    },

    /**
     * since old kryn version stores the value as string
     * we need to convert it to the new object def.
     * @param {String|Object} pValue
     * @return {Object}
     */
    normalizeValue: function (pValue) {
        if (typeOf(pValue) == 'object') {
            var bundle = pValue.bundle || pValue.module || '';

            bundle = bundle.toLowerCase();
            if ('bundle' === bundle.substr(-6)) {
                bundle = bundle.substr(0, bundle.length - 6);
            }

            pValue.bundle = bundle;
            return pValue;
        }

        if (typeOf(pValue) == 'string' && JSON.validate(pValue)) {
            return this.normalizeValue(JSON.decode(pValue));
        }
        if (typeOf(pValue) != 'string') {
            return {};
        }

        var bundle = pValue.substr(0, pValue.indexOf('::'));
        var plugin = pValue.substr(bundle.length + 2, pValue.substr(bundle.length + 2).indexOf('::'));
        var options = pValue.substr(bundle.length + plugin.length + 4);

        options = JSON.validate(options) ? JSON.decode(options) : {};

        return this.normalizeValue({
            bundle: bundle,
            plugin: plugin,
            options: options
        });
    },

    renderValue: function () {
        this.inner.empty();

        var bundle = this.value.bundle;
        var plugin = this.value.plugin;
        var options = this.value.options;

        if (ka.getConfig(bundle) &&ka.getConfig(bundle).plugins &&
            ka.getConfig(bundle).plugins[plugin]) {
            var pluginConfig = ka.getConfig(bundle).plugins[plugin];

            new Element('div', {
                'class': 'ka-content-inner-title',
                text: ka.getConfig(bundle).label || ka.getConfig(bundle).name
            }).inject(this.inner);

            new Element('div', {
                'class': 'ka-content-inner-subtitle',
                text: pluginConfig.label
            }).inject(this.inner);

        } else {
            if (!ka.getConfig(bundle)) {
                this.inner.set('text', tf('Bundle `%s` not found', bundle));
            } else if (!ka.getConfig(bundle).plugins || ka.getConfig(bundle).plugins[plugin]) {
                this.inner.set('text', tf('Plugin `%s` in bundle `%s` not found', plugin, bundle));
            }
        }

    },

    /**
     * adds/loads all additional fields to the inspector.
     */
    selected: function(inspectorContainer) {
        var toolbarContainer = new Element('div', {
            'class': 'ka-content-plugin-toolbarContainer'
        }).inject(inspectorContainer);

        this.pluginChoser = new ka.Field({
            type: 'plugin',
            noWrapper: true
        }, toolbarContainer);

        this.pluginChoser.setValue(this.value);

        this.pluginChoser.addEvent('change', function () {
            this.value = this.pluginChoser.getValue();
            this.value = this.normalizeValue(this.value);

            this.renderValue();
            this.contentInstance.fireChange();
        }.bind(this));
    },

    setValue: function (pValue) {
        if (!pValue) {
            this.value = null;
            return;
        }
        this.value = this.normalizeValue(pValue);
        this.renderValue();
    },

    getValue: function () {
        return typeOf(this.value) == 'string' ? this.value : JSON.encode(this.value);
    }

});
