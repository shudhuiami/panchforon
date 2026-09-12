<?php

namespace App\Services\Social;

use RuntimeException;

/**
 * Anything that stops a social sign-in from completing. The message is shown
 * to the person signing in, so it stays free of provider internals.
 */
class SocialAuthException extends RuntimeException {}
