<?php

namespace App\Presenters;

use Nette,
	App\Components\FbMetaControl,
	App\Components\MainMenuControl,
	App\Components\SignInForm;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	/** @inject @var \Nette\Caching\Cache */
	public $cache;

	/** @inject @var \App\Model\ContentModel */
	public $contentModel;

	/** @inject @var \App\Model\MainMenuModel */
	public $mainMenuModel;

	/** @var \App\Model\UserManager */
	private $userManager;


	public function startup() {
		parent::startup();

		$this->user->getStorage()->setNamespace($this->context->parameters['siteName'] . '/front');
	}

	/**
	 * @var var $element
	 * @forward Homepage:|Error:blank
	 */
	public function checkRequirements($element) {
		$this->startup();

		$class = $element->name;
		$method = 'action' . $this->action;

		$signal = $this->getSignal();
		if ($signal) {
			if ($signal[0]) {
				$class = $this->getComponent($signal[0]);
			}
			$method = 'handle' . $signal[1];
		}

		try {
			$c = new Nette\Reflection\Method($class, $method);

			$fail = false;

			if ($c->hasAnnotation('logged')) {
				if (!$this->user->loggedIn) {
					$this->flashMessage('Pro tuto akci musíte být přihlášen(a).', 'info');
					$fail = true;
				}
			} elseif ($c->hasAnnotation('only_guest')) {
				if ($this->user->loggedIn) {
					$this->flashMessage('Pro tuto akci nesmíte být přihlášen(a).', 'warning');
					$fail = true;
				}
			}

			if ($fail === true) {
				if ($this->isAjax()) {
					$this->forward('Error:blank');
				} else {
					$this->forward('Homepage:');
				}
			}
		} catch(\ReflectionException $e) {

		}
	}

	/**
	 * @param \App\Model\UserManager
	 */
	public function injectUserManager(\App\Model\UserManager $userManager) {
		$this->userManager = $userManager;
	}

	/**
	 * @return \App\Components\MainMenuControl
	 */
	protected function createComponentMainMenu() {
		$control = new MainMenuControl();

		$control->setModel($this->mainMenuModel);
		$control->setUser($this->user);

		return $control;
	}


	/**
	 * @return \App\Components\SignInForm
	 */
	protected function createComponentSignInForm() {
		$form = new SignInForm();

		$form->setUser($this->user);

		return $form;
	}

	/**
	 * @inject
	 * @var \Zenify\FlashMessageComponent\IControlFactory
	 */
	public $flashMessageControlFactory;


	/**
	 * @return \Zenify\FlashMessageComponent\Control
	 */
	public function createComponentFlashMessage() {
		return $this->flashMessageControlFactory->create();
	}


	public function afterRender() {
		if ($this->isAjax() && $this->hasFlashSession()) {
			$this->invalidateControl('flashMessage');
		}
	}

}
