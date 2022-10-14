<?php

function recurseRmdir($dir)
{
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file") && !is_link("$dir/$file")) ? recurseRmdir("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

function deleteDatabase($database)
{
    $dbhost = 'localhost';
    $dbuser = 'root';
    $dbpass = '';

    $mysql = new mysqli($dbhost, $dbuser, $dbpass);

    if ($mysql->query("DROP DATABASE $database")) {
        echo "Database deleted successfully\n";
    }

    $mysql->close();
}

beforeEach(function () {
    $this->directory = 'cwps-test';
    $this->dbName = 'cwpstest';
});

afterEach(function () {
    // delete temp directory
    recurseRmdir(getcwd() . '/' . $this->directory);

    // delete temp database
    deleteDatabase($this->dbName);
});

it('installs Vanilla WordPress when Vanilla is chosen', function () {
    $this->artisan('new', ['directory' => $this->directory])
        ->expectsQuestion('Database name?', $this->dbName)
        ->expectsQuestion('Database username?', 'root')
        ->expectsQuestion('Database password?', '')
        ->expectsQuestion('Database host?', 'localhost')
        ->expectsQuestion('WordPress url?', 'http://' . $this->directory . '.test')
        ->expectsQuestion('Site title?', 'Vanilla WP Test')
        ->expectsQuestion('Admin username?', 'admin')
        ->expectsQuestion('Admin password?', 'password')
        ->expectsQuestion('Admin email?', 'example@example.com')
        ->expectsQuestion('Install vanilla WordPress or Bedrock?', 'Vanilla')
        ->expectsOutput('WordPress core downloaded.')
        ->expectsOutput('Installing WordPress...')
        ->expectsOutput('WordPress installed successfully.')
        ->expectsOutput('Access your site at: http://' . $this->directory . '.test')
        ->expectsOutput('Access your WP admin at: http://' . $this->directory . '.test/wp-admin')
        ->assertExitCode(0);
});

it('installs Vanilla WordPress when --core flag is passed', function () {
    $this->artisan('new', ['directory' => $this->directory, '--core' => true])
        ->expectsQuestion('Database name?', $this->dbName)
        ->expectsQuestion('Database username?', 'root')
        ->expectsQuestion('Database password?', '')
        ->expectsQuestion('Database host?', 'localhost')
        ->expectsQuestion('WordPress url?', 'http://' . $this->directory . '.test')
        ->expectsQuestion('Site title?', 'Vanilla WP Test')
        ->expectsQuestion('Admin username?', 'admin')
        ->expectsQuestion('Admin password?', 'password')
        ->expectsQuestion('Admin email?', 'example@example.com')
        ->expectsOutput('WordPress core downloaded.')
        ->expectsOutput('Installing WordPress...')
        ->expectsOutput('WordPress installed successfully.')
        ->expectsOutput('Access your site at: http://' . $this->directory . '.test')
        ->expectsOutput('Access your WP admin at: http://' . $this->directory . '.test/wp-admin')
        ->assertExitCode(0);
});

it('installs Bedrock when Bedrock is chosen', function () {
    $this->artisan('new', ['directory' => $this->directory])
        ->expectsQuestion('Database name?', $this->dbName)
        ->expectsQuestion('Database username?', 'root')
        ->expectsQuestion('Database password?', '')
        ->expectsQuestion('Database host?', 'localhost')
        ->expectsQuestion('WordPress url?', 'http://' . $this->directory . '.test')
        ->expectsQuestion('Site title?', 'Vanilla WP Test')
        ->expectsQuestion('Admin username?', 'admin')
        ->expectsQuestion('Admin password?', 'password')
        ->expectsQuestion('Admin email?', 'example@example.com')
        ->expectsQuestion('Install vanilla WordPress or Bedrock?', 'Bedrock')
        ->expectsOutput('Bedrock installed successfully.')
        ->expectsOutput('Configuring Bedrock...')
        ->expectsOutput('WordPress installed successfully.')
        ->expectsOutput('Access your site at: http://' . $this->directory . '.test')
        ->expectsOutput('Access your WP admin at: http://' . $this->directory . '.test/wp/wp-admin')
        ->assertExitCode(0);
});

it('installs Bedrock when --bedrock flag is passed', function () {
    $this->artisan('new', ['directory' => $this->directory, '--bedrock' => true])
        ->expectsQuestion('Database name?', $this->dbName)
        ->expectsQuestion('Database username?', 'root')
        ->expectsQuestion('Database password?', '')
        ->expectsQuestion('Database host?', 'localhost')
        ->expectsQuestion('WordPress url?', 'http://' . $this->directory . '.test')
        ->expectsQuestion('Site title?', 'Vanilla WP Test')
        ->expectsQuestion('Admin username?', 'admin')
        ->expectsQuestion('Admin password?', 'password')
        ->expectsQuestion('Admin email?', 'example@example.com')
        ->expectsOutput('Bedrock installed successfully.')
        ->expectsOutput('Configuring Bedrock...')
        ->expectsOutput('WordPress installed successfully.')
        ->expectsOutput('Access your site at: http://' . $this->directory . '.test')
        ->expectsOutput('Access your WP admin at: http://' . $this->directory . '.test/wp/wp-admin')
        ->assertExitCode(0);
});
