ka.FieldTypes.Lang = new Class({

    Extends: ka.FieldTypes.Select,

    Statics: {
        asModel: true
    },

    initialize: function (fieldInstance, options) {
        options.object = 'kryncms/language';
        this.parent(fieldInstance, options);

        var hasSessionLang = false;
        Object.each(ka.settings.langs, function (lang, id) {

            if (id == window._session.lang) {
                hasSessionLang = true;
            }

        }.bind(this));

        if (hasSessionLang) {
            this.select.setValue(window._session.lang);
        }
    }

});