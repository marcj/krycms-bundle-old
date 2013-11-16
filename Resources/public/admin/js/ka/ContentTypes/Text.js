ka.ContentTypes = ka.ContentTypes || {};

ka.ContentTypes.Text = new Class({
    Extends: ka.ContentAbstract,

    Statics: {
        icon: 'icon-font',
        label: 'Rich Text'
    },

    options: {
        /**
         * Sets the height of the editor to a fix value. Default is auto.
         *
         * @var {String}
         */
        inputHeight: null,

        /**
         * Sets the width of the editor to a fix value. Default is auto.
         *
         * @var {String}
         */
        inputWidth: null,

        config: 'standard',

        configs: {
            standard: {
                plugins: [
                    "advlist autolink autosave link image lists charmap print preview hr anchor pagebreak spellchecker",
                    "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
                    "table contextmenu directionality emoticons template textcolor paste fullpage textcolor"
                ],
                menubar: false,
                toolbar_items_size: 'small',
                toolbar1: "undo redo | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | styleselect formatselect fontselect fontsizeselect",
                toolbar2: "searchreplace | bullist numlist | outdent indent blockquote | link unlink anchor image media code | forecolor backcolor",
                toolbar3: "table | hr removeformat | subscript superscript | charmap emoticons | ltr rtl | visualchars visualblocks nonbreaking template pagebreak"
            },
            full: {
                plugins: [
                    "advlist autolink lists link image charmap print preview hr anchor pagebreak",
                    "searchreplace wordcount visualblocks visualchars code fullscreen",
                    "insertdatetime media nonbreaking save table contextmenu directionality",
                    "emoticons template paste textcolor"
                ],
                toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
                toolbar2: "print preview media | forecolor backcolor emoticons",
                tools: "inserttable"
            }
        }
    },

    focus: function() {
        this.main.focus();
    },

    createLayout: function() {
        this.main = new Element('div', {
            contentEditable: true,
            html: '<p><br/></p>',
            'class': 'ka-content-text selectable'
        }).inject(this.getContentInstance());

        this.main.addEvent('focus', function(e) {
            this.getEditor().click(e, this.main.getParent('.ka-content'));
        }.bind(this));

        var id = (Math.random() * 10 * (Math.random() * 10)).toString(36).slice(2).replace('.', '');

        this.toolbar = new Element('div', {
            'id': id
        }).inject(document.body);

        var config = this.options.configs[this.options.config];

        tinymce.init(Object.merge({
            mode: 'exact',
            inline: true,
            elements: [this.main],
            content_document: this.main.getDocument(),
            content_window: this.main.getWindow(),
            fixed_toolbar_container: '#' + id,
            setup: function(editor) {
                this.editor = editor;
                this.ready = true;
                if (this.value) {
                    this.main.set('html', this.value);
                }
                editor.on('change', function(ed) {
                    this.checkChange();
                }.bind(this));
            }.bind(this)
        }, config));

        this.main.addEvent('keyup', function(ed) {
            this.checkChange();
        }.bind(this));
    },

    checkChange: function() {
        if (this.ready) {
            if (this.oldData != this.getValue()) {
                this.fireChange();
                this.oldData = this.getValue();
            }
        }
    },

    setValue: function(pValue) {
        this.value = pValue;
        this.oldData = this.value;
        this.main.set('html', this.value || '<p><br/></p>');
    },

    getValue: function() {
        return this.main.get('html');
    },

    deselected: function() {
        this.toolbar.dispose();
        this.toolbarContainer.destroy();
        delete this.isSelected;
    },

    selected: function(inspectorContainer) {
        this.isSelected = true;
        this.toolbarContainer = new Element('div', {
            'class': 'ka-content-text-toolbarContainer kryn_wysiwyg_toolbar'
        }).inject(inspectorContainer);
        this.toolbar.inject(this.toolbarContainer);
    }
});

