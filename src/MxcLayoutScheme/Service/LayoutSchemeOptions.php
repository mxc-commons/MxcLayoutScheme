<?php

namespace MxcLayoutScheme\Service;

use Zend\Stdlib\AbstractOptions;

class LayoutSchemeOptions extends AbstractOptions {

	/**
	 * @var bool
	 */
	protected $enableMcaLayouts = true;

	/**
	 * @var bool
	 */

	protected $enableRouteLayouts = true;
	/**
	 * @var array()
	 */

	protected $mcaLayouts = array();
	/**
	 * @var array()
	 */

	protected $routeLayouts = array();

	/**
	 * @var array
	 */
	protected $default = array();

	/**
	 * @return the $enableMcaLayouts
	 */
	public function getEnableMcaLayouts() {
		return $this->enableMcaLayouts;
	}

	/**
	 * @return the $enableRouteLayouts
	 */
	public function getEnableRouteLayouts() {
		return $this->enableRouteLayouts;
	}

	/**
	 * @return the $mcaLayouts
	 */
	public function getMcaLayouts() {
		return $this->mcaLayouts;
	}

	/**
	 * @return the $routeLayouts
	 */
	public function getRouteLayouts() {
		return $this->routeLayouts;
	}

	/**
	 * @return the $default
	 */
	public function getDefault() {
		return $this->default;
	}

	/**
	 * @param boolean $enableMcaLayouts
	 */
	public function setEnableMcaLayouts($enableMcaLayouts) {
		$this->enableMcaLayouts = $enableMcaLayouts;
	}

	/**
	 * @param boolean $enableRouteLayouts
	 */
	public function setEnableRouteLayouts($enableRouteLayouts) {
		$this->enableRouteLayouts = $enableRouteLayouts;
	}

	/**
	 * @param \MxcLayoutScheme\Service\array() $mcaLayouts
	 */
	public function setMcaLayouts($mcaLayouts) {
		$this->mcaLayouts = $mcaLayouts;
	}

	/**
	 * @param \MxcLayoutScheme\Service\array() $routeLayouts
	 */
	public function setRouteLayouts($routeLayouts) {
		$this->routeLayouts = $routeLayouts;
	}

	/**
	 * @param multitype: $default
	 */
	public function setDefault($default) {
		$this->default = $default;
	}

}