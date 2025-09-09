<?php
// === bootstrap ===
// require_once __DIR__ . '/../config/config.php'; // sets up $pdo

// get the requested path (without query string)
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// simple routing
if ($path === '' || $path === 'posts') {
    // list of posts
    require_once __DIR__ . '/../src/controllers/PostsController.php';
    PostsController::index();
}
elseif (preg_match('#^post/(\d+)$#', $path, $m)) {
    // show single post
    require_once __DIR__ . '/../src/controllers/PostsController.php';
    PostsController::show((int)$m[1]);
}
elseif ($path === 'tags') {
    require_once __DIR__ . '/../src/controllers/TagsController.php';
    TagsController::index();
}
else {
    http_response_code(404);
    $title = "404 – mm";
    include __DIR__ . '/../src/views/layout/head.php';
    echo "<main><h1>404 Not Found</h1></main>";
    include __DIR__ . '/../src/views/layout/footer.php';
}
