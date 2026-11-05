<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CandidatureSpontanee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CandidatureSpontaneeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telephone' => ['required', 'string', 'max:30'],
            'portfolio' => ['nullable', 'url:http,https', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'cv' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $data['portfolio'] = $data['portfolio'] ?: null;
        $data['cv_path'] = $request->file('cv')->store('candidatures-spontanees');
        unset($data['cv']);

        CandidatureSpontanee::create($data);

        return back();
    }
}
