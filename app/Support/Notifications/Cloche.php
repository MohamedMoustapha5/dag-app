<?php

namespace App\Support\Notifications;

use App\Models\MembreEquipe;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

/**
 * What the bell of the espace displays for a user or a team member: how many
 * notifications are unread and the latest ones.
 */
class Cloche
{
    private const RECENTES = 15;

    /**
     * @return array{nonLues: int, recentes: list<array{id: string, titre: string, message: string, url: string|null, lue: bool, date: string|null}>}|null
     */
    public static function pour(User|MembreEquipe|null $destinataire): ?array
    {
        if ($destinataire === null) {
            return null;
        }

        $recentes = [];

        foreach ($destinataire->notifications()->latest()->limit(self::RECENTES)->get() as $notification) {
            $recentes[] = self::presenter($notification);
        }

        return [
            'nonLues' => $destinataire->unreadNotifications()->count(),
            'recentes' => $recentes,
        ];
    }

    /**
     * @return array{id: string, titre: string, message: string, url: string|null, lue: bool, date: string|null}
     */
    private static function presenter(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $donnees */
        $donnees = $notification->data;

        return [
            'id' => $notification->id,
            'titre' => is_string($donnees['titre'] ?? null) ? $donnees['titre'] : 'Notification',
            'message' => is_string($donnees['message'] ?? null) ? $donnees['message'] : '',
            'url' => self::lienSur($donnees['url'] ?? null),
            'lue' => $notification->read_at !== null,
            'date' => $notification->created_at?->toIso8601String(),
        ];
    }

    /**
     * Only an internal path is ever followed from the bell.
     */
    public static function lienSur(mixed $url): ?string
    {
        if (! is_string($url) || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }

        return $url;
    }
}
