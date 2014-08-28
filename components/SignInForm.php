<?php

namespace App\Components;

use Nette,
	Kdyby\BootstrapFormRenderer\BSForm;

class SignInForm extends BSForm {

	/**
	 * @var \Nette\Security\User
	 */
	private $user;

	/**
	 * @var \App\Model\UserManager
	 */
	private $userManager;

	/**
	 * Sign-in form factory.
	 */
	public function __construct($bla = null, $bla2 = null)
	{
		parent::__construct($bla, $bla2);

		$this->addText('username', 'Uživatelské jméno:')
			->setRequired('Vložte uživatelské jméno.');

		$this->addPassword('password', 'Heslo:')
			->setRequired('Vložte heslo.');

		$this->addCheckbox('remember', 'Trvalé přihlášení');

		$this->addSubmit('login', 'Přihlásit');

		$this->addProtection('Ověření vypršelo. Odešlete formulář znovu.');
		$this->onSuccess[] = $this->signInFormSucceeded;
	}

	/**
	 * @param \Nette\Security\User
	 */
	public function setUser(Nette\Security\User $user) {
		$this->user = $user;
	}

	/**
	 * @param \Kdyby\BootstrapFormRenderer\BSForm $form
	 * @param array $values
	 */
	public function signInFormSucceeded(BSForm $form, $values)
	{
		if ($values->remember) {
			$this->user->setExpiration('14 days', FALSE);
		} else {
			$this->user->setExpiration('20 minutes', TRUE);
		}

		try {
			$this->user->login($values->username, $values->password);

			$this->presenter->flashMessage('Přihlášeno.', 'success');

			$this->presenter->redirect('Homepage:');

		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

}