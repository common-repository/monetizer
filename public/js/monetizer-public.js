(function( $ ) {
	'use strict';

	$(function() {
		$('body').on('click', onBodyClicked);
	});

	function onBodyClicked(ev) {
		if (isPopsEnabled() && localStorage) {
			let key = 'mtzrudata';
			let pops = monetizerPublicSettings.pops;
			let data = JSON.parse(localStorage.getItem(key));
			let now = Date.now();
			if (!data || data.expires < now) {
				data = {
					count: pops.frequency,
					lastTs: 0,
					expires: now + pops.period * 3600000
				};
			}

			if (now < data.lastTs + pops.delay || data.count <= 0) {
				return;
			}

			data.lastTs = now;
			data.count -= 1;
			localStorage.setItem(key, JSON.stringify(data));

			// should be called after setItem to prevent endless loop
			openUrl(pops.url);
		}
	}

	function isPopsEnabled() {
		return monetizerPublicSettings && monetizerPublicSettings.pops && monetizerPublicSettings.pops.enabled
	}

	function openUrl(url) {
		if (!url) {
			return;
		}
		let link = document.createElement('a');
		link.href = url;
		link.target = '_blank';
		link.rel = 'noopener';
		document.body.appendChild(link);
		link.click();
		link.parentNode.removeChild(link);
	}

})( jQuery );
