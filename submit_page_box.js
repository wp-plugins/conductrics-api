jQuery(document).ready(function($) {

	$('#conductrics_test').click(function() {
		if ( $(this).is(':checked') ) {
			$('#conductrics_test_params').show('blind');
		} else {
			$('#conductrics_test_params').hide('blind');	
		}
		
	});

	//enable this button after the page is done loading
	$('#conductrics_test').removeAttr('disabled');

	var spinner_settings = {
		step: 0.1
	};

	$('.conductrics-goal-spinner').spinner(spinner_settings);

	$('#conductrics_add_option').click(function() {
		conductrics_add_dropdown('option');
	});

	$('#conductrics_add_goal').click(function() {
		conductrics_add_dropdown('goal');
	});

	$('.dropdown-check').live('click', function() {
		$(this).parent().parent().remove();
	});

	function conductrics_add_dropdown(id_part) {
		var selects = $('#conductrics_' + id_part).find('select');

		var copy = $('.conductrics-' + id_part + '-boilerplate')
			.clone()
			.removeClass('conductrics-' + id_part + '-boilerplate')
			.insertBefore('#add_' + id_part + '_container')
			.show();

		copy.find('select[name="pages_' + id_part + '_boilerplate"]')
			.removeAttr('id')
			.attr('name', 'conductrics_' + id_part + '_id_' + selects.length);

		copy.find('.conductrics-new-spinner')
			.attr('name', 'conductrics_goal_value_' + selects.length);

		$('.conductrics-new-spinner').spinner(spinner_settings);
	}

});