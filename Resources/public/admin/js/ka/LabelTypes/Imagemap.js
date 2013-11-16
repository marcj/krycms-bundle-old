ka.LabelTypes.Imagemap = new Class({
    Extends: ka.LabelAbstract,

    options: {
        imageMap: {}
    },

    render: function(values) {
        var value = values[this.fieldId] || '', image;

        if (this.options.imageMap) {
            image = this.options.imageMap[value];
            if ('#' === image.substr(0, 1)) {
                return '<span class="' + ka.htmlEntities(image.slice(1))+ '"></span>';
            } else {
                return '<img src="' + _path + ka.htmlEntities(this.options.imageMap[value]) + '"/>';
            }
        }
    }
});