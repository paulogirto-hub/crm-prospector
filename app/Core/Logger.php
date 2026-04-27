<?php

namespace App\Core;

/**
 * Structured JSON Logger — OPS-22
 *
 * Outputs one JSON object per line for easy parsing by log aggregators.
 * Format: {"timestamp":"ISO8601","level":"INFO","message":"...","context":{...},"request_id":"..."}
 */
class Logger
{
    const DEBUG    = 'DEBUG';
    const INFO     = 'INFO';
    const WARNING  = 'WARNING';
    const ERROR    = 'ERROR';
    const CRITICAL = 'CRITICAL';

    /** @var string Request ID — generated once per request */
    private static string $requestId;

    /** @var string Log directory */
    private static string $logDir;

    /** @var int Days to keep log files */
    private static int $keepDays = 30;

    /**
     * Initialize the logger with a request ID
     */
    public static function init(string $requestId = ''): void
    {
        self::$requestId = $requestId ?: uniqid('req_', true);
        self::$logDir = dirname(__DIR__, 2) . '/storage/logs';

        // Ensure log directory exists
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0775, true);
        }

        // Rotate old logs
        self::rotate();
    }

    /**
     * Get the current request ID
     */
    public static function getRequestId(): string
    {
        return self::$requestId ?? '';
    }

    /**
     * Set request ID externally (called from index.php before init)
     */
    public static function setRequestId(string $id): void
    {
        self::$requestId = $id;
    }

    /**
     * Log at DEBUG level
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Log at INFO level
     */
    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log at WARNING level
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log at ERROR level
     */
    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Log at CRITICAL level
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Core logging method
     */
    private static function log(string $level, string $message, array $context = []): void
    {
        $entry = [
            'timestamp'  => date('c'),
            'level'      => $level,
            'message'    => $message,
            'context'    => (object) $context,
            'request_id' => self::$requestId ?? 'unknown',
        ];

        $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

        $logFile = self::$logDir . '/app-' . date('Y-m-d') . '.log';

        // Write to file
        file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);

        // Also send CRITICAL and ERROR to PHP error_log for external monitoring
        if ($level === self::CRITICAL || $level === self::ERROR) {
            error_log("[{$level}] {$message}" . (!empty($context) ? ' ' . json_encode($context) : ''));
        }
    }

    /**
     * Remove log files older than $keepDays
     */
    private static function rotate(): void
    {
        $pattern = self::$logDir . '/app-*.log';
        $files = glob($pattern);

        if (!$files) {
            return;
        }

        $cutoff = time() - (self::$keepDays * 86400);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    /**
     * Custom error handler — converts PHP errors to log entries
     */
    public static function errorHandler(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $level = match ($errno) {
            E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR => self::CRITICAL,
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => self::WARNING,
            E_NOTICE, E_USER_NOTICE, E_STRICT, E_DEPRECATED, E_USER_DEPRECATED => self::DEBUG,
            E_USER_ERROR => self::ERROR,
            default => self::ERROR,
        };

        self::log($level, $errstr, [
            'file' => $errfile,
            'line' => $errline,
            'type' => $errno,
        ]);

        // Return true to suppress default PHP error handler
        return true;
    }

    /**
     * Custom exception handler — logs uncaught exceptions as CRITICAL
     */
    public static function exceptionHandler(\Throwable $e): void
    {
        self::critical('Uncaught exception: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ]);
    }
}