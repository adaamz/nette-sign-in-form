<?php

namespace App\Components;

use Nette,
	Nette\Application\UI\Control,
	App\Model\MainMenuModel;

class MainMenuControl extends Control {

	/**
	 * @inject
	 * @var \App\Model\MainMenuModel
	 */
	public $model;

	/**
	 * @inject
	 * @var \Nette\Security\User
	 */
	public $user;

	/**
	 * @param \App\Model\MainMenuModel
	 */
	public function setModel(MainMenuModel $model) {
		$this->model = $model;
	}

	/**
	 * @param \Nette\Security\User
	 */
	public function setUser(Nette\Security\User $user) {
		$this->user = $user;
	}

	public function render() {
		$this->template->siteName = $this->presenter->context->parameters['siteName'];

		$this->template->user = $this->user;

		$this->template->setFile(__DIR__ . '/../templates/components/mainMenu.latte');
		$this->template->render();
	}
	
	/**
	 * @return Kdyby\BootstrapFormRenderer\BSForm
	 */
	public function createComponentSignInForm() {
		$form = new SignInForm();

		$form->setUser($this->user);

		return $form;
	}
}
