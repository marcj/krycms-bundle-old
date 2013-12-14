ka.ContentTypes = ka.ContentTypes || {};

ka.ContentTypes.Html = new Class({

    Extends: ka.ContentAbstract,
    Binds: ['applyValue'],

    Statics: {
        icon: 'icon-html5',
        label: 'HTML'
    },

    options: {

    },

    createLayout: function() {
        this.main = new Element('div', {
            'class': 'ka-normalize ka-content-plugin'
        }).inject(this.getContentInstance().getContentContainer());

        this.iconDiv = new Element('div', {
            'class': 'ka-content-inner-icon icon-html5'
        }).inject(this.main);

        this.inner = new Element('div', {
            'class': 'ka-content-inner ka-normalize',
            text: 'HTML'
        }).inject(this.main);
    },

    selected: function(inspectorContainer) {
        var toolbarContainer = new Element('div', {
            'class': 'ka-content-html-toolbarContainer'
        }).inject(inspectorContainer);

        this.openDialogBtn = new ka.Button(t('Edit HTML')).setButtonStyle('blue').inject(toolbarContainer);

        this.openDialogBtn.addEvent('click', this.openDialog.bind(this));
    },

    openDialog: function() {
        var dialog = new ka.Dialog(this.getWin(), {
            autoDisplay: true,
            withButtons: true,
            minWidth: '80%',
            mode: 'html',
            minHeight: '80%'
        });

        this.input = new ka.Field({
            noWrapper: true,
            type: 'codemirror',
            onChange: function(value) {
                this.value = value;
            }.bind(this)
        }, dialog.getContentContainer());

        this.input.setValue(this.value);

        dialog.addEvent('apply', function() {
            this.value = this.input.getValue();
            delete this.input;
        }.bind(this));
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
