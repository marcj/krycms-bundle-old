ka.LabelTypes.Select = new Class({
    Extends: ka.LabelAbstract,

    render: function(values) {
        this.main = new Element('span');

        var options = Object.clone(this.options);
        options.noWrapper = true;
        options.type = 'select';
        options.transparent = true;
        options.disabled = true;

        this.field = new ka.Field(options, this.main);

        var value = values[this.fieldId + '_' + this.definition.tableLabel] || values[this.fieldId + '__label'] || values[this.fieldId];
        this.field.setValue(value);

        document.id(this.field.getFieldObject()).removeClass('ka-Select-disabled');
        document.id(this.field.getFieldObject()).getChildren('.ka-Select-arrow').destroy();

        return this.main;
    }
});