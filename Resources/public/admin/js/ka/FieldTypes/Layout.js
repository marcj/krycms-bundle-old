ka.FieldTypes.Layout = new Class({

    Extends: ka.FieldTypes.Select,

    Statics: {
        label: 'Page Layout',
        asModel: true
    },

    createLayout: function () {
        this.parent();

        Object.each(ka.settings.configs, function(config, key) {
            if (config.themes) {
                Object.each(config.themes, function(theme){
                    var layouts = {};
                    if (theme.layouts) {
                        Array.each(theme.layouts, function(layout){
                            layouts[layout.file] = layout.label;
                        });
                    }

                    if (Object.getLength(layouts) > 0) {
                        this.select.addSplit(theme.label);
                        Object.each(layouts, function(label, id) {
                            id = id.replace(/@([^\/]*)/, '@$1.'+theme.id);
                            this.select.add(id, label);
                        }.bind(this))
                    }
                }.bind(this))
            }
        }.bind(this));

        if (this.select.options.selectFirst) {
            this.select.selectFirst();
        }
    }
});