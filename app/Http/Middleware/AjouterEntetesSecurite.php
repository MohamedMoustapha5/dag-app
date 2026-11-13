<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Browser-side protections sent with every response: no framing by other
 * sites (clickjacking), no MIME sniffing of uploaded documents, no full URLs
 * leaked to other sites, no camera, microphone or location, and HTTPS only
 * once the site has been reached over HTTPS.
 *
 * The policy leaves scripts and styles alone (no script-src): restricting
 * them needs a nonce on the inline appearance script and on the Vite tags.
 */
class AjouterEntetesSecurite
{
    /**
     * @var array<string, string>
     */
    private const ENTETES = [
        'Content-Security-Policy' => "base-uri 'self'; object-src 'none'; frame-ancestors 'self'",
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ];

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::ENTETES as $nom => $valeur) {
            if (! $response->headers->has($nom)) {
                $response->headers->set($nom, $valeur);
            }
        }

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
