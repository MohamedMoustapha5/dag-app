<?php

namespace App\Logging;

use Illuminate\Database\QueryException;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

/**
 * Keeps personal and secret data out of the log files: passwords, tokens,
 * identity numbers, e-mail addresses, phone and card numbers are masked in
 * the message, the context and the extra data of every record. An exception
 * is reduced to its class, masked message, place and call stack (file and
 * line only: the arguments of each call, which may hold a password, are
 * never written).
 */
class MasquageDonneesSensibles implements ProcessorInterface
{
    public const MASQUE = '[masqué]';

    /**
     * A context key made of one of these words ("cni_numero", "x-api-key"),
     * or containing one of the fragments below ("current_password",
     * "xsrfToken"), has its whole value masked, whatever it holds.
     */
    private const MOTS_SENSIBLES = [
        'password', 'passe', 'secret', 'token', 'authorization', 'cookie',
        'signature', 'apikey', 'key', 'private', 'otp', 'cvv', 'iban', 'cni',
        'verification', 'telephone', 'phone', 'tel', 'mobile',
    ];

    private const FRAGMENTS_SENSIBLES = ['password', 'token', 'secret', 'apikey'];

    /**
     * Patterns masked inside any text, in this order (card numbers before
     * phone numbers, which they would otherwise partly match).
     *
     * @var array<string, string>
     */
    private const MOTIFS = [
        // password=…, "token": "…", cni_numero: … inside a text or a URL.
        '/(["\']?\b(?:password|mot_de_passe|passe|secret|token|api_key|apikey|signature|code_verification|cni_numero|cni)\b["\']?\s*[:=]\s*)("[^"]*"|\'[^\']*\'|[^\s,;&}\]]+)/i' => '$1'.self::MASQUE,
        '/\bBearer\s+[A-Za-z0-9._~+\/=-]+/i' => 'Bearer '.self::MASQUE,
        '/\$(?:2[abxy]|argon2id|argon2i)\$[^\s,;)\'"]+/' => self::MASQUE,
        '/\b(?:sk|pk|pvk|whsec|rk)_[A-Za-z0-9_]{8,}\b/' => self::MASQUE,
        '/[A-Z0-9._%+-]+@([A-Z0-9-]+(?:\.[A-Z0-9-]+)*\.[A-Z]{2,})/i' => '***@$1',
        '/(?<![\w.-])(?:\d[ -]?){12,18}\d(?![\w-])/' => self::MASQUE,
        // Cameroonian numbers: 6XX XX XX XX, 2XX XX XX XX, with or without +237.
        '/(?<![\w.+-])(?:(?:\+|00)237[\s.]?)?[26]\d{2}(?:[\s.]?\d{2}){3}(?![\w-])/' => '[téléphone masqué]',
        '/(?<![\w.-])\+\d(?:[\s.-]?\d){7,14}(?![\w-])/' => '[téléphone masqué]',
    ];

    private const PROFONDEUR_MAX = 6;

    public function __invoke(LogRecord $record): LogRecord
    {
        $exception = $record->context['exception'] ?? null;

        // A reported exception is logged with its own message as the line:
        // it gets the same treatment as in the context (query values out).
        $message = $exception instanceof Throwable && $record->message === $exception->getMessage()
            ? $this->messageDe($exception)
            : $record->message;

        return $record->with(
            message: self::masquerTexte($message),
            context: $this->masquerTableau($record->context),
            extra: $this->masquerTableau($record->extra),
        );
    }

    public static function masquerTexte(string $texte): string
    {
        return (string) preg_replace(array_keys(self::MOTIFS), array_values(self::MOTIFS), $texte);
    }

    /**
     * @param  array<mixed>  $valeurs
     * @return array<mixed>
     */
    private function masquerTableau(array $valeurs, int $profondeur = 0): array
    {
        $masquees = [];

        foreach ($valeurs as $cle => $valeur) {
            $masquees[$cle] = is_string($cle) && $this->cleSensible($cle)
                ? self::MASQUE
                : $this->masquerValeur($valeur, $profondeur);
        }

        return $masquees;
    }

    private function masquerValeur(mixed $valeur, int $profondeur): mixed
    {
        return match (true) {
            $valeur instanceof Throwable => $this->resumerException($valeur),
            is_string($valeur) => self::masquerTexte($valeur),
            is_array($valeur) => $profondeur >= self::PROFONDEUR_MAX ? self::MASQUE : $this->masquerTableau($valeur, $profondeur + 1),
            is_object($valeur) => '['.$valeur::class.']',
            default => $valeur,
        };
    }

    private function cleSensible(string $cle): bool
    {
        $cle = strtolower($cle);
        $mots = preg_split('/[^a-z0-9]+/', $cle) ?: [];

        if (array_intersect($mots, self::MOTS_SENSIBLES) !== []) {
            return true;
        }

        foreach (self::FRAGMENTS_SENSIBLES as $fragment) {
            if (str_contains($cle, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{classe: string, message: string, code: int|string, fichier: string, trace: list<string>, precedente?: array<mixed>}
     */
    private function resumerException(Throwable $exception, int $profondeur = 0): array
    {
        $resume = [
            'classe' => $exception::class,
            'message' => self::masquerTexte($this->messageDe($exception)),
            'code' => $exception->getCode(),
            'fichier' => $exception->getFile().':'.$exception->getLine(),
            'trace' => (array_map(
                fn (array $appel): string => ($appel['file'] ?? '[interne]').':'.($appel['line'] ?? 0).' '.($appel['class'] ?? '').($appel['type'] ?? '').$appel['function'].'()',
                array_slice($exception->getTrace(), 0, 25),
            )),
        ];

        if ($exception->getPrevious() !== null && $profondeur < 2) {
            $resume['precedente'] = $this->resumerException($exception->getPrevious(), $profondeur + 1);
        }

        return $resume;
    }

    /**
     * A database error repeats the values of the query (an e-mail, an
     * identity number…): only the query with its placeholders is kept, and
     * the quoted values of the driver message are masked.
     */
    private function messageDe(Throwable $exception): string
    {
        if (! $exception instanceof QueryException) {
            return $exception->getMessage();
        }

        $pilote = $exception->getPrevious()?->getMessage() ?? 'Erreur SQL';
        $pilote = (string) preg_replace_callback(
            "/'([^']*)'/",
            fn (array $valeur): string => preg_match('/^[a-z_][a-z0-9_.]*$/', $valeur[1]) === 1 ? $valeur[0] : "'".self::MASQUE."'",
            $pilote,
        );

        return $pilote.' (SQL : '.$exception->getSql().')';
    }
}
