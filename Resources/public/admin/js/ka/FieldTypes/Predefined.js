ka.FieldTypes.Predefined = new Class({
    Extends: ka.FieldAbstract,

    Statics: {
        options: {
            object: {
                label: t('Object key'),
                type: 'objectKey',
                required: true
            },
            field: {
                label: t('Field key'),
                type: 'text',
                required: true
            }
        }
    },

    createLayout: function () {
        //ka.Field makes the magic
    }

});