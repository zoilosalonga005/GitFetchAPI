<?php
function connectRedis()
{
    $redis = new Redis();
    $redis->connect(getenv('REDIS_HOST') ?: 'redis', 6379);
    return $redis;
}

function getFromCache($key)
{
    $redis = connectRedis();
    $data = $redis->get($key);
    return $data ? json_decode($data, true) : null;
}

function saveToCache($key, $value)
{
    $redis = connectRedis();
    $redis->setex($key, 120, json_encode($value));
}
