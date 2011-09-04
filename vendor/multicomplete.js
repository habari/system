/*
 * Written by the HumanInternals team
 * URL: http://www.codenition.com/jquery-ui-multicomplete-widget-based-on-autocomplete
 * License: Dual licensed under MIT and GPL license
 */

(function($ ,$a, $p){ // getting $p as a parameter doesn't require me to "var $p=..." and saves a two bytes ;-) ("var " versus ",x" in argument list [when minifier is shrinking variables])
	$p=$a.prototype;
	$.widget('ui.multicomplete', $a, {
		// Search for everything after the last "," instead of the whole input contents
		_search: function(value){
			$p._search.call(this, value.match(/\s*([^,]*)\s*$/)[1]);
		},
		// Overwrite _trigger to make custom handling for events while still allowing user callbacks
		// Setting my own callbacks on this.options or binding using .bind() doesn't allow me to properly handle user callbacks, as this must be called AFTER the user callbacks are executed (which isn't possible by bind()ing when this.options[] is set)
		_trigger: function(type, event, data) {
			// call "real" triggers
			var ret = $p._trigger.apply(this, arguments);
			// When its select event, and user callback didn't return FALSE, do my handling and return false
			if (type == 'select' && ret !== false) {
				// When a selection is made, replace everything after the last "," with the selection instead of replacing everything
				var val=this.element.val();
				this.element.val(val.replace(/[^,]+$/,(val.indexOf(',') != -1 ?' ':'')+data.item.value));
				ret = false;
			}
			// Force false when its the focus event - parent should never set the value on focus
			return (type == 'focus' ? false : ret);
		},
		_create:function(){
			var self=this;
			// When menu item is selected and TAB is pressed focus should remain on current element to allow adding more values
			this.element.keydown(function(e){
				self.menu.active && e.keyCode == $.ui.keyCode.TAB && e.preventDefault();
			});
			$p._create.call(this);
		},
		_initSource: function() {
			// Change the way arrays are handled by making autocomplete think the user sent his own source callback instead of an array
			// The way autocomplete is written doesn't allow me to do it in a prettier way :(
			if ( $.isArray(this.options.source) ) {
				var array = this.options.source, self = this;
				this.options.source = function( request, response ) {
					response( self.filter(array, request) ); // Use our filter() and pass the entire request object so the filter can tell what's currently selected
				};
			}
 
			// call autocomplete._initSource to create this.source function according to user input
			$p._initSource.call(this);
 
			// Save a copy of current source() function, than new source() sets request.selected and delegate to original source
			var _source = this.source;
			this.source = function(request, response) {
				request.selected = this.element.val().split(/\s*,\s*/);
				request.selected.pop(); // don't include the term the user is currently writing as selected
				_source(request, response);
			};
			// TODO: instead of overwritting this.source, I can overwrite _search which is easier, but than I'll have to repeat jQuery-UI's code that might change
		},
 
		// Like $.ui.autocomplete.filter, but excludes items that are already selected
		filter: function(array, request) {
			return $.grep($a.filter(array, request.term),function(value){return $.inArray(value, request.selected) == -1;});
		}
	});
})(jQuery, jQuery.ui.autocomplete);