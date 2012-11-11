<?php

include('path/to/DataValidator.php');  //or use an autoloader

$validator = new DataValidator();  //instantiate the object


//your validation rules here...
$rules = array(
	array(
		'field' => 'email',
		'label' => 'Email Address',
		'rules' => array('is_required', 'is_unique[users.email]', 'is_valid_email')
	),
	array(
		'field' => 'password',
		'label' => 'Password',
		'rules' => array('is_required', 'is_valid_password')
	),
	array(
		'field' => 'password_confirm',
		'label' => 'Password Confirmation',
		'rules' => array('is_required', 'matches[password]')
	)
);

$validator->set_rules($rules);

//PDO connection required for 'is_unique'
$validator->set_connection(new PDO('mysql:host=localhost;dbname=mydb', 'root', 'password')); 

//this would usually be your $_POST array
$values = array(
	'email' => 'example',
	'password' => 'MyPassword1',
	'password_confirm' => 'MyPassword2'
);

if ( $validator->validate($values) ) {  //if validation is true (successful)
	$message = '<p class="success">You have been successfully registered!</p>';
} else {  //if something is invalid
	$message = $validator->get_errors('<p class="error">%s</p>');  //wrap messages in a 'p' tag
	//if you exclude the paramater, the method will return an array of the error messages instead of a string
}

echo $message;

/* 
* OUTPUT
*
* The Email Address field must be a valid email address.
*
* The Password Confirmation field must match the Password field.
*
*/
