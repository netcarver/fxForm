<?php

require_once( 'fxAssert.php' );
require_once( 'fxCSRFToken.php' );
require_once( 'fxNamedSet.php' );
require_once( 'fxFormElements.php' );
require_once( 'fxForm.php' );
require_once( 'fxRenderer.php' );
require_once( 'fxBasicHTMLFormRenderer.php' );


/**
 * Convenience creator functions. These allow chaining from point of creation and make for a more fluent interface...
 **/
function Form		( $form_name, $action, $method="post" )	{ return new fxForm( $form_name, $action, $method ); }
function Checkboxes	( $name, $label, $members_array ) 		{ return new fxFormCheckboxset( $name, $label, $members_array ); }
function Checkbox  	( $name, $label, $val )           		{ return new fxFormCheckbox($name, $label, $val); }
function Radios		( $name, $label, $members_array )	 	{ return new fxFormRadioset( $name, $label, $members_array ); }
function Select  	( $name, $label, $options_array )		{ return new fxFormSelect( $name, $label, $options_array ); }
function MSelect 	( $name, $label, $options_array )		{ return new fxFormMSelect( $name, $label, $options_array ); }
//function Text		( $text )            					{ return new fxFormString( $text ); }
function Input		( $name, $label, $note=null )	     	{ return new fxFormInput( $name, $label, $note ); }
function Integer    ( $name, $label, $note=null )			{ return Input($name, $label, $note)->type('integer'); }
function Email		( $name, $label, $note=null )	     	{ return Input($name, $label, $note)->type('email'); }
function URL  		( $name, $label, $note=null )	     	{ return Input($name, $label, $note)->type('url'); }
function Tel  		( $name, $label, $note=null )	     	{ return Input($name, $label, $note)->type('tel'); }
function Boolean    ( $name, $label, $note=null )			{ return Input($name, $label, $note)->type('boolean'); }
function YesNo      ( $name, $label, $note=null, $msg=null)	{ return Input($name, $label, $note)->pattern('/^yes|no$/i',$msg); }
function Password	( $name, $label )      					{ return new fxFormPassword( $name, $label ); }
function Hidden		( $name, $value ) 						{ return new fxFormHidden( $name, $value ); }
function TextArea	( $name, $label, $note )      			{ return new fxFormTextArea( $name, $label, $note ); }
function Button		( $text )         						{ return new fxFormButton( $text ); }
function Submit		( $text ) 	     						{ return new fxFormSubmit( $text ); }
//function Reset 		( $text ) 	 							{ return new fxFormReset( $text ); }
function Fieldset	( $legend, $name=null )					{ return new fxFormFieldset( $legend, $name ); }

#eof
