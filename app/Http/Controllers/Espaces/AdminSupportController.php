<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MessageSupport;
use App\Models\TicketSupport;
use App\Notifications\SupportReponseNotification;
use App\Support\Notifications\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Support inbox of the DAG team: requests opened from the client and
 * apprenant espaces, and messages from the public contact form. Reading
 * follows the "demandes clients" right; answering and closing need Modifier.
 */
class AdminSupportController extends Controller
{
    use SharesAdminEspaceProps;

    public function index(Request $request): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Support, Action::Voir), 403);

        $tickets = TicketSupport::query()
            ->with('user')
            ->orderByRaw("statut = 'OUVERT' desc")
            ->latest('dernier_message_at')
            ->get();

        return Inertia::render('espaces/admin-support', [
            ...$this->adminEspaceProps($membre),
            'tickets' => $tickets->map(fn (TicketSupport $ticket) => [
                'id' => $ticket->id,
                'reference' => $ticket->reference,
                'nom' => $ticket->nom,
                'provenance' => $this->provenance($ticket),
                'categorie' => TicketSupport::CATEGORIES[$ticket->categorie],
                'sujet' => $ticket->sujet,
                'statut' => $ticket->statut,
                'dernierMessage' => $ticket->dernier_message_at?->toIso8601String(),
            ]),
        ]);
    }

    public function show(Request $request, TicketSupport $ticket): Response
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Support, Action::Voir), 403);

        $ticket->load(['messages.membre', 'user']);

        return Inertia::render('espaces/admin-support-show', [
            ...$this->adminEspaceProps($membre),
            'peutRepondre' => $membre->peut(Donnee::Support, Action::Modifier),
            'ticket' => [
                'id' => $ticket->id,
                'reference' => $ticket->reference,
                'nom' => $ticket->nom,
                'email' => $ticket->email,
                'telephone' => $ticket->user?->routeNotificationForSms(),
                'provenance' => $this->provenance($ticket),
                'categorie' => TicketSupport::CATEGORIES[$ticket->categorie],
                'sujet' => $ticket->sujet,
                'statut' => $ticket->statut,
                'ouvertLe' => $ticket->created_at?->toIso8601String(),
            ],
            'messages' => $ticket->messages->map(fn (MessageSupport $message) => [
                'id' => $message->id,
                'deDag' => $message->auteur === 'EQUIPE',
                'auteur' => $message->auteur === 'EQUIPE'
                    ? ($message->membre !== null ? $message->membre->name.' — DAG' : 'Équipe DAG')
                    : $ticket->nom,
                'contenu' => $message->contenu,
                'date' => $message->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function repondre(Request $request, TicketSupport $ticket): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Support, Action::Modifier), 403);

        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        $reponse = $ticket->ajouterMessage($data['message'], $membre);
        $notification = new SupportReponseNotification($ticket, $reponse);

        // A visitor of the public form has no account: the answer goes by e-mail.
        if ($ticket->user !== null) {
            Notifier::envoyer($ticket->user, $notification);
        } else {
            Notifier::envoyer(Notification::route('mail', $ticket->email), $notification);
        }

        ActivityLog::record($membre, 'a répondu à la demande de support', $ticket, "{$ticket->reference} : {$ticket->sujet}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => "Réponse envoyée à {$ticket->nom}."]);

        return back();
    }

    public function update(Request $request, TicketSupport $ticket): RedirectResponse
    {
        $membre = $this->membre($request);
        abort_unless($membre->peut(Donnee::Support, Action::Modifier), 403);

        $data = $request->validate(['statut' => ['required', Rule::in(['OUVERT', 'CLOS'])]]);

        $ticket->update($data);

        ActivityLog::record($membre, $data['statut'] === 'CLOS' ? 'a clôturé la demande de support' : 'a rouvert la demande de support', $ticket, "{$ticket->reference}.");

        return back();
    }

    private function provenance(TicketSupport $ticket): string
    {
        if ($ticket->origine === 'PUBLIC') {
            return 'Visiteur (formulaire de contact)';
        }

        return $ticket->user?->estApprenant() ? 'Apprenant' : 'Client';
    }
}
