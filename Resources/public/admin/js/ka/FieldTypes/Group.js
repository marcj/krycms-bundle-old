ka.FieldTypes.Group = new Class({

    Extends: ka.FieldAbstract,

    createLayout: function () {
        if (this.fieldInstance.title) {
            this.fieldInstance.title.addClass('ka-Field-group-title');
        }

        this.fieldInstance.childContainer = new Element('div', {
            'class': 'ka-Field-group'
        }).inject(this.fieldInstance.toElement());
    }
});