(function() {

	function clickAddGroup() {
		var i = 0;
		var $group = $(this).closest('div');
		var $group_clone = $group.closest('.group_clone');
		var $new_group = $group.clone();

		// Give radio buttons a temporary random name
		$group_clone.find('input[type=radio]').each(function() {
			$(this).attr('name', 'temporary_random_name_' + Math.floor(Math.random() * Math.floor(999999)));
		});


		if($new_group.find('.remove_group').length == 0) {
			$new_group.append('<p><a href="#" class="button remove_group">Remove Group</a></p>');
		}

		$new_group.find('input[type=radio]').each(function() {
			$(this).attr('name', 'temporary_random_name_' + Math.floor(Math.random() * Math.floor(999999)));
			$(this).prop('checked', false);
		});

		$new_group.find('input[type=text]').each(function() {
			$(this).val('');
		});

		$new_group.find('option').each(function() {
			$(this).prop('selected', false);
		});

		$new_group = $group.after($new_group);


		$group_clone.find('.group').each(function() {
			if($(this).find('input[type=radio]').length) {
				$(this).find('input[type=radio]').each(function() {
					var base_name = $(this).attr('data-base-name');
					$(this).attr('name', base_name + '_' + i);
				});
				i++;
			}
		});

		$group_clone.find('[data-count=yes]').attr('value', $group_clone.find('.group').length);

		return false;
	}

	function clickRemoveGroup() {
		var i = 0;
		var $group = $(this).closest('div');
		var $group_clone = $group.closest('.group_clone');
		$group.remove();

		$group_clone.find('.group').each(function() {
			if($(this).find('input[type=radio]').length) {
				$(this).find('input[type=radio]').each(function() {
					var base_name = $(this).attr('data-base-name');
					$(this).attr('name', base_name + '_' + i);
				});
				i++;
			}
		});

		$group_clone.find('[data-count=yes]').attr('value', $group_clone.find('.group').length);

		return false;
	}

	function init() {
		$(document).on('click', '.add_group', clickAddGroup);
		$(document).on('click', '.remove_group', clickRemoveGroup);
	}

	jQuery(document).ready(function(jquery) {
		$ = jquery;
		init();
	});
})();
