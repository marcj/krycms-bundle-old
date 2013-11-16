ka.Helpsystem = new Class({

    initialize: function (pDesktop) {
        this.desktop = pDesktop;

        this.boxes = [];

        this.container = new Element('div', {
            'class': 'ka-helpsystem-container'
        }).inject(this.desktop);

    },

    _update: function () {

        var size = ka.adminInterface.desktopContainer.getSize();
        var maxHeight = size.y - 10;
        var curHeight = 0;

        for (var i = this.boxes.length - 1; i > 0; i--) {
            var box = this.boxes[i];
            var index = i;

            curHeight += box.getSize().y;

            if (curHeight > maxHeight) {
                box.setStyle('height', maxHeight - 50);
            }
        }

    },

    newBubble: function (pTitle, pText, pDelay) {

        if (!ka.adminInterface.desktopContainer) {
            return;
        }

        var box = new Element('div', {
            'class': 'ka-helpsystem-bubble selectable',
            styles: {
                opacity: 0
            }
        }).inject(this.container, 'top');

        this.boxes.include(box);

        new Element('h3', {
            html: pTitle
        }).inject(box);

        if ('element' === typeOf(pText)) {
            pText.inject(box);
        } else if (pText) {
            new Element('div', {
                'class': 'ka-helpsystem-bubble-desc',
                html: pText
            }).inject(box);
        }

        var delay = 4000; //4sec
        if (pDelay) {
            delay = pDelay;
        }

        var die = (function () {
            box.set('tween', {onComplete: function () {
                box.destroy();
            }});
            box.tween('opacity', 0);
        });

        if (delay > 0) {
            die.delay(delay);
        }

        new Element('a', {
            style: "cursor: pointer; font-family: 'icomoon';",
            title: t('Move down'),
            html: '&#xe1b2;',
            'class': 'ka-helpsystem-bubble-closer'
        }).addEvent('click', die).inject(box);

        box.tween('opacity', 1);
        this._update();

        box.die = die;
        return box;
    }

});