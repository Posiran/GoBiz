<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Listing;
use App\Models\BusinessDetail;

class HomeController extends Controller {
    public function __invoke() {
        return view('home', [
            'categories' => Category::whereNull('parent_id')->orderBy('sort_order')->get(),
            'featured' => Listing::published()->with(['city', 'industrialTown', 'company:id,name,verified_level', 'media', 'category', 'business'])
                ->featuredFirst()->latest('published_at')->limit(8)->get(),
            'businesses' => Listing::published()->whereIn('type', ['business', 'startup'])
                ->with(['city', 'company:id,name,verified_level', 'media', 'category', 'business'])
                ->latest('published_at')->limit(4)->get(),
        ]);
    }
}
