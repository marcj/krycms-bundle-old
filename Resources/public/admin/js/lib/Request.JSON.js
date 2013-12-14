/**
 * Request.JSON - extended to get some informations about calls and creates info for user if a call fails.
 */
Request.JSON = new Class({
    Extends: Request.JSON,

    initialize: function (options) {
        if (!'secure' in options) {
            options.secure = true;
        }
        this.parent(options);
        this.addEvent('failure', this.booboo.bind(this));
        this.addEvent('error', this.invalidJson.bind(this));

        this.addEvent('complete', function (pData) {
            window.fireEvent('restCall', [pData, this]);
        }.bind(this));

        if (options.noErrorReporting === true) {
            return;
        }
        this.addEvent('complete', this.checkError.bind(this));
    },

    send: function (options) {
        this.data = options.data;
        this.options.url += (this.options.url.indexOf('?') == -1 ? '?' : '&') + '_suppress_status_code=1';
        return this.parent(options);
    },

    invalidJson: function () {
        if (ka.lastRequestBubble) {
            ka.lastRequestBubble.die();
            delete ka.lastRequestBubble;
        }

        if (ka.helpsystem) {
            ka.lastRequestBubble = ka.helpsystem.newBubble(
                t('Response error'),
                t('Server\'s response is not valid JSON. Looks like the server has serious troubles. :-(') +
                    "<br/>" + 'URI: %s'.replace('%s', this.options.url) +
                    '<br/><a class="ka-Button" href="javascript:;">Details</a>',
                15000);
            throw 'Response Error %s'.replace('%s', this.options.url);
        }
    },

    booboo: function () {
        if (ka.lastRequestBubble) {
            ka.lastRequestBubble.die();
            delete ka.lastRequestBubble;
        }

        if (ka.helpsystem) {
            ka.lastRequestBubble = ka.helpsystem.newBubble(
                t('Request error'),
                t('There has been a error occured during the last request. Either you lost your internet connection or the server has serious troubles.') +
                    "<br/>" + 'URI: %s'.replace('%s', this.options.url) +
                    '<br/><a class="ka-Button" onclick="ka.wm.open(\'admin/system/rest-logger\')">Details</a>',
                15000);
            throw 'Request Error %s'.replace('%s', this.options.url);
        }
    },

    checkError: function (pResult) {
        if (pResult && pResult.error) {

            if (typeOf(this.options.noErrorReporting) == 'array' &&
                this.options.noErrorReporting.contains(pResult.error)) {
                return false;
            }

            if (true === this.options.noErrorReporting) {
                return false;
            }

            if (ka.lastRequestBubble) {
                ka.lastRequestBubble.die();
                delete ka.lastRequestBubble;
            }

            if (!ka.adminInterface || !ka.adminInterface.getHelpSystem()) {
                return false;
            }

            if ('AccessDeniedException' === pResult.error) {
                ka.lastRequestBubble = ka.adminInterface.getHelpSystem().newBubble(
                    t('Access denied'),
                    t('You started a secured action or requested a secured information.') +
                        "<br/>" + 'URI: %s'.replace('%s', this.options.url) +
                        '<br/><a class="ka-Button" onclick="ka.open(\'admin/system/rest-logger\')">Details</a>',
                    15000
                );
                throw 'Access Denied %s'.replace('%s', this.options.url);
            } else {
                ka.lastRequestBubble = ka.adminInterface.getHelpSystem().newBubble(
                    t('Request error'),
                    t('There has been a error occured during the last request. It looks like the server has currently some troubles. Please try it again.') +
                        "<br/><br/>" + t('Error code: %s').replace('%s', pResult.error) +
                        "<br/>" + t('Error message: %s').replace('%s', pResult.message) +
                        "<br/>" + 'URI: %s'.replace('%s', this.options.url) +
                        '<br/><a class="ka-Button" onclick="ka.wm.open(\'admin/system/rest-logger\')">Details</a>',
                    15000
                );
                throw 'Request Error %s'.replace('%s', this.options.url);
            }
        }
    }
});