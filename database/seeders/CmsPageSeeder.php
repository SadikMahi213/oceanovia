<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title'            => 'About Us',
                'slug'             => 'about-us',
                'content'          => '<p>Oceanovia.com is the premier American marketplace connecting customers with authentic sellers and suppliers nationwide. We are committed to quality, transparency, and supporting local businesses.</p><p>Whether you are a customer looking for great products or a seller ready to grow your business, MulitVendor gives you the tools you need to succeed.</p>',
                'meta_title'       => 'About Us',
                'meta_description' => 'Learn more about Oceanovia.com, the premier American multi-vendor marketplace.',
                'status'           => 'published',
            ],
            [
                'title'            => 'Privacy Policy',
                'slug'             => 'privacy-policy',
                'content'          => '<p>This Privacy Policy explains how Oceanovia.com collects, uses, and protects your personal information when you use our platform.</p><h2>Information We Collect</h2><ul><li>Account details such as name, email address, and contact information.</li><li>Order and transaction history.</li><li>Technical data such as IP address and browser information.</li></ul><h2>How We Use Your Information</h2><ul><li>To provide and improve our services.</li><li>To process orders, payments, and deliveries.</li><li>To communicate with you about your account and orders.</li></ul><h2>Data Protection</h2><p>We implement reasonable security measures to protect your information. We do not sell your personal data to third parties.</p>',
                'meta_title'       => 'Privacy Policy',
                'meta_description' => 'Read the Oceanovia.com privacy policy to understand how we collect, use, and protect your data.',
                'status'           => 'published',
            ],
            [
                'title'            => 'Terms of Service',
                'slug'             => 'terms-of-service',
                'content'          => '<p>These Terms of Service govern your use of the Oceanovia.com marketplace. By creating an account or using our platform, you agree to these terms.</p><h2>Your Account</h2><p>You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account.</p><h2>Orders and Payments</h2><p>Prices are listed in US Dollars. Orders are subject to payment confirmation before fulfillment.</p><h2>Returns and Refunds</h2><p>Return eligibility is determined by the seller and communicated at the time of purchase in accordance with applicable law.</p><h2>Prohibited Conduct</h2><p>You agree not to misuse the platform, attempt unauthorized access, or engage in fraudulent activity.</p>',
                'meta_title'       => 'Terms of Service',
                'meta_description' => 'Review the Oceanovia.com terms of service governing use of our marketplace.',
                'status'           => 'published',
            ],
            [
                'title'            => 'Help Center',
                'slug'             => 'help-center',
                'content'          => '<p>Welcome to the Oceanovia.com Help Center. Find quick answers about shopping, selling, and managing your account.</p><h2>Shopping</h2><p>Browse categories, use search, and filter by price or seller to find the right product. Track your orders from your account dashboard.</p><h2>Selling</h2><p>Create a seller account, list products, manage inventory, and track payouts from your seller dashboard.</p><h2>Returns</h2><p>Submit a refund request from your order details page if an item qualifies for return.</p><p>Still need help? <a href="/contact">Contact our support team</a>.</p>',
                'meta_title'       => 'Help Center',
                'meta_description' => 'Get help with shopping, selling, orders, returns, and more on Oceanovia.com.',
                'status'           => 'published',
            ],
        ];

        foreach ($pages as $page) {
            CmsPage::firstOrCreate(['slug' => $page['slug']], $page);
        }

        $this->command->info('CMS pages ready: ' . count($pages));
    }
}
