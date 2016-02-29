<?php

namespace SMW\Tests;

use SMW\Tests\Utils\UtilityFactory;
use SMW\ApplicationFactory;
use SMW\DataValueFactory;
use SMW\Store;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class TestEnvironment {

	/**
	 * @var ApplicationFactory
	 */
	private $applicationFactory = null;

	/**
	 * @var DataValueFactory
	 */
	private $dataValueFactory = null;

	/**
	 * @var array
	 */
	private $configuration = array();

	/**
	 * @since 2.4
	 */
	public function __construct() {
		$this->applicationFactory = ApplicationFactory::getInstance();
		$this->dataValueFactory = DataValueFactory::getInstance();
	}

	/**
	 * @since 2.4
	 *
	 * @param array $configuration
	 *
	 * @return self
	 */
	public function withConfiguration( array $configuration = array() ) {

		foreach ( $configuration as $key => $value ) {
			$this->configuration[$key] = $GLOBALS[$key];
			$GLOBALS[$key] = $value;
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		return $this;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $poolCache
	 *
	 * @return self
	 */
	public function resetPoolCacheFor( $poolCache ) {
		$this->applicationFactory->getInMemoryPoolCache()->resetPoolCacheFor( $poolCache );
		return $this;
	}

	/**
	 * @since 2.4
	 *
	 * @param string $id
	 * @param mixed $object
	 *
	 * @return self
	 */
	public function registerObject( $id, $object ) {
		$this->applicationFactory->registerObject( $id, $object );
		return $this;
	}

	/**
	 * @since 2.4
	 */
	public function tearDown() {

		foreach ( $this->configuration as $key => $value ) {
			$GLOBALS[$key] = $value;
			$this->applicationFactory->getSettings()->set( $key, $value );
		}

		$this->applicationFactory->clear();
		$this->dataValueFactory->clear();
	}

	/**
	 * @since 2.4
	 *
	 * @return UtilityFactory
	 */
	public function getUtilityFactory() {
		return UtilityFactory::getInstance();
	}

}
