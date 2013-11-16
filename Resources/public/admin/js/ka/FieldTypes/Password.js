ka.FieldTypes.Password = new Class({
    Extends: ka.FieldTypes.Text,

    Statics: {
        label: 'Password',
        asModel: true
    },

    createLayout: function () {
        this.parent();
        this.input.set('type', 'password');
    }
});