<?php

namespace App\Support\Paiements;

use RuntimeException;

/**
 * The provider could not be reached, refused the request or answered
 * something unexpected. Its message is safe to show to the payer.
 */
class PasserelleIndisponible extends RuntimeException {}
