<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiKeyController extends Controller
{
    public function show(Request $request): View
    {
        return view('api-key.show', ['user' => $request->user()]);
    }

    public function rotate(Request $request): RedirectResponse
    {
        $hadKey = $request->user()->api_key !== null;
        $request->user()->rotateApiKey();

        return redirect()->route('api-key.show')->with('status', $hadKey
            ? 'New API key created. The old key no longer works.'
            : 'API key created.');
    }
}
