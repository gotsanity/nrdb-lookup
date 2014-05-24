(function() {
    tinymce.create('tinymce.plugins.nrdbembed', {
        init : function(ed, url) {
            ed.addButton('nrdbembed', {
                title : 'Embed NRDB Card',
                image : url+'/embed.png',
                onclick : function() {
                	var align = prompt("Align Left, Center, Right?", "left");
                	var size = prompt("Display larger image?", "yes");
                	if (align != null) {
                		if (size != null) {
                			if (size === "yes") {
	                			ed.selection.setContent('[nrdb embed="' + align + '" size="large"]' + ed.selection.getContent() + '[/nrdb]');
                			} else {
	                			ed.selection.setContent('[nrdb embed="' + align + '"]' + ed.selection.getContent() + '[/nrdb]');
                			}
                		} else {
											ed.selection.setContent('[nrdb embed="' + align + '"]' + ed.selection.getContent() + '[/nrdb]');
										}
									}
                }
            });
        },
        createControl : function(n, cm) {
            return null;
        },
    });
    tinymce.PluginManager.add('nrdbembed', tinymce.plugins.nrdbembed);
})();
