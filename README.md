<a href="https://supportukrainenow.org/"><img src="https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner-direct.svg" width="100%"></a>

---

# Create WP Site

<p align="center">
  <a href="https://github.com/modernwpdev/create-wp-site/actions"><img src="https://img.shields.io/github/workflow/status/modernwpdev/create-wp-site/Tests.svg" alt="Build Status"></img></a>
  <a href="https://packagist.org/packages/modernwpdev/create-wp-site"><img src="https://img.shields.io/packagist/dt/modernwpdev/create-wp-site.svg" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/modernwpdev/create-wp-site"><img src="https://img.shields.io/packagist/v/modernwpdev/create-wp-site.svg?label=stable" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/modernwpdev/create-wp-site"><img src="https://img.shields.io/packagist/l/modernwpdev/create-wp-site.svg" alt="License"></a>
</p>

Create WP Site is a cli for quickly downloading, configuring, and installing WordPress using either core [WordPress](https://wordpress.org) or [Bedrock](https://roots.io/bedrock).

Save time and get straight to developing your next theme or plugin. Create WP Site handles the entire setup process, including:

-   Downloading WordPress core with WP-CLI or Bedrock with Composer.
-   Creates a database for you.
-   Configures wp-config.php (WP core) or .env (Bedrock)
-   Installs and configures WordPress using WP-CLI.

Future features include:

-   Automatically setting up multisite when you need it.
-   Automatically installing and setting up WooCommerce when you need it.
-   Install/uninstall and activate/deactivate any plugins you need.
-   Install/uninstall themes from the jump.

---

## Documentation

Using Create WP Site is as straight forward as possible.

### Installation

```bash
composer global require modernwpdev/create-wp-site
```

### Usage

Run the `create-wp-site` command...

```bash
# Basic usage
create-wp-site site-name

# Using the --core flag to install vanilla WordPress
create-wp-site site-name --core

# Using the --bedrock flag to install Bedrock
create-wp-site site-name --bedrock
```

Then, simply follow the prompts and let Create WP Site handle the rest.

> Create WP Site will create a Mysql database for you. When asked for a name, make sure a database with that name does not already exist. The database user must already exist. For local development, this is likely `root`.

## License

Create WP Site is an open-source software licensed under the MIT license.
