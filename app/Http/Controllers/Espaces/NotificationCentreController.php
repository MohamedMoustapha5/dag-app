<?php

namespace App\Http\Controllers\Espaces;

use App\Http\Controllers\Controller;
use App\Models\MembreEquipe;
use App\Models\User;
use App\Support\Notifications\Cloche;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Actions of the notification bell, shared by the three espaces (client,
 * apprenant, admin). The admin routes read the team member, the others the
 * signed-in user: nobody can touch another account's notifications.
 */
class NotificationCentreController extends Controller
{
    /**
     * Marks one notification as read and opens the page it points to.
     */
    public function lire(Request $request, string $notification): RedirectResponse
    {
        $element = $this->destinataire($request)->notifications()->whereKey($notification)->firstOrFail();

        $element->markAsRead();

        $url = Cloche::lienSur($element->data['url'] ?? null);

        return $url !== null ? redirect($url) : back();
    }

    public function toutesLues(Request $request): RedirectResponse
    {
        $this->destinataire($request)->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    private function destinataire(Request $request): User|MembreEquipe
    {
        $destinataire = $request->routeIs('espace-admin.*') ? $request->user('admin') : $request->user('web');

        abort_if($destinataire === null, 403);

        return $destinataire;
    }
}
