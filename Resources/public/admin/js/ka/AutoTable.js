ka.AutoTable = new Class({

    initialize: function (pOpts) {
        this.opts = pOpts;
        this._createLayout();
    },

    _createLayout: function () {
        this.box = new Element('div', {
            'class': 'ka-autotable-box'
        });
        this.boxBorder = new Element('div', {
            'class': 'ka-autotable-box-border'
        }).inject(this.box);
        ;

        this.boxTitle = new Element('div', {
            'class': 'ka-autotable-box-header',
            html: this.opts.title
        }).inject(this.boxBorder);

        this.boxMain = new Element('div', {
            'class': 'ka-autotable-box-main'
        }).inject(this.boxBorder);
    },

    inject: function (p1, p2) {
        this.box.inject(p1, p2);
    }


});
