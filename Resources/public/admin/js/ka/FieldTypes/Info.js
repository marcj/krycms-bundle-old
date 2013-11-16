ka.FieldTypes.Info = new Class({

    Extends: ka.FieldAbstract,

    container: null,

    Statics: {
        options: {
            safeInfo: {
                label: 'Safe info',
                desc: t('Sets the value as a `text` not as `html` if true'),
                'default': true,
                type: 'checkbox'
            }
        }
    },

    createLayout: function () {
        this.infoLabel = new Element('div', {
            'class': 'ka-Field-info'
        }).inject(this.getContainer());
    },

    setValue: function (pValue) {
        this.infoLabel.empty();
        if (!['string', 'number'].contains(typeOf(pValue))) {
            return;
        }

        this.infoLabel.set(this.options.safeInfo ? 'text' : 'html', pValue);
    },

    getValue: function () {
        return this.infoLabel.get(this.options.safeInfo ? 'text' : 'html');
    }
});
