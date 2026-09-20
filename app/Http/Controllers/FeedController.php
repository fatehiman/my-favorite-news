<?php

namespace App\Http\Controllers;

use App\Models\Feed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function index(): View
    {
        $feeds = Feed::orderBy('category')->orderBy('name')->get();

        return view('feeds.index', compact('feeds'));
    }

    public function create(): View
    {
        return view('feeds.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        Feed::create($data);

        return redirect()->route('feeds.index')->with('status', 'Feed added.');
    }

    public function edit(Feed $feed): View
    {
        return view('feeds.edit', compact('feed'));
    }

    public function update(Request $request, Feed $feed): RedirectResponse
    {
        $data = $this->validated($request);

        $feed->update($data);

        return redirect()->route('feeds.index')->with('status', 'Feed updated.');
    }

    public function destroy(Feed $feed): RedirectResponse
    {
        $feed->delete();

        return redirect()->route('feeds.index')->with('status', 'Feed deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.implode(',', Feed::CATEGORIES)],
            'url' => ['required', 'url', 'max:2048'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
