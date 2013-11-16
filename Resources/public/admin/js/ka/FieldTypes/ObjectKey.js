ka.FieldTypes.ObjectKey = new Class({

    Extends: ka.FieldTypes.Select,

    Statics: {
        label: 'Object Key',
        asModel: true
    },

    options: {
        combobox: true
    },

    createLayout: function () {
        this.parent();

        Object.each(ka.settings.configs, function (config, extensionKey) {
            if (config.objects) {
                extensionKey = extensionKey.charAt(0).toUpperCase() + extensionKey.substr(1);

                this.select.addSplit(config.label || extensionKey);

                Object.each(config.objects, function (object, object_key) {
                    object_key = object_key.charAt(0).toUpperCase() + object_key.substr(1);
                    this.select.add(
                        ka.normalizeObjectKey(extensionKey + ':' + object_key),
                        (object.label || object_key) + " (" + extensionKey + ':' + object_key + ")"
                    );
                }.bind(this));
            }
        }.bind(this));

        if (this.select.options.selectFirst) {
            this.select.selectFirst();
        }
    },

    setValue: function(value) {
        this.parent(value ? ka.normalizeObjectKey(value) : value);
    }
});