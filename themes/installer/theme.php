<?php
define( 'THEME_CLASS', 'DefaultInstaller' );

class DefaultInstaller extends Theme
{
			
	public function filter_control_theme_dir( $dirs )
	{
		return HABARI_PATH . '/system/themes/installer/formcontrols/';
	}
	
	public function add_template_vars()
	{
		Locale::set( $this->form_locales()->locales->value );
		$this->plugins = $this->get_plugins();
	}
	
	public function form_locales()
	{
		$locales= array();
		foreach (Locale::list_all() as $locale) {
			$locales[$locale]= $locale;
		}
		
		$form = new FormUI('locale-form');
		
		$form->append('wrapper', 'locale', null, 'installer_installstep_nohelp');
		$form->locale->caption = _t('Locale');
		$form->locale->class = 'locale-dropdown done';
				
		$form->locale->append('wrapper', 'locale_options', null, 'installer_options');
		$form->locale_options->append('select', 'locales', 'null:null', _t('Language'), $locales);
		$form->locales->value = Options::get('locale');
		$form->locales->required = false;
		$form->locales->help = '';
		
		$form->append('wrapper', 'locale_nextstep', null, 'installer_nextstep');

		$form->on_success( array($this, 'set_locale') );
		
		Plugins::act('install_form_localeform', $form, $this);
		return $form;
	}
	
	public function form_installform()
	{
		$form = new FormUI('installform');
		$form->append('hidden', 'locale', 'null:null', htmlspecialchars($this->locale));
		
		/* Database Setup */
		$form->append('wrapper', 'databasesetup', null, 'installer_installstep');
		$form->databasesetup->caption = _t('Database Setup');
		// Options
		$form->databasesetup->append('wrapper', 'databasesetup_options', null, 'installer_options');
		// Database Type
		$form->databasesetup_options->append('select', 'db_type', 'null:null', _t('Database Type'));
		$form->db_type->value = 'mysql';
		$form->db_type->required = true;
		$form->db_type->help = _t('<strong>Database Type</strong> specifies the type of database to which
		Habari will connect.  Changing this setting may affect the other fields
		that are available here.  If the database engine that you wanted to use
		is not in this list, you may need to install a PDO driver to enable it.') . ' <a href="#"> '. _t('Learn More...') . '</a>';
		// Advanced Options
		$form->databasesetup->append('wrapper', 'databasesetup_advancedoptions', null, 'installer_advancedoptions');
		// Table Prefix
		$form->databasesetup_advancedoptions->append('text', 'table_prefix', 'null:null', _t('Table Prefix'));
		$form->table_prefix->value = 'habari__';
		$form->table_prefix->help = _t('<strong>Table Prefix</strong> is a prefix that will be appended to
		each table that Habari creates in the database, making it easy to
		distinguish those tables in the database from those of other
		installations.') . ' <a href="#">' . _t('Learn More...') . '</a>';
		
		/* Next step down image */
		$form->append('wrapper', 'databasesetup_nextstep', null, 'installer_nextstep');

		/* Site Configuration */
		$form->append('wrapper', 'siteconfiguration', null, 'installer_installstep');
		$form->siteconfiguration->caption = _t('Site Configuration');
		// Options
		$form->siteconfiguration->append('wrapper', 'siteconfiguration_options', null, 'installer_options');
		// Blog Title
		$form->siteconfiguration_options->append('text', 'blog_title', 'null:null', _t('Site Name'));
		$form->blog_title->value = 'My Habari';
		$form->blog_title->required = true;
		$form->blog_title->help = _t('<strong>Site Name</strong> is the name of your site as it will appear
		to your visitors.') . ' <a href="#">' . _t('Learn More...') . '</a>';
		// Admin Username
		$form->siteconfiguration_options->append('text', 'admin_username', 'null:null', _t('Username'));
		$form->admin_username->value = 'admin';
		$form->admin_username->required = true;
		$form->admin_username->help = _t('<strong>Username</strong> is the username of the initial user in 
		Habari.') . ' <a href="#">' . _t('Learn More...') . '</a>';
		// Admin Password
		$form->siteconfiguration_options->append('wrapper', 'admin_pass_wrapper', null, 'installer_password_wrapper');
		$form->admin_pass_wrapper->append('password', 'admin_pass1', 'null:null', _t('Password'), 'installer_password');
		$form->admin_pass1->required = true;
		$form->admin_pass_wrapper->append('password', 'admin_pass2', 'null:null', _t('Password (again)'), 'installer_password');
		$form->admin_pass2->required = true;
		$form->admin_pass_wrapper->message = '';
		$form->admin_pass_wrapper->help = _t('<strong>Password</strong> is the password of the initial user in 
		Habari.') . ' <a href="#">' . _t('Learn More...') . '</a>';
		// Admin Email
		$form->siteconfiguration_options->append('text', 'admin_email', 'null:null', _t('E-mail'));
		$form->admin_email->required = true;
		$form->admin_email->help = _t('<strong>Admin Email</strong> is the email address of the first user
		account.') . ' <a href="#">' . _t('Learn More...') . '</a>';
		
		/* Next step down image */
		$form->append('wrapper', 'siteconfiguration_nextstep', null, 'installer_nextstep');
		
		/* Plugin Activation */
		$form->append('wrapper', 'pluginactivation', null, 'installer_installstep');
		$form->pluginactivation->caption = _t('Plugin Activation');
		// Options
		$form->pluginactivation->append('wrapper', 'pluginactivation_options', null, 'installer_options');
		$form->pluginactivation_options->class = 'items';
		// Plugins list
		$form->pluginactivation_options->append('checkboxes', 'plugins_checkboxes', 'null:null', '', $this->plugins, 'installer_checkboxes');
		
		/* Next step down image */
		$form->append('wrapper', 'pluginactivation_nextstep', null, 'installer_nextstep');

		/* Install Button */
		$form->append('wrapper', 'install', null, 'installer_installstep_nohelp');
		$form->install->caption = _t('Install');
		// Options
		$form->install->append('wrapper', 'install_options', null, 'installer_options');
		// Submit button
		$form->install_options->append('submit', 'submit', _t('Install Habari'), 'installer_submit');
		$form->submit->class = 'inputfield submit';
		$form->submit->storage = 'null:null';
		
		Plugins::act('install_form_installform', $form, $this);
		return $form;
	}
	
	/*
	 * Helper function to grab list of plugins
	 */
	public function get_plugins() {
		$all_plugins = Plugins::list_all();
		$recommended_list = array(
			'coredashmodules.plugin.php',
			'habarisilo.plugin.php',
			'pingback.plugin.php',
			'spamchecker.plugin.php',
			'undelete.plugin.php'
		);

		foreach ( $all_plugins as $file ) {
			$plugin = array();
			$plugin_id = Plugins::id_from_file( $file );
			$plugin['plugin_id']= $plugin_id;
			$plugin['file']= $file;

			$error = '';
			if ( Utils::php_check_file_syntax( $file, $error ) ) {
				$plugin['debug']= false;
				// instantiate this plugin
				// in order to get its info()
				include_once( $file );
				Plugins::get_plugin_classes();
				$pluginobj = Plugins::load( $file, false );
				$plugin['active']= false;
				$plugin['verb']= _t( 'Activate' );
				$plugin['actions']= array();
				$plugin['info']= $pluginobj->info;
				$plugin['recommended'] = in_array( basename($file), $recommended_list );
			}
			else {
				$plugin['debug']= true;
				$plugin['error']= $error;
				$plugin['active']= false;
			}
			
			$plugins[$plugin_id]= $plugin;
		}
		
		return $plugins;
	}
	
}
?>