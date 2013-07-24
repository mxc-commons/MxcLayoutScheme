<?php

namespace MxcLayoutScheme\Service;

use Zend\Stdlib\AbstractOptions;

class LayoutSchemeServiceOptions extends AbstractOptions 
{

	/**
	 * @var activeScheme
	 */
	protected $activeScheme = 'zf2';
	
	/**
	 * @var schemes
	 */
	protected $schemes = array();
	
    /**
	 * @return the $schemes
	 */
	public function getSchemes() {
		return $this->schemes;
	}

	/**
	 * @param \MxcLayoutScheme\Service\schemes $schemes
	 */
	public function setSchemes($schemes) {
		$this->schemes = $schemes;
	}

	/**
     * set active layout scheme
     *
     * @param string $activeScheme
     * @return LayoutSchemeOptions
     */
    public function setActiveScheme($activeScheme)
    {
        $this->activeScheme = $activeScheme;
        return $this;
    }

    /**
     * get active layout scheme
     *
     * @return string
     */
    public function getActiveScheme()
    {
        return $this->activeScheme;
    }

    /**
	 * register additional layout schemes
	 * existing schemes with same name get replaced
	 * 
	 * @param array
	 */
	protected function registerSchemes($schemes) {
		$this->schemes = array_merge($this->schemes, $schemes);
	}
}
