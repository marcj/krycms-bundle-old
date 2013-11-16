var admin_system_development_logs = new Class({

    initialize: function(pWin) {
        this.win = pWin;

        this.win.addEvent('close', function() {
            if (this.lastLiveLogTimer) {
                clearTimeout(this.lastLiveLogTimer);
            }
        }.bind(this));

        this._renderLayout();
    },

    _renderLayout: function() {
        var p = new Element('div', {
            style: 'position: absolute; left: 0px; top: 0px; right: 0px; bottom: 31px;'
        }).inject(this.win.content);

        var bottomBar = new ka.ButtonBar(this.win.content);

        this.logsTop = new Element('div', {
            style: 'position: absolute; left: 0px; top: 0px; right: 0px; height: 65px; padding: 5px;'
        }).inject(p);

        this.btnDiv = new Element('div', {style: 'position: absolute; right: 15px; top: 15px;'}).inject(this.logsTop);

        this.btnClearLogs = new ka.Button(_('Clear logs')).addEvent('click', this.clearLogs.bind(this)).inject(this.btnDiv);

        this.btnRefresh = new ka.Button(_('Refresh')).addEvent('click', this.reloadLogsItems.bind(this)).inject(this.btnDiv);

        this.btnDiv2 = new Element('div', {
            style: 'padding: 0px 17px; float: right;'
        }).inject(this.btnDiv);

        this.liveLog = new ka.Checkbox(this.btnDiv2).addEvent('change', this.toggleLiveLog.bind(this));

        new Element('label', {
            'for': this.win.id + 'admin-logs-liveLogCheckbox',
            text: _('Live log')
        }).inject(this.btnDiv2);

        this.logsLevelSelect = new ka.Field({
            type: 'select',
            label: _('Level'),
            inputWidth: 200,
            objectItems: [
                {all: t('All')},
                {100: t('Debug (100)')},
                {200: t('Info (200)')},
                {250: t('Notice (250)')},
                {300: t('Warning (300)')},
                {400: t('Error (400)')},
                {500: t('Critical (500)')},
                {550: t('Alert (550)')},
                {600: t('Emergency (600)')}
            ]
        }).addEvent('change', function() {
                this.loadLogsItems(1);
            }.bind(this)).inject(this.logsTop);

        this.logsTable = new Element('div', {
            style: 'position: absolute; left: 0px; top: 77px; right: 0px; bottom: 31px; overflow: auto;'
        }).inject(p);

        this.logsTable = new ka.Table().inject(this.logsTable);
        this.logsTable.setColumns([
            [_('Date'), 160],
            [_('Request'), 80],
            [_('Level'), 100],
            [_('Context'), 150],
            [_('Message')],
            [_('Actions'), 200]
        ]);

        document.id(this.logsTable).addClass('selectable');

        var myPath = _path + 'bundles/admin/images/icons/';

        this.logsCtrlPrevious = new Element('img', {
            src: myPath + 'control_back.png'
        }).addEvent('click', function() {
                this.loadLogsItems(parseInt(this.logsCurrentPage) - 1);
            }.bind(this)).inject(bottomBar.box);

        this.logsCtrlText = new Element('span', {
            text: 1,
            style: 'padding: 0px 3px 5px 3px; position: relative; top: -4px;'
        }).inject(bottomBar.box);

        this.logsCtrlNext = new Element('img', {
            src: myPath + 'control_play.png'
        }).addEvent('click', function() {
                this.loadLogsItems(parseInt(this.logsCurrentPage) + 1);
            }.bind(this)).inject(bottomBar.box);

        this.loadLogsItems();
    },

    toggleLiveLog: function() {
        if (!this.liveLog.getValue() && this.lastLiveLogTimer) {
            clearTimeout(this.lastLiveLogTimer);
        } else {
            this.reloadLogsItems(true);
        }

    },

    clearLogs: function() {
        this.btnClearLogs.startTip(_('Clearing logs ...'));

        new Request.JSON({url: _pathAdmin + 'admin/system/tools/logs', noCache: 1, onComplete: function() {
            this.btnClearLogs.stopTip(_('Cleared'));
            this.loadLogsItems(1);
        }.bind(this)}).get({_method: 'delete'});

    },

    renderLogCtrls: function() {
        this.logsCtrlPrevious.setStyle('opacity', 1);
        this.logsCtrlNext.setStyle('opacity', 1);

        if (this.logsCurrentPage == 1) {
            this.logsCtrlPrevious.setStyle('opacity', 0.3);
        }

        if (this.logsCurrentPage == this.logsMaxPages) {
            this.logsCtrlNext.setStyle('opacity', 0.3);
        }

        this.logsCtrlText.set('text', this.logsCurrentPage);
    },

    reloadLogsItems: function(pAgain) {
        this.loadLogsItems(this.logsCurrentPage, pAgain);
    },

    loadLogsItems: function(pPage, pAgain) {
        if (!pPage) {
            pPage = 1;
        }

        if (this.lastrq) {
            this.lastrq.cancel();
        }

        this.lastrq = new Request.JSON({url: _pathAdmin + 'admin/system/tools/logs', noCache: 1,
            onComplete: function(response) {

                var data = response.data;
                this.logsCurrentPage = pPage;
                this.logsMaxPages = data.maxPages;
                this.renderLogCtrls();

                this.renderItems(data.items);

                if (pAgain == true && this.liveLog.getValue()) {
                    this.lastLiveLogTimer = this.reloadLogsItems.delay(1000, this, true);
                }

            }.bind(this)}).get({page: pPage, level: this.logsLevelSelect.getValue()});
    },

    renderItems: function(items) {
        var level = {
            all: t('All'),
            100: t('Debug (100)'),
            200: t('Info (200)'),
            250: t('Notice (250)'),
            300: t('Warning (300)'),
            400: t('Error (400)'),
            500: t('Critical (500)'),
            550: t('Alert (550)'),
            600: t('Emergency (600)')
        };

        this.logsTable.empty();
        var lastRequestId;
        Object.each(items, function(item) {

            var row = [], a = '';

            var micro = ((item.date+'').split('.')[1] || '0').substr(0, 3);
            row.push(new Date(item.date * 1000).format('db') + '.' + micro);

            if (item.requestId) {
                if (item.requestId != lastRequestId) {
                    a = new Element('a', {
                        text: item.requestId.substr(0, 10),
                        title: item.requestId,
                        href: 'javascript: ;'
                    }).addEvent('click', function() {
                            this.loadRequestDetails(item);
                        }.bind(this));
                    lastRequestId = item.requestId;
                } else {
                    a = new Element('div', {
                        text: '"',
                        styles: {
                            textAlign: 'center'
                        }
                    })
                }
            }
            row.push(a);
            row.push(level[item.level] || item.level);
            row.push(item.context);
            row.push(item.message);

            var action = new Element('div');
            new ka.Button(t('Client')).inject(action);
            new ka.Button(t('Stack trace')).inject(action);
            row.push(action);

            this.logsTable.addRow(row);
        }.bind(this));
    },

    loadRequestDetails: function(item) {
        var dialog = new ka.Dialog(null, {
            autoClose: true,
            withButtons: true,
            cancelButton: false,
            absolute: true,
            minWidth: '80%',
            minHeight: '80%',
            applyButtonLabel: t('OK')
        });

        this.lastrq = new Request.JSON({url: _pathAdmin + 'admin/system/tools/request', noCache: 1,
            onFailure: function() {
                dialog.getContentContainer().set('text', 'Failed');
            },
            onComplete: function(response) {
                if (!response.data) {
                    dialog.getContentContainer().set('text', 'Deleted');
                } else {

                    var fields = {
                        '__tab1__': {
                            label: 'General',
                            type: 'tab',
                            fullPage: true,
                            children: {
                                id: {
                                    label: 'ID',
                                    type: 'info'
                                },
                                time: {
                                    label: 'Time',
                                    type: 'info'
                                },
                                username: {
                                    label: 'Username',
                                    type: 'info'
                                },
                                ip: {
                                    label: 'IP',
                                    type: 'info'
                                },
                                orm: {
                                    label: 'ORM',
                                    type: 'info'
                                },
                                queryCount: {
                                    label: 'SQL Query count',
                                    type: 'info'
                                }
                            }
                        },
                        '__tab2__': {
                            label: 'Exceptions',
                            type: 'tab',
                            children: {
                                exceptions: {
                                    type: 'html',
                                    tableItem: false,
                                    noWrapper: true,
                                    height: '100%',
                                    options: {
                                        iframe: true
                                    }
                                }
                            }
                        }
                    };

                    var request = response.data;
                    var micro = ((request.date+'').split('.')[1] || '0').substr(0, 3);
                    request.time = (new Date(request.date*1000)).format('db')+'.'+micro;

                    var orm = request.orm ? JSON.decode(request.orm) : {};
                    request.orm = "%s updates, %d deletes, %d adds.".sprintf(
                        orm.updates || 0,
                        orm.deleted || 0,
                        orm.adds || 0
                    );

                    var queries = request.queries ? JSON.decode(request.queries) : [];
                    request.queryCount = queries.length;

                    var form = new ka.FieldForm(dialog.getContentContainer(), fields, {
                        allTableItems: true
                    });

                    form.setValue(request);

                }
            }.bind(this)}).get({request: item.requestId});

        dialog.show();

    }
});