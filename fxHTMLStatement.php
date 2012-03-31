<?php

/**
 * Generic HTMLStatement class implementing setters via fluent __call() invocations.
 *
 * eg. $s = fxNamedSet('NAME')->class('xyz')->id('abc'); adds 'class' => 'xyz' and 'id' => 'abc' to a set called 'NAME'.
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
	protected $_data;

	/**
	 * Holds the meta data for this set
	 **/
	protected $_meta;


	public function __construct($name)
	{
		fxAssert::isNonEmptyString($name, 'name', "Each fxNamedSet must be 'named'.");
		$this->_data = $this->_meta = array();
		$this->_meta['name'] = $name;
	}


	/**
	 * Determines if the given string is a reference to the meta data. If it is, it converts it to
	 * a key for accessing the _meta array and returns true. If not, it leaves it alone and returns false.
	 **/
	public static function _isMeta( &$s )
	{
		if( mb_substr($s,0,1) === '_' && mb_strlen($s) > 1 ) {
			$s = mb_substr( $s, 1 );
			return true;
		}
		return false;
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
		if( self::_isMeta($name) ) {
			$this->_meta[ $name ] = $args[0];
		}
		else
			$this->_data[$name] = $args[0];
		return $this;
	}


	public function __get( $name )
	{
		if( self::_isMeta( $name ) )
			$r = @$this->_meta[$name];
		else
			$r = @$this->_data[$name];
		return $r;
	}


	public function _getAtts()
	{
		return $this->_data;
	}

	public function _getMeta()
	{
		return $this->_meta;
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
	 * Get the list of attributes in a format suitable for including in the generated HTML. TODO : this looks like it's rendering HTML to me! Belongs in the renderer.
	 **/
	public function _getAttrList( $excludes='' )
	{
		$o = '';
		if( !empty( $this->_data ) ) {
			$excludes = explode( ',', $excludes );
			foreach( $this->_data as $k => $v ) {
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
}


#eof
