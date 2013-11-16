var admin_files_tinyImage = new Class({
    initialize: function (pWin) {
        new Element('iframe', {
            width: '100%',
            height: '100%',
            src: '/inc/tinymce/jscripts/tiny_mce/plugins/advimage/image.htm'
        }).inject(pWin.content);
    }
});
