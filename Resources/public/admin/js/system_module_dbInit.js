var admin_system_module_dbInit = new Class({

    initialize: function (pWin) {
        this.win = pWin;
        this._createLayout();
    },

    _createLayout: function () {

        this.bb = this.win.addBottomBar();
        this.bb.addButton(_('Close'), function () {
            this.win.close();
        }.bind(this));

        this.output = new Element('div', {
            style: 'position: absolute;overflow: auto; font-size: 10px; color: #444; white-space: pre; left: 2px; right: 2px; top: 2px; bottom: 2px; border: 1px solid #aaa; background-color: #eee; font-family: Monospace;'
        }).inject(this.win.content);
        this.output.set('text', _('Please wait ...'));

        this.load();
    },

    load: function () {

        new Request.JSON({url: _pathAdmin + 'admin/system/module/dbInit', noCache: 1, onComplete: function (res) {
            this.output.set('html', res);
        }.bind(this)}).post({name: this.win.params.name});

    }

});
