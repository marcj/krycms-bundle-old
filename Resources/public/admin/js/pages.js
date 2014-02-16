var kryncms_pages = new Class({

    /**
     * @var {ka.Window}
     */
    win: null,

    initialize: function (pWin) {
        this.win = pWin;
        this.createLayout();
    },

    createLayout: function () {
        this.win.content.setStyle('border', 0);
        this.win.content.setStyle('top', 0);
        this.win.content.setStyle('overflow', 'hidden');
        this.win.content.setStyle('background-color', 'transparent');
        document.id(this.win.getMainLayout()).addClass('ka-pages-main-layout');

        new ka.Field({
            noWrapper: true,
            type: 'content',
            options: {
                standalone: true
            }
        }, this.win.content);
    }

});