ka.FieldTypes.Page = new Class({

    Extends: ka.FieldTypes.Object,

    Statics: {
        asModel: true
    },

    initialize: function (pFieldInstance, pOptions) {
        pOptions.objects = ['KrynCmsBundle:node'];

        this.parent(pFieldInstance, pOptions);
    }

});
