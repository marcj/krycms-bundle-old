ka.ContentTypes = ka.ContentTypes || {};

ka.ContentTypes.Markdown = new Class({

    Extends: ka.ContentAbstract,
    Binds: ['applyValue'],

    Statics: {
        icon: 'icon-hash-2',
        label: 'Markdown'
    },

    options: {

    },

    createLayout: function() {
        this.main = new Element('div', {
            'class': 'ka-normalize ka-content-markdown'
        }).inject(this.getContentInstance());

        this.input = new ka.Field({
            type: 'textarea',
            inputHeight: 'auto',
            noWrapper: true,
            onChange: function(value) {
                this.value = value;
            }.bind(this)
        }, this.main);
    },

    selected: function(inspectorContainer) {
        var toolbarContainer = new Element('div', {
            'class': 'ka-content-markdown-toolbarContainer'
        }).inject(inspectorContainer);
    },

    setValue: function(pValue) {
        this.value = pValue;
        if (this.input) {
            this.input.setValue(pValue);
        }
    },

    getValue: function() {
        return this.value;
    }
});
