<?php

namespace App\Http\Controllers\Espaces;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\MessageSupport;
use App\Models\TicketSupport;
use App\Models\User;
use App\Support\Notifications\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Besoin d'aide ?" of the client and apprenant espaces: the account holder
 * opens support requests and talks with the DAG team without leaving their
 * espace. Both espaces share this controller; each only ever sees its own
 * tickets (404 otherwise, so references can't be probed).
 */
class SupportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user('web');

        $tickets = TicketSupport::query()
            ->where('user_id', $user->id)
            ->latest('dernier_message_at')
            ->get();

        return Inertia::render($this->page($user, 'support-index'), [
            ...$this->entete($user),
            'categories' => $this->options(TicketSupport::CATEGORIES),
            'tickets' => $tickets->map(fn (TicketSupport $ticket) => [
                'id' => $ticket->id,
                'reference' => $ticket->reference,
                'sujet' => $ticket->sujet,
                'categorie' => TicketSupport::CATEGORIES[$ticket->categorie],
                'statut' => $ticket->statut,
                'statutLabel' => TicketSupport::STATUTS[$ticket->statut],
                'dernierMessage' => $ticket->dernier_message_at?->toIso8601String(),
                'url' => route($this->espace($user).'.support.show', $ticket),
            ]),
            'expediteur' => [
                'nom' => $user->client !== null ? $user->client->nom : $user->name,
                'email' => $user->email,
            ],
            'urls' => ['creer' => route($this->espace($user).'.support.store')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user('web');

        $data = $request->validate([
            'categorie' => ['required', Rule::in(array_keys(TicketSupport::CATEGORIES))],
            'sujet' => ['required', 'string', 'max:191'],
            'message' => ['required', 'string', 'max:5000'],
        ], [], ['sujet' => 'objet', 'message' => 'message']);

        $ticket = DB::transaction(function () use ($user, $data): TicketSupport {
            $ticket = TicketSupport::create([
                'user_id' => $user->id,
                'origine' => 'ESPACE',
                'nom' => $user->client !== null ? $user->client->nom : $user->name,
                'email' => $user->email,
                'categorie' => $data['categorie'],
                'sujet' => $data['sujet'],
            ]);

            $ticket->ajouterMessage($data['message']);

            return $ticket;
        });

        $this->alerterEquipe($ticket, "Nouvelle demande de support — {$ticket->reference}", "{$ticket->nom} : {$ticket->sujet}.");

        Inertia::flash('toast', ['type' => 'success', 'message' => "Demande {$ticket->reference} envoyée : l'équipe DAG vous répond ici."]);

        return to_route($this->espace($user).'.support.show', $ticket);
    }

    public function show(Request $request, TicketSupport $ticket): Response
    {
        $user = $request->user('web');
        $this->autoriser($user, $ticket);

        $ticket->load('messages.membre');

        return Inertia::render($this->page($user, 'support-show'), [
            ...$this->entete($user),
            'ticket' => [
                'id' => $ticket->id,
                'reference' => $ticket->reference,
                'sujet' => $ticket->sujet,
                'categorie' => TicketSupport::CATEGORIES[$ticket->categorie],
                'statut' => $ticket->statut,
                'statutLabel' => TicketSupport::STATUTS[$ticket->statut],
                'ouvertLe' => $ticket->created_at?->toIso8601String(),
            ],
            'messages' => $ticket->messages->map(fn (MessageSupport $message) => [
                'id' => $message->id,
                'deDag' => $message->auteur === 'EQUIPE',
                'auteur' => $message->auteur === 'EQUIPE' ? 'Équipe DAG' : 'Vous',
                'contenu' => $message->contenu,
                'date' => $message->created_at?->toIso8601String(),
            ]),
            'urls' => [
                'liste' => route($this->espace($user).'.support.index'),
                'repondre' => route($this->espace($user).'.support.repondre', $ticket),
                'clore' => route($this->espace($user).'.support.clore', $ticket),
            ],
        ]);
    }

    public function repondre(Request $request, TicketSupport $ticket): RedirectResponse
    {
        $user = $request->user('web');
        $this->autoriser($user, $ticket);

        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);

        $ticket->ajouterMessage($data['message']);

        $this->alerterEquipe($ticket, "Nouveau message — {$ticket->reference}", "{$ticket->nom} a répondu : ".Str::limit($data['message'], 120));

        return back();
    }

    /**
     * The requester marks their question as settled; writing again reopens it.
     */
    public function clore(Request $request, TicketSupport $ticket): RedirectResponse
    {
        $user = $request->user('web');
        $this->autoriser($user, $ticket);

        $ticket->update(['statut' => 'CLOS']);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Demande clôturée. Écrivez à nouveau à tout moment pour la rouvrir.']);

        return back();
    }

    private function autoriser(User $user, TicketSupport $ticket): void
    {
        abort_unless($ticket->user_id === $user->id, 404);
    }

    private function alerterEquipe(TicketSupport $ticket, string $titre, string $message): void
    {
        Notifier::alerterEquipe(
            Donnee::DemandesClients,
            Action::Modifier,
            'support.message',
            $titre,
            $message,
            route('espace-admin.support.show', $ticket),
        );
    }

    /**
     * Route-name prefix of the espace the user belongs to.
     */
    private function espace(User $user): string
    {
        return $user->estApprenant() ? 'espace-apprenant' : 'espace-client';
    }

    private function page(User $user, string $vue): string
    {
        return $user->estApprenant() ? "espaces/apprenant/{$vue}" : "espaces/client/{$vue}";
    }

    /**
     * Header props the espace layout reads.
     *
     * @return array<string, mixed>
     */
    private function entete(User $user): array
    {
        return $user->estApprenant()
            ? ['apprenant' => $user->apprenant->espaceHeaderProps()]
            : ['client' => $user->client->espaceHeaderProps()];
    }

    /**
     * @param  array<string, string>  $libelles
     * @return list<array{value: string, label: string}>
     */
    private function options(array $libelles): array
    {
        $options = [];

        foreach ($libelles as $valeur => $libelle) {
            $options[] = ['value' => $valeur, 'label' => $libelle];
        }

        return $options;
    }
}
