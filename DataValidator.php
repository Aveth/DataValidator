<?php

/**
* A data validation class. Inspired by CodeIgniter's form validation library.
*
* @author Avais Sethi
*/

class DataValidator {

	private $_errors 	= array();  //array of error messages
	private $_rules 	= array();  //array of rules to validate against
	private $_values 	= array();  //array of values to validate
	private $_message 	= null;  //error message for current validation
	private $_db 		= null;  //PDO connection for DB related validation
 
	/**
	* Set PDO connection for DB related validation.
	*
	* @access public
	* @param PDO $conn PDO connection
	*/
	public function set_connection($conn) {
		$this->_db = $conn;
	}

	/**
	* Set values to validate.
	*
	* @access public
	* @param mixed $field  If array is given, this will set all values. If string, will be used as a key for $value.
	* @param mixed $value  Must be provided if string is given for field.
	*/
	public function set_values($field, $value = null) {
		if ( is_array($field) ) {  //if is array, then overwrite the entire array
			$this->_values = $field;
		} else {
			$this->_values[$field] = $value;  //otherwise, just stack values
		}
	}

	/**
	* Set rules to validate against.
	*
	* @access public
	* @param mixed $field  If array is given, this will set all rules. If string, will be used as a key for $value.
	* Must be a valid validation function.
	* @param mixed $value  Must be provided if string is given for field.
	*/
	public function set_rules($field, $value = null) {
		if ( is_array($field) ) {  //if is array, then overwrite the entire array
			$this->_rules = $field;
		} else {
			$this->_rules[$field] = $value;  //otherwise, just stack rules
		}
	}

	/**
	* Run validation.
	*
	* @access public
	* @param array $values  Must be provided if values to validate have not been set in set_values
	* @return bool  TRUE if no values are invalid, otherwise FALSE
	*/
	public function validate($values = null) {
		if ( isset($values) ) $this->_values = $values;

		//loop through array of rule sets
		foreach ( $this->_rules as $details ) {
			extract($details);
			$value = isset($this->_values[$field]) ? $this->_values[$field] : '';  //make sure there is a value set for the field

			//loop through each rule for that field
			foreach ( $rules as $rule ) {
				if ( is_array($rule) ) {  //check to see if a custom message was provided through an assoc array
					$message = isset($rule['message']) ? $rule['message'] : null;  //if there is a message, use it instead of the default
					$this->_execute($field, $rule['rule'], $value, $label, $message);  //execute the validation
				} else {
					$this->_execute($field, $rule, $value, $label);  //execute the validation
				}
			}
		}

		return ( empty($this->_errors) );  //check to see if any errors were logged and return
	}

	/**
	* Execute a validation rule for a single value.
	*
	* @access private
	* @param string $field
	* @param string $rule
	* @param mixed $value
	* @param string $label
	* @param string $message
	*/
	private function _execute($field, $rule, $value, $label, $message = null) {
		//if the field is not required, then ignore empty strings
		if ( ($rule == 'is_required') || ($rule != 'is_required' && trim($value) != '') ) { 
			$params = null;

			//extract rule and params
			if ( preg_match('/(.*?)\[(.*)\]/', $rule, $match) ) { 
				$rule = $match[1];  //set rule
				$params = $match[2];  //set params
				$params = preg_split('/,|(, )/', $params);  //split params by comma
				$params = count($params) > 1 ? $params : $params[0];  //if single param
			}

			//run validation 
			if ( !$this->$rule($value, $params) ) {
				$this->_errors[$field] = str_replace('%s', $label, 
					(isset($message) ? $message : $this->_message));
			}
		} 
	}
	
	public function add_error($key, $message) {
		$this->_errors[$key] = $message;
	}

	/**
	* Retrieve all error messages.
	* 
	* @access public
	* @param string $wrapper  Wrap error messages in a valid HTML element. Token %s will be replaced by message.
	* @return mixed  If $wrapper is provided, returns string of messages wrapped in HTML elements.
	* If $wrapper is not provided, returns array of messages.
	*/
	public function get_errors($wrapper = null) {
		if ( !isset($wrapper) ) return $this->_errors;  //if no wrapper, just return the array
		
		$errors = null;
		foreach ( $this->_errors as $error ) {
			$errors .= str_replace('%s', $error, $wrapper);  //wrap each error message in given string
		}

		return $errors; 
	}
	
