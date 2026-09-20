<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TagController extends Controller
{
    public function index(): View
    {
        $included = Setting::getJson('included_tags', []);
        $excluded = Setting::getJson('excluded_tags', []);

        return view('tags.index', [
            'includedTags' => $included,
            'excludedTags' => $excluded,
            'seenTags' => Tag::orderBy('name')->pluck('name')
                ->reject(fn ($t) => in_array($t, $included) || in_array($t, $excluded))
                ->values(),
        ]);
    }

    /**
     * Include, exclude, or clear a tag/keyword. Not tied to a real Tag row —
     * this also covers manually-added free-text keywords (e.g. a person's
     * name), which Favorites matches against article text, not just the
     * feed-supplied <category> tags.
     */
    public function setTag(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'action' => ['required', 'in:include,exclude,clear'],
        ]);

        $name = mb_strtolower(trim($request->input('name')));
        $action = $request->input('action');

        $included = collect(Setting::getJson('included_tags', []))->reject(fn ($t) => $t === $name);
        $excluded = collect(Setting::getJson('excluded_tags', []))->reject(fn ($t) => $t === $name);

        if ($action === 'include') {
            $included->push($name);
        } elseif ($action === 'exclude') {
            $excluded->push($name);
        }

        Setting::setJson('included_tags', $included->unique()->values()->all());
        Setting::setJson('excluded_tags', $excluded->unique()->values()->all());

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'name' => $name, 'action' => $action]);
        }

        return back()->with('status', match ($action) {
            'include' => "\"{$name}\" added to Favorites (included).",
            'exclude' => "\"{$name}\" excluded from Favorites.",
            default => "\"{$name}\" removed from Favorites filters.",
        });
    }
}
