ka.Checkbox = new Class({

    Implements: [Events],

    initialize: function (pContainer) {

        this.box = new Element('a', {
            'class': 'ka-Checkbox ka-Checkbox-off',
            href: 'javascript: ;'
        });

        new Element('div', {
            text: '|',
            'class': 'ka-Checkbox-text-on'
        }).inject(this.box);

        new Element('div', {
            text: 'O',
            'class': 'ka-Checkbox-text-off'
        }).inject(this.box);

        var knob = new Element('div', {
            'class': 'ka-Checkbox-knob'
        }).inject(this.box);

        this.value = false;

        this.box.addEvent('click', function () {
            this.setValue(this.value == false ? true : false);
            this.fireEvent('change');
        }.bind(this));

        if (pContainer) {
            this.box.inject(pContainer);
        }
    },

    toElement: function () {
        return this.box;
    },

    getValue: function () {
        return this.value == false ? false : true;
    },

    setValue: function (p) {
        if (typeOf(p) == 'null') {
            p = false;
        }
        p = (!p || p == 'false') ? false : true;

        this.value = p;
        if (this.value) {
            this.box.removeClass('ka-Checkbox-off');
            this.box.addClass('ka-Checkbox-on');
        } else {
            this.box.addClass('ka-Checkbox-off');
            this.box.removeClass('ka-Checkbox-on');
        }
    }

});