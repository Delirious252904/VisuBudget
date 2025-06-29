<?php
// app/config.php

// This file contains application-wide configuration settings.

// IMPORTANT: This key should be kept secret!
// It's used to sign the JWTs. Change this to your own long, random string.
define('JWT_SECRET_KEY', $_ENV['JWT_SECRET_KEY']);

// Information about the token issuer
define('JWT_ISSUER', 'visubudget.local'); // Your website domain
define('JWT_AUDIENCE', 'visubudget.local'); // Your website domain

// How long the token is valid for. 1 day = 86400 seconds.
define('JWT_EXPIRATION_TIME', 86400); 
