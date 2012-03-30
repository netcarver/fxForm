<?php

/**
 * Generic HTMLStatement class implementing setters via fluent __call() invocations.
 *
 * eg. $s = fxHTMLStatement('NAME')->class('xyz')->id('abc'); adds 'class' => 'xyz' and 'id' => 'abc' to a set called 'NAME'.
 *
 * Intended for use in defining the attributes that will go in each element's HTML. To avoid possible name collisions between
 * methods in the class and valid HTML attributes all the method names will start with a single underscore.
 *
 **/
abstract class fxNamedSet
{
	/**
	 * Holds the data for this set
	 **/
	protected $_atts;

	/**
	 * Holds the meta data for this set
	 **/
	protected $_meta;


	public function __construct($name)
	{
		fxAssert::isNonEmptyString($name, 'name', "HTML statement must be 'named'.");
		$this->_atts = $this->_meta = array();
		$this->_meta['name'] = $name;
	}


	/**
	 * Used as a generic setter to allow fluent calls to set data or meta-data for the set.
	 *
	 * NB, unmatched calls using names starting with a leading underscore set the meta-data.
	 * eg. $set->_renderer('xyz');	causes 'renderer' => 'xyz' to be added to the $_meta array whilst
	 *     $set->required();  	    causes 'required' => null  to be added to the $_data array.
	 **/
	public function __call( $name, $args )
	{
		if( mb_substr($name,0,1) === '_' && mb_strlen($name) > 1 ) {
			$this->_meta[mb_substr($name,1)] = $args[0];
		}
		else
			$this->_atts[$name] = $args[0];
		return $this;
	}


	public function __get( $name )
	{
		if( ('_name' === $name) || ('name' === $name && !array_key_exists('name',$this->_atts) ) )
			return $this->_set_name;

		$r = @$this->_atts[$name];
		if( null === $r ) $r = '';
		return $r;
	}


	public function _getAtts()
	{
		return $this->_atts;
	}


	public function _getName()
	{
		return $this->_meta['name'];
	}


	/**
	 * Provides a unique value that can be used to identify an HTML entity.
	 **/
	protected function _fingerprint()
	{
		return md5( serialize($this) );
	}


	/**
	 * Get the list of attributes in a format suitable for including in the generated HTML.
	 **/
	public function _getAttrList( $excludes='' )
	{
		$o = '';
		if( !empty( $this->_atts ) ) {
			$excludes = explode( ',', $excludes );
			foreach( $this->_atts as $k => $v ) {
				if( !in_array($k, $excludes) ) {
					$k = htmlspecialchars( $k );

					// NULL values lead to output like <XYZ ... readonly ...>
					if( NULL === $v ) {
						$o .= " $k";
					}

					// Otherwise we get <XYZ ... class="abc" ... >
					else {
						$v = htmlspecialchars( $v );
						$o .= " $k=\"$v\"";
					}
				}
			}
		}
		return $o;
	}


	/**
	 * Can be used to convert strings such as textual labels into simpler strings suitable for use as an HTML statement's id.
	 **/
	static public function _simplify($name)
	{
		return wire()->sanitizer->pageName($name, true);
	}
}


#eof
