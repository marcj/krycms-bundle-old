var admin_files_edit = new Class({

    __images: ['jpg', 'bmp', 'png', 'gif', 'jpeg'],

    initialize: function (pWindow) {
        this.win = pWindow;
        this.win.content.setStyle('overflow', 'hidden');

        if (this.win.params.file.name) {
            this.win.setTitle(this.win.params.file.name + ' ' + _('edit'));
        } else {
            this.win.setTitle(this.win.params.file.path.substr(this.win.params.file.path.lastIndexOf('/')) + ' ' +
                _('edit'));
        }
        this._createLayout();
    },

    loadFile: function () {
        new Request.JSON({url: _pathAdmin + 'admin/files/getContent', noCache: 1, onComplete: function (res) {
            this.renderCodeMirror(res);
        }.bind(this)}).get({ path: this.win.params.file.path });
    },

    renderCodeMirror: function (pContent) {

        if (!pContent) {
            pContent = '';
        }

        var type = this.win.params.file.path.substr(this.win.params.file.path.lastIndexOf('.') + 1);
        var lineNumbers = false;
        var json;

        var mode = 'htmlmixed';

        if (type == 'css') {
            mode = 'css';
        }

        if (type == 'js') {
            mode = 'javascript';
        }

        if (['php', 'php3'].contains(type)) {
            mode = 'php';
        }

        if (type == 'json') {
            mode = 'javascript';
            json = true;
        }

        if (['php', 'javascript', 'htmlmixed', 'css'].contains(mode)) {
            lineNumbers = true;
        }

        try {
            this.editor = CodeMirror(this.fileContainer, {
                value: pContent,
                lineNumbers: lineNumbers,
                json: json,
                mode: mode
            });
        } catch (e) {
            this.fileContainer.set('text', pContent);
        }
    },

    save: function () {
        var _this = this;
        this.saveBtn.startTip(_('Save ...'));
        var value = (this.editor) ? this.editor.getValue() : this.fileContainer.get('html');
        new Request.JSON({url: _pathAdmin + 'admin/files/setContent', noCache: 1, onComplete: function (res) {
            this.saveBtn.stopTip(t('Saved'));
        }.bind(this)}).post({ path: this.win.params.file.path, content: value });
    },

    _createLayout: function () {

        if (!this.__images.contains(this.win.params.file.path.substr(this.win.params.file.path.lastIndexOf('.') +
            1).toLowerCase())) {
            var boxNavi = this.win.addButtonGroup();
            this.fileSaveGrp = boxNavi;
            this.saveBtn =
                boxNavi.addButton(_('Save'), _path + 'bundles/admin/images/button-save.png', this.save.bind(this));
            this.fileContainer = new Element('div', {
                value: t('Loading ...'),
                'class': 'admin-files-edit-fileContainer'
            }).inject(this.win.content);
            this.loadFile();
        } else {

            this.win.setTitle(_('Image %s').replace('%s', this.win.params.file.name));
            //var boxNavi = this.win.addButtonGroup();
            //boxNavi.hide();

            this.loader = new ka.Loader(true).inject(this.win.content);
            this.loader.show();
            this.win.content.setStyle('overflow', 'hidden');
            //          boxNavi.addButton( scroller );

            this.imageDiv = new Element('div', {
                style: 'position: absolute; bottom: 60px; top: 0px; left: 0px; right: 150px; overflow: auto; background-color: white'
            }).inject(this.win.content);

            this.bottom = new Element('div', {
                style: 'position: absolute; bottom: 0px; left: 0px; right: 150px; height: 58px; border-top: 1px solid silver; background-color: #f4f4f4;'
            }).inject(this.win.content);

            this.sidebar = new Element('div', {
                style: 'position: absolute; bottom: 0px; top: 0px; right: 0px; width: 148px; border-left: 1px solid silver; background-color: #eee; overflow: hidden; overflow-y: scroll; text-align: center;'
            }).inject(this.win.content);

            this.sidebarActions = new Element('div', {
                style: 'text-align: center; padding-top: 5px;'
            }).inject(this.bottom);

            var scroller = new Element('div', {
                'class': 'kwindow-win-buttonGroup',
                style: 'width: 150px; height: 20px; position: absolute; right: 10px; top: 23px;'
            }).addEvent('mousedown',
                function (e) {
                    e.stop();
                    e.stopPropagation();
                }).inject(this.sidebarActions);

            this.scrollerInfo = new Element('div', {
                text: '100%',
                style: 'position: absolute; height: 20px; top: 3px; width: 100%; text-align: center; color: #333;'
            }).inject(scroller);

            var scrollerItem = new Element('div', {
                'class': 'kwindow-win-buttonGroup-scrollerItem',
                style: 'position: absolute; height: 20px; width: 15px;background-color: gray; top: 0px;',
                styles: {
                    opacity: 0.7
                }
            }).inject(scroller);

            this.slider = new Slider(scroller, scrollerItem, {
                steps: 150,
                onChange: this.onSlide.bind(this)
            }).set(100);

            this.step = 100;

            this.imgInfo = new Element('div', {
                'html': _('Loading image'),
                style: 'text-align: right; height: 20px; position: absolute; right: 10px; top: 4px;'
            }).inject(this.sidebarActions);

            new Element('img', {
                src: _path + 'bundles/admin/images/icons/arrow_turn_left.png',
                title: _('Rotate 90° left'),
                style: 'cursor: pointer;'
            }).addEvent('click', this.rotate.bind(this, 'left')).inject(this.sidebarActions);

            /*this.saveBtn = new Element('img', {
             src: _path+'bundles/admin/images/button-save.png',
             style: 'margin-left: 12px; cursor: pointer;',
             title: _('Save')
             }).inject( this.sidebarActions );
             this.saveBtn.setStyle('opacity', 0.4);*/

            new Element('img', {
                src: _path + 'bundles/admin/images/icons/arrow_turn_right.png',
                style: 'margin-left: 12px; cursor: pointer;',
                title: _('Rotate 90° right')
            }).addEvent('click', this.rotate.bind(this, 'right')).inject(this.sidebarActions);

            var resizeDiv = new Element('div', {
                style: 'position: absolute; left: 0px; border-right: 1px solid #ddd; top: 1px; bottom: 1px; width:200px;'
            }).inject(this.sidebarActions);

            this.resizeWidth = new Element('input', {'class': 'text', style: 'width: 50px;'}).inject(resizeDiv);
            new Element('span', {text: ' x '}).inject(resizeDiv);
            this.resizeHeight = new Element('input', {'class': 'text', style: 'width: 50px;'}).inject(resizeDiv);
            new Element('span', {html: '<br />'}).inject(resizeDiv);
            new ka.Button(_('Resize to this dimension')).addEvent('click', function () {

                this.resize(this.resizeWidth.value, this.resizeHeight.value);

            }.bind(this)).inject(resizeDiv);

            this.imageScroller = new Fx.Scroll(this.sidebar, {
                transition: Fx.Transitions.Expo.easeInOut,
                offset: {'x': -7, 'y': -7}
            });

            var table = new Element('table', {style: 'width: 100%; height: 100%;'}).inject(this.imageDiv);
            var body = new Element('tbody').inject(table);
            var tr = new Element('tr').inject(body);
            var td = new Element('td', {
                style: 'width: 100%; height: 100%;',
                align: 'center', valign: 'center'
            }).inject(tr);
            this.td = td;

            var fId = 'adminFilesImgOnLoad' + new Date().getTime() + ((Math.random() + "").replace(/\./g, ''));
            window[fId] = function () {
                this.imgLoaded();
            }.bind(this);

            var path = this.win.params.file.path;

            this.oriImagePath = path;

            var path = _path + 'admin/backend/showImage?' +
                Object.toQueryString({path: path, noCache: (new Date()).getTime()});
            this.img = new Element('img', {
                src: path,
                onLoad: fId + '()'
            }).inject(td);

            this.win.content.setStyle('overflow', 'auto');

            this._loadImageSidebar();
        }
        this.win.content.setStyle('background-color', 'white');
    },

    rotate: function (pPos) {

        var loader = new ka.Loader(true, true).inject(this.img.getParent());
        loader.show();

        if (this.lastRotateRq) {
            this.lastRotateRq.cancel();
        }

        this.lastRotateRq =
            new Request.JSON({url: _pathAdmin + 'admin/files/rotate', noCache: 1, onComplete: function () {

                if (this._images[ this.oriImagePath ]) {
                    this._images[ this.oriImagePath ].src =
                        _path + 'admin/backend/imageThumb/?'
                            + Object.toQueryString({path: this.oriImagePath, mtime: (new Date).getTime()});
                }

                loader.hide();
                this.loadImage(this.oriImagePath);
            }.bind(this)}).post({position: pPos, path: this.oriImagePath });
    },

    resize: function (pWidth, pHeight) {

        var loader = new ka.Loader(true, true).inject(this.img.getParent());
        loader.show();

        if (this.lastRotateRq) {
            this.lastRotateRq.cancel();
        }

        this.lastRotateRq =
            new Request.JSON({url: _pathAdmin + 'admin/files/resize', noCache: 1, onComplete: function (pMtime) {
                this.loadImage(this.oriImagePath);

                if (this._images[ this.oriImagePath ]) {
                    this._images[ this.oriImagePath ].src =
                        _path + 'admin/backend/imageThumb/?'
                            + Object.toQueryString({path: this.oriImagePath, mtime: (new Date).getTime()});
                }

                loader.hide();
            }.bind(this)}).post({width: pWidth, height: pHeight, path: this.oriImagePath });

    },

    _loadImageSidebar: function () {

        new Element('img', {
            src: _path + 'bundles/admin/images/loading.gif'
        }).inject(this.sidebar);

        var path = this.win.params.file.path.substr(0, this.win.params.file.path.lastIndexOf('/'));
        if (!path) {
            path = '/';
        }

        new Request.JSON({url: _pathAdmin + 'admin/files/getImages', noCache: 1, onComplete: function (res) {
            this.sidebar.empty();
            this._images = {};
            if (res) {
                res.each(function (item) {

                    this._images[item.path] = new Element('img', {
                        'class': 'admin-files-sidebar-image' +
                            ((item.path == this.oriImagePath) ? ' admin-files-sidebar-image-active' : ''),
                        src: _pathAdmin + 'admin/backend/imageThumb/?' +
                            Object.toQueryString({path: item.path, mtime: item.mtime})
                    }).addEvent('click', function () {
                            this._goToImage(item.path, true);
                        }.bind(this)).inject(this.sidebar);

                }.bind(this));
            }
            this._goToImage(this.win.params.file.path);

        }.bind(this)}).post({path: path});

    },

    _goToImage: function (pItem, pWithView) {

        var image = this._images[ pItem ];
        if (!image) {
            return;
        }

        Object.each(this._images, function (item) {
            item.set('class', 'admin-files-sidebar-image');
        });

        image.addClass('admin-files-sidebar-image-active');

        var pos = image.getPosition(this.sidebar);
        this.imageScroller.start(0, pos.y + this.sidebar.getScroll().y);

        if (pWithView) {
            if (this.img) {
                this.img.destroy();
                this.td.empty();
                new Element('img', {
                    src: _path + 'bundles/admin/images/loading.gif',
                }).inject(this.td);
            }

            this.loadImage(pItem);
        }

    },

    loadImage: function (pImage) {

        this.td.empty();

        var loader = new ka.Loader(true, true).inject(this.td);
        loader.show();

        this.win.params = {file: {path: pImage}};
        this.win.setTitle(_('Image %s').replace('%s', pImage.substr(pImage.lastIndexOf('/'))));

        var path = _path + 'admin/backend/showImage?' +
            Object.toQueryString({path: pImage, noCache: (new Date()).getTime()});
        this.img = new Asset.image(path, {
            onLoad: function () {
                this.td.empty();
                this.img.inject(this.td);
                this.imgLoaded();
            }.bind(this)
        });

        var qPos = pImage.indexOf('?');
        if (qPos > 0) {
            pImage = pImage.substr(0, qPos);
        }

        this.oriImagePath = pImage;

    },

    onSlide: function (step) {
        this.step = step;
        this.scrollerInfo.set('text', step + '%');

        var faktor = step / 100;

        if (!this.imgSize) {
            return;
        }

        var newX = this.imgSize.x * faktor;
        this.img.width = newX;
        var newY = this.imgSize.y * faktor;
        this.img.height = newY;

        this.resizeHeight.value = this.img.height;
        this.resizeWidth.value = this.img.width;
    },

    calcMax: function () {

        var size = this.imageDiv.getSize();
        size.x -= 3;
        size.y -= 3;

        var faktor = 1;

        if (this.imgSize.x > size.x) {
            //trim to max size.x
            faktor = this.imgSize.x / size.x;
        }

        if (this.imgSize.y / faktor > size.y) {
            //height is still to height
            faktor = this.imgSize.y / size.y;
        }

        proz = (100 / faktor);
        var pos = Math.floor(proz);
        this.slider.set(pos);
        this.onSlide(pos);
    },

    imgLoaded: function () {
        this.imgSize = {x: this.img.width, y: this.img.height };

        this.imgInfo.set('text', _('Resolution: %s').replace('%s', this.img.width + 'x' + this.img.height));
        this.loader.hide();

        this.calcMax();
        this.win.content.setStyle('overflow', 'auto');
    }
});
