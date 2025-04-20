<?php

require_once __DIR__ . '/modules/database.php';
require_once __DIR__ . '/modules/page.php';

require_once __DIR__ . '/config.php';

$db = new Database($config["db"]["path"]);

$page = new Page(__DIR__ . '/templates/index.tpl');

// Get page ID from GET parameter with validation
$pageId = isset($_GET['page']) ? intval($_GET['page']) : 1;

$data = $db->Read("page", $pageId);

// If page not found, use default data
if (!$data) {
    $data = [
        'title' => 'Page not found',
        'content' => 'The requested page does not exist.'
    ];
}

echo $page->Render($data);

?>