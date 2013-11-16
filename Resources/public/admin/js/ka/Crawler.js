ka.Crawler = new Class({

    startDelay: 2 * 60 * 1000,

    initialize: function () {

        this.id = window._sid + '_' + new Date().getTime() + '-' + (1 + parseInt(Math.random() * ( 999999 - 1 + 1 )));
        ka.addStreamParam('crawlerId', this.id);

        this.start();

    },

    start: function () {

        this._start.delay(this.startDelay, this);
    },

    stop: function () {

        if (this.lastRq) {
            this.lastRq.cancel();
        }
        this.stopped = true;
    },

    restart: function () {
        this.stop();

        ka.stopSearchCrawlerInfo(_('Crawling interrupted'));
        this.start();
    },

    _start: function () {

        //waiting for permission
        this.check = function (res) {

            if (!ka.settings.user.autocrawler || ka.settings.user.autocrawler != 1) {
                return;
            }

            if (res.hasCrawlPermission == true) {
                if (this.stopped == true) {
                    return;
                }
                ka.startSearchCrawlerInfo(_('Check searchindex'));
                this.step1.delay(2000, this);
                window.removeEvent('stream', this.check);
            }

        }.bind(this);
        window.addEvent('stream', this.check);
    },

    step1: function () {

        ka.setSearchCrawlerInfo(_('Crawl unindexed'));
        this.lastRq = new Request.JSON({url: _pathAdmin + 'admin/backend/searchIndexer/getWaitlist', noCache: 1,
            onComplete: function (pRes) {
                if (pRes.access == false) {
                    return this.restart();
                }

                if (pRes.pages.length == 0) {
                    this.step1_5();
                } else {
                    this.crawl(pRes.pages, this.step1_5.bind(this));
                }

            }.bind(this)}).post({crawlerId: this.id});

    },

    step1_5: function () {

        ka.setSearchCrawlerInfo(_('Searching for new pages'));
        this.lastRq =
            new Request.JSON({url: _pathAdmin + 'admin/backend/searchIndexer/getNewUnindexedPages', noCache: 1,
                onComplete: function (pRes) {
                    if (pRes.access == false) {
                        return this.restart();
                    }

                    if (pRes.pages.length == 0) {
                        //no new pages, just keep up to date
                        this.step2();
                    } else {
                        //we found some pages and put'em to waitlist
                        this.step1();
                    }

                }.bind(this)}).post({crawlerId: this.id});
    },

    step2: function () {

        ka.setSearchCrawlerInfo(_('Keep index up to date'));

        this.lastRq = new Request.JSON({url: _pathAdmin + 'admin/backend/searchIndexer/getIndex', noCache: 1,
            onComplete: function (pRes) {
                if (pRes.access == false) {
                    return this.restart();
                }

                this.crawl(pRes.pages, this.done.bind(this));

            }.bind(this)}).post({crawlerId: this.id});
    },

    done: function () {
        ka.stopSearchCrawlerInfo(_('Crawling done'));
        this.start();
    },

    crawl: function (pList, pCallback) {

        this.pages = pList;
        ka.setSearchCrawlerProgress(0);
        this.crawlPage(0, pCallback);
    },

    crawlPage: function (pPos, pCallback) {

        var page = this.pages[pPos];
        if (!page) {
            ka.stopSearchCrawlerProgress();
            return pCallback.call();
        }

        ka.setSearchCrawlerProgress(pPos / (this.pages.length / 100));

        var page_url = page.url.substr(1, page.url.length); //cut first slash
        var url = page.path + page_url;
        if (page.master != 1) {
            url = page.path + page.lang + '/' + page_url;
        }

        var startTimeout = 100;
        if (ka.settings.user.autocrawler_minddelay) {
            startTimeout = ka.settings.user.autocrawler_minddelay;
        }

        if (this.lastDiff > 100) {
            startTimeout += this.lastDiff;
        }

        (function () {
            var start = new Date().getTime();
            this.lastRq = new Request.JSON({url: url, noCache: 1,
                onComplete: function (pRes) {
                    if (pRes && pRes.access == false) {
                        return this.restart();
                    }

                    this.lastDiff = new Date().getTime() - start;
                    this.crawlPage(pPos + 1, pCallback);

                }.bind(this)}).post({
                    enableSearchIndexMode: 1,
                    crawlerId: this.id,
                    jsonOut: 1,
                    kryn_domain: page.domain
                });
        }).delay(startTimeout, this);

        return true;

    }


});