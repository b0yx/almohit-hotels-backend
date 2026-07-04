<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqSeoTest extends TestCase
{
    use RefreshDatabase;

    private function authHeader(): array
    {
        $admin = User::query()->create([
            'email' => 'admin-faq-seo@example.com',
            'full_name' => 'Admin FAQ SEO',
            'role' => User::ROLE_ADMIN,
            'is_staff' => true,
            'is_superuser' => true,
            'is_active' => true,
            'email_verified' => true,
            'password' => 'password123',
        ]);

        return [
            'Authorization' => 'Bearer '.$admin->createToken('test')->plainTextToken,
            'Accept' => 'application/json',
        ];
    }

    public function test_property_faqs_store_seo_fields_and_return_faq_schema(): void
    {
        $response = $this->postJson('/api/properties/', [
            'name' => 'FAQ SEO Hotel',
            'slug' => 'faq-seo-hotel',
            'subdomain' => 'faq-seo',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Test Address',
            'stars' => 5,
            'description' => 'A hotel with SEO FAQs.',
            'faqs' => [
                [
                    'question' => 'Does FAQ schema help hotel SEO?',
                    'answer' => '<p>Yes, it can qualify the page for FAQ rich results.</p>',
                    'question_ar' => 'هل FAQ Schema يساعد SEO للفندق؟',
                    'answer_ar' => 'نعم، يساعد الصفحة على فهم الأسئلة والأجوبة.',
                    'slug' => 'faq-schema-hotel-seo',
                    'meta_title' => 'FAQ schema for hotel SEO',
                    'meta_description' => 'Learn how FAQ schema supports hotel SEO pages.',
                    'canonical_url' => 'https://example.com/hotels/faq-seo-hotel/faqs/faq-schema-hotel-seo',
                    'sort_order' => 1,
                    'is_active' => true,
                ],
            ],
        ], $this->authHeader());

        $response->assertCreated()
            ->assertJsonPath('faqs.0.slug', 'faq-schema-hotel-seo')
            ->assertJsonPath('faqs.0.question_ar', 'هل FAQ Schema يساعد SEO للفندق؟')
            ->assertJsonPath('faq_schema.@type', 'FAQPage')
            ->assertJsonPath('faq_schema.mainEntity.0.@type', 'Question')
            ->assertJsonPath('faq_schema.mainEntity.0.acceptedAnswer.text', 'Yes, it can qualify the page for FAQ rich results.');

        $this->assertDatabaseHas('faqs', [
            'question' => 'Does FAQ schema help hotel SEO?',
            'slug' => 'faq-schema-hotel-seo',
            'meta_title' => 'FAQ schema for hotel SEO',
        ]);
    }

    public function test_single_faq_endpoint_returns_seo_payload_and_hides_inactive_public_faqs(): void
    {
        $hotel = Hotel::query()->create([
            'name' => 'Public FAQ Hotel',
            'slug' => 'public-faq-hotel',
            'subdomain' => 'public-faq',
            'property_type' => 'hotel',
            'country' => 'UAE',
            'city' => 'Dubai',
            'address' => 'Test Address',
            'stars' => 4,
            'short_description' => 'Public FAQ test.',
            'publishing_status' => 'published',
            'is_active' => true,
        ]);

        $activeFaq = $hotel->faqs()->create([
            'question' => 'Can I book airport transfer?',
            'answer' => 'Yes, airport transfer can be arranged before arrival.',
            'slug' => 'airport-transfer',
            'meta_title' => 'Airport transfer FAQ',
            'meta_description' => 'Airport transfer booking information.',
            'is_active' => true,
        ]);

        $inactiveFaq = $hotel->faqs()->create([
            'question' => 'Hidden question',
            'answer' => 'Hidden answer',
            'slug' => 'hidden-question',
            'is_active' => false,
        ]);

        $this->getJson('/api/properties/'.$hotel->id.'/faqs/'.$activeFaq->slug.'/')
            ->assertOk()
            ->assertJsonPath('faq.id', $activeFaq->id)
            ->assertJsonPath('seo.title', 'Airport transfer FAQ')
            ->assertJsonPath('faq_schema.@type', 'FAQPage');

        $this->getJson('/api/properties/'.$hotel->id.'/faqs/'.$inactiveFaq->slug.'/')
            ->assertNotFound();
    }
}
