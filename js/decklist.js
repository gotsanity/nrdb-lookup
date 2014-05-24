(function() {
    tinymce.create('tinymce.plugins.nrdbdecklist', {
        init : function(ed, url) {
            ed.addButton('nrdbdecklist', {
                title : 'NRDB Decklist',
                image : url+'/decklist.png',
                onclick : function() {
                	var deckid = prompt("What is the ID of the deck (the number in the url of the decklist)?");
                	if (deckid != null) {
										ed.selection.setContent('[nrdb decklist="' + deckid + '"]' + ed.selection.getContent() + '[/nrdb]');
									}
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('nrdbdecklist', tinymce.plugins.nrdbdecklist);
})();
