<?php

namespace App\Model;

use Nette,
	Nette\Utils\Strings,
	Nette\Security\Passwords;


/**
 * Users management.
 */
class UserManager extends Nette\Object implements Nette\Security\IAuthenticator
{
	const
		TABLE_NAME = 'user',
		COLUMN_ID = 'user_id',
		COLUMN_NAME = 'username',
		COLUMN_PASSWORD_HASH = 'password',
		COLUMN_ROLE = 'role';

	const EMAIL_BANNED = 4;

	/** @var \Nette\Database\Context */
	public $database;

	/** @var \Nette\Http\Request */
	private $request;


	/**
	 * @param \Nette\Database\Context $database
	 * @param \Nette\Http\Request $request
	 */
	public function __construct(Nette\Database\Context $database, Nette\Http\Request $request)
	{
		$this->database = $database;
		$this->request = $request;
	}


	/**
	 * Performs an authentication.
	 * @param array (username, password) $credentials
	 * @return Nette\Security\Identity
	 * @throws Nette\Security\AuthenticationException
	 */
	public function authenticate(array $credentials)
	{
		$canLogin = $this->canLogin();
		if ($canLogin !== true) {
			throw new Nette\Security\AuthenticationException('Vyčerpali jste pokusy o přihlášení. Zkuste to znovu za ' . $canLogin . ' minut.', self::FAILURE);
		}

		list($username, $password) = $credentials;

		$row = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_NAME, $username)->fetch();

		if ($row && $row->approved != 1 && $row->approved != 2) {
			$this->badLoginAttempt();

			if ($row->approved == 99) {
				throw new Nette\Security\AuthenticationException('Uživatel zablokován.', self::EMAIL_BANNED);
			} elseif ($row->approved == 0) {
				throw new Nette\Security\AuthenticationException('Neověřená emailová adresa.', self::NOT_APPROVED);
			} else {
				throw new Nette\Security\AuthenticationException('WAT?' . $row->approved, self::FAILURE);
			}
		} elseif (!$row) {
			$this->badLoginAttempt();

			throw new Nette\Security\AuthenticationException('Špatné uživatelské jméno.', self::IDENTITY_NOT_FOUND);

		} elseif (!Passwords::verify($password, $row[self::COLUMN_PASSWORD_HASH])) {
			$this->badLoginAttempt();

			throw new Nette\Security\AuthenticationException('Špatné heslo.', self::INVALID_CREDENTIAL);

		} elseif (Passwords::needsRehash($row[self::COLUMN_PASSWORD_HASH])) {
			$row->update(array(
				self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			));
		}

		$this->database->table('login_attempt')->where('ip', $this->request->remoteAddress)->delete();

		$arr = $row->toArray();
		unset($arr[self::COLUMN_PASSWORD_HASH]);
		return new Nette\Security\Identity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
	}

	/**
	 * @param int
	 * @return \Nette\Database\Selection
	 */
	public function get($user_id) {
		return $this->database->table(self::TABLE_NAME)->select('username,name,email,email AS email2,address,paypal')->where(self::COLUMN_ID, $user_id);
	}

	/**
	 * @param string $username
	 * @return \Nette\Database\ActiveRow
	 */
	public function getByUserName($username) {
		return $this->database->table(self::TABLE_NAME)->where('username', $username)->fetch();
	}

	/**
	 * Adds new user.
	 * @param  string
	 * @param  string
	 * @param  array
	 * @return void
	 */
	public function add($username, $password, $data = array())
	{
		$this->database->table(self::TABLE_NAME)->insert(array_merge(array(
			self::COLUMN_NAME => $username,
			self::COLUMN_PASSWORD_HASH => Passwords::hash($password),
			'ip'	=> $this->request->remoteAddress,
		), $data));
	}

	/**
	 * Updates current user.
	 * @param  int
	 * @param  array
	 * @return void
	 */
	public function edit($user_id, $data = array())
	{
		if (!empty($data['password'])) {
			$data['password']  = Passwords::hash($data['password']);
		} else {
			unset($data['password']);
		}

		$this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $user_id)->update($data);
	}

	public function approve($email, $approve, $hash) {
		return $this->database->table(self::TABLE_NAME)->where('email', $email)->where('approve_hash', $hash)->update(array('approved' => $approve, 'approve_hash' => ''));
	}

	/**
	 * @param string
	 * @param string
	 * @param int|null
	 * @return \Nette\Database\ActiveRow
	 */
	public function rowExist($column, $value, $user_id = null) {
		$exist = $this->database->table(self::TABLE_NAME)->where($column, $value);

		if ($user_id) {
			$exist->where(self::COLUMN_ID . ' != ?', $user_id);
		}

		return $exist->fetch();
	}

	/**
	 * Log bad attempt login
	 * @param Nette\Database\Table\IRow|boolean $login_attempt
	 * @return void
	 */
	private function badLoginAttempt() {
		$login_attempt = $this->database->table('login_attempt')->where('ip', $this->request->remoteAddress)->fetch();

		if ($login_attempt) {
			$this->database->table('login_attempt')->where('ip', $this->request->remoteAddress)->update(array(
				'last_attempt'		=> new \DateTime,
				'total_attempts'	=> new Nette\Database\SqlLiteral('total_attempts + 1'),
			));
		} else {
			$this->database->table('login_attempt')->insert(array(
				'ip'				=> $this->request->remoteAddress,
				'last_attempt'		=> new \DateTime,
				'total_attempts'	=> 1,
			));
		}
	}

	/**
	 * Check if user can login (is banned?)
	 * @return int|boolean
	 */
	public function canLogin() {
		$login_attempt = $this->database->table('login_attempt')->where('ip', $this->request->remoteAddress)->fetch();
		$now = new \DateTime;

		if ($login_attempt) {
			$seconds = $now->getTimestamp() - $login_attempt->last_attempt->getTimestamp();

			switch($login_attempt->total_attempts) {
				case 1:
					return true;
				default:
					$bannedFor = ($login_attempt->total_attempts - 1) * 5;	/* the third attempt is processed now */
					$wait = $now->getTimestamp() - $login_attempt->last_attempt->modify('+' . $bannedFor . ' minutes')->getTimestamp();
					if ($wait >= 0) {
						return true;
					} else {
						return (int)((-$wait)/60);
					}

			}
		}

		return true;
	}

}
