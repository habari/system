<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<?php
/*  before outputting the tags we must run through them once to find the min and max
    post counts, these are needed to calculate the proper per-post size increment between
    the user's defined min/max font sizes
 */
$tags = $content->tags;
if ($content->cloud_max != '') { $maxSize = $content->cloud_max; } else { $maxSize = 4; }
if ($content->cloud_min != '') { $minSize = $content->cloud_min; } else { $minSize = 0.6; }
$minPosts = 1000;
$maxPosts = 1;
foreach ($tags as $tag) {
    if ($tag['count'] > $maxPosts) { $maxPosts = $tag['count']; }
    if ($tag['count'] < $minPosts) { $minPosts = $tag['count']; }
}
$sizeIncrement = round(($maxSize - $minSize) / ($maxPosts - $minPosts),1);
?>
<div id="tag_archives">
    <?php $tags = $content->tags; foreach( $tags as $tag ): ?>
        <span style="font-size: <?php echo ($minSize + round((($tag['count'] - $minPosts) * $sizeIncrement),1)); ?>em;" class="cloud-tag">
            <a  href="<?php echo $tag[ 'url' ]; ?>"
                title="View entries tagged '<?php echo $tag[ 'tag' ]; ?>'">
                <?php echo $tag[ 'tag' ]; ?>
                <?php if ($content->show_counts) { ?>(<?php echo $tag['count']; ?>)<?php } ?>
            </a>
        </span>
    <?php endforeach; ?>
</div>