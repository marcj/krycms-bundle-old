ka.FieldTypes.Tab = new Class({
    Extends: ka.FieldAbstract,

    Statics: {
        options: {
            fullPage: {
                label: t('Full page'),
                type: 'checkbox'
            }
        }
    },

    options: {
        fullPage: false
    },

    createLayout: function () {
        //this.tab = new ka.TabPane(this.fieldInstance.fieldPanel, this.options.fullPage);

        //FieldForm does the magic already.
        //Maybe we should move that part into this.
    }

});