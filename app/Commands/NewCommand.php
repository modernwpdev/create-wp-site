<?php

namespace App\Commands;

use Symfony\Component\Process\Process;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use mysqli;

class NewCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new
                            {directory : The directory to install WordPress into (required)}
                            {--core : Install core WordPress (optional)}
                            {--bedrock : Install Roots Bedrock (optional)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create a new WordPress powered website';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Get directory
        $directory = $this->argument('directory');

        $answers = $this->askInstallQuestions();

        // Make sure wp-cli is installed, if not install it.
        if (!$this->wpCliInstalled()) {
            // Let the user know wp-cli is required and install it with composer
            $this->error('WP-CLI is required to use create-wp-site. Installing it now...');

            $this->installWpCli();
        }

        // If --core, install WP core
        if ($this->option('core')) {
            $this->installWPCore($directory, $answers);
        }

        // If --bedrock, install Bedrock
        if ($this->option('bedrock')) {
            $this->installBedrock($directory, $answers);
        }

        // If no --core or --bedrock flag passed, ask which to install
        if (!$this->option('core') && !$this->option('bedrock')) {
            $wpType = $this->choice(
                'Install vanilla WordPress or Bedrock?',
                ['Vanilla', 'Bedrock'],
                0
            );

            if ($wpType === 'Vanilla') {
                $this->installWPCore($directory, $answers);
            } elseif ($wpType === 'Bedrock') {
                $this->installBedrock($directory, $answers);
            }
        }

        // Change cwd back to original
        chdir('../');
    }

    /**
     * Define the command's schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    /**
     * Gets required information for installation.
     *
     * @return array
     */
    protected function askInstallQuestions(): array
    {
        $answers = [];

        $answers['dbName'] = $this->ask('Database name?');
        $answers['dbUser'] = $this->ask('Database username?', 'root');
        $answers['dbPassword'] = $this->ask('Database password?');
        $answers['dbHost'] = $this->ask('Database host?', 'localhost');
        $answers['wpUrl'] = $this->ask('WordPress url?');
        $answers['wpTitle'] = $this->ask('Site title?');
        $answers['wpAdmin'] = $this->ask('Admin username?');
        $answers['wpPassword'] = $this->ask('Admin password?');
        $answers['wpEmail'] = $this->ask('Admin email?');

        return $answers;
    }

    /**
     * Determine if WP-CLI is installed.
     *
     * @return bool
     */
    protected function wpCliInstalled(): bool
    {
        $testMethod = (stripos(PHP_OS, 'WIN')) ? 'command -v' : 'where';
        return (null === shell_exec("$testMethod wp")) ? false : true;
    }

    /**
     * Determine how to run Composer commands
     * (Borrowed from https://github.com/laravel/installer)
     *
     * @return string
     */
    protected function getComposer(): string
    {
        $composer = getcwd() . '/composer.phar';

        if (file_exists($composer)) {
            return '"' . PHP_BINARY . '" ' . $composer;
        }

        return 'composer';
    }

    /**
     * Run commands.
     * (Borrowed from https://github.com/laravel/installer)
     *
     * @param  array  $commands
     * @param  array  $env
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function runCommands(array $commands, array $env = [])
    {
        $process = Process::fromShellCommandline(implode(' && ', $commands), null, $env, null, null);

        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
            try {
                $process->setTty(true);
            } catch (\RuntimeException $e) {
                $this->warn('Warning: ' . $e->getMessage());
            }
        }

        $process->run(function ($type, $line) {
            $this->line('    ' . $line);
        });

        return $process;
    }

    /**
     * Install WP-CLI globally with Composer
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function installWpCli()
    {
        // Install wp-cli with Composer
        $composer = $this->getComposer();

        $commands = [
            $composer . " global require wp-cli/wp-cli-bundle",
        ];

        if (($process = $this->runCommands($commands))->isSuccessful()) {
            $this->info('WP-CLI was successfully installed. Continuing...');

            return $process;
        }
    }

    /**
     * Create a directory if if doesn't exist
     *
     * @param string $directory
     *
     * @return bool
     */
    protected function createDirectory(string $directory): bool
    {
        if (is_dir($directory) && $directory != getcwd()) {
            throw new \RuntimeException('Directory already exists!');
        }

        if ($directory !== '.') {
            return mkdir($directory);
        }
    }

    /**
     * Runs the WordPress installer with WP-CLI
     *
     * @param string $directory
     * @param array $answers
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function installWPCore(string $directory, array $answers)
    {
        // Create directory if not exists, fail if exists and not cwd
        $this->createDirectory($directory);

        chdir($directory);

        // Run wp core download
        $commands = [
            'wp core download',
        ];

        if (($process = $this->runCommands($commands))->isSuccessful()) {
            $this->info('WordPress core downloaded.');
            $this->line('Installing WordPress...');

            // Create database from user input
            $dbHost = $answers['dbHost'];
            $dbUser = $answers['dbUser'];
            $dbPassword = $answers['dbPassword'];
            $dbName = $answers['dbName'];

            if ($this->createDatabase($dbHost, $dbUser, $dbPassword, $dbName)) {
                // Update wp-config.php with database variables
                $this->buildWpConfig($answers, $directory);

                // Run wp core install with user input
                $wpUrl = $answers['wpUrl'];
                $wpTitle = $answers['wpTitle'];
                $wpAdmin = $answers['wpAdmin'];
                $wpPassword = $answers['wpPassword'];
                $wpEmail = $answers['wpEmail'];

                $installWp = [
                    'wp core install --url=' . $wpUrl . ' --title=' . escapeshellarg($wpTitle) . ' --admin_user=' . $wpAdmin . ' --admin_password=' . $wpPassword . ' --admin_email=' . $wpEmail
                ];

                if ($this->runCommands($installWp)->isSuccessful()) {
                    $this->info('WordPress installed successfully.');
                    $this->info('Access your site at: ' . $wpUrl);
                    $this->info('Access your WP admin at: ' . $wpUrl . '/wp-admin');
                }
            }

            return $process;
        }
    }

    protected function buildWpConfig(array $answers, string $directory)
    {
        $wpConfig = file_get_contents(getcwd() . '/wp-config-sample.php');

        $wpConfig = str_replace('database_name_here', $answers['dbName'], $wpConfig);
        $wpConfig = str_replace('username_here', $answers['dbUser'], $wpConfig);
        $wpConfig = str_replace('password_here', $answers['dbPassword'], $wpConfig);
        $wpConfig = str_replace('localhost', $answers['dbHost'], $wpConfig);

        $wpConfig = str_replace('define( \'AUTH_KEY\',         \'put your unique phrase here\' );', 'define( \'AUTH_KEY\', \'' . $this->generateSalt(36) . '\');', $wpConfig);
        $wpConfig = str_replace('define( \'SECURE_AUTH_KEY\',  \'put your unique phrase here\' );', 'define( \'SECURE_AUTH_KEY\', \'' . $this->generateSalt(36) . '\');', $wpConfig);
        $wpConfig = str_replace('define( \'LOGGED_IN_KEY\',    \'put your unique phrase here\' );', 'define( \'LOGGED_IN_KEY\', \'' . $this->generateSalt(36) . '\');', $wpConfig);
        $wpConfig = str_replace('define( \'NONCE_KEY\',        \'put your unique phrase here\' );', 'define( \'NONCE_KEY\', \'' . $this->generateSalt(36) . '\');', $wpConfig);
        $wpConfig = str_replace('define( \'AUTH_SALT\',        \'put your unique phrase here\' );', 'define( \'AUTH_SALT\', \'' . $this->generateSalt(36) . '\');', $wpConfig);
        $wpConfig = str_replace('define( \'SECURE_AUTH_SALT\', \'put your unique phrase here\' );', 'define( \'SECURE_AUTH_SALT\', \'' . $this->generateSalt(36) . '\');', $wpConfig);
        $wpConfig = str_replace('define( \'LOGGED_IN_SALT\',   \'put your unique phrase here\' );', 'define( \'LOGGED_IN_SALT\', \'' . $this->generateSalt(36) . '\');', $wpConfig);
        $wpConfig = str_replace('define( \'NONCE_SALT\',       \'put your unique phrase here\' );', 'define( \'NONCE_SALT\', \'' . $this->generateSalt(36) . '\');', $wpConfig);

        return file_put_contents(getcwd() . '/wp-config.php', $wpConfig);
    }

    /**
     * Generate a random salt.
     *
     * @param int $length
     *
     * @return string
     */
    protected function generateSalt($length)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!-.[]?*()';

        $salt = '';

        $characterListLength = mb_strlen($characters, '8bit') - 1;

        foreach (range(1, $length) as $i) {
            $salt .= $characters[random_int(0, $characterListLength)];
        }

        return $salt;
    }

    /**
     * Installs Bedrock using Composer
     *
     * @param string $directory
     * @param array $answers
     *
     * @return \Symfony\Component\Process\Process
     */
    protected function installBedrock(string $directory, array $answers)
    {
        // Install Bedrock with Composer
        $composer = $this->getComposer();
        $commands = [
            "$composer create-project roots/bedrock $directory",
        ];

        if (($process = $this->runCommands($commands))->isSuccessful()) {
            $this->info('Bedrock installed successfully.');
            $this->line('Configuring Bedrock...');

            chdir($directory);

            // Create database from user input
            $dbHost = $answers['dbHost'];
            $dbUser = $answers['dbUser'];
            $dbPassword = $answers['dbPassword'];
            $dbName = $answers['dbName'];

            if ($this->createDatabase($dbHost, $dbUser, $dbPassword, $dbName)) {
                $this->buildBedrockEnv($answers);

                // Run wp core install with user input
                $wpUrl = $answers['wpUrl'];
                $wpTitle = $answers['wpTitle'];
                $wpAdmin = $answers['wpAdmin'];
                $wpPassword = $answers['wpPassword'];
                $wpEmail = $answers['wpEmail'];
                $wpPath = getcwd() . '/web/wp';

                $installWp = [
                    'wp core install --url=' . $wpUrl . ' --title=' . escapeshellarg($wpTitle) . ' --admin_user=' . $wpAdmin . ' --admin_password=' . $wpPassword . ' --admin_email=' . $wpEmail . ' --path=' . $wpPath
                ];

                if ($this->runCommands($installWp)->isSuccessful()) {
                    $this->info('WordPress installed successfully.');
                    $this->info('Access your site at: ' . $wpUrl);
                    $this->info('Access your WP admin at: ' . $wpUrl . '/wp/wp-admin');
                }
            }

            return $process;
        }
    }

    protected function buildBedrockEnv(array $answers)
    {
        $envFile = file_get_contents(getcwd() . '/.env');

        $envFile = str_replace('database_name', $answers['dbName'], $envFile);
        $envFile = str_replace('database_user', $answers['dbUser'], $envFile);
        $envFile = str_replace('database_password', $answers['dbPassword'], $envFile);
        $envFile = str_replace('# DB_HOST=\'localhost\'', 'DB_HOST=\'' . $answers['dbHost'] . '\'', $envFile);
        $envFile = str_replace('# DB_PREFIX=\'wp_\'', 'DB_PREFIX=\'wp_\'', $envFile);
        $envFile = str_replace('http://example.com', $answers['wpUrl'], $envFile);

        $envFile = str_replace('AUTH_KEY=\'generateme\'', 'AUTH_KEY=\'' . $this->generateSalt(36) . '\'', $envFile);
        $envFile = str_replace('SECURE_AUTH_KEY=\'generateme\'', 'SECURE_AUTH_KEY=\'' . $this->generateSalt(36) . '\'', $envFile);
        $envFile = str_replace('LOGGED_IN_KEY=\'generateme\'', 'LOGGED_IN_KEY=\'' . $this->generateSalt(36) . '\'', $envFile);
        $envFile = str_replace('NONCE_KEY=\'generateme\'', 'NONCE_KEY=\'' . $this->generateSalt(36) . '\'', $envFile);
        $envFile = str_replace('AUTH_SALT=\'generateme\'', 'AUTH_SALT=\'' . $this->generateSalt(36) . '\'', $envFile);
        $envFile = str_replace('SECURE_AUTH_SALT=\'generateme\'', 'SECURE_AUTH_SALT=\'' . $this->generateSalt(36) . '\'', $envFile);
        $envFile = str_replace('LOGGED_IN_SALT=\'generateme\'', 'LOGGED_IN_SALT=\'' . $this->generateSalt(36) . '\'', $envFile);
        $envFile = str_replace('NONCE_SALT=\'generateme\'', 'NONCE_SALT=\'' . $this->generateSalt(36) . '\'', $envFile);

        return file_put_contents(getcwd() . '/.env', $envFile);
    }

    protected function createDatabase(string $dbHost, string $dbUser, string $dbPassword, string $dbName)
    {
        // Connect to mysql
        $mysql = new mysqli($dbHost, $dbUser, $dbPassword);

        // Create the database
        if ($mysql->query("CREATE DATABASE $dbName")) {
            $this->info('Database ' . $dbName . ' successfully created.');
        } else {
            $this->error('Database ' . $dbName . ' could not be created. Exiting.');
            throw new \RuntimeException();
        }

        // Close mysql connection
        $mysql->close();

        return true;
    }
}
