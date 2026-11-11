<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Logger as Monolog;

/**
 * Log channel tap: every record of the channel goes through the masking of
 * sensitive data before any handler writes or sends it.
 */
class AppliquerMasquage
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if ($monolog instanceof Monolog) {
            $monolog->pushProcessor(new MasquageDonneesSensibles);
        }
    }
}
