ka.FieldTypes.Lang = new Class({

    Extends: ka.FieldTypes.Select,

    Statics: {
        asModel: true
    },

    createLayout: function () {
        this.parent();

        var hasSessionLang = false;
        Object.each(ka.settings.langs, function (lang, id) {

            this.select.add(id, lang.langtitle + ' (' + lang.title + ', ' + id + ')');
            if (id == window._session.lang) {
                hasSessionLang = true;
            }

        }.bind(this));

        if (hasSessionLang) {
            this.select.setValue(window._session.lang);
        }

        if (this.select.options.selectFirst) {
            this.select.selectFirst();
        }
    }

});