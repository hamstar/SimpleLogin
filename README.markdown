# SimpleLogin

SimpleLogin is a PHP class for simple user authentication and registration.  It includes login, logout and registration methods and supports user levels and password salts.

Currently in version 0.1alpha so don't expect things to work properly yet.

## Requirements

SimpleLogin requires the [EasyDB](http://github.com/hamstar/EasyDB) class by myself.

## Configuration

Before you do anything you will need to set your configuration settings that are in the define() function at the start of the class.simplelogin.php file.

Heres what the settings mean.

**USER_TABLE**: The database table containing your users
**USER_NAME_FIELD**: The name of the field in the table that contains the usernames
**USER_PASS_FIELD**: The name of the password field
**USER_EMAIL_FIELD**: The name of the email field
**USER_SALT_FIELD**: The name of the salt field
**USER_LEVEL_FIELD**: The name of the userlevel field

**PRINT_ERRORS**: When set to 1 the class errors are printed to the page.  0 disables this.
**ERROR_WRAPPER**: This is the html that surrounds the errors to style them so they stand out.

You also might like to set the user levels to some other than default.  The user levels are very simple.  Basically it is just an array, the key of an array element (i.e. 1) is the user level and the value of the array element (i.e. 'admin') is the user role.  It is the user level that is saved to the database and converted to a user role on PHP's side of things.

	$user_levels = array(
		1 => 'admin',
		2 => 'user'
	);

Once you set them to match your environment you are ready to go.

## Usage

### Initialization

Init like this (don't forget your session_start() on every page):

	session_start();

	require 'class.simplelogin.php';
	$auth = new SimpleLogin;

### Register a user

To register a user you need the username, the password and the email and optionally the userlevel

	$auth->register('jimbo', 'cornflakes', 'jimbo@example.com');

The password created is an md5 hash of an md5 hash of 'cornflakes' and a random salt.  Basically the equivalent of this:

	md5( md5( $password ) . $salt );

Just like vbulletin.  The password is then stored to the password field and the salt to the salt field in the user table.

### Log a user in or out

To log a user in or out you just need the username and password

	$auth->login('jimbo', 'cornflakes');

This assigns the username to $_SESSION['user'] and the userlevel to $_SESSION['level']

### Log a user out

To log a user out do this:

	$auth->logout();

This runs session_destroy, sets $_SESSION = array() and invalidates any cookies.

### User Levels

Set a user level like this:

	$auth->setLevel('admin', 'jimbo');

	// OR

	$auth->login('jimbo', 'cornflakes');
	$auth->setLevel('admin');

Get a user level like this:

	$userlevel = $auth->getLevel('jimbo');

	// OR

	$auth->login('jimbo', 'cornflakes');
	$userlevel = $auth->getLevel();

	// Outputs "admin"
	echo $userlevel;

Because the login() and register() methods load the username into the class, you can omit the username argument if calling setLevel() or getLevel() after them.

## Contact

Suggestions, problems or comments all welcome at: [hamstar@telescum.co.nz](hamstar@telescum.co.nz)
