ka.DashboardWidgets.LiveVisitor = new Class({
    Extends: ka.DashboardWidgets.Base,

    streamPath: 'admin/uptime',

    create: function () {
        this.header = new Element('h3', {
            text: ka.tc('dashboardWidget.liveVisitor', 'Live Visitor')
        })
            .inject(this.main);

        this.visitor = new Element('div', {
            style: 'padding-top: 25px; text-align: center; font-size: 62px;',
            text: '4'
        }).inject(this.main);

        this.visitorDay = new Element('div', {
            style: 'padding-top: 25px; text-align: center; font-size: 32px;',
            text: '53 / day'
        }).inject(this.main);
    },

    update: function (value) {
    }
});