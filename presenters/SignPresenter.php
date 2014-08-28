<?php

namespace App\Presenters;

/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{

	public function actionIn($reason = null) {
		if ($reason) {
			$this->flashMessage('Pro tuto akci musíte být přihlášen(a).', 'info');
		}
	}

	/**
	 * @logged
	 */
	public function actionOut()
	{
		$this->user->logout();
		$this->flashMessage('Odhlášeno.', 'info');
		$this->redirect('in');
	}

}
