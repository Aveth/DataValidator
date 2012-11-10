DataValidator
============

Use this class to validate user input in your models. Inspired by CodeIgniter's form validation library,
but allows you to provide the input values. 

Rules currently allowed:
* is_required
* is_numeric
* is_valid_password (checks password for 6 characters, 1 uppercase, 1 lowercase, 1 number or special char)
* is_phone_number (checks to make sure 10 digits are provided)
* is_valid_email
* is_natural_number
* is_unique (requires an open PDO connection to be set via set_pdo_connection)
* matches[field]
* is_min_length
* is_max_length
* is_length
* is_greater_than
* is_less_than
* is_year

I will add rules by request!