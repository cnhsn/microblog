<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ||
                $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domain = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = str_replace('\\', '/', dirname($script));
    if ($path == '/' || $path == '\\') {
        $path = '';
    }
    return rtrim($protocol . $domain . $path, '/');
}

$base_url = base_url();

$db = new PDO('sqlite:microblog.db');

$db->exec("CREATE TABLE IF NOT EXISTS posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT,
    slug TEXT UNIQUE,
    image TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT,
    slug TEXT UNIQUE,
    image TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER,
    name TEXT,
    comment TEXT,
    approved INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS navbar_links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    link TEXT,
    target TEXT
)");
$db->exec("CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    logo TEXT,
    description TEXT,
    footer TEXT,
    favicon TEXT,
    display_option TEXT DEFAULT 'all'
)");
$db->exec("CREATE TABLE IF NOT EXISTS admin (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    password TEXT
)");

$adminExists = $db->query("SELECT COUNT(*) FROM admin")->fetchColumn();
if (!$adminExists) {
    $db->exec("INSERT INTO admin (username, password) VALUES ('admin', '".password_hash('admin', PASSWORD_DEFAULT)."')");
    $db->exec("INSERT INTO settings (logo, description, footer) VALUES ('Logo', 'Description', '© 2024 All rights reserved.')");
}

function isLoggedIn() {
    return isset($_SESSION['admin']);
}

$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$script_dir = dirname($script_name);

$base_path = str_replace('\\', '/', $script_dir);
if ($base_path == '/' || $base_path == '\\') {
    $base_path = '';
}

$path = substr($request_uri, strlen($base_path));
if (strpos($path, '?') !== false) {
    $path = substr($path, 0, strpos($path, '?'));
}
$path = trim($path, '/');

$page = 'home';
$slug = '';
$page_num = 1;

if ($path == '') {
    $page = 'home';
} elseif ($path == 'login') {
    $page = 'login';
} elseif ($path == 'logout') {
    $page = 'logout';
} elseif ($path == 'admin') {
    $page = 'admin';
} elseif (preg_match('/^page\/(\d+)$/', $path, $matches)) {
    $page = 'home';
    $page_num = (int)$matches[1];
} else {
    $stmt = $db->prepare("SELECT * FROM posts WHERE slug = ?");
    $stmt->execute([$path]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $page = 'post';
        $slug = $path;
    } else {
        $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ?");
        $stmt->execute([$path]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $page = 'page';
            $slug = $path;
        } else {
            $page = '404';
        }
    }
}

