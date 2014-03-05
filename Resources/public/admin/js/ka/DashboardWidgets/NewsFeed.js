ka.DashboardWidgets.NewsFeed = new Class({
    Extends: ka.DashboardWidgets.Base,

    streamPath: 'kryncms/newsFeed',

    create: function () {
        this.header = new Element('h3', {
            text: ka.tc('dashboardWidget.newsFeed', 'News Feed')
        })
            .inject(this.main);

        this.main.addClass('ka-Dashboard-widget-full');

        this.container = new Element('div', {
            style: ''
        }).inject(this.main);
    },

    update: function (value) {

        if (value.items) {
            Array.each(value.items, function(item) {
                this.addNewsFeed(item);
            }.bind(this));
        }

        ka.setStreamParam('newsFeed/lastTime', value.lastTime);
    },

    addNewsFeed: function(item) {
        var div = new Element('div');

        new Element('div', {
            text: item.title
        }).inject(div);

        new Element('div', {
            'style': 'padding: 5px;',
            text: item.message
        }).inject(div);

        new Element('div', {
            'style': 'padding: 5px; color: gray',
            text: new Date(item.created).format('%B %e at %H:%M')
        }).inject(div);

        div.inject(this.container, 'top');
    }
});