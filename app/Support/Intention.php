<?php

namespace App\Support;

use App\Models\FormationSession;
use App\Models\Formule;
use App\Models\Service;

/**
 * What a visitor was trying to do when the login wall stopped them: requesting
 * a service, choosing a subscription or enrolling in a formation. Read from the
 * "intended" URL Laravel keeps in the session, it lets the login and
 * registration pages explain why the visitor is asked to sign in, and suggest
 * the matching account type — the visitor is then sent straight back to the
 * action once signed in.
 */
class Intention
{
    /**
     * @return array{message: string, compteType: string}|null
     */
    public static function depuis(?string $url): ?array
    {
        if ($url === null || $url === '') {
            return null;
        }

        $chemin = parse_url($url, PHP_URL_PATH) ?: '';
        parse_str((string) parse_url($url, PHP_URL_QUERY), $requete);

        if (str_ends_with($chemin, '/espace-client/demandes/creer')) {
            return self::demande($requete);
        }

        if (preg_match('#/espace-apprenant/formations/([0-9a-f-]{36})/inscription$#', $chemin, $correspondance) === 1) {
            $session = FormationSession::query()->with('formation')->where('id', $correspondance[1])->first();

            return [
                'message' => $session !== null
                    ? "Connectez-vous pour vous inscrire à la formation « {$session->formation->nom} »."
                    : 'Connectez-vous pour vous inscrire à cette formation.',
                'compteType' => 'apprenant',
            ];
        }

        return null;
    }

    /**
     * @param  array<int|string, mixed>  $requete
     * @return array{message: string, compteType: string}
     */
    private static function demande(array $requete): array
    {
        $formuleId = is_string($requete['formule'] ?? null) ? $requete['formule'] : null;
        $serviceId = is_string($requete['service'] ?? null) ? $requete['service'] : null;

        if ($formuleId !== null) {
            $formule = Formule::query()->where('id', $formuleId)->first();

            return [
                'message' => $formule !== null
                    ? "Connectez-vous pour choisir la formule « {$formule->nom} »."
                    : 'Connectez-vous pour choisir cette formule.',
                'compteType' => 'particulier',
            ];
        }

        $service = $serviceId !== null ? Service::query()->where('id', $serviceId)->first() : null;

        return [
            'message' => $service !== null
                ? "Connectez-vous pour demander le service « {$service->nom} »."
                : 'Connectez-vous pour faire votre demande.',
            'compteType' => 'particulier',
        ];
    }
}
