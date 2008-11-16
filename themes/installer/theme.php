<?php
define( 'THEME_CLASS', 'DefaultInstaller' );

class DefaultInstaller extends Theme
{
		
	public function filter_control_theme_dir( $dirs )
	{
		return HABARI_PATH . '/system/themes/installer/formcontrols/';
	}
	
	public function form_locales()
	{
		$locs= array();
		foreach ($this->locales as $loc) {
			$locs[$loc]= $loc;
		}
		
		$form = new FormUI('locale-form');
		
		$form->append('wrapper', 'locale', null, 'installer_installstep_nohelp');
		$form->locale->caption = _t('Locale');
		$form->locale->class = 'locale-dropdown done';
				
		$form->locale->append('wrapper', 'locale_options', null, 'installer_options');
		$form->locale_options->append('select', 'locales', 'null:null', _t('Language'), $locs);
		$form->locales->selected = $this->locale;
		$form->locales->required = false;
		$form->locales->help = '';
		
		$form->append('wrapper', 'locale_nextstep', null, 'installer_nextstep');

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
		$form->db_type->selected = $this->db_type;
		$form->db_type->required = true;
		$form->db_type->help = _t('<strong>Database Type</strong> specifies the type of database to which
		Habari will connect.  Changing this setting may affect the other fields
		that are available here.  If the database engine that you wanted to use
		is not in this list, you may need to install a PDO driver to enable it.') . ' <a href="#"> '. _t('Learn More...') . '</a>';
		// Advanced Options
		$form->databasesetup->append('wrapper', 'databasesetup_advancedoptions', null, 'installer_advancedoptions');
		// Table Prefix
		$form->databasesetup_advancedoptions->append('text', 'table_prefix', 'null:null', _t('Table Prefix'));
		$form->table_prefix->value = $this->table_prefix;
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
		$form->blog_title->value = $this->blog_title;
		$form->blog_title->required = true;
		$form->blog_title->help = _t('<strong>Site Name</strong> is the name of your site as it will appear
		to your visitors.') . ' <a href="#">' . _t('Learn More...') . '</a>';
		// Admin Username
		$form->siteconfiguration_options->append('text', 'admin_username', 'null:null', _t('Username'));
		$form->admin_username->value = $this->admin_username;
		$form->admin_username->required = true;
		$form->admin_username->help = _t('<strong>Username</strong> is the username of the initial user in 
		Habari.') . ' <a href="#">' . _t('Learn More...') . '</a>';
		// Admin Password
		$form->siteconfiguration_options->append('wrapper', 'admin_pass_wrapper', null, 'installer_password_wrapper');
		$form->admin_pass_wrapper->append('password', 'admin_pass1', 'null:null', _t('Password'), 'installer_password');
		$form->admin_pass1->value = $this->admin_pass1;
		$form->admin_pass1->required = true;
		$form->admin_pass_wrapper->append('password', 'admin_pass2', 'null:null', _t('Password (again)'), 'installer_password');
		$form->admin_pass2->value = $this->admin_pass2;
		$form->admin_pass2->required = true;
		$form->admin_pass_wrapper->message = '';
		$form->admin_pass_wrapper->help = _t('<strong>Password</strong> is the password of the initial user in 
		Habari.') . ' <a href="#">' . _t('Learn More...') . '</a>';
		// Admin Email
		$form->siteconfiguration_options->append('text', 'admin_email', 'null:null', _t('E-mail'));
		$form->admin_email->value = $this->admin_email;
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
	
}
?>