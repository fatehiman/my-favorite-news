<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', [
            'categories' => Feed::CATEGORIES,
            'hiddenCategories' => Setting::getJson('hidden_categories', []),
            'includedTags' => Setting::getJson('included_tags', []),
            'excludedTags' => Setting::getJson('excluded_tags', []),
            'allTags' => Tag::orderBy('name')->pluck('name'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $hidden = array_values(array_intersect(
            $request->input('hidden_categories', []),
            Feed::CATEGORIES
        ));

        Setting::setJson('hidden_categories', $hidden);
        Setting::setJson('included_tags', $this->parseTagList($request->input('included_tags', '')));
        Setting::setJson('excluded_tags', $this->parseTagList($request->input('excluded_tags', '')));

        return redirect()->route('settings.edit')->with('status', 'Preferences saved.');
    }

    /**
     * Quick action from a tag chip on an article card: include, exclude, or clear a single tag.
     */
    public function setTag(Request $request, string $tag, string $action): RedirectResponse
    {
        $tag = mb_strtolower(trim($tag));
        $included = collect(Setting::getJson('included_tags', []));
        $excluded = collect(Setting::getJson('excluded_tags', []));

        $included = $included->reject(fn ($t) => $t === $tag);
        $excluded = $excluded->reject(fn ($t) => $t === $tag);

        if ($action === 'include') {
            $included->push($tag);
        } elseif ($action === 'exclude') {
            $excluded->push($tag);
        }

        Setting::setJson('included_tags', $included->unique()->values()->all());
        Setting::setJson('excluded_tags', $excluded->unique()->values()->all());

        return back();
    }

    private function parseTagList(string $raw): array
    {
        return collect(explode(',', $raw))
            ->map(fn ($t) => mb_strtolower(trim($t)))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