if ($page == 'login' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $db->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin'] = $admin['username'];
        header("Location: ".$base_url."/admin");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

if ($page == 'logout') {
    session_destroy();
    header("Location: ".$base_url."/");
    exit;
}

if (isLoggedIn()) {
    if ($page == 'admin' && isset($_POST['save_post'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $slug = strtolower(str_replace(' ', '-', $title));
        $slug = preg_replace('/[^A-Za-z0-9\-]/', '', $slug);

        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageFileType, $allowedTypes)) {
                if (!is_dir('uploads/posts')) {
                    mkdir('uploads/posts', 0777, true);
                }
                $imagePath = 'uploads/posts/'.$slug.'.'.$imageFileType;
                move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
                // Resize image here if needed
            }
        }

        if (isset($_POST['id']) && $_POST['id']) {
            $id = $_POST['id'];
            $stmt = $db->prepare("UPDATE posts SET title = ?, content = ?, slug = ?, image = ? WHERE id = ?");
            $stmt->execute([$title, $content, $slug, $imagePath, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO posts (title, content, slug, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $slug, $imagePath]);
        }
    }
    if ($page == 'admin' && isset($_POST['save_page'])) {
        $title = $_POST['title'];
        $content = $_POST['content'];
        $slug = strtolower(str_replace(' ', '-', $title));
        $slug = preg_replace('/[^A-Za-z0-9\-]/', '', $slug);

        $imagePath = '';
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $imageFileType = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array($imageFileType, $allowedTypes)) {
                if (!is_dir('uploads/pages')) {
                    mkdir('uploads/pages', 0777, true);
                }
                $imagePath = 'uploads/pages/'.$slug.'.'.$imageFileType;
                move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
                // Resize image here if needed
            }
        }

        if (isset($_POST['id']) && $_POST['id']) {
            $id = $_POST['id'];
            $stmt = $db->prepare("UPDATE pages SET title = ?, content = ?, slug = ?, image = ? WHERE id = ?");
            $stmt->execute([$title, $content, $slug, $imagePath, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO pages (title, content, slug, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $slug, $imagePath]);
        }
    }
    if ($page == 'admin' && isset($_GET['delete_post'])) {
        $id = $_GET['delete_post'];
        $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
        $stmt->execute([$id]);
    }
    if ($page == 'admin' && isset($_GET['delete_page'])) {
        $id = $_GET['delete_page'];
        $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
        $stmt->execute([$id]);
    }
    if ($page == 'admin' && isset($_GET['approve_comment'])) {
        $id = $_GET['approve_comment'];
        $stmt = $db->prepare("UPDATE comments SET approved = 1 WHERE id = ?");
        $stmt->execute([$id]);
    }
    if ($page == 'admin' && isset($_GET['delete_comment'])) {
        $id = $_GET['delete_comment'];
        $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$id]);
    }
    if ($page == 'admin' && isset($_POST['update_settings'])) {
        $logo = $_POST['logo'];
        $description = $_POST['description'];
        $footer = $_POST['footer'];
        $display_option = $_POST['display_option'];

        $faviconPath = '';
        if (isset($_FILES['favicon']) && $_FILES['favicon']['size'] > 0) {
            $imageFileType = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            $allowedTypes = ['ico', 'png', 'jpg', 'jpeg', 'gif'];
            if (in_array($imageFileType, $allowedTypes)) {
                $faviconPath = 'uploads/favicon/favicon.'.$imageFileType;
                if (!is_dir('uploads/favicon')) {
                    mkdir('uploads/favicon', 0777, true);
                }
                move_uploaded_file($_FILES['favicon']['tmp_name'], $faviconPath);
            }
        }

        if ($faviconPath) {
            $stmt = $db->prepare("UPDATE settings SET logo = ?, description = ?, footer = ?, favicon = ?, display_option = ? WHERE id = 1");
            $stmt->execute([$logo, $description, $footer, $faviconPath, $display_option]);
        } else {
            $stmt = $db->prepare("UPDATE settings SET logo = ?, description = ?, footer = ?, display_option = ? WHERE id = 1");
            $stmt->execute([$logo, $description, $footer, $display_option]);
        }
    }
    if ($page == 'admin' && isset($_POST['update_admin'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        if (!empty($password)) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admin SET username = ?, password = ? WHERE id = 1");
            $stmt->execute([$username, $passwordHash]);
        } else {
            $stmt = $db->prepare("UPDATE admin SET username = ? WHERE id = 1");
            $stmt->execute([$username]);
        }
        $_SESSION['admin'] = $username;
    }
    if ($page == 'admin' && isset($_POST['add_navbar_link'])) {
        $name = $_POST['name'];
        $link = $_POST['link'];
        $target = $_POST['target'];
        $stmt = $db->prepare("INSERT INTO navbar_links (name, link, target) VALUES (?, ?, ?)");
        $stmt->execute([$name, $link, $target]);
    }
    if ($page == 'admin' && isset($_GET['delete_navbar_link'])) {
        $id = $_GET['delete_navbar_link'];
        $stmt = $db->prepare("DELETE FROM navbar_links WHERE id = ?");
        $stmt->execute([$id]);
    }
}

