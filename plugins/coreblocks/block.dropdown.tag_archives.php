<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<form id="tag_archive_form" action="">
<fieldset><legend>Browse Archives</legend>

<select name="archive_tags" onchange="window.location =
(document.forms.tag_archive_form.archive_tags[document.forms.tag_archive_form.archive_tags.selectedIndex].value);">

		<option value=''>by tag</option>
		<?php $tags = $content->tags; foreach( $tags as $tag ): ?>
			<option value="<?php echo $tag[ 'url' ]; ?>"><?php echo $tag[ 'tag' ] . $tag[ 'count' ];
	?></option>
		<?php endforeach; ?>
</select>
</fieldset>
</form>
