<?php

require_once __DIR__ . '/testframework.php';

require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$tests = new TestFramework();

function getTestDbPath() {
    return __DIR__ . '/test.sqlite';
}

function createTestDatabase() {
    if (!extension_loaded('pdo_sqlite')) {
        return null;
    }

    $path = getTestDbPath();

    if (file_exists($path)) {
        unlink($path);
    }

    $db = new Database($path);
    $db->Execute('CREATE TABLE page (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, content TEXT)');
    $db->Execute("INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1')");
    $db->Execute("INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2')");
    $db->Execute("INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3')");

    return $db;
}

// test 1: check database connection
function testDbConnection() {
    $db = createTestDatabase();

    return assertExpression(
        $db instanceof Database,
        'Database object created',
        'Database object was not created'
    );
}

// test 2: test count method
function testDbCount() {
    $db = createTestDatabase();

    if ($db === null) {
        return assertExpression(false, 'Count works', 'pdo_sqlite is not enabled');
    }

    $count = $db->Count('page');

    return assertExpression(
        $count == 3,
        'Count method works',
        'Count method does not work'
    );
}

// test 3: test create method
function testDbCreate() {
    $db = createTestDatabase();

    if ($db === null) {
        return assertExpression(false, 'Create works', 'pdo_sqlite is not enabled');
    }

    $id = $db->Create('page', [
        'title' => 'New page',
        'content' => 'New content'
    ]);

    $row = $db->Read('page', $id);

    return assertExpression(
        $id > 0 && $row['title'] == 'New page',
        'Create method works',
        'Create method does not work'
    );
}

// test 4: test read method
function testDbRead() {
    $db = createTestDatabase();

    if ($db === null) {
        return assertExpression(false, 'Read works', 'pdo_sqlite is not enabled');
    }

    $row = $db->Read('page', 1);

    return assertExpression(
        $row['title'] == 'Page 1' && $row['content'] == 'Content 1',
        'Read method works',
        'Read method does not work'
    );
}

function testDbExecute() {
    $db = createTestDatabase();

    if ($db === null) {
        return assertExpression(false, 'Execute works', 'pdo_sqlite is not enabled');
    }

    $db->Execute("INSERT INTO page (title, content) VALUES ('Page 4', 'Content 4')");
    $count = $db->Count('page');

    return assertExpression(
        $count == 4,
        'Execute method works',
        'Execute method does not work'
    );
}

function testDbFetch() {
    $db = createTestDatabase();

    if ($db === null) {
        return assertExpression(false, 'Fetch works', 'pdo_sqlite is not enabled');
    }

    $rows = $db->Fetch('SELECT * FROM page ORDER BY id');

    return assertExpression(
        count($rows) == 3 && $rows[1]['title'] == 'Page 2',
        'Fetch method works',
        'Fetch method does not work'
    );
}

function testDbUpdate() {
    $db = createTestDatabase();

    if ($db === null) {
        return assertExpression(false, 'Update works', 'pdo_sqlite is not enabled');
    }

    $db->Update('page', 1, [
        'title' => 'Updated page',
        'content' => 'Updated content'
    ]);

    $row = $db->Read('page', 1);

    return assertExpression(
        $row['title'] == 'Updated page' && $row['content'] == 'Updated content',
        'Update method works',
        'Update method does not work'
    );
}

function testDbDelete() {
    $db = createTestDatabase();

    if ($db === null) {
        return assertExpression(false, 'Delete works', 'pdo_sqlite is not enabled');
    }

    $db->Delete('page', 1);
    $row = $db->Read('page', 1);

    return assertExpression(
        $row === null,
        'Delete method works',
        'Delete method does not work'
    );
}

function testPageRender() {
    $templatePath = __DIR__ . '/page_test.tpl';
    file_put_contents($templatePath, '<h1>{{title}}</h1><p>{{content}}</p>');

    $page = new Page($templatePath);
    $result = $page->Render([
        'title' => 'Hello',
        'content' => 'World'
    ]);

    return assertExpression(
        $result == '<h1>Hello</h1><p>World</p>',
        'Page render works',
        'Page render does not work'
    );
}

function testPageConstruct() {
    $templatePath = __DIR__ . '/page_test_2.tpl';
    file_put_contents($templatePath, 'Test {{title}}');

    $page = new Page($templatePath);
    $result = $page->Render([
        'title' => 'page'
    ]);

    return assertExpression(
        $result == 'Test page',
        'Page constructor works',
        'Page constructor does not work'
    );
}

// add tests
$tests->add('Database connection', 'testDbConnection');
$tests->add('Database execute', 'testDbExecute');
$tests->add('Database fetch', 'testDbFetch');
$tests->add('Database count', 'testDbCount');
$tests->add('Database create', 'testDbCreate');
$tests->add('Database read', 'testDbRead');
$tests->add('Database update', 'testDbUpdate');
$tests->add('Database delete', 'testDbDelete');
$tests->add('Page construct', 'testPageConstruct');
$tests->add('Page render', 'testPageRender');

// run tests
$tests->run();

echo $tests->getResult();