$settings = $db->query("SELECT * FROM settings WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Microblog</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (!empty($settings['favicon'])): ?>
        <link rel="icon" href="<?php echo $base_url.'/'.$settings['favicon']; ?>">
    <?php endif; ?>
    <?php if ($page == 'home') { ?>
        <meta name="description" content="<?php echo htmlspecialchars($settings['description']); ?>">
    <?php } elseif ($page == 'post') {
        $stmt = $db->prepare("SELECT * FROM posts WHERE slug = ?");
        $stmt->execute([$slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post) {
            echo '<meta name="description" content="'.substr(strip_tags($post['content']), 0, 150).'">';
        }
    } ?>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <style>
        html, body {
            min-height: 100%;
        }
        body {
            margin: 0;
            padding-bottom: 60px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            height: 60px;
        }
        .card-title {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .card {
            height: 100%;
        }
        .card-body {
            display: flex;
            flex-direction: column;
        }
        .card-text {
            flex-grow: 1;
        }
		.card-img-top {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        @media (max-width: 767px) {
            .navbar-toggler {
                order: 2;
            }
            .navbar-brand {
                order: 1;
            }
            .navbar-nav {
                text-align: center;
            }
            .col-md-4 {
                width: 100%;
            }
            .card {
                margin-bottom: 20px;
            }
        }
        .featured-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto 20px;
        }
        .featured-image-fixed {
            width: 600px;
            height: 400px;
            object-fit: cover;
            display: block;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <a class="navbar-brand" href="<?php echo $base_url; ?>/"><?php echo htmlspecialchars($settings['logo']); ?></a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Menu">
        <span class="navbar-toggler-icon"></span>
    </button>
    <?php
    $navbarItems = $db->query("SELECT * FROM navbar_links")->fetchAll(PDO::FETCH_ASSOC);
    ?>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
            <?php foreach ($navbarItems as $item): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo htmlspecialchars($item['link']); ?>" target="<?php echo htmlspecialchars($item['target']); ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <ul class="navbar-nav">
            <?php if (isLoggedIn()): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo $base_url; ?>/admin">Admin Panel</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo $base_url; ?>/logout">Logout</a></li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo $base_url; ?>/login">Login</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="container mt-4">
    <?php
    if ($page == 'home') {
        $display_option = isset($settings['display_option']) ? $settings['display_option'] : 'all';
        if ($display_option == 'pagination') {
            $per_page = 12;
            if ($page_num < 1) $page_num = 1;
            $offset = ($page_num - 1) * $per_page;
            $total_posts = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
            $total_pages = ceil($total_posts / $per_page);
            $stmt = $db->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($display_option == 'infinite') {
            $per_page = 12;
            $page_num = 1;
            $offset = 0;
            $total_posts = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
            $stmt = $db->prepare("SELECT * FROM posts ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
            $stmt->bindValue(2, $offset, PDO::PARAM_INT);
            $stmt->execute();
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $posts = $db->query("SELECT * FROM posts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
        }

        echo '<div class="row" id="post-container">';
        foreach ($posts as $post) {
            echo '<div class="col-md-4">';
            echo '<div class="card mb-4">';
            if (!empty($post['image']) && file_exists($post['image'])) {
                echo '<img src="'.$base_url.'/'.$post['image'].'" alt="'.htmlspecialchars($post['title']).'" class="card-img-top">';
            }
            echo '<div class="card-body">';
            $title = htmlspecialchars($post['title']);
            echo '<h5 class="card-title" title="'.$title.'"><a href="'.$base_url.'/'.$post['slug'].'">'.$title.'</a></h5>';
            $content = substr(strip_tags($post['content']), 0, 100).'...';
            echo '<p class="card-text">'.$content.'</p>';
            echo '<a href="'.$base_url.'/'.$post['slug'].'" class="btn btn-primary mt-auto">Read More</a>';
            echo '</div></div></div>';
        }
        echo '</div>';

        if ($display_option == 'pagination' && $total_pages > 1) {
            echo '<nav aria-label="Page navigation">';
            echo '<ul class="pagination justify-content-center">';
            if ($page_num > 1) {
                echo '<li class="page-item"><a class="page-link" href="'.$base_url.'/page/'.($page_num - 1).'">Previous</a></li>';
            } else {
                echo '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $page_num) {
                    echo '<li class="page-item active"><span class="page-link">'.$i.'</span></li>';
                } else {
                    echo '<li class="page-item"><a class="page-link" href="'.$base_url.'/page/'.$i.'">'.$i.'</a></li>';
                }
            }
            if ($page_num < $total_pages) {
                echo '<li class="page-item"><a class="page-link" href="'.$base_url.'/page/'.($page_num + 1).'">Next</a></li>';
            } else {
                echo '<li class="page-item disabled"><span class="page-link">Next</span></li>';
            }
            echo '</ul>';
            echo '</nav>';
        } elseif ($display_option == 'infinite' && $total_posts > $per_page) {
            echo '<div id="load-more-container" class="text-center mb-4">';
            echo '<button id="load-more" class="btn btn-primary">Load More</button>';
            echo '</div>';
        }

    } elseif ($page == 'post') {
        $stmt = $db->prepare("SELECT * FROM posts WHERE slug = ?");
        $stmt->execute([$slug]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($post) {
            echo '<div class="card mb-4">';
            echo '<div class="card-body">';
            if (!empty($post['image']) && file_exists($post['image'])) {
                echo '<img src="'.$base_url.'/'.$post['image'].'" alt="'.htmlspecialchars($post['title']).'" class="featured-image">';
            }
            echo '<h2>'.htmlspecialchars($post['title']).'</h2>';
            echo '<p>'.nl2br($post['content']).'</p>';
            echo '</div></div>';

            $stmt = $db->prepare("SELECT * FROM comments WHERE post_id = ? AND approved = 1 ORDER BY created_at DESC");
            $stmt->execute([$post['id']]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<h4>Comments</h4>';
            echo '<div class="list-group mb-4">';
            foreach ($comments as $comment) {
                echo '<div class="list-group-item">';
                echo '<strong>'.htmlspecialchars($comment['name']).'</strong><br>';
                echo '<p>'.nl2br(htmlspecialchars($comment['comment'])).'</p>';
                echo '</div>';
            }
            echo '</div>';

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_comment'])) {
                $name = $_POST['name'];
                $commentText = $_POST['comment'];
                $stmt = $db->prepare("INSERT INTO comments (post_id, name, comment) VALUES (?, ?, ?)");
                $stmt->execute([$post['id'], $name, $commentText]);
                echo '<div class="alert alert-success">Your comment will be published after approval.</div>';
            }
            ?>
            <h4>Leave a Comment</h4>
            <form method="post" class="mb-4">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Comment</label>
                    <textarea name="comment" class="form-control" rows="5" required></textarea>
                </div>
                <button type="submit" name="add_comment" class="btn btn-primary">Submit</button>
            </form>
            <?php
        } else {
            echo '<div class="alert alert-danger">Post not found.</div>';
        }
    } elseif ($page == 'page') {
        $stmt = $db->prepare("SELECT * FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pageData) {
            echo '<div class="card mb-4">';
            echo '<div class="card-body">';
            if (!empty($pageData['image']) && file_exists($pageData['image'])) {
                echo '<img src="'.$base_url.'/'.$pageData['image'].'" alt="'.htmlspecialchars($pageData['title']).'" class="featured-image">';
            }
            echo '<h2>'.htmlspecialchars($pageData['title']).'</h2>';
            echo '<p>'.nl2br($pageData['content']).'</p>';
            echo '</div></div>';
        } else {
            echo '<div class="alert alert-danger">Page not found.</div>';
        }
    } elseif ($page == 'login') {
        if (isset($error)) {
            echo '<div class="alert alert-danger">'.$error.'</div>';
        }
        ?>
        <form method="post" class="mx-auto" style="max-width: 400px;">
            <h2>Admin Login</h2>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <?php
    } elseif ($page == 'admin' && isLoggedIn()) {
        ?>
        <h2>Admin Panel</h2>
        <ul class="nav nav-tabs" id="adminTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="posts-tab" data-toggle="tab" href="#posts" role="tab">Posts</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="pages-tab" data-toggle="tab" href="#pages" role="tab">Pages</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="comments-tab" data-toggle="tab" href="#comments" role="tab">Comments</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="navbar-tab" data-toggle="tab" href="#navbar" role="tab">Navbar Links</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="settings-tab" data-toggle="tab" href="#settings" role="tab">Settings</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="admin-tab" data-toggle="tab" href="#admin-settings" role="tab">Admin Settings</a>
            </li>
        </ul>
        <div class="tab-content" id="adminTabContent">
            <div class="tab-pane fade show active" id="posts" role="tabpanel">
                <h3>Posts</h3>
                <?php
                if (isset($_GET['edit_post'])) {
                    $id = $_GET['edit_post'];
                    $stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
                    $stmt->execute([$id]);
                    $post = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                ?>
                <form method="post" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="id" value="<?php echo isset($post['id']) ? $post['id'] : ''; ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo isset($post['title']) ? htmlspecialchars($post['title']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" class="form-control" rows="5" required><?php echo isset($post['content']) ? htmlspecialchars($post['content']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Featured Image</label>
                        <input type="file" name="image" class="form-control-file">
                    </div>
                    <button type="submit" name="save_post" class="btn btn-success"><?php echo isset($post) ? 'Update' : 'Add'; ?></button>
                </form>
                <?php
                $posts = $db->query("SELECT * FROM posts ORDER BY created_at DESC");
                foreach ($posts as $postItem) {
                    echo '<div class="card mb-3">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">'.htmlspecialchars($postItem['title']).'</h5>';
                    echo '<p class="card-text">'.substr(strip_tags($postItem['content']), 0, 100).'...</p>';
                    echo '<a href="'.$base_url.'/admin?edit_post='.$postItem['id'].'" class="btn btn-primary">Edit</a> ';
                    echo '<a href="'.$base_url.'/admin?delete_post='.$postItem['id'].'" class="btn btn-danger">Delete</a>';
                    echo '</div></div>';
                }
                ?>
            </div>
            <div class="tab-pane fade" id="pages" role="tabpanel">
                <h3>Pages</h3>
                <?php
                if (isset($_GET['edit_page'])) {
                    $id = $_GET['edit_page'];
                    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
                    $stmt->execute([$id]);
                    $pageItem = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                ?>
                <form method="post" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="id" value="<?php echo isset($pageItem['id']) ? $pageItem['id'] : ''; ?>">
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo isset($pageItem['title']) ? htmlspecialchars($pageItem['title']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="content" class="form-control" rows="5" required><?php echo isset($pageItem['content']) ? htmlspecialchars($pageItem['content']) : ''; ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Featured Image</label>
                        <input type="file" name="image" class="form-control-file">
                    </div>
                    <button type="submit" name="save_page" class="btn btn-success"><?php echo isset($pageItem) ? 'Update' : 'Add'; ?></button>
                </form>
                <?php
                $pages = $db->query("SELECT * FROM pages ORDER BY created_at DESC");
                foreach ($pages as $pageListItem) {
                    echo '<div class="card mb-3">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">'.htmlspecialchars($pageListItem['title']).'</h5>';
                    echo '<p class="card-text">'.substr(strip_tags($pageListItem['content']), 0, 100).'...</p>';
                    echo '<a href="'.$base_url.'/admin?edit_page='.$pageListItem['id'].'" class="btn btn-primary">Edit</a> ';
                    echo '<a href="'.$base_url.'/admin?delete_page='.$pageListItem['id'].'" class="btn btn-danger">Delete</a>';
                    echo '</div></div>';
                }
                ?>
            </div>
            <div class="tab-pane fade" id="comments" role="tabpanel">
                <h3>Comments</h3>
                <?php
                $comments = $db->query("SELECT * FROM comments ORDER BY created_at DESC");
                foreach ($comments as $comment) {
                    echo '<div class="card mb-3">';
                    echo '<div class="card-body">';
                    echo '<strong>'.htmlspecialchars($comment['name']).'</strong><br>';
                    echo '<p>'.nl2br(htmlspecialchars($comment['comment'])).'</p>';
                    if (!$comment['approved']) {
                        echo '<a href="'.$base_url.'/admin?approve_comment='.$comment['id'].'" class="btn btn-success">Approve</a> ';
                    }
                    echo '<a href="'.$base_url.'/admin?delete_comment='.$comment['id'].'" class="btn btn-danger">Delete</a>';
                    echo '</div></div>';
                }
                ?>
            </div>
            <div class="tab-pane fade" id="navbar" role="tabpanel">
                <h3>Navbar Links</h3>
                <form method="post" class="mb-4">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Link</label>
                        <input type="text" name="link" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Target</label>
                        <select name="target" class="form-control">
                            <option value="_self">Same Tab</option>
                            <option value="_blank">New Tab</option>
                        </select>
                    </div>
                    <button type="submit" name="add_navbar_link" class="btn btn-success">Add</button>
                </form>
                <?php
                $navbarLinks = $db->query("SELECT * FROM navbar_links ORDER BY id DESC");
                foreach ($navbarLinks as $link) {
                    echo '<div class="card mb-3">';
                    echo '<div class="card-body">';
                    echo '<strong>'.htmlspecialchars($link['name']).'</strong> - '.htmlspecialchars($link['link']).' ('.htmlspecialchars($link['target']).')';
                    echo '<a href="'.$base_url.'/admin?delete_navbar_link='.$link['id'].'" class="btn btn-danger float-right">Delete</a>';
                    echo '</div></div>';
                }
                ?>
            </div>
            <div class="tab-pane fade" id="settings" role="tabpanel">
                <h3>Settings</h3>
                <form method="post" enctype="multipart/form-data" class="mb-4">
                    <div class="form-group">
                        <label>Logo</label>
                        <input type="text" name="logo" class="form-control" value="<?php echo htmlspecialchars($settings['logo']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($settings['description']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Footer</label>
                        <input type="text" name="footer" class="form-control" value="<?php echo htmlspecialchars($settings['footer']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Favicon</label>
                        <input type="file" name="favicon" class="form-control-file">
                    </div>
                    <div class="form-group">
                        <label>Homepage Display Option</label>
                        <select name="display_option" class="form-control">
                            <option value="pagination" <?php if(isset($settings['display_option']) && $settings['display_option'] == 'pagination') echo 'selected'; ?>>Pagination</option>
                            <option value="infinite" <?php if(isset($settings['display_option']) && $settings['display_option'] == 'infinite') echo 'selected'; ?>>Infinite Scroll</option>
                            <option value="all" <?php if(isset($settings['display_option']) && $settings['display_option'] == 'all') echo 'selected'; ?>>Show All</option>
                        </select>
                    </div>
                    <button type="submit" name="update_settings" class="btn btn-primary">Update</button>
                </form>
            </div>
            <div class="tab-pane fade" id="admin-settings" role="tabpanel">
                <h3>Admin Settings</h3>
                <?php
                $admin = $db->query("SELECT * FROM admin WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
                ?>
                <form method="post" class="mb-4">
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($admin['username']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>New Password (leave blank to keep current password)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                    <button type="submit" name="update_admin" class="btn btn-primary">Update</button>
                </form>
            </div>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-danger">Page not found.</div>';
    }
    ?>
</div>

<footer class="footer bg-light text-center py-4">
    <?php echo htmlspecialchars($settings['footer']); ?>
</footer>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<?php
if ($page == 'home' && isset($settings['display_option']) && $settings['display_option'] == 'infinite') {
    ?>
    <script>
        var currentPage = <?php echo $page_num; ?>;
        var perPage = <?php echo $per_page; ?>;
        var totalPosts = <?php echo $total_posts; ?>;
        $(document).ready(function() {
            $('#load-more').click(function() {
                currentPage++;
                $.ajax({
                    url: '<?php echo $base_url; ?>/page/' + currentPage,
                    method: 'GET',
                    success: function(data) {
                        var newPosts = $(data).find('#post-container').html();
                        $('#post-container').append(newPosts);
                        if (currentPage * perPage >= totalPosts) {
                            $('#load-more-container').hide();
                        }
                    }
                });
            });
        });
    </script>
    <?php
}
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
