var admin_system_development_orm = new Class({


    initialize: function (pWin) {
        this.win = pWin;
        this._createLayout();

        if (this.win.params && this.win.params.doUpdate) {

        }
    },

    _createLayout: function () {

        this.win.content.empty();

        this.buttonBar = this.win.addBottomBar();

        this.btnEnv =
            this.buttonBar.addButton('Propel Build Environment', this.callPropelGen.bind(this, 'environment'));
        this.btnCheck = this.buttonBar.addButton('Check model.xml', this.callPropelGen.bind(this, 'check'));
        this.btnModel = this.buttonBar.addButton('Write FieldModel', this.callPropelGen.bind(this, 'models'));
        this.btnUpdate = this.buttonBar.addButton('Update Database', this.callPropelGen.bind(this, 'update'));
        this.btnAll = this.buttonBar.addButton('Do all', this.doAll.bind(this));

    },

    doAll: function () {
        this.callPropelGen(['check', 'models', 'update']);

    },

    callPropelGen: function (pCmd, pCb) {

        //prepare gui
        [this.btnEnv, this.btnCheck, this.btnModel, this.btnUpdate].invoke('setEnabled', false);
        this.win.content.empty();

        this.progressBar = new ka.Progress(t('Wait for action.'));
        this.progressBar.inject(this.win.content);

        this.resultContainer = new Element('div', {
            'class': 'selectable',
            style: 'position: absolute; left: 5px; right: 5px; top: 25px; bottom: 5px;'
                + 'border: 1px solid silver; background-color: white; white-space: pre; padding: 5px; overflow: auto;'
        }).inject(this.win.content);

        this._callGen(typeOf(pCmd) == 'array' ? pCmd : [pCmd], pCb);

    },

    done: function () {

        //activate gui
        [this.btnEnv, this.btnCheck, this.btnModel, this.btnUpdate].invoke('setEnabled', true);

    },

    requestCompleted: function (pResult, pCb) {

        var div = new Element('div').inject(this.resultContainer, 'top');

        new Element('div', {
            text: 'Command: ' + this.lastCmd
        }).inject(div);

        var success = false;

        if (pResult.error) {
            new Element('h2', {style: 'color: red', text: 'Failed ' + pResult.error}).inject(div);
            new Element('div', {html: pResult.message}).inject(div);

        } else if (typeOf(pResult.data) == 'array') {
            new Element('h2', {style: 'color: red', text: 'Failed'}).inject(div);
            new Element('div', {html: pResult.data.join('<br/>')}).inject(div);

        } else {
            success = true;
            new Element('h2', {style: 'color: green', text: 'Success'}).inject(div);
            new Element('div', {html: pResult.data}).inject(div);
        }

        new Element('hr').inject(div);

        this.progressBar.setText(t('Done.'));
        this.progressBar.setValue(100);

        this.done();
        if (typeOf(this.lastCb) == 'function') {
            this.lastCb(success);
        }

        this._callGenNext();
    },

    _callGenNext: function (pCb) {
        if (this.q.length == this.qi) {
            return;
        }

        var cmds = {
            'environment': 'Build environment',
            'check': 'Check model.xml',
            'models': 'Build models',
            'update': 'Update database'
        };

        var cmd = this.q[this.qi++];
        this.progressBar.setText(cmds[cmd] + ' ...');
        this.progressBar.setValue((this.qi - 1) / this.q.length * 100);
        this._callGen(cmd, pCb);
    },

    _callGen: function (pCmd, pCb) {

        if (this.displayWaitForAction) {
            clearTimeout(this.displayWaitForAction);
        }

        if (typeOf(pCmd) == 'array') {

            this.resultContainer.empty();
            this.progressBar.setText(t('Pending ...'));
            this.progressBar.setValue(0);
            this.q = pCmd;
            this.qi = 0;
            this._callGenNext(pCb);
            return;
        }

        this.lastCmd = pCmd;
        this.lastCb = pCb;

        this.lr = new Request.JSON({url: _pathAdmin + 'admin/system/orm/' + pCmd, noCache: 1,
            onComplete: this.requestCompleted.bind(this)}).get();

    }


});