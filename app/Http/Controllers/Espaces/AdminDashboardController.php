<?php

namespace App\Http\Controllers\Espaces;

use App\Concerns\SharesAdminEspaceProps;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    use SharesAdminEspaceProps;

    public function __invoke(Request $request): Response
    {
        $membre = $request->user()->membreEquipe;

        return Inertia::render('espaces/admin-dashboard', [
            ...$this->adminEspaceProps($membre),
        ]);
    }
}
