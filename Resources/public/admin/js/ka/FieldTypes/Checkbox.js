ka.FieldTypes.Checkbox = new Class({

    Extends: ka.FieldAbstract,

    Statics: {
        asModel: true
    },

    createLayout: function () {
        this.checkbox = new ka.Checkbox(this.fieldInstance.fieldPanel);

        this.checkbox.addEvent('change', this.fireChange);
    },

    setValue: function (pValue) {
        this.checkbox.setValue(pValue);
    },

    getValue: function () {
        return this.checkbox.getValue();
    }
});