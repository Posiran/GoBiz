<?php
namespace App\Http\Controllers;
use App\Models\Company;
use App\Models\Listing;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller {
    /** تا ۴۰٬۰۰۰ آگهی؛ برای سایت بزرگ‌تر باید به sitemap index تقسیم شود */
    public function __invoke() {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $u = fn ($loc, $mod = null) => '<url><loc>' . e($loc) . '</loc>' . ($mod ? '<lastmod>' . $mod->toAtomString() . '</lastmod>' : '') . '</url>';
            $out = [$u(route('home')), $u(route('listings.index')), $u(route('companies.index'))];
            Listing::published()->select('slug', 'updated_at')->orderBy('id')->limit(40000)->cursor()
                ->each(function ($l) use (&$out, $u) { $out[] = $u(route('listings.show', $l->slug), $l->updated_at); });
            Company::where('status', 'active')->select('slug', 'updated_at')->limit(9000)->cursor()
                ->each(function ($c) use (&$out, $u) { $out[] = $u(route('companies.show', $c->slug), $c->updated_at); });
            return '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . implode('', $out) . '</urlset>';
        });
        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
