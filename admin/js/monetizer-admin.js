(function( $ ) {
	'use strict';

	$(function() {
		if ($('#monetizer_modules_push').is(':checked')) {
			show_hide_prompt_fields()
			$('#monetizer_push_enable_prompt').on('change', prompt_enabled_clicked)
		}
		if ($('#monetizer_modules_links').is(':checked')) {
			if ($('#monetizer_links_url').val() === '') {
				populate_links_url();
			}
			$('#monetizer_links_link').on('change', populate_links_url);
			$('#monetizer_links_domain').on('change', populate_links_url);
		}
	});

	function prompt_enabled_clicked(ev) {
		show_hide_prompt_fields();
	}

	function show_hide_prompt_fields() {
		var enabled = $('#monetizer_push_enable_prompt').is(':checked');
		if (enabled) {
			$('#monetizer_push_prompt_text').closest('tr').show();
			$('#monetizer_push_prompt_accept_btn_text').closest('tr').show();
			$('#monetizer_push_prompt_deny_btn_text').closest('tr').show();
			$('#monetizer_push_prompt_hide_deny_btn').closest('tr').show();
		} else {
			$('#monetizer_push_prompt_text').closest('tr').hide();
			$('#monetizer_push_prompt_accept_btn_text').closest('tr').hide();
			$('#monetizer_push_prompt_deny_btn_text').closest('tr').hide();
			$('#monetizer_push_prompt_hide_deny_btn').closest('tr').hide();
		}
	}

	function populate_links_url() {
		var link = $('#monetizer_links_link').val();
		var domain = $('#monetizer_links_domain').val();
		var urlInput = $('#monetizer_links_url');
		if (link && domain) {
			var url = link.replace('__DOMAIN__', domain);
			urlInput.val(url);
		}
	}

})( jQuery );
