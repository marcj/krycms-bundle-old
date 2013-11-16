var admin_pages = new Class({

    initialize: function (pWin) {
        this.win = pWin;
        this.createLayout();
    },

    createLayout: function () {
        this.win.content.setStyle('border', 0);
        this.win.content.setStyle('top', 0);
        this.win.content.setStyle('overflow', 'hidden');
        this.win.content.setStyle('background-color', 'transparent');

        new ka.Field({
            noWrapper: true,
            type: 'content',
            options: {
                standalone: true
            }
        }, this.win.content);
    }

});