<?php
// index.php

require_once 'vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

// Middleware for JSON response
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Database and Redis setup
require_once 'config.php';

// Routes
require_once 'routes/auth.php';
require_once 'routes/github.php';

// Error Handling
$app->addErrorMiddleware(true, true, true);

$app->run();

// config
$pdo = new PDO('mysql:host=localhost;dbname=github_api', 'root', 'password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// routes
$app->post('/register', function ($request, $response) use ($pdo) {
    $data = $request->getParsedBody();
    $email = $data['email'] ?? '';
    $password = password_hash($data['password'] ?? '', PASSWORD_BCRYPT);

    if (!$email || !$password) {
        return $response->withStatus(400)->withJson(['error' => 'Email and password are required']);
    }

    $stmt = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
    $stmt->execute([$email, $password]);

    return $response->withJson(['message' => 'User registered successfully']);
});

$app->post('/login', function ($request, $response) use ($pdo) {
    $data = $request->getParsedBody();
    $email = $data['email'] ?? '';
    $password = $data['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $token = bin2hex(random_bytes(16));
        $redis->setex($token, 3600, $user['id']);
        return $response->withJson(['token' => $token]);
    }

    return $response->withStatus(401)->withJson(['error' => 'Invalid credentials']);
});

// routes
$app->get('/github/users', function ($request, $response) use ($redis) {
    $params = $request->getQueryParams();
    $usernames = explode(',', $params['usernames'] ?? '');

    if (count($usernames) > 10) {
        return $response->withStatus(400)->withJson(['error' => 'Maximum 10 usernames allowed']);
    }

    $results = [];
    foreach ($usernames as $username) {
        $cachedUser = $redis->get($username);

        if ($cachedUser) {
            $results[] = json_decode($cachedUser, true);
        } else {
            $ch = curl_init("https://api.github.com/users/$username");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: PHP']);
            $apiResponse = curl_exec($ch);
            $userData = json_decode($apiResponse, true);

            if (isset($userData['login'])) {
                $userData['average_followers'] = $userData['followers'] / max($userData['public_repos'], 1);
                $redis->setex($username, 120, json_encode($userData));
                $results[] = $userData;
            }
            curl_close($ch);
        }
    }

    usort($results, fn($a, $b) => $a['name'] <=> $b['name']);

    return $response->withJson($results);
});
