ka.ObjectAttributeTable = new Class({
    Extends: ka.FieldTable,

    _createLayout: function () {
        this.parent();

        new Element('th', {text: 'Target object'}).inject(this.headerTr, 'top');
        this.thKey.set('text', t('Attribute key'));
        this.thType.set('text', t('Attribute type'));
    },

    getValue: function () {
        var result = [];
        this.table.getChildren('tr.ka-fieldProperty-item').each(function (item) {

            var fieldProperty = item.retrieve('ka.FieldProperty');
            var value = fieldProperty.getValue();
            if (value && value.key && value.target) {
                var definition = Object.clone(value.definition);
                definition.target = value.target;
                definition.id = value.key;
                result.push(definition);
            }

        }.bind(this));

        return result;
    },

    setValue: function (value) {
        this.table.getChildren('tr').destroy();

        if (typeOf(value) == 'array') {
            Object.each(value, function (field) {
                this.add(field.id, field);
            }.bind(this));
        }
    },

    add: function (key, definition) {
        var fieldProperty = new ka.ObjectAttributeProperty(key, definition, this.table, this.options, this.win);
        fieldProperty.addEvent('change', this.fireChange);

        this.fireEvent('add', fieldProperty);
        return fieldProperty;
    }

});