$(document).ready(function() {

	$('body').append('<div id="anchorTitle"></div>');

	$('a[data-nrdb]').each(function() {

		var a = $(this);

		a.data('data-nrdb', a.attr('data-nrdb'))
		.removeAttr('data-nrdb')
		.hover(
			function() { showAnchorTitle(a, a.data('data-nrdb')); }, 
			function() { hideAnchorTitle(); }
		);

	});

});

function showAnchorTitle(element, text) {

	var offset = element.offset();

	$('#anchorTitle')
	.append('<img src="'+text+'" />')
	.css({ 
		'top'  : (offset.top + element.outerHeight() + 4) + 'px',
		'left' : offset.left + 'px'
	})
	.show();

}

function hideAnchorTitle() {
  $('#anchorTitle').empty();
	$('#anchorTitle').hide();
}
