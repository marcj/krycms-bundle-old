ka.FieldTypes.ImageGroup = new Class({
    Extends: ka.FieldAbstract,

    Statics: {
        asModel: true,
        options: {
            items: {
                label: 'Items',
                type: 'array',
                columns: [
                    'Value', 'Label', 'Image'
                ],
                fields: {
                    value: {
                        type: 'text'
                    },
                    label: {
                        type: 'text'
                    },
                    src: {
                        type: 'text'
                    }
                }
            }
        }
    },

    createLayout: function () {
        this.main = new Element('div', {
            style: 'padding: 5px;',
            'class': 'ka-field-imageGroup'
        }).inject(this.fieldInstance.fieldPanel);

        this.imageGroup = new ka.ImageGroup(this.main);

        this.imageGroupImages = {};

        var useOwnKey = 'array' === typeOf(this.options.items);

        Object.each(this.options.items, function (image, value) {
            this.imageGroupImages[useOwnKey ? image.value : value] = this.imageGroup.addButton(image.label, image.src);
        }.bind(this));

        this.imageGroup.addEvent('change', this.fieldInstance.fireChange);
    },

    setValue: function (pValue) {
        Object.each(this.imageGroupImages, function (button, tvalue) {
            button.removeClass('ka-buttonGroup-item');
            if (pValue == tvalue) {
                button.addClass('ka-buttonGroup-item');
            }
        });
    },

    getValue: function () {
        var value = null;
        Object.each(this.imageGroupImages, function (button, tvalue) {
            if (button.hasClass('ka-buttonGroup-item')) {
                value = tvalue;
            }
        });

        return value;
    }
});