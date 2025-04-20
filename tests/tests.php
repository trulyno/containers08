<?php

require_once __DIR__ . '/testframework.php';

// Adaptăm calea pentru a funcționa în containerul Docker
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$tests = new TestFramework();

// test 1: check database connection
function testDbConnection() {
    global $config;
    
    try {
        $db = new Database($config["db"]["path"]);
        return assertExpression(true, "Database connection successful");
    } catch (Exception $e) {
        return assertExpression(false, "Should connect to database", "Database connection failed: " . $e->getMessage());
    }
}

// test 2: test count method
function testDbCount() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    $count = $db->Count("page");
    
    return assertExpression($count >= 3, "Count returned correct number of records: " . $count, "Count should return at least 3 records, got: " . $count);
}

// test 3: test create method
function testDbCreate() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    $initialCount = $db->Count("page");
    
    $data = [
        'title' => 'Test Page',
        'content' => 'Test Content'
    ];
    
    $id = $db->Create("page", $data);
    $newCount = $db->Count("page");
    
    $success = $id > 0 && $newCount == $initialCount + 1;
    return assertExpression($success, "Create method works correctly, new ID: " . $id, "Create method failed");
}

// test 4: test read method
function testDbRead() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    $data = $db->Read("page", 1);
    
    $success = is_array($data) && isset($data['id']) && $data['id'] == 1;
    return assertExpression($success, "Read method works correctly", "Read method failed");
}

// test 5: test update method
function testDbUpdate() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    
    // Create a test record
    $data = [
        'title' => 'Update Test',
        'content' => 'Before Update'
    ];
    
    $id = $db->Create("page", $data);
    
    // Update the record
    $updateData = [
        'content' => 'After Update'
    ];
    
    $result = $db->Update("page", $id, $updateData);
    
    // Verify update
    $updated = $db->Read("page", $id);
    
    $success = $result && $updated['content'] == 'After Update';
    return assertExpression($success, "Update method works correctly", "Update method failed");
}

// test 6: test delete method
function testDbDelete() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    
    // Create a test record to delete
    $data = [
        'title' => 'Delete Test',
        'content' => 'To be deleted'
    ];
    
    $id = $db->Create("page", $data);
    $initialCount = $db->Count("page");
    
    // Delete the record
    $result = $db->Delete("page", $id);
    $newCount = $db->Count("page");
    
    $success = $result && $newCount == $initialCount - 1;
    return assertExpression($success, "Delete method works correctly", "Delete method failed");
}

// test 7: test fetch method
function testDbFetch() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    $results = $db->Fetch("SELECT * FROM page LIMIT 3");
    
    $success = is_array($results) && count($results) > 0;
    return assertExpression($success, "Fetch method works correctly, returned " . count($results) . " records", "Fetch method failed");
}

// test 8: test page render
function testPageRender() {
    $page = new Page(__DIR__ . '/../templates/index.tpl');
    
    $data = [
        'title' => 'Test Title',
        'content' => 'Test Content'
    ];
    
    $result = $page->Render($data);
    
    $success = strpos($result, 'Test Title') !== false && strpos($result, 'Test Content') !== false;
    return assertExpression($success, "Page render works correctly", "Page render failed");
}

// add tests
$tests->add('Database connection', 'testDbConnection');
$tests->add('Table count', 'testDbCount');
$tests->add('Data create', 'testDbCreate');
$tests->add('Data read', 'testDbRead');
$tests->add('Data update', 'testDbUpdate');
$tests->add('Data delete', 'testDbDelete');
$tests->add('Data fetch', 'testDbFetch');
$tests->add('Page render', 'testPageRender');

// run tests
$tests->run();

echo "Tests result: " . $tests->getResult();

?>