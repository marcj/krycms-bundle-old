ka.DashboardWidgets.Uptime = new Class({
    Extends: ka.DashboardWidgets.Base,

    streamPath: 'admin/uptime',

    create: function () {
        this.header = new Element('h3', {
            text: ka.tc('dashboardWidget.uptime', 'Uptime')
        })
            .inject(this.main);

        this.uptime = new Element('div', {
            style: 'padding-top: 25px; text-align: center; font-size: 62px;'
        }).inject(this.main);
    },

    update: function (value) {
        this.uptime.set('text', value);
    }
});