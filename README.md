# Perseo Feedback System for WordPress

## Description

The Perseo Feedback System is an open-source plugin for WordPress sites. It adds a feedback prompt to your pages, asking users the question "Hai trovato utile questa pagina?" ("Did you find this page useful?") with "Yes" and "No" options. This feedback is then saved to the WordPress database for later analysis.

The feedback prompt appears 5 seconds after the page has loaded, providing a non-intrusive way of gathering user feedback.

## Installation

1. Download the zip file of the plugin from this GitHub repository.
2. Go to the WordPress admin dashboard, navigate to 'Plugins > Add New > Upload Plugin', and upload the zip file.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. The feedback prompt will now appear on your site.

## Configuration

Before using the Perseo Feedback System, you need to create a `db-config.php` file in the root directory of the plugin. This file should contain the necessary database connection information. You can use the following template:

```php
<?php

define('DB_HOST', 'your_database_host');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASSWORD', 'your_database_password');

?>

```

Make sure to replace your_database_host, your_database_name, your_database_user, and your_database_password with the appropriate values for your WordPress database.

## Usage

The Perseo Feedback System works automatically once activated. All feedback responses from users are stored in a new database table called 'perseo_feedback'.

## Development

This plugin is open for enhancements and bug fixing. You are welcome to contribute to its development.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for more details.

## Authors

- Giovanni Manetti - [GitHub](https://github.com/giovannimanetti11)

