ka.FieldTypes.Files = new Class({

    Extends: ka.FieldTypes.Select,

    Statics: {
        asModel: true
    },

    initialize: function (pFieldInstance, pOptions) {

        pOptions.object = 'KrynCmsBundle:File';
        pOptions.objectBranch = pOptions.directory;
        pOptions.objectLabel = 'name';
        pOptions.labelTemplate = '{name}';

        this.parent(pFieldInstance, pOptions);
    }

});
