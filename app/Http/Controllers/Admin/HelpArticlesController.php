<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class HelpArticlesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/HelpArticles', [
            'articles' => HelpArticle::orderBy('category')->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'    => 'required|string|max:200',
            'category' => 'required|string|max:100',
            'body'     => 'required|string|max:50000',
            'slug'     => 'nullable|string|max:200',
        ]);
        HelpArticle::create(array_merge($data, [
            'id'        => 'ha_' . Str::lower(Str::random(12)),
            'slug'      => $data['slug'] ?? Str::slug($data['title']),
            'published' => 0,
            'created_at'=> now(),
            'updated_at'=> now(),
        ]));
        return back()->with('success', 'Article created.');
    }

    public function update(Request $request, HelpArticle $article): RedirectResponse
    {
        $data = $request->validate([
            'title'    => 'nullable|string|max:200',
            'category' => 'nullable|string|max:100',
            'body'     => 'nullable|string|max:50000',
        ]);
        $article->update($data + ['updated_at' => now()]);
        return back()->with('success', 'Article updated.');
    }

    public function publish(HelpArticle $article): RedirectResponse
    {
        $article->update(['published' => 1, 'published_at' => now()]);
        return back()->with('success', 'Article published.');
    }

    public function destroy(HelpArticle $article): RedirectResponse
    {
        $article->delete();
        return back()->with('success', 'Article deleted.');
    }
}
