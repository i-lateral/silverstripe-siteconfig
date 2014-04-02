<?php

/**
 * Sitewide configuration.
 * 
 * This is shamlessley copy > pasted from CMS. Though have removed permissions
 * stuff as this is more intrinsically linked to the CMS.
 *
 * @property string Title Title of the website.
 * @property string Tagline Tagline of the website.
 *
 * @author Tom Rix
 * @package siteconfig
 */
class SiteConfig extends DataObject implements PermissionProvider {
	private static $db = array(
		"Title" => "Varchar(255)",
		"Tagline" => "Varchar(255)"
	);
	
	private static $many_many = array(
		"ViewerGroups" => "Group",
		"EditorGroups" => "Group",
		"CreateTopLevelGroups" => "Group"
	);
	
	/**
	 * @config
	 * @var array
	 */
	private static $disabled_themes = array();
	
	/**
	 * @deprecated 3.2 Use the "SiteConfig.disabled_themes" config setting instead
	 */
	static public function disable_theme($theme) {
		Deprecation::notice('3.2', 'Use the "SiteConfig.disabled_themes" config setting instead');
		Config::inst()->update('SiteConfig', 'disabled_themes', array($theme));
	}

	public function populateDefaults() {
		$this->Title = _t('SiteConfig.SITENAMEDEFAULT', "Your Site Name");
		$this->Tagline = _t('SiteConfig.TAGLINEDEFAULT', "your tagline here");
		
		// Allow these defaults to be overridden
		parent::populateDefaults();
	}

	/**
	 * Get the fields that are sent to the CMS. In
	 * your extensions: updateCMSFields($fields)
	 *
	 * @return FieldList
	 */
	public function getCMSFields() {

		$groupsMap = array();
		foreach(Group::get() as $group) {
			// Listboxfield values are escaped, use ASCII char instead of &raquo;
			$groupsMap[$group->ID] = $group->getBreadcrumbs(' > ');
		}
		asort($groupsMap);

		$fields = new FieldList(
			new TabSet("Root",
				$tabMain = new Tab('Main',
					$titleField = new TextField("Title", _t('SiteConfig.SITETITLE', "Site title")),
					$taglineField = new TextField("Tagline", _t('SiteConfig.SITETAGLINE', "Site Tagline/Slogan"))
				)
			),
			new HiddenField('ID')
		);
		
		if(!Permission::check('EDIT_SITECONFIG')) {
			$fields->makeFieldReadonly($taglineField);
			$fields->makeFieldReadonly($titleField);
		}

		if(file_exists(BASE_PATH . '/install.php')) {
			$fields->addFieldToTab("Root.Main", new LiteralField("InstallWarningHeader", 
				"<p class=\"message warning\">" . _t("SiteTree.REMOVE_INSTALL_WARNING", 
				"Warning: You should remove install.php from this SilverStripe install for security reasons.")
				. "</p>"), "Title");
		}
		
		$tabMain->setTitle(_t('SiteConfig.TABMAIN', "Main"));
		
		$this->extend('updateCMSFields', $fields);
		
		return $fields;
	}

	/**
	 * Get all available themes that haven't been marked as disabled.
	 * @param string $baseDir Optional alternative theme base directory for testing
	 * @return array of theme directory names
	 */
	public function getAvailableThemes($baseDir = null) {
		$themes = SSViewer::get_themes($baseDir);
		$disabled = (array)$this->config()->disabled_themes;
		foreach($disabled as $theme) {
			if(isset($themes[$theme])) unset($themes[$theme]);
		}
		return $themes;
	}
	
	/**
	 * Get the actions that are sent to the CMS. In
	 * your extensions: updateEditFormActions($actions)
	 *
	 * @return Fieldset
	 */
	public function getCMSActions() {
		if (Permission::check('ADMIN') || Permission::check('EDIT_SITECONFIG')) {
			$actions = new FieldList(
				FormAction::create('save_siteconfig', _t('CMSMain.SAVE','Save'))
					->addExtraClass('ss-ui-action-constructive')->setAttribute('data-icon', 'accept')
			);
		} else {
			$actions = new FieldList();
		}
		
		$this->extend('updateCMSActions', $actions);
		
		return $actions;
	}

	/**
	 * @return String
	 */
	public function CMSEditLink() {
		return singleton('CMSSettingsController')->Link();
	}
	
	/**
	 * Get the current sites SiteConfig, and creates a new one
	 * through {@link make_site_config()} if none is found.
	 *
	 * @return SiteConfig
	 */
	static public function current_site_config() {
		if ($siteConfig = DataObject::get_one('SiteConfig')) return $siteConfig;
		
		return self::make_site_config();
	}

	/**
	 * Setup a default SiteConfig record if none exists
	 */
	public function requireDefaultRecords() {
		parent::requireDefaultRecords();
		$siteConfig = DataObject::get_one('SiteConfig');
		if(!$siteConfig) {
			self::make_site_config();
			DB::alteration_message("Added default site config","created");
		}
	}
	
	/**
	 * Create SiteConfig with defaults from language file.
	 * 
	 * @return SiteConfig
	 */
	static public function make_site_config() {
		$config = SiteConfig::create();
		$config->write();
		return $config;
	}
	
	public function providePermissions() {
		return array(
			'EDIT_SITECONFIG' => array(
				'name' => _t('SiteConfig.EDIT_PERMISSION', 'Manage site configuration'),
				'category' => _t('Permissions.PERMISSIONS_CATEGORY', 'Roles and access permissions'),
				'help' => _t('SiteConfig.EDIT_PERMISSION_HELP', 'Ability to edit global access settings/top-level page permissions.'),
				'sort' => 400
			)
		);
	}
}
