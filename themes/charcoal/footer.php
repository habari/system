<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
	</div>
	<div id="bottom-secondary">
		<div id="tags"><?php echo (Plugins::is_loaded('tagcloud')) ? $theme->tag_cloud() : $theme->show_tags();?></div>
	</div>
	<div id="footer">
		<p>
			<?php _e( '%1$s is powered by %2$s', array(
					Options::get('title'),
					'<a href="http://www.habariproject.org/" title="Habari">Habari ' . Version::get_habariversion() . '</a>'
				)); ?> - 
			<a href="<?php URL::out( 'atom_feed', array( 'index' => '1' ) ); ?>"><?php _e( 'Atom Entries' ); ?></a><?php _e( ' and ' ); ?>
			<a href="<?php URL::out( 'atom_feed_comments' ); ?>"><?php _e( 'Atom Comments' ); ?></a>
		</p>
	</div>
	<div class="clear"></div>
</div>
</div>
<?php echo $theme->footer(); ?>
</body>
</html>
