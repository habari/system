var media = {

	showdir: function (path, el, container) {
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
						output += '<tr><td><a class="directory" href="#" onclick="return media.clickdir(this, \'' + result.dirs[dir].path + '\');">' + result.dirs[dir].title + '</a></td></tr>';
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
				},
				'json'
			);
		}
	},

	clickdir: function(el, path) {
		this.showdir(path, el);
		return false;
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
				media.showdir(path, null, shown);
				$('.toload', shown).removeClass('toload');
			}
			return true;
		}
	});
});
