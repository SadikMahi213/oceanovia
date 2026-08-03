<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CmsPage;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $sitemap = Cache::remember('sitemap', 3600, function () {
            $products = Product::published()
                ->select('slug', 'updated_at')
                ->latest()
                ->get();

            $categories = Category::active()
                ->select('slug', 'updated_at')
                ->get();

            $cmsPages = CmsPage::published()
                ->select('slug', 'updated_at')
                ->get();

            $staticPages = [
                ['url' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['url' => route('products.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['url' => route('products.deals'), 'priority' => '0.8', 'changefreq' => 'daily'],
                ['url' => route('products.sellers'), 'priority' => '0.7', 'changefreq' => 'weekly'],
                ['url' => route('cart.index'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ];

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($staticPages as $page) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . e($page['url']) . "</loc>\n";
                $xml .= "    <priority>{$page['priority']}</priority>\n";
                $xml .= "    <changefreq>{$page['changefreq']}</changefreq>\n";
                $xml .= "  </url>\n";
            }

            foreach ($products as $product) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . e(route('products.show', $product->slug)) . "</loc>\n";
                $xml .= "    <lastmod>" . $product->updated_at->toW3cString() . "</lastmod>\n";
                $xml .= "    <priority>0.8</priority>\n";
                $xml .= "    <changefreq>weekly</changefreq>\n";
                $xml .= "  </url>\n";
            }

            foreach ($categories as $category) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . e(route('categories.show', $category->slug)) . "</loc>\n";
                $xml .= "    <lastmod>" . $category->updated_at->toW3cString() . "</lastmod>\n";
                $xml .= "    <priority>0.7</priority>\n";
                $xml .= "    <changefreq>weekly</changefreq>\n";
                $xml .= "  </url>\n";
            }

            foreach ($cmsPages as $page) {
                $xml .= "  <url>\n";
                $xml .= "    <loc>" . e(url('page/' . $page->slug)) . "</loc>\n";
                $xml .= "    <lastmod>" . $page->updated_at->toW3cString() . "</lastmod>\n";
                $xml .= "    <priority>0.6</priority>\n";
                $xml .= "    <changefreq>monthly</changefreq>\n";
                $xml .= "  </url>\n";
            }

            $xml .= '</urlset>';

            return $xml;
        });

        return response($sitemap, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
