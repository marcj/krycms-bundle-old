admin_overview = new Class({
    initialize: function (pWindow) {
        this.win = pWindow;

        this.topGroup = this.win.addSmallTabGroup();
        this.buttons = new Hash();

        this.addMenu = function (pConfig, pKey) {

            if (!pConfig || !pConfig.widgets) {
                return;
            }
            var titleTxt = (pConfig.title[window._session.lang]) ? pConfig.title[window._session.lang] :
                pConfig.title['en'];
            var img = null;
            if (pConfig.icon_mini) {
                img = _path + pConfig.icon_mini;
            }
            this.buttons[pKey] = this.topGroup.addButton(titleTxt, this.viewType.bind(this, pKey), img);

        }.bind(this);

        this.addMenu(ka.settings.configs['admin'], 'admin');
        Object.each(ka.settings.configs, function (config, extCode) {
            if (extCode == 'admin') {
                return;
            }

            //checkaccess admin/users/widgets/widgetSessions/%
            this.addMenu(config, extCode);

        }.bind(this));

        //this.buttons['general'] = this.topGroup.addButton(_('General'), _path+ PATH_WEB + '/admin/images/admin-pages-viewType-general.png', this.viewType.bind(this,'general'));
        //this.buttons['system'] = this.topGroup.addButton(_('System'), _path+ PATH_WEB + '/admin/images/icons/computer.png', this.viewType.bind(this,'system'));
        //this.buttons['statistic'] = this.topGroup.addButton(_('Statistic'), _path+ PATH_WEB + '/admin/images/icons/chart_pie.png', this.viewType.bind(this,'statistic'));

        this._widgets = [];

        this.win.content.setStyle('padding', 16);

        this.panes = new Hash();
        this.buttons.each(function (button, key) {
            this.panes[key] = new Element('div', {
                'style': 'position: absolute; left: 15px; right: 15px; top: 0px; bottom: 0px; padding: 15px 0px; overflow auto; display: none;'
            }).inject(this.win.content);

        }.bind(this));

        this.win.addEvent('close', function () {
            this.clearWidgets();
        }.bind(this));

        this.viewType('admin');

        //this.viewType('general');

    },

    viewType: function (pExt) {
        this.panes[pExt].empty();

        this.buttons.each(function (button, key) {
            button.setPressed(false);
            this.panes[ key ].setStyle('display', 'none');
        }.bind(this));
        this.buttons[ pExt ].setPressed(true);
        this.panes[ pExt ].setStyle('display', 'block');

        this.clearWidgets();
        this._widgets.clean();

        var widgetsLayout = ka.settings.configs[pExt].widgetsLayout;
        if (widgetsLayout) {

            this.panes[pExt].set('html', widgetsLayout);

        } else {
            var left = new Element('div', {
                style: 'float: left; width: 49%;'
            }).inject(this.panes[pExt]);

            var right = new Element('div', {
                style: 'float: right; width: 49%;'
            }).inject(this.panes[pExt]);

            new Element('div', {'style': 'clear: both;'}).inject(this.panes[pExt]);
        }

        $H(ka.settings.configs[pExt].widgets).each(function (widget, key) {

            var target = false;
            if (!widgetsLayout) {
                target = (widget.position == 'right') ? right : left;
            } else {
                target = this.panes[pExt].getElement('[id=' + widget.position + ']');
                if (!target) {
                    this.win._alert(_('Can not find position %1 in the widgetsLayout from extension %2').replace('%1',
                        widget.position).replace('%2', pExt));
                }
            }

            widget.extension = pExt;
            widget.code = key;

            var widgetObj = new ka.Widget(widget, target, true);

            this._widgets.include(widgetObj);

        }.bind(this));

    },

    clearWidgets: function () {
        this._widgets.each(function (widget, index) {
            widget.fireEvent('close');
            this._widgets[index] = null;
            delete this._widgets[index];
        }.bind(this));
        this._widgets.empty();
    },

    viewType222: function (pType) {
        this.buttons.each(function (button, key) {
            button.setPressed(false);
            this.panes[ key ].setStyle('display', 'none');
        }.bind(this));
        this.buttons[ pType ].setPressed(true);
        this.panes[ pType ].setStyle('display', 'block');

        this.clearWidgets();

        this._widgets.clean();

        switch (pType) {
            case 'general':
                this.loadWidgets('overview');
                break;
            case 'statistic':
                this.loadWidgets('statistic');
                break;
        }
    },

    loadWidgets: function (pCategory) {

        pPane = (pCategory == 'overview') ? 'general' : 'statistic';
        p = this.panes[pPane];
        p.setStyle('padding', 10);
        p.empty();

        new Request.JSON({url: _pathAdmin +
            'admin/widgets/getWidgets/', noCache: 1, onComplete: function (pExtensions) {

            if (typeOf(pExtensions) == 'array') {
                new Element('div', {
                    html: _('No widgets found on this system in category: %s'.replace('%s', pCategory)),
                    style: 'color: gray; text-align: center; padding: 15px;'
                }).inject(p);
            }

            $H(pExtensions).each(function (item, index) {

                var h3 = new Element('h4', {
                    html: _(item.title),
                    style: 'margin: 0px;',
                    'class': 'admin-overview-headline'
                }).inject(p);

                var container = new Element('div', {
                    style: 'display: none; padding: 2px;'
                }).inject(p);

                var img = new Element('img', {
                    src: _path + 'bundles/admin/images/icons/tree_plus.png',
                    style: 'position: relative; top: 1px; margin-right: 3px;',
                    lang: 0
                }).addEvent('click',
                    function (e) {
                        if (this.lang == 0) {
                            this.src = _path + 'bundles/admin/images/icons/tree_minus.png';
                            this.lang = 1;
                        } else {
                            this.src = _path + 'bundles/admin/images/icons/tree_plus.png';
                            this.lang = 0;
                        }
                        container.setStyle('display', (this.lang == 0) ? 'none' : 'block');
                        if (e) {
                            e.stop();
                        }

                    }).fireEvent('click').inject(h3, 'top');

                var left = new Element('div', {
                    style: 'float: left; width: 49%;'
                }).inject(container);

                var right = new Element('div', {
                    style: 'float: right; width: 49%;'
                }).inject(container);

                new Element('div', {'style': 'clear: both;'}).inject(container);

                item.widgets.each(function (widget) {

                    var target = (widget.position == 'right') ? right : left;

                    var widget = new ka.Widget(widget, target);
                    widget.main.setStyle('opacity', 0.7);
                    this._widgets.include(widget);

                }.bind(this));

            }.bind(this));

        }.bind(this)}).post({category: pCategory});

        //this.renderGeneral();
        //this.pullWidgets();
    },

    pullWidgets: function () {
        this.widgets.each(function (items, boxid) {
            items.each(function (item) {
                this.pullWidget(item, boxid);
            }.bind(this));
        }.bind(this));
    },

    pullWidget: function (pItem, pBoxId) {

        var p = this.boxes[pBoxId];

        var box = new Element('div', {
            'class': 'admin-overview-box'
        }).inject(p);

        var title = new Element('div', {
            'class': 'admin-overview-box-title',
            html: _('Loading ...')
        }).inject(box);

        var content = new Element('div', {
            'class': 'admin-overview-box-content'
        }).inject(box);

        this.loadWidget(pItem, box);

    },

    loadWidget: function (pItem, pBox) {
        new Request.JSON({url: _pathAdmin + 'admin/overview/widgets/load/', onComplete: function (res) {

            pBox.getElement('div[class=admin-overview-box-title]').set('html', res.title);
            pBox.getElement('div[class=admin-overview-box-content]').set('html', res.content);

        }.bind(this)}).post(pItem);
    }

});
