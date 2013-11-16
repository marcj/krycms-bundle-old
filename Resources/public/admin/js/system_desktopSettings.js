var admin_system_desktopSettings = new Class({

    initialize: function (pWin) {

        this.win = pWin;

        this.createLayout();
        this.loadDefaultImages();

    },

    createLayout: function () {

        this.actionBar = this.win.addBottomBar();
        this.actionBar.addButton(t('Apply'), this.save.bind(this));

        this.defaultImages = new Element('div', {
            'class': 'admin-system-desktopSettings-defaultImages',
            style: "height: 150px; margin: 15px; border: 1px solid #ccc; background-color: white; overflow: auto;"
        }).inject(this.win.content);

        this.options = new Element('div', {
            style: 'margin: 15px;'
        }).inject(this.win.content);

        this.fieldBg =
            new ka.Field({label: t('Background image'), type: 'fileChooser'}).addEvent('change', function (pValue) {
                this.choose(pValue);
            }.bind(this)).inject(this.options);
        this.fieldBg.setValue(ka.settings.user.userBg);

        var _this = this;
        this.fieldBg.input.addEvent('keyup', function () {
            _this.choose(this.value);
        });

    },

    loadDefaultImages: function () {
        var _this = this;

        new Request.JSON({url: _pathAdmin +
            'admin/backend/getDefaultImages', noCache: 1, onComplete: function (pFiles) {
            pFiles.each(function (file) {

                file = '/admin/images/userBgs/defaultImages/' + file;
                bg = _pathAdmin + 'admin/backend/imageThumb/?' + Object.toQueryString({path: file});

                var img = new Element('img', {
                    src: bg
                }).inject(this.defaultImages);

                img.addEvent('click', function () {
                    _this.defaultImages.getElements('img').set('class', '');
                    this.set('class', 'active');
                    _this.choose(file, true);
                });

                if (file == ka.settings.user.userBg) {
                    this.defaultImages.getElements('img').set('class', '');
                    img.set('class', 'active');
                }

            }.bind(this));

        }.bind(this)}).post();
    },

    choose: function (pFile, pWithSet) {
        if (pFile.substr(0, 1) != '/') {
            pFile = '/' + pFile; //this.fieldBg.input.value.substr( 1, this.fieldBg.input.value.length );
            this.fieldBg.setValue(pFile);
        }
        ka.settings.user['userBg'] = pFile;
        $(document.body).setStyle('background-image', 'url(' + _path + ka.settings.user.userBg + ')');
        if (pWithSet) {
            this.fieldBg.setValue(ka.settings.user.userBg);
        }
        ka.saveUserSettings();
    },

    save: function () {

        this.win.close();

    }

});
