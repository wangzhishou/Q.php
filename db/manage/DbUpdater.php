<?php
/**
 * DbUpdater abstract class file.
 *
 * @author Richard Myers <richard.myers@hotmail.co.uk>
 * @link http://www.php.com/
 * @copyright Copyright &copy; 2009 Leng Sheng Hong
 * @license http://www.php.com/license
 * @package .db
 * @since 1.3
 */
abstract class DbUpdater {

	/**
	 * This represents the newest version update avaliable
	 * This should reflect the id of the newest upgrade_* function
	 * @var int
	 */
	protected $latestVersion = 0;

	/**
	 * A DbAdmin adapater for the active DB engine
	 * @var ManageDb
	 */
	public $db = null;


	/**
	 * Sets up the DbUpdater
	 * @param array $dbConfig Database connection settings (db_host, db_name, db_user, db_pwd, db_driver, db_connection_cache)
	 */
	public function __construct($dbConfig) {

		// Load the correct Db Manager adapter based on the db_engine/driver being used
		$this->db = $this->getDbEngineManager($dbConfig[4]);	// element 4 is the db_driver

		// Configure the database connection settings
		$this->db->setDb($dbConfig);
		$this->db->connect();
	}

	public function enableDebugging($enable) {
		$this->db->enableSqlHistory($enable);
	}

	

	/**
	 * Will run all of the updates since the current version upto and including the
	 * DbUpdater::latestVersion. eg. if the current version is 5 and the latestVersion
	 * is set to 10 then updates 6, 7, 8, 9 and 10 will be run
	 * @return void
	 */
	public function updateToLatestVersion() {
		$this->updateToVersion($this->latestVersion);
	}

	/**
	 * Will run all of the downgrade steps from the current version to the very start
	 * This will leave the database as it was before any upgrades
	 * @return
	 */
	public function revertToStart() {
		$this->downgradeToVersion(0);
	}

	/**
	 * Will run all of the required updates since the current version upto and including
	 * the the version specified. eg. A call to updateToVersion(10) when the current version
	 * is 5 will run updates 6, 7, 8, 9 and 10
	 * @param int $version Version to update to
	 * @return
	 */
	public function updateToVersion($version) {

		if ($this->getCurrentDbVersion() >= $version) {
			return;
		}

		$updateTo = null;

		try {
			for ($updateTo = $this->getCurrentDbVersion() + 1; $updateTo <= $version; $updateTo++) {
				$this->executeVersionUpdate($updateTo);
				$this->storeCurrentDbVersion($updateTo);
			}
		} catch (Exception $ex) {
			throw new DbUpdateException("Error Running Database Update : $updateTo", $ex->getMessage());
		}
	}

	/**
	 * Will run all of the required downgrades from the current version down to and including
	 * the the version specified. eg. A call to downgradeToVersion(5) when the current version
	 * is 10 will run downgrades 10, 9, 8, 7 and 6
	 * @param int $version Version to downgrade to
	 * @return
	 */
	public function downgradeToVersion($version) {

		if ($this->getCurrentDbVersion() <= $version) {
			return;
		}

		$downgradeTo = null;

		try {
			for ($downgradeTo = $this->getCurrentDbVersion(); $downgradeTo > $version; $downgradeTo--) {
				$this->executeVersionDowngrade($downgradeTo);
				$this->storeCurrentDbVersion($downgradeTo);
			}
			$this->storeCurrentDbVersion($downgradeTo);
		} catch (Exception $ex) {
			throw new DbUpdateException("Error Running Database Downgrade : $downgradeTo", $ex->getMessage());
		}
	}


	/**
	 * Runs a single update step so a call to executeVersionUpdate(12) will run the function
	 * upgrade_12() in the extending class. It will throw an exception on an error running the
	 * update or if the update function is missing
	 * @param int $version The update version to run
	 * @return
	 */
	public function executeVersionUpdate($version) {
		$methodName = 'upgrade_' . $version;

		if (method_exists($this, $methodName) == true) {
			$this->$methodName();
		} else {
			throw new DbUpdateException("Database upgrade function $methodName could not be found");
		}
	}

	/**
	 * Runs a single downgrade step so a call to executeVersionDowngrade(12) will run the function
	 * downgrade_12() in the extending class. It will throw an exception on an error running the
	 * downgrade or if the downgrade function is missing
	 * @param int $version The downgrade version to run
	 * @return
	 */
	public function executeVersionDowngrade($version) {
		$methodName = 'downgrade_' . $version;

		if (method_exists($this, $methodName) == true) {
			$this->$methodName();
		} else {
			throw new DbUpdateException("Database downgrade function $methodName could not be found");
		}
	}

	abstract public function getCurrentDbVersion();
	abstract public function storeCurrentDbVersion($version);

	/**
	 * Gets an instance of a Database Admin Adapter for the current DB's Engine
	 * @param object $engine
	 * @return
	 */
	private function getDbEngineManager($engine) {

		if ($engine == 'mysql') {
			Q::loadCore('db/manage/adapters/ManageMySqlDb');
			return new ManageMySqlDb();
		} else if ($engine == 'pgsql') {
			Q::loadCore('db/manage/adapters/ManagePgSqlDb');
			return new ManagePgSqlDb();
		} else if ($engine == 'sqlite') {
			Q::loadCore('db/manage/adapters/ManageSqliteDb');
			return new ManageSqliteDb();
		} else {
			throw new DbUpdateException("Unsupported Database Engine : $engine");
		}
	}
}


class DbUpdateException extends Exception {
	/**
	 * An exception thrown by the Updater
	 * @param string $error The error which occured
	 * @param string $reason [optional] Reason for the error
	 * @return
	 */
	function __construct($error, $info = "Unknown") {
		parent::__construct($error . "\n" . $info);
	}
}
