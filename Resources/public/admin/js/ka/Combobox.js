ka.Combobox = new Class({

    Extends: ka.Select,

    createLayout: function () {

        this.parent();

    },

    getValue: function () {
        return this.value || this.input.value;
    }

});