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
function Checkboxes	( $label, $members_array, $name=null ) 	{ return new fxFormCheckboxset( $label, $members_array, $name ); }
function Radios		( $label, $members_array, $name=null ) 	{ return new fxFormRadioset( $label, $members_array, $name ); }
function Select  	( $label, $options_array, $name=null )	{ return new fxFormSelect( $label, $options_array, $name ); }
function MSelect 	( $label, $options_array, $name=null )	{ return new fxFormMSelect( $label, $options_array, $name ); }
function Text		( $text )            					{ return new fxFormString( $text ); }
function Input		( $label, $note=null )	     			{ return new fxFormInput( $label, $note ); }
function Password	( $label )      						{ return new fxFormPassword( $label ); }
function Hidden		( $name, $value ) 						{ return new fxFormHidden( $name, $value ); }
function TextArea	( $label, $note )      					{ return new fxFormTextArea( $label, $note ); }
function Button		( $text )         						{ return new fxFormButton( $text ); }
function Submit		( $text ) 	     						{ return new fxFormSubmit( $text ); }
//function Reset 		( $text ) 	 							{ return new fxFormReset( $text ); }
function Fieldset	( $legend )     						{ return new fxFormFieldset( $legend ); }

#eof
