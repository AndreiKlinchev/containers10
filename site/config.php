<?php

function get_file_contents(string $path): string
{
    if (!file_exists($path)) {
        return '';
    }

    $content = file_get_contents($path);

    if ($content === false) {
        return '';
    }

    return trim($content);
}

$config = [
    "db" => []
];

$config['db']['host'] = getenv('MYSQL_HOST');
$config['db']['database'] = getenv('MYSQL_DATABASE');
// $config['db']['username'] = getenv('MYSQL_USER');
// $config['db']['password'] = getenv('MYSQL_PASSWORD');
$config['db']['username'] = get_file_contents('/run/secrets/user');
$config['db']['password'] = get_file_contents('/run/secrets/secret');
