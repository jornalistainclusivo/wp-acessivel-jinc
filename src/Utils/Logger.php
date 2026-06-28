<?php declare(strict_types=1);

namespace WpAcessivelJinc\Utils;

/**
 * Structured logging utility.
 */
final class Logger
{
    private const PREFIX = '[WP-Acessível-JINC] ';

    /**
     * @var bool Whether debug logging is enabled.
     */
    private bool $debugMode = false;

    public function enableDebugMode(): void
    {
        $this->debugMode = true;
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        if ($this->debugMode) {
            $this->log('DEBUG', $message, $context);
        }
    }

    private function log(string $level, string $message, array $context): void
    {
        $contextStr = empty($context) ? '' : ' | Context: ' . wp_json_encode($context);
        error_log(self::PREFIX . "[$level] $message" . $contextStr);
    }
}
