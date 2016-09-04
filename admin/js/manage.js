(function ( $ ) {

	$.fn.manager = function(opt, parameters) {
		var self = this;
		// If the passed value is an object or not provided, initialize the manager.
		if($.isPlainObject(opt) || $.type(opt) === 'undefined') {
			options = $.extend(
				{
					itemSelector: 'manage_item'
				},
				opt
			);
			$(this).data('options', options);
			console.log('saving options', options, $(this).data('options'));
			return this;
		}
		else if($.type(opt) === 'string') {
			options = $(this).data('options');
			console.log('manager performing ' + opt, arguments);
			switch(opt) {
				case 'init':
					return $.fn.manager.init(opt);
				case 'update':
					query = {query: parameters};
					spinner.start();
					habari_ajax.post(options.updateURL, query, self, function(){
						spinner.stop();
					});
					return this;
			}
		}
		return this;
	};

}( jQuery ));

