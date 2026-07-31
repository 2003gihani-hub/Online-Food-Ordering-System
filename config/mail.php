<?php
// Prevent direct access to this configuration file
if (count(get_included_files()) === 1) {
    exit("Direct access not permitted.");
}

/**
 * FoodExpress SMTP Gmail Configuration
 * 
 * Replace placeholders with your actual Gmail account and Gmail App Password.
 * To generate an App Password:
 * 1. Enable 2-Step Verification on your Google Account.
 * 2. Go to Security > App Passwords.
 * 3. Generate a password for 'Mail' / 'Other (Custom name)'.
 */
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);                     // 587 for TLS, 465 for SSL
define('SMTP_SECURE', 'tls');                 // 'tls' or 'ssl'
define('SMTP_USER', '2003gihani@gmail.com'); // Replace with your Gmail address
define('SMTP_PASS', 'yuyq ekrh rimz rsrx');       // Replace with your Gmail App Password
define('SMTP_FROM_EMAIL', '2003gihani@gmail.com'); // Replace with your sender email
define('SMTP_FROM_NAME', 'FoodExpress');

// Set to true to bypass SSL certificate verification issues (common in local XAMPP development environments)
define('SMTP_ALLOW_SELF_SIGNED', true);

