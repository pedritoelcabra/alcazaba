<?php

class Logger
{
    public static function info(string $message, array $context = []): void
    {
        $msg = $message;
        if ($context !== []) {
            $msg .= json_encode($context);
        }
        $msg .= PHP_EOL;

        $logFileDir = sprintf('%s../../logs/', plugin_dir_path(__FILE__));
        $logFileName = $logFileDir . 'info.log';

        if (! is_writable($logFileName)) {
            throw new RuntimeException($logFileName . ' is not writable');
        }

        file_put_contents($logFileName, $msg, FILE_APPEND);
    }
}
