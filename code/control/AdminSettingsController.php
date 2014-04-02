<?php
/**
 * Controller that deals with managing editting sttings in the Admin interface
 *
 */
class AdminSettingsController extends LeftAndMain {

	private static $url_segment = 'settings';
	private static $url_rule = '/$Action/$ID/$OtherID';
	private static $menu_priority = -1;
	private static $menu_title = 'Settings';
	private static $tree_class = 'SiteConfig';
	private static $required_permission_codes = array('EDIT_SITECONFIG');
	
	public function init() {
		parent::init();

		Requirements::javascript(SITECONFIG_DIR . '/javascript/CMSMain.EditForm.js');
	}

	public function getResponseNegotiator() {
		$neg = parent::getResponseNegotiator();
		$controller = $this;
		$neg->setCallback('CurrentForm', function() use(&$controller) {
			return $controller->renderWith($controller->getTemplatesWithSuffix('_Content'));
		});
		return $neg;
	}

	/**
	 * @param null $id Not used.
	 * @param null $fields Not used.
	 * @return Form
	 */
	public function getEditForm($id = null, $fields = null) {
		$siteConfig = SiteConfig::current_site_config();
		
		$fields = $siteConfig->getCMSFields();

		$actions = $siteConfig->getCMSActions();
		
		$form = CMSForm::create( 
			$this, 'EditForm', $fields, $actions
		)->setHTMLID('Form_EditForm');
		
		$form->setResponseNegotiator($this->getResponseNegotiator());
		$form->addExtraClass('cms-content center cms-edit-form');
		// don't add data-pjax-fragment=CurrentForm, its added in the content template instead

		if($form->Fields()->hasTabset()) $form->Fields()->findOrMakeTab('Root')->setTemplate('CMSTabSet');
		$form->setHTMLID('Form_EditForm');
		$form->loadDataFrom($siteConfig);
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));

		// Use <button> to allow full jQuery UI styling
		$actions = $actions->dataFields();
		if($actions) foreach($actions as $action) $action->setUseButtonTag(true);

		$this->extend('updateEditForm', $form);

		return $form;
	}

	/**
	 * Used for preview controls, mainly links which switch between different states of the page.
	 *
	 * @return ArrayData
	 */
	public function getSilverStripeNavigator() {
		return $this->renderWith('AdminSettingsController_SilverStripeNavigator');
	}

	/**
	 * Save the current sites {@link SiteConfig} into the database
	 *
	 * @param array $data 
	 * @param Form $form 
	 * @return String
	 */
	public function save_siteconfig($data, $form) {
		$siteConfig = SiteConfig::current_site_config();
		$form->saveInto($siteConfig);
		
		try {
			$siteConfig->write();
		} catch(ValidationException $ex) {
			$form->sessionMessage($ex->getResult()->message(), 'bad');
			return $this->getResponseNegotiator()->respond($this->request);
		}
		
		$this->response->addHeader('X-Status', rawurlencode(_t('LeftAndMain.SAVEDUP', 'Saved.')));
		return $this->getResponseNegotiator()->respond($this->request);
	}
	
	public function LinkPreview() {
		$record = $this->getRecord($this->currentPageID());
		$baseLink = ($record && $record instanceof Page) ? $record->Link('?stage=Stage') : Director::absoluteBaseURL();
		return $baseLink;
	}

	public function Breadcrumbs($unlinked = false) {
		$defaultTitle = self::menu_title_for_class(get_class($this));
		return new ArrayList(array(
			new ArrayData(array(
				'Title' => _t("{$this->class}.MENUTITLE", $defaultTitle),
				'Link' => false
			))
		));
	}
	
}
