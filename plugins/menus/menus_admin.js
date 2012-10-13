habari.menu_admin = {
	submit_menu_item_edit: function (e) {
		$.post(
			$('#menu_item_edit').attr('action'),
			$('.tm_db_action').serialize(),
			function(data){
				$('#menu_popup').html(data);
			}
		);
		return false;
	},
	submit_menu_update: function(el) {
		$.post(
			$(el).attr('action'),
			$(el).serialize(),
			function(data){
				$('#menu_popup').html(data);
			}
		);
	},
	init_link_buttons: function() {
		$("a.modal_popup_form").click(
			function(){
				$('#menu_popup').html('');
				$("#menu_popup")
					.load($(this).attr("href"))
					.dialog({
						title: $(this).text(),
						width: 500
					});
				return false;
			}
		);
	},
	init_form: function() {
		findChildren();
		controls.init();
		habari.menu_admin.init_link_buttons()
	}
}

$(function(){
	habari.menu_admin.init_link_buttons();
});
