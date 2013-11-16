ka.FieldTypes.Container = new Class({
    Extends: ka.FieldAbstract,

    createLayout: function (container) {
        //deactivate auto-hiding of the childrenContainer.
        this.fieldInstance.handleChildsMySelf = true;

        this.fieldInstance.prepareChildContainer = function() {
            this.fieldInstance.childContainer = new Element('div', {
                'class': 'ka-field-container'
            }).inject(container);
        }.bind(this);
    }
});