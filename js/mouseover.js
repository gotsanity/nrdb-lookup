(function() {
    tinymce.create('tinymce.plugins.nrdbmouseover', {
        init : function(ed, url) {
            ed.addButton('nrdbmouseover', {
                title : 'NRDB Mouseover',
                image : url+'/mouseover.png',
                onclick : function() {
                     ed.selection.setContent('[nrdb]' + ed.selection.getContent() + '[/nrdb]');
 
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('nrdbmouseover', tinymce.plugins.nrdbmouseover);
})();
