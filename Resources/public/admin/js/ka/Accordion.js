ka.Accordion = new Class({
    Implements: Events,

    togglerHeight: 20, //defines the height of the toggler elements (for calculating the pane height)
    height: 100,
    index: 0,

    initialize: function (pParent) {

        this.box = new Element('div', {
            'class': 'win-Accordion',
            styles: {
                'height': this.height
            }
        }).inject(pParent);

        this.initAccordion();
    },

    initAccordion: function () {

        if (this.lastInit) {
            clearTimeout(this.lastInit);
        }

        this.lastInit = this._initAccordion.delay(10, this);

    },

    _initAccordion: function () {
        var _this = this;

        if (this.lastAccordion) {
            this.lastAccordion.detach();
            delete this.lastAccordion;
        }

        var paneHeight = this.height;
        paneHeight = paneHeight - ( this.togglerHeight * (this.box.getElements('.ka-Accordion-toggler').length));

        this.lastAccordion =
            new Accordion(this.box.getElements('.ka-Accordion-toggler'), this.box.getElements('.ka-Accordion-pane'), {
                duration: 400,
                display: this.index,
                fixedHeight: paneHeight,
                transition: Fx.Transitions.Cubic.easeOut,
                onActive: function (toggler, element) {
                    toggler.addClass('ka-Accordion-current');

                    _this.box.getElements('.ka-Accordion-toggler-img').each(function (img) {
                        img.set('src', _path + 'bundles/admin/images/icons/tree_plus.png');
                    });

                    toggler.getElement('.ka-Accordion-toggler-img').set('src',
                        _pathAdmin + 'bundles/admin/images/icons/tree_minus.png');

                    element.setStyles({ overflowX: 'hidden', overflowY: 'auto' });
                },
                onBackground: function (toggler, element) {
                    toggler.removeClass('ka-Accordion-current');
                }
            }, this.box);

        this.ready = true;
        this.fireEvent('ready');

    },

    setHeight: function (pHeight) {
        this.height = pHeight
        this.box.setStyle('height', this.height);
        this.initAccordion();
    },

    display: function (pId) {
        if (!this.ready) {
            return;
        }
        this.index = pId;
        this.lastAccordion.display(pId);
    },

    addSection: function (pTitle) {

        var toggler = new Element('a', {
            'class': 'ka-Accordion-toggler',
            href: 'javascript:;'
        }).inject(this.box);

        new Element('img', {
            'class': 'ka-Accordion-toggler-img',
            src: _path + 'bundles/admin/images/icons/tree_plus.png'
        }).inject(toggler, 'top');

        new Element('span', {
            html: pTitle
        }).inject(toggler);

        var pane = new Element('div', {
            'class': 'ka-Accordion-pane'
        }).inject(this.box);

        var panepane = new Element('div', {
            'class': 'ka-Accordion-pane-pane'
        }).inject(pane);

        this.initAccordion();
        //this.lastAccordion.addSection(toggler, pane);

        return panepane;
    },

    _to: function (id) {
        this.fireEvent('change', id);
        this.to(id);
    },

    to: function (id) {
        this.index = id;
    },

    destroy: function () {
        this.box.destroy();
    },

    inject: function (pTo, pWhere) {
        this.box.inject(pTo, pWhere);
    },

    hide: function () {
        this.box.setStyle('display', 'none');
    },

    show: function () {
        this.box.setStyle('display', 'block');
    }
});
