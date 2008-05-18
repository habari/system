<?php foreach($monthcts as $pdata): ?>
<div><span style="width: <?php echo $pdata->ct; ?>px"><?php echo date('M', mktime(0, 0, 0, $pdata->month)) ?></span></div>
<?php endforeach; ?>
