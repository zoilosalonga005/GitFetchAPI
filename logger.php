<?php
function logInfo($message)
{
    logMessage('INFO', $message);
}

function logError($message)
{
    logMessage('ERROR', $message);
}

function logMessage($level, $message)
{
    $logFile = __DIR__ . '/app.log'; // Log file path
    $timestamp = date('Y-m-d H:i:s'); // Current timestamp
    $formattedMessage = "[$timestamp] [$level] $message" . PHP_EOL;

    // Write log to file
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}
