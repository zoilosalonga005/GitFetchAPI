<?php
function fetchGitHubUser($username)
{
    $url = "https://api.github.com/users/$username";
    $options = [
        "http" => [
            "header" => "User-Agent: PHP\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    if (!isset($data['login'])) {
        return null;
    }

    $averageFollowers = $data['public_repos'] > 0
        ? $data['followers'] / $data['public_repos']
        : 0;

    return [
        'name' => $data['name'] ?? 'N/A',
        'login' => $data['login'],
        'company' => $data['company'] ?? 'N/A',
        'followers' => $data['followers'],
        'public_repos' => $data['public_repos'],
        'average_followers' => $averageFollowers
    ];
}
