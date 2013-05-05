<?php
namespace Habari;
/**
 * @var string $helpaction
 * @var array $plugin
 * @var Theme $theme
 * @var string $configure
 * @var string $configaction
 */
?>
<?php if ( !defined( 'HABARI_PATH' ) ) { die('No direct access'); } ?>
<?php if ( $plugin['debug'] ): ?>
<div class="item plugin columns sixteen">
	<div class="head">
		<p><?php printf( _t('The plugin file %s had syntax errors and could not load.'), $plugin['file'] ); ?></p>
		<div style="display:none;" id="error_<?php echo $plugin['plugin_id']; ?>"><?php echo $plugin['error']; ?></div>
			<ul class="dropbutton">
				<li><a href="#" onclick="$('#error_<?php echo $plugin['plugin_id']; ?>').show();"><?php _e('Show Error'); ?></a></li>
			</ul>
	</div>
</div>
<?php elseif ( $plugin['info'] == 'legacy' ): ?>
<div class="item plugin">
	<div class="head">
		<p><?php printf( _t('The plugin file %s is a legacy plugin, and does not include an XML info file.'), $plugin['file'] ); ?></p>
	</div>
</div>
<?php elseif ( $plugin['info'] == 'broken' ): ?>
<div class="item plugin">
	<div class="head">
		<p><?php echo _t('The XML file for the plugin %s contained errors and could not be loaded.', array( basename( $plugin['file'] ) ) ); ?></p>
	</div>
</div>
<?php else: ?>
<div class="item plugin" id="plugin_<?php echo $plugin['plugin_id']; ?>">
	<div class="head">
		<div class="title">
		<a href="<?php echo $plugin['info']->url; ?>" class="plugin"><?php echo $plugin['info']->name; ?> <span class="version"><?php echo $plugin['info']->version; ?></span></a>
		<?php _e('by'); ?>

		<?php
		$authors = array();
		foreach ( $plugin['info']->author as $author ) {
			$authors[] = isset( $author['url'] ) ? '<a href="' . $author['url'] . '">' . $author . '</a>' : $author;
		}
		// @locale The string used between the last two items in the list of authors of a plugin on the admin page (one, two, three *and* four).
		echo Format::and_list( $authors,  _t( ' and ' ));
		?>
		</div>
		<?php if ( $plugin['core'] ): ?>
			<span class="core"><?php _e('core'); ?></span>
		<?php endif; ?>

		<?php if ( isset($plugin['help']) ): ?>
		<a class="help" href="<?php echo $plugin['help']['url']; ?>">?</a>
		<?php endif; ?>

		<?php
		/** @var FormControlDropbutton $dbtn */
		if(count($plugin['actions']) > 0):
			$dbtn = FormControlDropbutton::create('actions');
			foreach($plugin['actions'] as $key => $data) {
				$dbtn->append(FormControlSubmit::create($key)->set_url($data['href'])->set_caption($data['caption']));
			}
			echo $dbtn->pre_out();
			echo $dbtn->get($theme);
		endif;
		?>

		<?php if ( isset($plugin['update']) ): ?>
		<ul class="dropbutton alert">
			<li><a href="#"><?php _e('v1.1 Update Available Now'); ?></a></li>
		</ul>
		<?php endif; ?>

		<p class="description"><?php echo $plugin['info']->description; ?></p>

	</div>
	
	<div class="requirements">
		<?php if ( isset( $plugin['missing'] ) ) : ?>
			<ul>
				<?php foreach ( $plugin['missing'] as $feature => $url ) : ?>
				<li class="error"><?php
				$feature = '<span class="feature">' . ( $url == '' ? $feature : sprintf('<a href="%s">%s</a>', $url, $feature) ) . '</span>';
				_e('This plugin requires the %1$s feature and cannot be activated.', array($feature))
				?></li>
				<?php endforeach; ?>
			</ul>
				<?php elseif ( isset( $plugin['available'] ) ) : ?>
			<ul>
				<?php foreach ( $plugin['available'] as $feature => $plugins ) : if(count($plugins) == 1) : ?>
				<li>
					<?php 
						$dependency = reset($plugins);
						_e('%1$s will be activated to provide %2$s.', array("<b>{$dependency}</b>", "<span class=\"feature\">{$feature}</span>"));
					?>
				</li>
				
				<?php else: ?>
					<li class="error"><?php _e('One of these plugins must be activated to provide %s:', array("<span class=\"feature\">{$feature}</span>")); ?>

						<ul class="dependency_options">
						<?php foreach ( $plugins as $plugin_id => $plugin_name ) : ?>
							<?php if(isset($inactive_plugins[$plugin_id]['actions']['activate'])): ?>
								<li class="fulfills"><a href="#plugin_<?php echo $plugin_id; ?>" onclick="$('html,body').animate({scrollTop: $('#plugin_<?php echo $plugin_id; ?>').offset().top-40},500);$('#plugin_<?php echo $plugin_id; ?>').effect('pulsate', {}, 500);return false;"><?php echo $plugin_name; ?></a></li>
							<?php endif; ?>
						<?php endforeach; ?>
						</ul>
					</li>
				<?php endif; endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>


	<div class="pluginhelp"<?php if ( $helpaction == '_help' ): ?> class="active"<?php endif; ?>>
		<?php
		if ( Plugins::is_loaded((string) $plugin['info']->name) ) {
			Plugins::act_id( 'plugin_ui', $plugin['plugin_id'], $plugin['plugin_id'], '_help' );
		}
		elseif ( isset($plugin['info']->help) ) {
			foreach ( $plugin['info']->help as $help ) {
				if ( (string)$help['name'] == '' ) {
					echo  '<div class="help">' . Pluggable::get_xml_text($plugin['info']['filename'], $help) . '</div>';
				}
			}
		} ?>
	</div>

	<?php if ( isset($config) && ($config == true)): ?>
	<div id="pluginconfigure">
		<?php Plugins::plugin_ui( $configure, $configaction ); ?>
		<a class="link_as_button" href="<?php URL::out( 'display_plugins'); ?>"><?php _e('Close'); ?></a>
	</div>
	<?php endif; ?>

</div>

<?php endif; ?>
