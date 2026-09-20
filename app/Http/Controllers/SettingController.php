<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $hiddenCategories = Setting::getJson('hidden_categories', []);

        return view('settings.edit', [
            'categories' => Feed::CATEGORIES,
            'hiddenCategories' => $hiddenCategories,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $hidden = $request->input('hidden_categories', []);
        $hidden = array_values(array_intersect($hidden, Feed::CATEGORIES));

        Setting::setJson('hidden_categories', $hidden);

        return redirect()->route('settings.edit')->with('status', 'Preferences saved.');
    }
}
