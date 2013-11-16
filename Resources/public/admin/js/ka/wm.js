/* ka window.manager */
ka.wm = {
    windows: {},

    instanceIdx: 0,
    /* depend: [was => mitWem] */
    depend: {},
    lastWindow: null,
    events: {},
    zIndex: 1000,

    activeWindowInformation: [],
    tempItems: {},

    openWindow: function (pEntryPoint, pLink, pParentWindowId, pParams, pInline) {
        var win;

        if (!ka.entrypoint.get(pEntryPoint)) {
            logger(tf('Entry point `%s` not found.', pEntryPoint));
            return;
        }

        if ((win = this.checkOpen(pEntryPoint, null, pParams)) && !pInline) {
            return win.toFront();
        }

        return ka.wm.loadWindow(pEntryPoint, pLink, pParentWindowId, pParams, pInline);
    },

    addEvent: function (pEv, pFunc) {
        if (!ka.wm.events[pEv]) {
            ka.wm.events[pEv] = [];
        }

        ka.wm.events[pEv].include(pFunc);
    },

    fireEvent: function (pEv) {
        if (ka.wm.events[pEv]) {
            Object.each(ka.wm.events[pEv], function (func) {
                $try(func);
            });
        }
    },

    open: function (pEntryPoint, pParams, pParentWindowId, pInline) {
        return ka.wm.openWindow(pEntryPoint, null, pParentWindowId, pParams, pInline);
    },

    getWindow: function (pId) {
        if (pId == -1 && ka.wm.lastWindow) {
            pId = ka.wm.lastWindow.getId();
        }
        return ka.wm.windows[ pId ];
    },

    getWindows: function() {
        return ka.wm.windows;
    },

    sendSoftReload: function (pEntryPoint) {
        ka.wm.softReloadWindows(pEntryPoint);
    },

    softReloadWindows: function (pEntryPoint) {
        Object.each(ka.wm.windows, function (win) {
            if (win && win.getEntryPoint() == pEntryPoint) {
                win.softReload();
            }
        });
    },

    fireResize: function () {
        Object.each(ka.wm.windows, function (win) {
            win.fireEvent('resize');
        });
    },

    resizeAll: function () {
        ka.settings['user']['windows'] = {};
        Object.each(ka.wm.windows, function (win) {
            win.loadDimensions();
        });
    },

    getActiveWindow: function() {
        return ka.wm.lastWindow;
    },

    setFrontWindow: function (pWindow) {
        Object.each(ka.wm.windows, function (win, winId) {
            if (win && pWindow.id != winId) {
                win.toBack();
            }
        });
        ka.wm.lastWindow = pWindow;
    },

    loadWindow: function (pEntryPoint, pLink, pParentWindowId, pParams, pInline) {
        var instance = ++ka.wm.instanceIdx;

        if (pParentWindowId == -1) {
            pParentWindowId = ka.wm.lastWindow ? ka.wm.lastWindow.id : false;
        }

        if (false === pParentWindowId || (pParentWindowId && !ka.wm.getWindow(pParentWindowId))) {
            throw tf('Parent `%d` window not found.', pParentWindowId);
        }

        ka.wm.windows[instance] = new ka.Window(pEntryPoint, pLink, instance, pParams, pInline, pParentWindowId);
        ka.wm.windows[instance].toFront();
        ka.wm.updateWindowBar();
        ka.wm.reloadHashtag();
    },

    close: function (pWindow) {
        var parent = pWindow.getParent();
        if (parent && instanceOf(parent, ka.Window)) {
            parent.removeChildren();
        }

        if (ka.wm.tempItems[pWindow.getId()]) {
            ka.wm.tempItems[pWindow.getId()].destroy();
            delete ka.wm.tempItems[pWindow.getId()];
        }

        delete ka.wm.windows[pWindow.id];

        if (parent) {
            parent.toFront();
        } else {
            ka.wm.bringLastWindow2Front();
        }

        ka.wm.updateWindowBar();
        ka.wm.reloadHashtag();
    },

    bringLastWindow2Front: function () {
        var lastWindow;

        Object.each(ka.wm.windows, function (win) {
            if (!win) {
                return;
            }
            if (!lastWindow || win.border.getStyle('z-index') > lastWindow.border.getStyle('z-index')) {
                lastWindow = win;
            }
        });

        if (lastWindow) {
            lastWindow.toFront();
        }
    },

    getWindowsCount: function () {
        var count = 0;
        Object.each(ka.wm.windows, function (win, winId) {
            if (!win) {
                return;
            }
            if (win.inline) {
                return;
            }
            count++;
        });
        return count;
    },

    updateWindowBar: function () {
        var openWindows = 0;

        var wmTabContainer = ka.adminInterface.getWMTabContainer();

        wmTabContainer.empty();
        var fragment = document.createDocumentFragment();

        var el, icon;
        Object.each(ka.wm.windows, function (win) {
            if (win.getParent()) {
                return;
            }

            if (win.isInFront()) {
                openWindows++;
            }

            el = new Element('div', {
                'class': 'ka-wm-tab' + (win.isInFront() ? ' ka-wm-tab-active' : ''),
                text: win.getTitle() || (win.getEntryPointDefinition() || {}).label
            })
            .addEvent('click', function(){ win.toFront(); });

            if (icon = (win.getEntryPointDefinition() || {}).icon) {
                if ('#' === icon.substr(0, 1)) {
                    el.addClass(icon.substr(1));
                } else {
                    //new img
                }
            }

            new Element('a', {
                'class': 'icon-cancel-8'
            }).addEvent('click', function(e){
                win.close();
                e.stopPropagation();
                e.stop();
            }).inject(el);

            fragment.appendChild(el);
        });

        wmTabContainer.appendChild(fragment);

        if (ka.adminInterface.dashboardLink) {
            if (0 === openWindows) {
                ka.adminInterface.dashboardLink.addClass('ka-main-menu-item-open');
                ka.adminInterface.dashboardLink.addClass('ka-main-menu-item-active');
            } else {
                ka.adminInterface.dashboardLink.removeClass('ka-main-menu-item-open');
                ka.adminInterface.dashboardLink.removeClass('ka-main-menu-item-active');
            }
            ka.adminInterface.showDashboard(0 === openWindows);
        }

        ka.wm.reloadHashtag();
    },

    reloadHashtag: function (pForce) {
        var hash = []

        Object.each(ka.wm.windows, function (win) {
            if (!win.isInline()) {
                hash.push(win.getEntryPoint() + ( win.hasParameters() ? '?' + Object.toQueryString(win.getParameters()) : '' ));
            }
        });

        hash = hash.join(';');

        if (hash != window.location.hash) {
            window.location.hash = hash;
        }

    },

    handleHashtag: function (pForce) {
        if (ka.wm.hashHandled && !pForce) {
            return;
        }

        ka.wm.hashHandled = true;

        if (!window.location.hash.substr(1)) {
            return;
        }

        var hashes = window.location.hash.substr(1).split(';');

        if (hashes) {
            Array.each(hashes, function(hash){
                var first = hash.indexOf('?');
                var entryPoint = hash;
                var parameters = null;
                if (first !== -1) {
                    entryPoint = entryPoint.substr(0, first);

                    parameters = hash.substr(first + 1);
                    if (parameters && 'string' === typeOf(parameters)) {
                        parameters = parameters.parseQueryString();
                    }
                }
                ka.wm.open(entryPoint, parameters);
            });
        }
    },

    removeActiveWindowInformation: function () {
        ka.adminInterface.mainMenuTopNavigation.getElements('a').removeClass('ka-main-menu-item-active');
        ka.adminInterface.mainMenu.getElements('a').removeClass('ka-main-menu-item-active');
        ka.adminInterface.mainMenuTopNavigation.getElements('a').removeClass('ka-main-menu-item-open');
        ka.adminInterface.mainLinks.getElements('a').removeClass('ka-main-menu-item-open');
    },

    checkOpen: function (pEntryPoint, pInstanceId, pParams) {
        var opened = false;
        Object.each(ka.wm.windows, function (win) {
            if (win && win.getEntryPoint() == pEntryPoint) {
                if (pInstanceId && pInstanceId == win.id) {
                    return;
                }
                if (pParams) {
                    if (JSON.encode(win.getOriginParameters()) != JSON.encode(pParams)){
                        return;
                    }
                }
                opened = win;
            }
        });
        return opened;
    },

    closeAll: function () {
        Object.each(ka.wm.windows, function (win) {
            win.close();
        });
    },

    hideContents: function () {
        Object.each(ka.wm.windows, function (win, winId) {
            win.content.setStyle('display', 'none');
        });
    },

    showContents: function () {
        Object.each(ka.wm.windows, function (win, winId) {
            win.content.setStyle('display', 'block');
        });
    }
};

window.addEvent('resize', function(){
    ka.wm.fireResize();
});