	public function error_exists($key) {
		return array_key_exists($key, $this->_errors);
	}

	public function is_required($value) {
		$value = trim($value);
		$this->_message = 'The %s field is required.';
		return $value !== '';
	}

	public function is_numeric($value) {
		$this->_message = 'The %s field must be a number.';
		return is_numeric($value);
	}

	public function is_valid_password($value) {
		$this->_message = 'The %s field must be a stronger password.';
		$regex = '/(?=^.{6,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/';

		return preg_match($regex, $value, $array);
	}

	public function is_phone_number($value) {
		$replace = '/[^0-9]/';
		$value = preg_replace($replace, '', $value);
		$this->_message = 'The %s field must be a valid phone number.';
		return ( strlen($value) >= 10 );
	}

	public function is_valid_email($email) {
		$checkDNS = false;
		$this->_message = 'The %s field must be a valid email address.';

		try {
		    $valid = (function_exists('filter_var') && filter_var($email, FILTER_VALIDATE_EMAIL)) || 
		    	(
		    		strlen($email) <= 320 && preg_match(
		                '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?))'. 
		                '{255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?))'.
		                '{65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|'.
		                '(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))'.
		                '(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|'.
		                '(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|'.
		                '(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})'.
		                '(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126})'.'{1,}'.
		                '(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|'.
		                '(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|'.
		                '(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::'.
		                '(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|'.
		                '(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|'.
		                '(?:(?!(?:.*[a-f0-9]:){5,})'.'(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::'.
		                '(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|'.
		                '(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|'.
		                '(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))\.$/iD',
		                $email
		            )
		    	);
	    
			    if ( $valid ) {
			        if ( $checkDNS && ($domain = end(explode('@',$email, 2))) ) {
						return checkdnsrr($domain . '.', 'MX');
			        }
			        return true;
			    }
			    return false;
		} catch (Exception $e) {
			return false;
		}
	}

	public function is_natural_number($value) {
		$this->_message = 'The %s field must be an integer greater than 0.';
		return ( ctype_digit($value) && $value >= 0 );
	}

	public function is_unique($value, $field) {
		if ( !isset($this->_db) ) throw new Exception('<p><b>ERROR:</b> Cannot connect to database to validate uniqueness.');
		$this->_message = 'The %s given already exists in the system.';

		list($table, $field) = explode('.', $field);

		$cmd = $this->_db->prepare('SELECT '.$field.' FROM '.$table.' WHERE '.$field.' = ?');
		$cmd->execute(array($value));
		$results = $cmd->fetchAll(PDO::FETCH_ASSOC);

		return empty($results);
	}

	public function matches($value, $field) {
		$label = $field;
		
		foreach ( $this->_rules as $details ) {
			if ( $details['field'] === $field) {
				$label = $details['label'];
				break;
			}
		}
		
		$this->_message = 'The %s field must match the '.$label.' field.';
		return ($value === $this->_values[$field]);

	}

	public function is_min_length($value, $length) {
		$this->_message = 'The %s field must be at least '.$length.' characters.';
		return !(strlen($value) < $length)
	}

	public function is_max_length($value, $length) {
		$this->_message = 'The %s field must cannot exceed '.$length.' characters.';
		return !(strlen($value) > $length);
	}

	public function is_length($value, $length) {
		$this->_message = 'The %s field must be exactly '.$length.' characters.';
		return (strlen($value) == $length);
	}

	public function is_greater_than($value, $number) {
		$this->_message = 'The %s field must be numeric and greater than '.$number.'.';
		if ( !is_numeric($value) ) return false;
		return ($value > $number);
	}

	public function is_less_than($value, $number) {
		$this->_message = 'The %s field must be numeric and less than '.$number.'.';
		if ( !is_numeric($value) ) return false;
		return ($value < $number);
	}

	public function is_year($value) {
		$this->_message = 'The %s field must be a valid year.';
		if ( !ctype_digit($value) ) return false;
		$value = (int)$value;
		return ($value >= 1800 && $value <= date('Y')); 
	}

}
