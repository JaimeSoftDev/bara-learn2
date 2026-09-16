<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::withCount(['courses' => fn ($q) => $q->where('status', 'published')])
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $category = Category::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $data['icon'] ?? null,
        ]);

        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
            'icon' => ['nullable', 'string', 'max:50'],
        ]);

        $category->update([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'icon' => $data['icon'] ?? $category->icon,
        ]);

        return response()->json($category);
    }

    public function destroy(Request $request, Category $category)
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $category->delete();

        return response()->json(status: 204);
    }
}
