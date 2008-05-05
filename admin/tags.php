<?php include('header.php');?>


<div class="container timeline">
	<span class="older pct10"><a href="#">&laquo; Older</a></span>
	<span class="currentposition pct15 minor">0-20 of 480</span>
	<span class="search pct50"><input type="search" placeholder="Type and wait to search tags" autosave="habaricontent" results="10"></input></span>
	<span class="nothing pct15">&nbsp;</span>
	<span class="newer pct10"><a href="#">Newer &raquo;</a></span>
</div>
<?php
	$tags=Tags::get();
	//what's the max count?
	//ugly! and probably needs to be a Tags method or something
	$max=0;
	foreach ($tags as $tag){if ($max < $tag->count) $max=$tag->count;}
?>
<div class="container tags">
<?php foreach ($tags as $tag) { ?>
	<a href="#" class="tag wt<?php echo round(($tag->count * 10)/$max); ?>"><span><?php echo $tag->tag; ?></span><sup><?php echo $tag->count; ?></sup></a>
<?php } ?>
		<ul class="dropbutton">
			<li><a href="#">Select Visible</a></li>
			<li><a href="#">Select All</a></li>
			<li><a href="#">Deselect All</a></li>
		</ul>
</div>

<div class="container tags transparent">

	<div class="item controls">
		<span class="checkboxandselected pct15">
			<span class="selectedtext minor none">None selected</span>
		</span>
		<span><input type="button" value="Delete" class="deletebutton"></input></span>
		<span class="or pct5">or</span>
		<span class="renamecontrols">
			<input type="text"></input>
			<input type="button" value="Rename" class="renamebutton"></input>
		</span>
	</div>
</div>



<?php include('footer.php');?>