<?php

require_once __DIR__ . '/modules/database.php';
require_once __DIR__ . '/modules/page.php';
require_once __DIR__ . '/config.php';

$dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['database']};charset=utf8";

$db = new Database($dsn, $config['db']['username'], $config['db']['password']);
$page = new Page(__DIR__ . '/templates/index.tpl');

$pageId = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$data = $db->Read('page', $pageId);

if ($data === null) {
    http_response_code(404);
    $data = [
        'title' => 'Page not found',
        'content' => 'The requested page does not exist.',
    ];
}

echo $page->Render($data);
