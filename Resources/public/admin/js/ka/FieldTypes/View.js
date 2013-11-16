ka.FieldTypes.View = new Class({

    Extends: ka.FieldTypes.Select,

    Statics: {
        asModel: true,
        options: {
            directory: {
                label: 'Path to directory',
                type: 'text',
                desc: 'Example: @CoreBundle/folder1/'
            },
            fullPath: {
                label: 'Full path',
                desc: 'Returns and uses the full path instead of the relative to the `directory` option.',
                type: 'checkbox'
            }
        }
    },

    options: {
        inputWidth: '100%',
        directory: '',
        fullPath: false
    },

    module: '',
    path: '',

    initialize: function (pFieldInstance, pOptions) {

        pOptions.object = 'core:view';

        if (!pOptions.directory) {
            throw 'Option `directory` is empty in ka.Field `view`.';
        }

        if (pOptions.directory.substr(0, 1) == '/') {
            pOptions.directory = pOptions.directory.substr(1);
        }

        if (pOptions.directory.substr(pOptions.directory.length - 1, 1) != '/') {
            pOptions.directory += '/';
        }

        var sIdx = pOptions.directory.indexOf('/');
        this.module = pOptions.directory.substr(0, sIdx);
        this.path = pOptions.directory.substr(sIdx + 1);

        pOptions.objectBranch = pOptions.directory ? pOptions.directory : true;
        this.parent(pFieldInstance, pOptions);
    },

    getValue: function () {
        var value = this.parent();
        value = value.path || '';
        return this.options.fullPath ? value : value.substr((this.module + '/' + this.path).length);
    },

    setValue: function (pValue) {
        if (pValue && !this.options.fullPath) {
            pValue = (this.module + '/' + this.path) + pValue;
        }
        this.parent(pValue);
    }
});