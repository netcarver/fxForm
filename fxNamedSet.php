<?php

/**
 * Generic class storing data and meta-data and implementing all getters and setters upon those data via __call() invocations.
 *
 * Developed with defining the attributes of HTML statements in mind, this class also allows meta-data to be saved using the same methods but
 * prefixing the keys of all meta-data with an underscore.
 *
 * eg.  $s = new fxNamedSet('NAME');
 *      $s->class('xyz')->id('abc');  // adds 'class' => 'xyz' and 'id' => 'abc' to the data array in a set called 'NAME'.
 *      $s->_renderer('bootstrap');   // adds 'renderer' => 'bootstrap' to the meta-data array in the set.
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



	/**
	 * Sets up a named set containing both data and meta-data.
	 **/
	public function __construct($name)
	{
		fxAssert::isNonEmptyString($name, 'name', "Each fxNamedSet must be have a name.");
		$this->_data = $this->_meta = array();
		$this->_meta['name'] = $name;
	}


	/**
	 * Determines if the given string is a reference to the meta data. If it is, it converts it to
	 * a key for accessing the _meta array and returns true. If not, it leaves it alone and returns false.
	 *
	 * Meta data is always accessed using a single underscore as the first character.
	 **/
	public static function _isMeta( &$s )
	{
		fxAssert::isNonEmptyString($s,'$s');
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
	 * eg. $s->_renderer('xyz');    causes 'renderer' => 'xyz' to be added to the $_meta array but
	 *     $s->required('xyz');     causes 'required' => 'xyz' to be added to the $_data array.
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


	/**
	 * Allows direct read of _meta or _data items...
	 **/
	public function __get( $name )
	{
		if( self::_isMeta( $name ) )
			$r = @$this->_meta[$name];
		else
			$r = @$this->_data[$name];
		return $r;
	}


	/**
	 * Allows direct setting of _meta or _data items...
	 **/
	public function __set( $name , $arg )
	{
		if( self::_isMeta($name) ) {
			$this->_meta[ $name ] = $arg;
		}
		else
			$this->_data[$name] = $arg;
		return $arg;
	}



	/**
	 * Allows fxNamed set objects to be used in empty() and isset() tests.
	 * Without this we can get some unexpected results.
	 **/
	public function __isset( $name )
	{
		if( self::_isMeta($name) )
			return isset( $this->_meta[$name] );

		return isset($this->_data[$name]);
	}



	/**
	 * Allows values in the _data and _meta arrays to be unset.
	 **/
	public function __unset($name)
	{
		if( self::_isMeta($name) ) {
			unset( $this->_meta[ $name ] );
		}
		else
			unset( $this->_data[$name] );
	}


	/**
	 * Tests if the given key exists in the set's data array.
	 **/
	public function _inData($key)
	{
		return array_key_exists( $key, $this->_data );
	}



	/**
	 * Tests if the given key exists in the set's meta data array.
	 **/
	public function _inMeta($key)
	{
		return array_key_exists( $key, $this->_meta );
	}



	/**
	 * Returns the array of data from this set.
	 **/
	public function _getData()
	{
		return $this->_data;
	}



	/**
	 * Returns the array of meta data from this set.
	 **/
	public function _getMeta()
	{
		return $this->_meta;
	}



	/**
	 * Allows access to all but named, excluded, items from _data or _meta.
	 * Can be used by renderers to pull subsets of _data for creating an HTML element's attributes.
	 **/
	public function _getInfoExcept( $excludes = '', $use_meta = false )
	{
		$excludes = array_flip(explode( ',', $excludes ));
		return array_diff_key( (($use_meta) ? $this->_meta : $this->_data) , $excludes );
	}



	/**
	 * Provides a unique value that can be used to identify an HTML entity.
	 **/
	protected function _fingerprint()
	{
		return md5( serialize($this) );
	}

}


#eof
