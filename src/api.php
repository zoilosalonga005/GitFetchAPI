<?php
require 'db.php';
require 'auth.php';
require 'github.php';
require 'cache.php';
require 'logger.php';

header('Content-Type: application/json');

// Authenticate user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['endpoint'] === 'register') {
    // Handle user registration
    echo json_encode(registerUser($_POST));
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['endpoint'] === 'login') {
    // Handle user login
    echo json_encode(loginUser($_POST));
    exit;
}

// Ensure valid session
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Fetch GitHub user details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['endpoint'] === 'github') {
    $usernames = $_POST['usernames'] ?? [];
    if (count($usernames) > 10) {
        http_response_code(400);
        echo json_encode(['error' => 'Maximum of 10 usernames allowed']);
        exit;
    }

    $results = [];
    foreach ($usernames as $username) {
        $cachedData = getFromCache($username);
        if ($cachedData) {
            $results[] = $cachedData;
        } else {
            $data = fetchGitHubUser($username);
            if ($data) {
                saveToCache($username, $data);
                $results[] = $data;
            } else {
                logError("GitHub user not found: $username");
            }
        }
    }

    usort($results, fn($a, $b) => strcmp($a['name'], $b['name']));
    echo json_encode($results);
    exit;
}
