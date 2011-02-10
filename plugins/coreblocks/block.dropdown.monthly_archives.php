<form id="month_archive_form" action="">
<fieldset><legend>Browse Archives</legend>

<select name="archive_tags" onchange="window.location =
(document.forms.month_archive_form.archive_tags[document.forms.month_archive_form.archive_tags.selectedIndex].value);">

		<option value=''>by date</option>
		<?php $months = $content->months; foreach( $months as $month ): ?>
	<option value="<?php echo $month[ 'url' ]; ?>"><?php echo $month[ 'display_month' ] . " " . $month[ 'year' ] . $month[ 'count' ];
	?></option>
		<?php endforeach; ?>
</select>
</fieldset>
</form>
