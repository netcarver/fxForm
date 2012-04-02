<?php

class fxBasicHTMLFormRenderer extends fxHTMLRenderer
{
	static public function render( fxFormElement &$e, $parent_id )
	{
		$attr   = self::renderAtts( $e->_getInfoExcept( 'class,value' ) );
		$label  = htmlspecialchars($e->_name);
		$subval = $e->_value;
		$elval  = htmlspecialchars($e->value);
		$id     = htmlspecialchars($e->id);
		$plce   = (string)$e->_note;
		if( '' !== $plce )
			$plce = ' placeholder="'.htmlspecialchars($plce).'"';

		$o = array();

		$class = self::getClasses($e);
		$type  = htmlspecialchars( strtr( strtolower($e->_getHTMLType()), array('fxform'=>'') ) );

		$o[] = "<$type$attr$plce$class";

		if( 'button' == $type || 'submit' == $e->type || 'reset' == $e->type )
			$o[] = "value=\"$elval\" />$label</button>";
		elseif( in_array( $e->type, fxFormElement::$radio_types) || 'hidden' === $e->type )
			$o[] = "value=\"$elval\" />";
		else
			$o[] = "value=\"$subval\" />";

		$o = join( " ", $o );
		return self::addLabel( $o, $e );
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
echo "<pre>",htmlspecialchars( var_export( $o, true ) ), "</pre>\n";
		return $o;
	}


	static public function renderElementSet( fxFormElementSet &$e, fxForm &$f, $parent_id )
	{
		$o = array();
		foreach( $e->getElements() as $el ) {
			$o[] = $el->renderUsing( __CLASS__, $f, $parent_id );
		}
		$o = implode( "\n", $o );
		return $o;
	}

	static public function renderOptions( $options, fxFormElementSet &$e, fxForm &$f, $parent = '' )
	{
		$html5 = $f->_target === 'html5';
		$html5 = false;
		$o = array();
		if( !empty( $options ) ) {
			foreach( $options as $k => $v ) {
				if( is_array( $v ) ) {
					$o[] = '<optgroup label="'.htmlspecialchars($k).'">';
					$o[] = self::renderOptions($v, $e, $f, fxForm::_simplify($k) );
					if( !$html5 ) $o[] = "</optgroup>";
				}
				else {
					$selected = in_array( fxForm::_simplify($k), $e->_value) ? ' selected' : '' ;
					$o[] = "<option$selected value=\"".fxForm::_simplify($k)."\">".htmlspecialchars($v)."</option>";
				}
			}
		}
		return implode("\n",$o);
	}

	static public function renderSelect( fxFormElementSet &$e, fxForm &$f, $parent_id )
	{
		$o = array();
		$attr   = self::renderAtts( $e->_getInfoExcept( 'class,value' ) );
		//$id     = htmlspecialchars( $e->id );
		$label  = htmlspecialchars( $e->_name );

		$o[] = "<select$attr>";
		/* foreach( $e->_members as $k => $v ) { */
		/* 	if( is_array( $v ) ) { */
		/* 		$o[] = ($f->_target == 'html5') ? '<optgroup label="'.htmlspecialchars($k).'">' : '<optgroup>'; */
		/* 	} */
		/* 	else { */
		/* 		$selected = in_array( $k, $e->_value) ? ' selected' : '' ; */
		/* 		$o[] = "<option$selected value=\"".htmlspecialchars($k)."\">".htmlspecialchars($v)."</option>"; */
		/* 	} */
		/* } */
		$o[] = self::renderOptions( $e->_members, $e, $f );
		$o[] = '</select>';

		$o = implode( "\n", $o );
		return self::addLabel( $o, $e );
	}


	static public function renderTextarea( fxFormElement &$e, $parent_id )
	{
		$attr  = self::renderAtts($e->_getInfoExcept( 'class,value' ));
		$class = self::getClasses($e);
		return self::addLabel( "<textarea$attr$class>{$e->_value}</textarea>", $e );
	}


	static public function renderButton( fxFormButton &$e, $parent_id )
	{
		$attr  = self::renderAtts($e->_getInfoExcept( 'class,value' ));
		$class = self::getClasses($e);
		$label = htmlspecialchars($e->_name);
		return "<button$attr$class>$label</button>";
	}

	/* static public function getID(fxFormElement &$e, $parent_id) */
	/* { */
	/* 	if( $e->_inData('id') ) */
	/* 		return htmlspecialchars($e->id); */

	/* 	$id = ( $parent_id ) ? $parent_id : '' ; */
	/* 	$id = fxForm::_simplify( $id . '_' . $e->_name ); */
	/* 	return $id; */
	/* } */
}


#eof
