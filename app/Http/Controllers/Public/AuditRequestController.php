<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\AuditRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuditRequestController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'page_url' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        AuditRequest::create($data);

        return back();
    }
}
