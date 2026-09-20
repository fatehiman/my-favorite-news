@if ($errors->any())
    <div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
        {{ $errors->first() }}
    </div>
@endif

<div class="space-y-4">
    <div>
        <label class="block text-sm mb-1">Name</label>
        <input type="text" name="name" value="{{ old('name', $feed->name ?? '') }}" required
               class="w-full border rounded-md px-3 py-2">
    </div>
    <div>
        <label class="block text-sm mb-1">Category</label>
        <select name="category" class="w-full border rounded-md px-3 py-2">
            @foreach (\App\Models\Feed::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(old('category', $feed->category ?? '') === $category)>
                    {{ ucfirst($category) }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm mb-1">RSS URL</label>
        <input type="url" name="url" value="{{ old('url', $feed->url ?? '') }}" required
               class="w-full border rounded-md px-3 py-2">
    </div>
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $feed->is_active ?? true))>
        Active
    </label>
</div>
