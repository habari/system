<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<table id="dash_status">
<?php foreach ( $status_data as $label => $value ): ?>
<tr class="status_item"><th scope="row" class="label"><?php echo $label; ?></th><td><?php echo $value; ?></td></tr>
<?php endforeach; ?>
</table>
