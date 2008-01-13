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
				'/admin_ajax/media',
				{
					path: path
				},
				function(result) {
					$('.pathstore', container).html(result.path);
					var output = '<div class="media_dirlevel"><div>';
					for(var dir in result.dirs) {
						output += '<a class="directory" href="#" onclick="return media.clickdir(this, \'' + result.dirs[dir].path + '\');">' + dir + '</a>';
					}
					output += '</div></div>';
					if(el) {
						deldir = $($(el).parents('.media_dirlevel')).next();
						while(deldir.size()) {
							deldir.remove();
							deldir = $($(el).parents('.media_dirlevel')).next();
						}
						$(el).siblings().removeClass('active');
						$(el).addClass('active');
						$(el).parents('.media_dirlevel').after(output);
					}
					else {
						$('.mediadir', container).html(output);
					}
					output = '';
					var first = ' first';
					for(var file in result.files) {
						stats = '';
						output += '<div class="media' + first + '"><div class="mediatitle">' + result.files[file].title + '</div><img src="' + result.files[file].thumbnail_url + '"><div class="mediastats"> ' + stats + '</div><div class="foroutput"><img src="' + result.files[file].url + '"></div></div>';
						first = '';
					}
					$('.mediaphotos', container).html(output);
					$('.media').dblclick(function(){
						habari_editor.insert_selection($('.foroutput', this).html());
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
