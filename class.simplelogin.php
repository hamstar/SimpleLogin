<?php

	/**
	* SimpleLogin class
	*
	* Makes logging in and out and registering users a breeze.
	*
	* Requires:	http://github.com/hamstar/EasyDB
	*
	* @author		Robert McLeod
	* @copyright	Copyright 2009 Robert McLeod
	* @licence		GNU GPL
	* @version		0.1a
	*/
	

	// Constants
	define('USER_TABLE','users');
	define('USER_NAME_FIELD','u');
	define('USER_PASS_FIELD','p');
	define('USER_SALT_FIELD','s');
	define('USER_EMAIL_FIELD','e');
	define('USER_LEVEL_FIELD','l');

	define('PRINT_ERRORS',1);
	define('ERROR_WRAPPER','<p style="border: 2px solid red; color: red;">{ERROR_MESSAGE}</p>');
	
	/*** Role levels ***
	* Enter your roles and levels here.
	* Remember to leave the comma off for the last array entrie
	* Probably best to go upwards (1,2,3 etc) instead of 
	* backwards (-1,-2,-3 etc) for the user levels
	*/
	$user_levels = array(
		1 => 'admin',
		2 => 'user'
	);
	
	/*** Error Messages ***
	* These are just here to help you debug issues quicker
	* Errors are called in the class by the error method and
	* then the error message array key is given as an argument
	* Should probably leave them alone unless you know what you
	* are doing
	*/
	$error_messages = array(
		1 => 'Invalid username or password.',
		2 => 'No user data was found in the object.',
		3 => 'No user given for Auth::getLevel method',
		4 => 'User not found while running Auth::getLevel method',
		5 => 'No user given for Auth::setLevel method',
		6 => 'Userlevel could not be updated in Auth::setLevel method',
		7 => 'Invalid userlevel specification given in Auth::setLevel method',
		8 => 'Failed to logout'
	);
	
	// Add the arrays into constants
	define('USER_LEVELS', serialize($user_levels));
	define('ERROR_MESSAGES', serialize($error_messages));

	// Dependancies
	include 'class.easydb.php';

	/**
	* Main Class
	*	
	*/
	class SimpleLogin {
	
		public $pass;
		public $user;
		private $errors;
		private $userlevels;
		private $db;
		
		// Construct function
		function __construct($u = false, $p = false) {
		
			// For the wierdos who like entering data
			// on opening the class
			$this->user = ($u) ? $u : false;
			$this->pass = ($p) ? $p : false;
		
			// Start up the array constants
			$this->errors = unserialize(ERROR_MESSAGES);
			$this->userlevels = unserialize(USER_LEVELS);
		
			// Connect to the database
			$this->db = new DB;
		
		}
		
		// Destroy function
		function __destroy() {
			
			$this->errors = null;
			$this->userlevels = null;
			$this->db->__destroy();
			
		}

		/**
		* Function to handle errors
		*
		* @param integer
		* @return false
		*/
		private function error($num) {
			// Set the error for this object
			$this->error = $this->errors[$num];
			
			// Print errors if need be
			if( PRINT_ERRORS ) {
				echo str_replace( '{ERROR_MESSAGE}', $this->error, ERROR_WRAPPER);
			}
			
			// Return false
			return false;
		}

		/**
		* Function to add a user to the database
		*
		* @param string
		* @param string
		* @param string
		* @return integer
		*/
		public function register($u,$p,$e,$l = 'user') {
		
			// Add all the user data to class and array
			$this->pass = $p;
			$this->user = $user[USER_NAME_FIELD] = $u;
			$user[USER_PASS_FIELD] = $this->genPasswd();
			$user[USER_SALT_FIELD] = $this->salt;
			$user[USER_EMAIL_FIELD] = $e;
			$user[USER_LEVEL_FIELD] = $l;
			
			// Return the user id
			return $this->db->insert(USER_TABLE, $user);
		
		}
		
		/**
		* Logs a user in by adding data to the session
		*
		* @param string
		* @param string
		* @return bool
		*/
		public function login($u = false,$p = false) {
		
			// Check that we have user and password data
			if(!$u || !$p) {
				if(!$this->user || !$this->pass) {
					return $this->error(2);  // No data at all return false;
				} else {
					// Otherwise use the class user data
					$u = $this->user;
					$p = $this->pass;
				}
			} else {
				// Otherwise add the given user data to the class
				$this->user = $u;
				$this->pass = $p;
			}
			
			// Get the salt
			$this->salt = $this->db->read('SELECT '.USER_SALT_FIELD.' FROM '.USER_TABLE.' WHERE `'.USER_NAME_FIELD.'` = \''.$u.'\' LIMIT 1;')->salt;
			
			// The usernmae wasn't found
			if(!$this->salt) {
				$this->error(1);
			}
			
			// Check the password
			if($this->chkPasswd()) {
			
				// Add the user and level into the session headers
				$_SESSION['user'] = $this->user;
				$_SESSION['level'] = $this->getLevel();
				
				return true;
				
			}
			
			// The password given was incorrect
			return $this->error(1);
		
		}
		
		/**
		* Logout function
		*
		* @return bool
		*/
		public function logout() {

			// Unset the session array
			$_SESSION = array();
			
			// Delete the cookie if there was one
			if (isset($_COOKIE[session_name()])) {
				setcookie(session_name(), '', time()-42000, '/');
			}
			
			// Destroy the session
			session_destroy();
			
			// If the session has nothing left in it return true
			if(count($_SESSION) == 0) {
				return true;
			}
			
			return $this->error(8);
		
		}
		
		/**
		* Generates a users password for either checking against or
		* putting in the database of a new user
		*
		* @return string
		*/
		private function genPasswd() {
		
			// If there is no salt in the class then generate one
			if(!$this->salt) {
				// Generate a four digit salt
				$this->salt = substr( md5( mt_rand(0,256) ), 0, 4);
			}
			
			// Generate the password and return it
			return md5( md5( $this->pass . $this->salt ));
		}
		
		/**
		* Checks that the users password is correct
		*
		* @return bool
		*/
		private function chkPasswd() {
		
			// Query the database for user and password
			$this->db->read(
				'SELECT '.USER_NAME_FIELD.' 
				FROM '.USER_TABLE.' 
				WHERE `'.USER_NAME_FIELD.'` = \''.$this->user.'\' 
					AND `'.USER_PASS_FIELD.'` = \''. $this->genPasswd() .'\';'
			);
			
			// Check if the password was correct and return
			if($this->db->rows()) {
				return true;
			}
		
		}
		
		/**
		* Get the user level
		*
		* @return string
		*/
		public function getLevel($u = false) {
			
			// Check for user name
			if(!$u) {
				if(!$this->user) {
					$this->error(3);
				}
			} else {
				$this->user = $u;
			}
			
			// Query the database for the user level
			$user = $this->db->read('SELECT '.USER_LEVEL_FIELD.' FROM '.USER_TABLE.' WHERE `'.USER_NAME_FIELD.'` = \''.$this->user.'\';');
			
			// If there is a user level check which it is
			if($user->{USER_LEVEL_FIELD}) {
				return $this->userlevels[$user->{USER_LEVEL_FIELD}];
			}
			
			// Username not found in database
			return $this->error(4);
		}
		
		/**
		* Set the user level of a user
		*
		* @param string
		* @return bool
		*/
		public function setLevel($l = 'user', $u = false) {
		
			// Check for user name
			if(!$u) {
				if(!$this->user) {
					$this->error(5);
				}
			} else {
				$this->user = $u;
			}
			
			// Get the userlevel for
			$l = array_search($l, $userlevels);
			
			// If we found the given userlevel..
			if($l !== false) {
				// ...update the user table with the new level
				$this->db->update(USER_TABLE, array( USER_LEVEL_FIELD => $l ), array( USER_NAME_FIELD => $this->user ));
				
				// Check if the update worked
				if($this->db->rows()) {
					return true;
				} else {
					// Couldn't update the database
					return $this->error(6);
				}
			}
			
			// Couldn't find given userlevel
			$this->error(7);
		
		}
	
	}

?>