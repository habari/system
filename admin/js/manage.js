(function ( $ ) {

	$.fn.manager = function(opt, parameters) {
		var self = this;
		self.searchRows = [];
		self.searchCache = [];
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
					// Create URL parameter list for this query and save page on the way
					var tags = [];
					var params = [];
					$(this).data('page', 1);
					for(var param in parameters) {
						for(var prop in parameters[param]) {
							if(prop == 'tag') {
								tags.push(parameters[param][prop]);
							}
							else {
								if(prop == 'page') {
									$(this).data('page', parameters[param][prop]);
								}
								params.push(prop + "=" + parameters[param][prop]);
							}
						}
					}
					if(tags.length > 0) {
						params.push("tag=" + tags.join(','));
					}
					$(this).data('querystring', params.join('&'));

					// Do the actual update
					query = {query: parameters};
					spinner.start();
					habari_ajax.post(options.updateURL, query, self, function(){
						spinner.stop();
						// if after_update is set, we expect it to be the name of a global function
						if(typeof window[options.after_update] == 'function') {
							window[options.after_update]();
						}
					});
					return this;
				case 'quicksearch':
					if(arguments.length > 1 && arguments[1].length > 0) {
						search = arguments[1][0].text; // .text is the facet, it's the only one we use currently
						search = $.trim( search.toLowerCase() );
					}
					else {
						search = '';
					}

					// cache search items on first call
					if ( self.searchCache.length === 0 ) {
						self.searchRows = $('li.item, .item.plugin, .item.tag');
						self.searchCache = self.searchRows.map(function() {
							return $(this).text().toLowerCase();
						});
					}

					self.searchCache.each(function(i) {
						if ( this.search( search ) == -1 ) {
							$(self.searchRows[i]).hide();
						} else {
							$(self.searchRows[i]).show();
						}
					});

					return this;
			}
		}
		return this;
	};

}( jQuery ));

