ka.DashboardWidgets.NewsFeed = new Class({
    Extends: ka.DashboardWidgets.Base,

    streamPath: 'kryncms/newsFeed',

    firstLoad: true,
    loadedItems: false,

    create: function () {
        this.header = new Element('h3', {
            text: ka.tc('dashboardWidget.newsFeed', 'News Feed')
        })
            .inject(this.main);

        this.main.addClass('ka-Dashboard-widget-block');

        this.container = new Element('div', {
            style: ''
        }).inject(this.main);

        ka.setStreamParam('newsFeed/lastTime', null);
    },

    update: function (value) {
        if (value && value.items && value.items.length) {
            if (!this.loadedItems) {
                this.container.empty();
            }
            this.loadedItems = true;
            Array.each(value.items, function(item, idx) {
                this.addNewsFeed(item);
            }.bind(this));
        } else {
            if (!this.loadedItems && this.firstLoad) {
                this.firstLoad = false;
                new Element('div', {
                    'style': 'text-align: center; padding: 15px;',
                    text: t('No news yet')
                }).inject(this.container);
            }
        }


        ka.setStreamParam('newsFeed/lastTime', value.time);
    },

    addNewsFeed: function(item) {
        var div = new Element('div', {
            'class': 'ka-Dashboard-newsFeed-item'
        });

        new Element('a', {
            'class': 'ka-Dashboard-newsFeed-item-user',
            text: item.username
        }).inject(div);

        new Element('span', {
            'class': 'ka-Dashboard-newsFeed-item-verb',
            text: item.verb
        }).inject(div);

        if (item.targetObject) {
            var objectDefinition = ka.getObjectDefinition(item.targetObject);
            var objectLabel = objectDefinition.label || item.targetObject;
            new Element('a', {
                'class': 'ka-Dashboard-newsFeed-item-object-label',
                text: objectLabel+':'
            }).inject(div);
        }

        new Element('a', {
            'class': 'ka-Dashboard-newsFeed-item-label',
            text: item.targetLabel
        }).inject(div);

        new Element('div', {
            'class': 'ka-Dashboard-newsFeed-item-date',
            text: new Date(item.created*1000).format('%B %e at %H:%M')
        }).inject(div);

        if (item.message) {
            new Element('div', {
                'style': 'padding: 5px;',
                'class': 'ka-Dashboard-newsFeed-item-message',
                html: item.message
            }).inject(div);
        }
        div.inject(this.container, 'top');
    }
});