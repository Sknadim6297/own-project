<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\CategoryHub;
use App\Services\ExploreCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request, CategoryHub $hub): View
    {
        $q = trim(strip_tags((string) $request->query('q', '')));
        $categories = $hub->list($q);

        return view('frontend.sections.categories-index', [
            'categories' => $categories,
            'q' => $q,
        ]);
    }

    public function show(Request $request, string $category, CategoryHub $hub): View
    {
        if (! array_key_exists($category, ExploreCatalog::CATEGORIES)) {
            abort(404);
        }

        $tab = (string) $request->query('tab', 'tools');
        $payload = $hub->show($category, $tab);

        return view('frontend.sections.category-show', $payload);
    }

    /**
     * Preserve old Explore bookmarks: /gridlisting → All Categories,
     * /gridlisting?category=slug → category detail.
     */
    public function legacyExploreRedirect(Request $request, ExploreCatalog $explore): RedirectResponse
    {
        $category = (string) $request->query('category', '');

        if ($category === '' || ! array_key_exists($category, ExploreCatalog::CATEGORIES)) {
            // Infer category from legacy ?service=slug bookmarks via ExploreCatalog filters.
            $browse = $explore->browse($request);
            $category = (string) ($browse['filters']['category'] ?? '');
        }

        if ($category !== '' && array_key_exists($category, ExploreCatalog::CATEGORIES)) {
            $tab = (string) $request->query('type', 'tools');
            if ($tab === 'experts') {
                $tab = 'experts';
            } elseif ($tab === 'programmes') {
                $tab = 'programmes';
            } else {
                $tab = 'tools';
            }

            return redirect()->route('categories.show', [
                'category' => $category,
                'tab' => $tab,
            ], 301);
        }

        return redirect()->route('categories.index', $request->only('q'), 301);
    }
}
