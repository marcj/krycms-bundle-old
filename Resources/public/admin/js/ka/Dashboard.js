ka.Dashboard = new Class({

    Implements: [Events],

    options: {

    },

    container: null,

    widgets: [],

    initialize: function (container, options) {
        this.container = container;
        this.createLayout(container);
        this.container.getDocument().body.addClass('ka-Dashboard-active');
    },

    createLayout: function () {
        this.main = new Element('div', {
            'class': 'ka-Dashboard'
        }).inject(this.container);

        this.main.setStyle('opacity', 0);

        this.loadWidgets();
        this.fireEvent('load');
    },

    loadWidgets: function () {
        this.main.empty();
        [
            'ka.DashboardWidgets.LiveVisitor',
            'ka.DashboardWidgets.Latency',
            'ka.DashboardWidgets.LatencyChart',
            'ka.DashboardWidgets.Uptime',
            'ka.DashboardWidgets.Load',
            'ka.DashboardWidgets.Space'
        ].each(function (clazz) {
                clazz = ka.getClass(clazz);
                this.widgets.push(new clazz(this.main));
            }.bind(this));

        this.main.tween('opacity', 1);
    },

    destroy: function () {
        Array.each(this.widgets, function (widget) {
            widget.destroy();
        });
        this.container.getDocument().body.removeClass('ka-Dashboard-active');
        this.main.destroy();
    }


});