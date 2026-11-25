<?php

namespace App\Http\Controllers\Public;

use App\Enums\Action;
use App\Enums\Donnee;
use App\Http\Controllers\Controller;
use App\Models\TicketSupport;
use App\Support\Notifications\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('public/contact');
    }

    /**
     * A visitor's message becomes a support ticket (origine PUBLIC), so it
     * lands in the team's support inbox and is answered by e-mail.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'sujet' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $ticket = DB::transaction(function () use ($data): TicketSupport {
            $ticket = TicketSupport::create([
                'user_id' => null,
                'origine' => 'PUBLIC',
                'nom' => $data['nom'],
                'email' => $data['email'],
                'categorie' => 'AUTRE',
                'sujet' => $data['sujet'],
            ]);

            $ticket->ajouterMessage($data['message']);

            return $ticket;
        });

        Notifier::alerterEquipe(
            Donnee::Support,
            Action::Modifier,
            'support.message',
            "Message du site — {$ticket->reference}",
            "{$ticket->nom} : {$ticket->sujet}.",
            route('espace-admin.support.show', $ticket),
        );

        return back();
    }
}
