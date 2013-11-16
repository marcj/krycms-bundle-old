ka.FieldTypes.Page = new Class({

    Extends: ka.FieldTypes.Object,

    Statics: {
        asModel: true
    },

    initialize: function (pFieldInstance, pOptions) {
        pOptions.objects = ['core:node'];

        this.parent(pFieldInstance, pOptions);
    }

});
