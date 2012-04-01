<?php

class fxBasicHTMLFormRenderer extends fxHTMLRenderer
{
	static public function render( fxFormElement &$e, $parent_id )
	{
		$attr   = self::renderAtts( $e->_getInfoExcept( 'class,value' ) );
		$label  = htmlspecialchars($e->_name);
		$class  = htmlspecialchars($e->class);
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


	static public function renderLabelFor( fxFormElement &$e, $for_id )
	{
		if( $e->_nolabel )
			return '';

		return '<label for="'.htmlspecialchars($for_id).'">'.htmlspecialchars($e->_name).'</label>';
	}


	static public function renderForm( fxForm &$f )
	{
		$o = array();
		$atts = self::renderAtts( $f->_getInfoExcept() );
		$o[] = "<form action=\"{$f->_action}\" method=\"{$f->_method}\"$atts>";
		$o[] = "<input type=\"hidden\" name=\"_form_id\" value=\"{$f->_form_id}\" />";
		$o[] = "<input type=\"hidden\" name=\"_form_token\" value=\"{$f->_form_token}\" />";
		foreach( $f->getElements() as $child ) {
			if( is_string($child) )
				$o[] = $child;
			else {
				$o[] = $child->renderUsing( __CLASS__, $f, $f->id );
			}
		}
		$o[] = "</form>";
		$o = implode( "\n", $o );
		return $o;
	}



	static public function renderRadioset( fxFormRadioset &$e, fxForm &$f, $parent_id )
	{
//$f->dump();
		$o = array();
		foreach( $e->getElements() as $radio ) {
			$o[] = $radio->renderUsing( __CLASS__, $f, $parent_id );
		}
		$o = implode( "\n", $o );
		return $o;
	}



	static public function renderTextarea( fxFormElement &$e, $parent_id )
	{
		return "<textarea>$parent_id</textarea>";
	}



	static public function renderButton( fxFormButton &$e, $parent_id )
	{
		return "<button>$parent_id</button>";
	}

}


#eof
