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
					var reload = $('.pathstore.toload', container).size();
					habari.media.unqueueLoad();
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
					else if($('.mediadir', container).html() == '') {
						$('.mediadir', container).html(output);
					}
					else if(reload) {
						// only need to reload the current directory
						$('.media_dirlevel:last', container).remove();
						$('.mediadir', container).append(output);
					}

					output = '<table><tr>';
					var first = ' first';
					habari.media.assets = result.files;
					for(var file in result.files) {
						stats = '';
						output += '<td><div class="media' + first + '"><span class="foroutput">' + file + '</span>';

						if(result.files[file].filetype && habari.media.preview[result.files[file].filetype]) {
							output += habari.media.preview[result.files[file].filetype](file, result.files[file]);
						}
						else {
							output += habari.media.preview._(file, result.files[file]);
						}

						output += '</div></td>';
						first = '';
					}
					output += '</tr></table>';
					$('.mediaphotos', container).html(output);
					$('.media').dblclick(function(){
						habari.media.insertAsset(this);
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
	},

	unqueueLoad: function() {
		container = $('.mediasplitter:visible');
		$('.toload', container).removeClass('toload');
	},

	forceReload: function() {
		container = $('.mediasplitter:visible');
		$('.pathstore', container).addClass('toload');
	},

	preview: {
		_: function(fileindex, fileobj) {
			var stats = '';
			return '<div class="mediatitle">' + fileobj.title + '</div><img src="' + fileobj.thumbnail_url + '"><div class="mediastats"> ' + stats + '</div>';
		}
	},

	output: {
		image_jpeg: {image: function(fileindex, fileobj) {habari.editor.insertSelection('<img alt="' + fileobj.title + '" src="' + fileobj.url + '">');}},
		image_gif: {image: function(fileindex, fileobj) {habari.editor.insertSelection('<img alt="' + fileobj.title + '" src="' + fileobj.url + '">');}},
		image_png: {image: function(fileindex, fileobj) {habari.editor.insertSelection('<img alt="' + fileobj.title + '" src="' + fileobj.url + '">');}},
		audio_mpeg3: {link: function(fileindex, fileobj) {habari.editor.insertSelection('<a href="' + fileobj.url + '">' + fileobj.title + '</a>');}},
		video_mpeg: {link: function(fileindex, fileobj) {habari.editor.insertSelection('<a href="' + fileobj.url + '">' + fileobj.title + '</a>');}},
		audio_wav: {link: function(fileindex, fileobj) {habari.editor.insertSelection('<a href="' + fileobj.url + '">' + fileobj.title + '</a>');}},
		application_x_shockwave_flash: {link: function(fileindex, fileobj) {habari.editor.insertSelection('<a href="' + fileobj.url + '">' + fileobj.title + '</a>');}},
		_: {link: function(fileindex, fileobj) {habari.editor.insertSelection('<a href="' + fileobj.url + '">' + fileobj.title + '</a>');}}
	},

	submitPanel: function(path, panel, form, callback) {
		var query = $(form).serializeArray();
		query.push({name: 'path', value: path});
		query.push({name: 'panel', value: panel});

		$.post(
			habari.url.habari + '/admin_ajax/media_panel',
			query,
			function(result) {
				$('.media_panel').html(result.panel);
				if (callback != '') {
					eval(callback);
				}
			},
			'json');
	},

	insertAsset: function(asset) {
		var id = $('.foroutput', asset).html();
		if(this.assets[id].filetype && habari.media.output[this.assets[id].filetype]) {
			fns = habari.media.output[this.assets[id].filetype];
		}
		else {
			fns = habari.media.output._;
		}
		if(jQuery.makeArray(fns).length == 1) {
			for(var fname in fns) {
				fns[fname](id, this.assets[id]);
			}
		}
		else {
			habari.media.multioutput(fns, id, this.assets[id]);
		}
	},

	multioutput: function(fns, id, asset) {
		/* @todo Create interface for multiple output methods on a single file type */
		for(var fname in fns) {
			fns[fname](id, this.assets[id]);
			break;
		}
	},

	clearSelections: function() {
		var container = $('.mediasplitter:visible')
		// remove all highlights
		$('.mediadir .directory', container).removeClass('active');
		// remove second level directories
		$('.mediadir .media_dirlevel', $('.mediasplitter:visible')).nextAll().remove()
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
				habari.media.unqueueLoad();
			}
			return true;
		}
	});
});
