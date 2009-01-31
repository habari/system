<table id="dash_status">
<?php foreach($status_data as $label => $value): ?>
<tr class="status_item"><th scope="row" class="label pct70"><?php echo $label; ?></th><td class="pct25"><?php echo $value; ?></td></tr>
<?php endforeach; ?>
</table>