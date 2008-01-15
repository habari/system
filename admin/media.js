habari.media = {

	showdir: function (path, el, container) {
		$('.media_browser', container).show();
		$('.media_panel', container).hide();
		if(!el && !container) {
			container = $('.mediasplitter:visible');
		}
		if(el && !container) {
			container = $(el).parents('.mediasplitter');
		}
		if($('.pathstore', container).html() != path || $('.pathstore.toload', container).size()) {
			$.post(
				habari.url.habari + '/admin_ajax/media',
				{
					path: path
				},
				function(result) {
					$('.pathstore', container).html(result.path);
					var output = '<div class="media_dirlevel"><table>';
					for(var dir in result.dirs) {
						output += '<tr><td><a class="directory" href="#" onclick="return habari.media.clickdir(this, \'' + result.dirs[dir].path + '\');">' + result.dirs[dir].title + '</a></td></tr>';
					}
					output += '</table></div>';
					if(el) {
						$($(el).parents('.media_dirlevel')).nextAll().remove();
						$('.directory', $(el).parents('.media_dirlevel')).removeClass('active');
						$(el).addClass('active');
						$(el).parents('.media_dirlevel').after(output);
					}
					else {
						$('.mediadir', container).html(output);
					}
					output = '<table><tr>';
					var first = ' first';
					for(var file in result.files) {
						stats = '';
						output += '<td><div class="media' + first + '"><div class="mediatitle">' + result.files[file].title + '</div><img src="' + result.files[file].thumbnail_url + '"><div class="mediastats"> ' + stats + '</div><div class="foroutput"><img src="' + result.files[file].url + '"></div></div></td>';
						first = '';
					}
					output += '</tr></table>';
					$('.mediaphotos', container).html(output);
					$('.media').dblclick(function(){
						habari.editor.insertSelection($('.foroutput', this).html());
					});
					$('.media_controls ul li:first', container).nextAll().remove();
					$('.media_controls ul li:first', container).after(result.controls);
				},
				'json'
			);
		}
	},

	clickdir: function(el, path) {
		this.showdir(path, el);
		return false;
	},

	showpanel: function (path, panel) {
		$.post(
			habari.url.habari + '/admin_ajax/media_panel',
			{
				path: path,
				panel: panel
			},
			habari.media.jsonpanel,
			'json'
		);
	},

	jsonpanel: function(result) {
		container = $('.mediasplitter:visible');
		$('.media_controls ul li:first', container).nextAll().remove();
		$('.media_controls ul li:first', container).after(result.controls);
		$('.media_panel', container).html(result.panel);
		$('.media_browser', container).hide();
		$('.media_panel', container).show();
	}

};

$(document).ready(function(){
	$('#mediatabs').tabs({
		fxShow: { height: 'show', opacity: 'show' },
		fxHide: { height: 'hide', opacity: 'hide' },
		unselected: true,
		show: function(clicked, shown, hidden){
			var path = $('.pathstore', shown).html().trim();
			if(path != '') {
				habari.media.showdir(path, null, shown);
				$('.toload', shown).removeClass('toload');
			}
			return true;
		}
	});
});
