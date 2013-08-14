<?php if ( !defined( 'HABARI_PATH' ) ) { die( 'No direct access' ); } ?>
<?php $tags = $content->tags; ?>
<div id="tag_archives">
    <?php $tags = $content->tags; foreach( $tags as $tag ): ?>
        <span style="font-size: <?php echo ($content->cloud_min + round((($tag['count'] - $content->min_post_count) * $content->size_increment),1)); ?>em;" class="cloud-tag">
            <a  href="<?php echo $tag[ 'url' ]; ?>"
                title="View entries tagged '<?php echo $tag[ 'tag' ]; ?>'">
                <?php echo $tag[ 'tag' ]; ?>
                <?php if ($content->show_counts) { ?>(<?php echo $tag['count']; ?>)<?php } ?>
            </a>
        </span>
    <?php endforeach; ?>
</div>