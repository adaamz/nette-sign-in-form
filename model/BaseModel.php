<?php

namespace App\Model;

abstract class BaseModel extends \Nette\Object {

	/**
	 * @inject
	 * @var \Nette\Database\Context
	 */
	public $db;

	/**
	 * @inject
	 * @var \Nette\Caching\Cache
	 */
	public $cache;

	/**
	 * @param \Nette\Database\Context $db
	 * @param \Nette\Caching\Cache $cache
	 */
	public function __construct(\Nette\Database\Context $db, \Nette\Caching\Cache $cache) {
		$this->db = $db;
		$this->cache = $cache;
	}

}