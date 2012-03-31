<?php

class fxBasicHTMLFormRenderer extends fxHTMLRenderer
{
	static public function render( fxFormElement $e, $values = array() )
	{
		$array  = $e->_getInfoExcept( 'class,value' );
		$attr   = self::renderAtts( $array );
		$label  = htmlspecialchars($e->_name);
		$class  = htmlspecialchars($e->class);
		$chckd  = (@$e->_checked) ? 'checked' : '';
		$subval = $e->_value;
		$elval  = htmlspecialchars($e->value);
		$id     = htmlspecialchars($e->id);
		$plce   = (string)$e->_note;
		if( '' !== $plce )
			$plce = ' placeholder="'.htmlspecialchars($plce).'"';
		$o = array();

		if( $e->required ) {
			$class  .= ' required';
		}
		$class   = trim( $class );
		$type  = htmlspecialchars( strtr( strtolower($e->_getHTMLType()), array('fxform'=>'') ) );

		if( !$e->_nolabel )
			$o[] = "<label for=\"$id\">$label</label>";
		$o[] = "<$type$attr$plce";
		$o[] = "class=\"$class\"";
		$o[] = $chckd;

		if( 'textarea' == $type )
			$o[] = ">$subval</textarea>";
		elseif( 'button' == $type || 'submit' == $e->type || 'reset' == $e->type )
			$o[] = "value=\"$elval\" />$label</button>";
		elseif( in_array( $e->type, fxFormElement::$radio_types) || 'hidden' === $e->type )
			$o[] = "value=\"$elval\" />";
		else
			$o[] = "value=\"$subval\" />";

		return join( " ", $o );
	}
}




#eof
