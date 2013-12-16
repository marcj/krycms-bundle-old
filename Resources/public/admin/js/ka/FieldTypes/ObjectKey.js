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

        Object.each(ka.settings.configs, function (config, bundleName) {
            if (config.objects) {
                this.select.addSplit(config.label || bundleName);

                bundleName = ka.getShortBundleName(bundleName);

                Object.each(config.objects, function (object, objectName) {
                    objectName = objectName.lcfirst();
                    this.select.add(
                        bundleName + '/' + objectName,
                        (object.label || objectName) + " (" + bundleName + '/' + objectName + ")"
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