var admin_files_diff = new Class({

    initialize: function (pWindow) {
        this.win = pWindow;
        this.win.content.setStyle('overflow', 'auto');

        // Set flags for files
        this.win.params.filefrom.ready = false;
        this.win.params.fileto.ready = false;

        // Check that extension has been set
        if (!this.win.params.filefrom.ext) {
            this.win.params.filefrom.ext = '';
        }
        if (!this.win.params.fileto.ext) {
            this.win.params.fileto.ext = '';
        }

        // Set title
        if (this.win.params.filefrom.name && this.win.params.fileto.name) {
            this.win.setTitle(_('diff') + ' ' + this.win.params.filefrom.name);
        } else {
            this.win.setTitle(_('diff') + ' ' + this.win.params.filefrom.path);
        }

        this._createLayout();

        this.loadFileContents(this.win.params.filefrom.path, true);

    },

    loadFileContents: function (path, isFrom) {
        new Request.JSON({
            url: _pathAdmin + 'files/get',
            noCache: 1,

            onComplete: function (res) {
                var file = isFrom ? this.win.params.filefrom : this.win.params.fileto;
                file.contents = res;
                this.loadFineDiff();
            }.bind(this)
        }).get({
                path: path
            });
    },

    loadFineDiff: function () {
        new Request.JSON({
            url: _pathAdmin + 'files/diffFiles',
            noCache: 1,

            onComplete: function (res) {
                //this.div.set('html', res+'<p>');
                this.prepareDiff(res);
            }.bind(this)
        }).get({
                from: this.win.params.filefrom.path,
                to: this.win.params.fileto.path
            });
    },

    prepareDiff: function (opCodes) {
        // Clear div
        this.div.set('html', '');

        var edits = [];
        var editNr = 0;
        var index = 0, length = opCodes.length;
        var chr, op = "", nr = 0, add = "", sIndex = 0;

        while (index < length) {
            chr = opCodes.substring(index, index + 1);
            index++;

            if (chr.test('[0-9]')) {
                nr = nr * 10 + parseInt(chr);
            } else {
                // Is op code set?
                // If not, set it else execute diff mod
                if (op == "") {
                    op = chr;
                } else { // Execute diff
                    // If nr is 0, make it 1
                    if (nr == 0) {
                        nr = 1;
                    }

                    // If it is an insert, retrieve add from opCodes and update index
                    if (op == "i") {
                        add = opCodes.substring(index, index + nr);
                        index += nr;
                    }

                    // Perform diff
                    this.performDiff(op, nr, sIndex, add);

                    // Update source index when c or d are used
                    if (op == "c" || op == "d") {
                        sIndex += nr;
                    }

                    // Do not set : as opcode
                    if (chr == ":") {
                        chr = "";
                    }

                    // Reset vars
                    op = chr;
                    nr = 0;
                    add = "";
                }
            }
        }

        // Perform last opcode
        if (op != "") {
            // If nr is 0, make it 1
            if (nr == 0) {
                nr = 1;
            }

            // If it is an insert, retrieve add from opCodes and update index
            if (op == "i") {
                add = opCodes.substring(index, index + nr);
                index += nr;
            }

            // Perform diff
            this.performDiff(op, nr, sIndex, add);
        }
    },

    escapeChars: function (s) {
        var mod = s;

        mod =
            mod.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/ /g,
                "&nbsp;").replace(/\n/g, "&para;<br />");

        return mod;
    },

    performDiff: function (op, length, index, add) {
        //logger("performDiff("+op+", "+length+", "+index+", '"+add+"')");

        var c = this.win.params.filefrom.contents;
        var mod = "";

        if (op == "c") { // Copy
            mod = this.escapeChars(c.substring(index, index + length));
        } else if (op == "d") { // Delete
            mod = this.escapeChars(c.substring(index, index + length));
            mod = '<del>' + mod + '</del>';
        } else if (op == "i") { // Insert
            mod = this.escapeChars(add);
            mod = '<ins>' + mod + '</ins>';
        }

        // Update div with performed diff
        this.div.set('html', this.div.get('html') + mod);

        //logger(c.substr(index, index+length));
        //logger("perform done");
    },

    _createLayout: function () {
        this.div = new Element('div', {
            html: _('Loading ...'),
            styles: {
                width: '100%',
                height: '100%',
                'border': 0,
                'font-size': '14px',
                'font-family': 'Courier New',
                'padding': 0
            },
            'class': 'filesDiff',
            id: 'filesDiff_' + this.win.id
        }).inject(this.win.content);
    }
});
