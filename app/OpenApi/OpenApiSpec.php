<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class OpenApiSpec
{
    // ─── Schema: ErrorResponse ────────────────────────────────
    #[OA\Schema(
        schema: 'ErrorResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'detail', type: 'string', nullable: false),
            new OA\Property(property: 'code', type: 'string', nullable: false),
        ]
    )]
    private $ErrorResponse;

    // ─── Schema: ValidationError ────────────────────────────────
    #[OA\Schema(
        schema: 'ValidationError',
        type: 'object',
        properties: [
            new OA\Property(property: 'field_name', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
        ]
    )]
    private $ValidationError;

    // ─── Schema: PaginatedResponse ────────────────────────────────
    #[OA\Schema(
        schema: 'PaginatedResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'count', type: 'integer', nullable: false, description: 'Total results'),
            new OA\Property(property: 'next', type: 'string', nullable: true, description: 'Next page URL'),
            new OA\Property(property: 'previous', type: 'string', nullable: true, description: 'Previous page URL'),
            new OA\Property(property: 'results', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
        ]
    )]
    private $PaginatedResponse;

    // ─── Schema: User ────────────────────────────────
    #[OA\Schema(
        schema: 'User',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'email', type: 'string', nullable: false),
            new OA\Property(property: 'full_name', type: 'string', nullable: false),
            new OA\Property(property: 'phone', type: 'string', nullable: false),
            new OA\Property(property: 'role', type: 'string', nullable: false),
            new OA\Property(property: 'is_staff', type: 'boolean', nullable: false),
            new OA\Property(property: 'is_active', type: 'boolean', nullable: false),
            new OA\Property(property: 'email_verified', type: 'boolean', nullable: false),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
        ]
    )]
    private $User;

    // ─── Schema: LoginResponse ────────────────────────────────
    #[OA\Schema(
        schema: 'LoginResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'token', type: 'string', nullable: false, description: 'API token (same as access)'),
            new OA\Property(property: 'access', type: 'string', nullable: false, description: 'Bearer token'),
            new OA\Property(property: 'token_type', type: 'string', nullable: false),
            new OA\Property(property: 'role', type: 'string', nullable: false),
            new OA\Property(property: 'redirect_url', type: 'string', nullable: false),
            new OA\Property(property: 'user', type: 'string', nullable: false),
        ]
    )]
    private $LoginResponse;

    // ─── Schema: Hotel ────────────────────────────────
    #[OA\Schema(
        schema: 'Hotel',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'name', type: 'string', nullable: false),
            new OA\Property(property: 'name_ar', type: 'string', nullable: true),
            new OA\Property(property: 'slug', type: 'string', nullable: false),
            new OA\Property(property: 'subdomain', type: 'string', nullable: false),
            new OA\Property(property: 'property_type', type: 'string', nullable: false, default: 'hotel'),
            new OA\Property(property: 'country', type: 'string', nullable: false),
            new OA\Property(property: 'city', type: 'string', nullable: false),
            new OA\Property(property: 'address', type: 'string', nullable: false),
            new OA\Property(property: 'phone', type: 'string', nullable: false),
            new OA\Property(property: 'email', type: 'string', nullable: false),
            new OA\Property(property: 'website', type: 'string', nullable: false),
            new OA\Property(property: 'stars', type: 'integer', nullable: false),
            new OA\Property(property: 'description', type: 'string', nullable: false),
            new OA\Property(property: 'description_ar', type: 'string', nullable: true),
            new OA\Property(property: 'short_description', type: 'string', nullable: false),
            new OA\Property(property: 'short_description_ar', type: 'string', nullable: true),
            new OA\Property(property: 'meta_title', type: 'string', maxLength: 60, nullable: true),
            new OA\Property(property: 'meta_description', type: 'string', maxLength: 160, nullable: true),
            new OA\Property(property: 'meta_title_ar', type: 'string', maxLength: 60, nullable: true),
            new OA\Property(property: 'meta_description_ar', type: 'string', maxLength: 160, nullable: true),
            new OA\Property(property: 'timezone', type: 'string', nullable: false, default: 'UTC'),
            new OA\Property(property: 'languages_spoken', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
            new OA\Property(property: 'parking_available', type: 'boolean', nullable: false),
            new OA\Property(property: 'airport_transfer', type: 'boolean', nullable: false),
            new OA\Property(property: 'shuttle_service', type: 'boolean', nullable: false),
            new OA\Property(property: 'opening_year', type: 'integer', nullable: true),
            new OA\Property(property: 'renovation_year', type: 'integer', nullable: true),
            new OA\Property(property: 'video_url', type: 'string', nullable: false),
            new OA\Property(property: 'virtual_tour_url', type: 'string', nullable: false),
            new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
            new OA\Property(property: 'average_rating', type: 'number', nullable: true),
            new OA\Property(property: 'total_reviews', type: 'integer', nullable: false),
            new OA\Property(property: 'images', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
            new OA\Property(property: 'policy', ref: '#/components/schemas/HotelPolicy', nullable: true),
            new OA\Property(property: 'social_media', type: 'object', nullable: true),
            new OA\Property(property: 'contacts', type: 'object', nullable: true),
            new OA\Property(property: 'setup_status', type: 'object', nullable: true),
            new OA\Property(property: 'is_active', type: 'boolean', nullable: false),
            new OA\Property(property: 'publishing_status', type: 'string', nullable: false),
            new OA\Property(property: 'published_at', type: 'string', nullable: true),
            new OA\Property(property: 'is_favorite', type: 'boolean', nullable: false, default: false),
            new OA\Property(property: 'owner', type: 'integer', nullable: true),
            new OA\Property(property: 'readiness_errors', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
            new OA\Property(property: 'is_ready_to_publish', type: 'boolean', nullable: false),
            new OA\Property(property: 'latitude', type: 'number', nullable: true),
            new OA\Property(property: 'longitude', type: 'number', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
            new OA\Property(property: 'updated_at', type: 'string', nullable: false),
        ]
    )]
    private $Hotel;

    // Arabic content fields are plain content fields. Localized slugs, canonical URLs, and locale fallback are future phases.

    // ─── Schema: BlogPost ────────────────────────────────
    #[OA\Schema(
        schema: 'BlogPost',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'title', type: 'string', nullable: false),
            new OA\Property(property: 'slug', type: 'string', nullable: false),
            new OA\Property(property: 'excerpt', type: 'string', nullable: false),
            new OA\Property(property: 'content', type: 'string', nullable: false),
            new OA\Property(property: 'featured_image', type: 'string', nullable: false),
            new OA\Property(property: 'featured_image_alt', type: 'string', nullable: false),
            new OA\Property(property: 'meta_title', type: 'string', maxLength: 60, nullable: true),
            new OA\Property(property: 'meta_description', type: 'string', maxLength: 160, nullable: true),
            new OA\Property(property: 'meta_title_ar', type: 'string', maxLength: 60, nullable: true),
            new OA\Property(property: 'meta_description_ar', type: 'string', maxLength: 160, nullable: true),
            new OA\Property(property: 'status', type: 'string', nullable: false),
            new OA\Property(property: 'published_at', type: 'string', nullable: true),
            new OA\Property(property: 'locale', type: 'string', nullable: false),
            new OA\Property(property: 'author', type: 'object', nullable: true),
            new OA\Property(property: 'category', type: 'object', nullable: true),
            new OA\Property(property: 'hotel', ref: '#/components/schemas/Hotel', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
            new OA\Property(property: 'updated_at', type: 'string', nullable: false),
        ]
    )]
    private $BlogPost;

    // ─── Schema: HotelPolicy ────────────────────────────────
    #[OA\Schema(
        schema: 'HotelPolicy',
        type: 'object',
        properties: [
            new OA\Property(property: 'cancellation_policy', type: 'string', nullable: true),
            new OA\Property(property: 'cancellation_policy_ar', type: 'string', nullable: true),
            new OA\Property(property: 'children_policy', type: 'string', nullable: true),
            new OA\Property(property: 'children_policy_ar', type: 'string', nullable: true),
            new OA\Property(property: 'pet_policy', type: 'string', nullable: true),
            new OA\Property(property: 'pet_policy_ar', type: 'string', nullable: true),
            new OA\Property(property: 'smoking_policy', type: 'string', nullable: true),
            new OA\Property(property: 'smoking_policy_ar', type: 'string', nullable: true),
            new OA\Property(property: 'extra_bed_policy', type: 'string', nullable: true),
            new OA\Property(property: 'extra_bed_policy_ar', type: 'string', nullable: true),
            new OA\Property(property: 'age_restriction', type: 'string', nullable: true),
            new OA\Property(property: 'age_restriction_ar', type: 'string', nullable: true),
            new OA\Property(property: 'accepted_payment_methods', type: 'string', nullable: true),
            new OA\Property(property: 'accepted_payment_methods_ar', type: 'string', nullable: true),
            new OA\Property(property: 'check_in_from', type: 'string', nullable: true, description: 'Earliest allowed check-in time.'),
            new OA\Property(property: 'check_in_to', type: 'string', nullable: true, description: 'Latest allowed check-in time.'),
            new OA\Property(property: 'check_out_from', type: 'string', nullable: true, description: 'Earliest allowed check-out time.'),
            new OA\Property(property: 'check_out_to', type: 'string', nullable: true, description: 'Latest allowed check-out time.'),
        ]
    )]
    private $HotelPolicy;

    // ─── Schema: RoomType ────────────────────────────────
    #[OA\Schema(
        schema: 'RoomType',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'property', type: 'integer', nullable: false, description: 'Hotel ID'),
            new OA\Property(property: 'name', type: 'string', nullable: false),
            new OA\Property(property: 'name_ar', type: 'string', nullable: true),
            new OA\Property(property: 'description', type: 'string', nullable: false),
            new OA\Property(property: 'description_ar', type: 'string', nullable: true),
            new OA\Property(property: 'room_size', type: 'number', nullable: true),
            new OA\Property(property: 'bed_type', type: 'string', nullable: false),
            new OA\Property(property: 'smoking_allowed', type: 'boolean', nullable: false),
            new OA\Property(property: 'max_adults', type: 'integer', nullable: false),
            new OA\Property(property: 'max_children', type: 'integer', nullable: false),
            new OA\Property(property: 'total_units', type: 'integer', nullable: false),
            new OA\Property(property: 'base_price', type: 'string', nullable: false, description: 'Decimal as string'),
            new OA\Property(property: 'weekend_price', type: 'string', nullable: true, description: 'Decimal as string'),
            new OA\Property(property: 'pricing_mode', type: 'string', nullable: false, default: 'per_night'),
            new OA\Property(property: 'currency', type: 'string', nullable: false, default: 'USD'),
            new OA\Property(property: 'extra_bed_allowed', type: 'boolean', nullable: false),
            new OA\Property(property: 'extra_bed_price', type: 'string', nullable: false, description: 'Decimal as string'),
            new OA\Property(property: 'breakfast_included', type: 'boolean', nullable: false),
            new OA\Property(property: 'is_active', type: 'boolean', nullable: false),
            new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
            new OA\Property(property: 'images', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
            new OA\Property(property: 'prices', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
            new OA\Property(property: 'updated_at', type: 'string', nullable: false),
        ]
    )]
    private $RoomType;

    // ─── Schema: BookingInquiry ────────────────────────────────
    #[OA\Schema(
        schema: 'BookingInquiry',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'customer_name', type: 'string', nullable: false),
            new OA\Property(property: 'phone', type: 'string', nullable: false),
            new OA\Property(property: 'email', type: 'string', nullable: false),
            new OA\Property(property: 'customer', type: 'integer', nullable: true, description: 'User ID'),
            new OA\Property(property: 'property', type: 'integer', nullable: false, description: 'Hotel ID'),
            new OA\Property(property: 'property_name', type: 'string', nullable: false),
            new OA\Property(property: 'room_type', type: 'integer', nullable: false, description: 'RoomType ID'),
            new OA\Property(property: 'room_type_name', type: 'string', nullable: false),
            new OA\Property(property: 'check_in', type: 'string', nullable: false),
            new OA\Property(property: 'check_out', type: 'string', nullable: false),
            new OA\Property(property: 'nights', type: 'integer', nullable: false),
            new OA\Property(property: 'adults', type: 'integer', nullable: false),
            new OA\Property(property: 'children', type: 'integer', nullable: false),
            new OA\Property(property: 'infants', type: 'integer', nullable: false),
            new OA\Property(property: 'extra_bed_needed', type: 'boolean', nullable: false),
            new OA\Property(property: 'extra_bed_count', type: 'integer', nullable: false),
            new OA\Property(property: 'estimated_total', type: 'string', nullable: false, description: 'Decimal as string'),
            new OA\Property(property: 'status', type: 'string', nullable: false),
            new OA\Property(property: 'guests', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
            new OA\Property(property: 'available_units_after_booking', type: 'integer', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
            new OA\Property(property: 'updated_at', type: 'string', nullable: false),
        ]
    )]
    private $BookingInquiry;

    // ─── Schema: Review ────────────────────────────────
    #[OA\Schema(
        schema: 'Review',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'property', type: 'integer', nullable: false, description: 'Hotel ID'),
            new OA\Property(property: 'guest_name', type: 'string', nullable: false),
            new OA\Property(property: 'guest_email', type: 'string', nullable: false),
            new OA\Property(property: 'user_id', type: 'integer', nullable: true),
            new OA\Property(property: 'rating', type: 'integer', nullable: false),
            new OA\Property(property: 'title', type: 'string', nullable: false),
            new OA\Property(property: 'comment', type: 'string', nullable: false),
            new OA\Property(property: 'cleanliness', type: 'integer', nullable: true),
            new OA\Property(property: 'location', type: 'integer', nullable: true),
            new OA\Property(property: 'staff', type: 'integer', nullable: true),
            new OA\Property(property: 'comfort', type: 'integer', nullable: true),
            new OA\Property(property: 'value_for_money', type: 'integer', nullable: true),
            new OA\Property(property: 'is_active', type: 'boolean', nullable: false),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
        ]
    )]
    private $Review;

    // ─── Schema: ReviewSummary ────────────────────────────────
    #[OA\Schema(
        schema: 'ReviewSummary',
        type: 'object',
        properties: [
            new OA\Property(property: 'average_rating', type: 'number', nullable: true),
            new OA\Property(property: 'total_reviews', type: 'integer', nullable: false),
            new OA\Property(property: 'rating_breakdown', type: 'object', nullable: false),
            new OA\Property(property: 'category_averages', type: 'object', nullable: false),
        ]
    )]
    private $ReviewSummary;

    // ─── Schema: Favorite ────────────────────────────────
    #[OA\Schema(
        schema: 'Favorite',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'user_id', type: 'integer', nullable: false),
            new OA\Property(property: 'hotel_id', type: 'integer', nullable: false),
            new OA\Property(property: 'hotel', ref: '#/components/schemas/Hotel', nullable: true),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
        ]
    )]
    private $Favorite;

    // ─── Schema: ServiceCategory ────────────────────────────────
    #[OA\Schema(
        schema: 'ServiceCategory',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'name', type: 'string', nullable: false),
            new OA\Property(property: 'slug', type: 'string', nullable: false),
            new OA\Property(property: 'description', type: 'string', nullable: false),
            new OA\Property(property: 'icon', type: 'string', nullable: false),
            new OA\Property(property: 'is_active', type: 'boolean', nullable: false),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
            new OA\Property(property: 'updated_at', type: 'string', nullable: false),
        ]
    )]
    private $ServiceCategory;

    // ─── Schema: HotelService ────────────────────────────────
    #[OA\Schema(
        schema: 'HotelService',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'property', type: 'integer', nullable: false, description: 'Hotel ID'),
            new OA\Property(property: 'property_name', type: 'string', nullable: false),
            new OA\Property(property: 'category', type: 'integer', nullable: true, description: 'ServiceCategory ID'),
            new OA\Property(property: 'category_name', type: 'string', nullable: false),
            new OA\Property(property: 'name', type: 'string', nullable: false),
            new OA\Property(property: 'name_ar', type: 'string', nullable: true),
            new OA\Property(property: 'slug', type: 'string', nullable: false),
            new OA\Property(property: 'short_description', type: 'string', nullable: false),
            new OA\Property(property: 'short_description_ar', type: 'string', nullable: true),
            new OA\Property(property: 'description', type: 'string', nullable: false),
            new OA\Property(property: 'description_ar', type: 'string', nullable: true),
            new OA\Property(property: 'price', type: 'string', nullable: false, description: 'Decimal as string'),
            new OA\Property(property: 'currency', type: 'string', nullable: false),
            new OA\Property(property: 'pricing_type', type: 'string', nullable: false),
            new OA\Property(property: 'duration_minutes', type: 'integer', nullable: true),
            new OA\Property(property: 'available_from', type: 'string', nullable: true),
            new OA\Property(property: 'available_until', type: 'string', nullable: true),
            new OA\Property(property: 'advance_booking_required', type: 'boolean', nullable: false),
            new OA\Property(property: 'is_featured', type: 'boolean', nullable: false),
            new OA\Property(property: 'is_active', type: 'boolean', nullable: false),
            new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
            new OA\Property(property: 'images', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
            new OA\Property(property: 'updated_at', type: 'string', nullable: false),
        ]
    )]
    private $HotelService;

    // ─── Schema: PropertyImage ────────────────────────────────
    #[OA\Schema(
        schema: 'PropertyImage',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', nullable: false),
            new OA\Property(property: 'property', type: 'integer', nullable: false, description: 'Hotel ID'),
            new OA\Property(property: 'image', type: 'string', nullable: false, description: 'Image URL path'),
            new OA\Property(property: 'thumbnail', type: 'string', nullable: true),
            new OA\Property(property: 'caption', type: 'string', nullable: false),
            new OA\Property(property: 'alt_text', type: 'string', nullable: false),
            new OA\Property(property: 'display_order', type: 'integer', nullable: false),
            new OA\Property(property: 'is_cover', type: 'boolean', nullable: false),
            new OA\Property(property: 'is_active', type: 'boolean', nullable: false),
            new OA\Property(property: 'image_url', type: 'string', nullable: false),
            new OA\Property(property: 'created_at', type: 'string', nullable: false),
            new OA\Property(property: 'updated_at', type: 'string', nullable: false),
        ]
    )]
    private $PropertyImage;

    // ─── Schema: AvailabilityResponse ────────────────────────────────
    #[OA\Schema(
        schema: 'AvailabilityResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'property_id', type: 'integer', nullable: false),
            new OA\Property(property: 'property', type: 'integer', nullable: false),
            new OA\Property(property: 'property_name', type: 'string', nullable: false),
            new OA\Property(property: 'property_type', type: 'string', nullable: false),
            new OA\Property(property: 'check_in', type: 'string', nullable: false),
            new OA\Property(property: 'check_out', type: 'string', nullable: false),
            new OA\Property(property: 'is_available', type: 'boolean', nullable: false),
            new OA\Property(property: 'available_rooms', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
            new OA\Property(property: 'units', type: 'array', nullable: false, items: new OA\Items(type: 'string')),
        ]
    )]
    private $AvailabilityResponse;

    // ─── Schema: HealthCheck ────────────────────────────────
    #[OA\Schema(
        schema: 'HealthCheck',
        type: 'object',
        properties: [
            new OA\Property(property: 'status', type: 'string', nullable: false),
            new OA\Property(property: 'checks', type: 'object', nullable: false),
            new OA\Property(property: 'timestamp', type: 'string', nullable: false),
            new OA\Property(property: 'app_env', type: 'string', nullable: false),
        ]
    )]
    private $HealthCheck;

    // ─── Endpoint: Get /api/health/ ──────────────────────────
    #[OA\Get(
        path: '/api/health/',
        summary: 'Health check endpoint',
        description: 'Returns database and cache status. Laravel Cloud load balancer target.',
        tags: ['System'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '200',
                description: 'All systems healthy'
            ),
            new OA\Response(
                response: '503',
                description: 'Service degraded'
            ),
        ]
    )]
    public function route_1() {}

    // ─── Endpoint: Get /api/public/hotel-context/ ──────────────────────────
    #[OA\Get(
        path: '/api/public/hotel-context/',
        summary: 'Get resolved public hotel context',
        description: 'Resolves hotel from X-Hotel-Subdomain header or hostname subdomain.',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'X-Hotel-Subdomain', in: 'header', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Hotel context resolved'
            ),
        ]
    )]
    public function route_2() {}

    // ─── Endpoint: Post /api/auth/signup/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/signup/',
        summary: 'Create a new customer account',
        description: '',
        tags: ['Authentication'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '201',
                description: 'Account created'
            ),
            new OA\Response(
                response: '400',
                description: 'Validation error'
            ),
        ]
    )]
    public function route_3() {}

    // ─── Endpoint: Post /api/auth/verify-otp/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/verify-otp/',
        summary: 'Verify OTP code to activate account',
        description: '',
        tags: ['Authentication'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Email verified'
            ),
            new OA\Response(
                response: '400',
                description: 'Invalid code'
            ),
        ]
    )]
    public function route_4() {}

    // ─── Endpoint: Post /api/auth/resend-otp/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/resend-otp/',
        summary: 'Resend OTP verification code',
        description: '',
        tags: ['Authentication'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'New code sent'
            ),
        ]
    )]
    public function route_5() {}

    // ─── Endpoint: Post /api/auth/login/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/login/',
        summary: 'Login with email and password',
        description: '',
        tags: ['Authentication'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Login successful'
            ),
            new OA\Response(
                response: '400',
                description: 'Email not verified'
            ),
            new OA\Response(
                response: '422',
                description: 'Invalid credentials'
            ),
        ]
    )]
    public function route_6() {}

    // ─── Endpoint: Post /api/auth/admin/login/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/admin/login/',
        summary: 'Admin-only login (rejects non-admin users)',
        description: '',
        tags: ['Authentication'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Admin login successful'
            ),
            new OA\Response(
                response: '403',
                description: 'Admin account required'
            ),
        ]
    )]
    public function route_7() {}

    // ─── Endpoint: Post /api/auth/customer/login/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/customer/login/',
        summary: 'Customer-only login (rejects admin accounts)',
        description: '',
        tags: ['Authentication'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Customer login successful'
            ),
            new OA\Response(
                response: '403',
                description: 'Customer account required'
            ),
        ]
    )]
    public function route_8() {}

    // ─── Endpoint: Get /api/auth/me/ ──────────────────────────
    #[OA\Get(
        path: '/api/auth/me/',
        summary: 'Get current authenticated user profile',
        description: '',
        tags: ['Authentication'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User profile'
            ),
            new OA\Response(
                response: '401',
                description: 'Not authenticated'
            ),
        ]
    )]
    public function route_9() {}

    // ─── Endpoint: Patch /api/auth/me/ ──────────────────────────
    #[OA\Patch(
        path: '/api/auth/me/',
        summary: 'Update current user profile',
        description: '',
        tags: ['Authentication'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Updated profile'
            ),
        ]
    )]
    public function route_10() {}

    // ─── Endpoint: Post /api/auth/logout/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/logout/',
        summary: 'Logout and invalidate token',
        description: '',
        tags: ['Authentication'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '204',
                description: 'Token deleted (no content)'
            ),
        ]
    )]
    public function route_11() {}

    // ─── Endpoint: Get /api/auth/users/ ──────────────────────────
    #[OA\Get(
        path: '/api/auth/users/',
        summary: 'List all users (admin only)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page_size', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated user list'
            ),
        ]
    )]
    public function route_12() {}

    // ─── Endpoint: Post /api/auth/users/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/users/',
        summary: 'Create a new user (admin only)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '201',
                description: 'User created'
            ),
        ]
    )]
    public function route_13() {}

    // ─── Endpoint: Get /api/auth/users/{id}/ ──────────────────────────
    #[OA\Get(
        path: '/api/auth/users/{id}/',
        summary: 'Get user detail (admin only)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User detail'
            ),
        ]
    )]
    public function route_14() {}

    // ─── Endpoint: Patch /api/auth/users/{id}/ ──────────────────────────
    #[OA\Patch(
        path: '/api/auth/users/{id}/',
        summary: 'Update user (admin only)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User updated'
            ),
        ]
    )]
    public function route_15() {}

    // ─── Endpoint: Delete /api/auth/users/{id}/ ──────────────────────────
    #[OA\Delete(
        path: '/api/auth/users/{id}/',
        summary: 'Delete user (admin only)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '204',
                description: 'User deleted'
            ),
        ]
    )]
    public function route_16() {}

    // ─── Endpoint: Post /api/auth/users/{id}/activate/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/users/{id}/activate/',
        summary: 'Activate a user account',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User activated'
            ),
        ]
    )]
    public function route_17() {}

    // ─── Endpoint: Post /api/auth/users/{id}/deactivate/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/users/{id}/deactivate/',
        summary: 'Deactivate a user account',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'User deactivated'
            ),
        ]
    )]
    public function route_18() {}

    // ─── Endpoint: Post /api/auth/users/{id}/change-role/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/users/{id}/change-role/',
        summary: 'Change user role',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Successful operation'
            ),
        ]
    )]
    public function route_19() {}

    // ─── Endpoint: Post /api/auth/users/{id}/reset-password/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/users/{id}/reset-password/',
        summary: 'Reset user password',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Successful operation'
            ),
        ]
    )]
    public function route_20() {}

    // ─── Endpoint: Get /api/auth/admins/ ──────────────────────────
    #[OA\Get(
        path: '/api/auth/admins/',
        summary: 'List admin accounts (admin only)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated admin list'
            ),
        ]
    )]
    public function route_21() {}

    // ─── Endpoint: Post /api/auth/admins/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/admins/',
        summary: 'Create admin account (admin only)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '201',
                description: 'Admin created'
            ),
        ]
    )]
    public function route_22() {}

    // ─── Endpoint: Get /api/properties/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/',
        summary: 'List hotels/properties',
        description: 'Guest: Active published hotels.
Staff: Assigned hotels.
Admin: All hotels.
Tenant-scoped: Single hotel if X-Hotel-Subdomain resolves.
',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page_size', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'country', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'city', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'property_type', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'stars', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated hotel list'
            ),
        ]
    )]
    public function route_23() {}

    // ─── Endpoint: Post /api/properties/ ──────────────────────────
    #[OA\Post(
        path: '/api/properties/',
        summary: 'Create a hotel (admin/staff)',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '201',
                description: 'Hotel created'
            ),
        ]
    )]
    public function route_24() {}

    // ─── Endpoint: Get /api/properties/available/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/available/',
        summary: 'List available hotels (alias for index with published scope)',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Available hotels list'
            ),
        ]
    )]
    public function route_25() {}

    // ─── Endpoint: Get /api/properties/{id}/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/{id}/',
        summary: 'Get hotel detail',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Hotel detail'
            ),
        ]
    )]
    public function route_26() {}

    // ─── Endpoint: Patch /api/properties/{id}/ ──────────────────────────
    #[OA\Patch(
        path: '/api/properties/{id}/',
        summary: 'Update hotel (admin/staff assigned)',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Hotel updated'
            ),
        ]
    )]
    public function route_27() {}

    // ─── Endpoint: Delete /api/properties/{id}/ ──────────────────────────
    #[OA\Delete(
        path: '/api/properties/{id}/',
        summary: 'Delete hotel (admin/staff assigned)',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '204',
                description: 'Hotel deleted'
            ),
        ]
    )]
    public function route_28() {}

    // ─── Endpoint: Post /api/properties/{id}/publish/ ──────────────────────────
    #[OA\Post(
        path: '/api/properties/{id}/publish/',
        summary: 'Publish hotel (requires readiness)',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Hotel published'
            ),
        ]
    )]
    public function route_29() {}

    // ─── Endpoint: Post /api/properties/{id}/unpublish/ ──────────────────────────
    #[OA\Post(
        path: '/api/properties/{id}/unpublish/',
        summary: 'Unpublish hotel (set to draft)',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Hotel unpublished'
            ),
        ]
    )]
    public function route_30() {}

    // ─── Endpoint: Post /api/properties/{id}/archive/ ──────────────────────────
    #[OA\Post(
        path: '/api/properties/{id}/archive/',
        summary: 'Archive hotel (inactive)',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Hotel archived'
            ),
        ]
    )]
    public function route_31() {}

    // ─── Endpoint: Post /api/properties/{id}/unarchive/ ──────────────────────────
    #[OA\Post(
        path: '/api/properties/{id}/unarchive/',
        summary: 'Unarchive hotel (active draft)',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Hotel unarchived'
            ),
        ]
    )]
    public function route_32() {}

    // ─── Endpoint: Get /api/properties/{id}/readiness/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/{id}/readiness/',
        summary: 'Check hotel readiness for publishing',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Readiness status'
            ),
        ]
    )]
    public function route_33() {}

    // ─── Endpoint: Get /api/properties/{id}/setup-status/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/{id}/setup-status/',
        summary: 'Get hotel setup wizard completion status',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Setup status'
            ),
        ]
    )]
    public function route_34() {}

    // ─── Endpoint: Patch /api/properties/{id}/autosave/ ──────────────────────────
    #[OA\Patch(
        path: '/api/properties/{id}/autosave/',
        summary: 'Auto-save hotel with setup progress',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Autosaved'
            ),
        ]
    )]
    public function route_35() {}

    // ─── Endpoint: Get /api/properties/{id}/workspace/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/{id}/workspace/',
        summary: 'Get hotel owner workspace data',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Workspace data'
            ),
        ]
    )]
    public function route_36() {}

    // ─── Endpoint: Get /api/properties/{id}/rooms/search/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/{id}/rooms/search/',
        summary: 'Search active room types for a hotel',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Room search results'
            ),
        ]
    )]
    public function route_37() {}

    // ─── Endpoint: Get /api/properties/{id}/availability/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/{id}/availability/',
        summary: 'Check room availability for date range',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'check_in', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'check_out', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Availability data'
            ),
        ]
    )]
    public function route_38() {}

    // ─── Endpoint: Get /api/properties/{id}/rates/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/{id}/rates/',
        summary: 'Get room rates with seasonal prices',
        description: '',
        tags: ['Properties'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Rate data'
            ),
        ]
    )]
    public function route_39() {}

    // ─── Endpoint: Get /api/properties/{property}/reviews/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/{property}/reviews/',
        summary: 'List active reviews for a hotel',
        description: '',
        tags: ['Reviews'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page_size', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated reviews'
            ),
        ]
    )]
    public function route_40() {}

    // ─── Endpoint: Post /api/properties/{property}/reviews/ ──────────────────────────
    #[OA\Post(
        path: '/api/properties/{property}/reviews/',
        summary: 'Submit a public review for a hotel',
        description: '',
        tags: ['Reviews'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '201',
                description: 'Review created'
            ),
        ]
    )]
    public function route_41() {}

    // ─── Endpoint: Get /api/properties/{property}/reviews/summary/ ──────────────────────────
    #[OA\Get(
        path: '/api/properties/{property}/reviews/summary/',
        summary: 'Get review summary with breakdown',
        description: '',
        tags: ['Reviews'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Review summary'
            ),
        ]
    )]
    public function route_42() {}

    // ─── Endpoint: Get /api/bookings/ ──────────────────────────
    #[OA\Get(
        path: '/api/bookings/',
        summary: 'List bookings',
        description: 'Customer: Own bookings.
Staff: Assigned hotel bookings.
Admin: All bookings.
',
        tags: ['Bookings'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page_size', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated bookings'
            ),
        ]
    )]
    public function route_43() {}

    // ─── Endpoint: Post /api/bookings/ ──────────────────────────
    #[OA\Post(
        path: '/api/bookings/',
        summary: 'Create a booking (staff/admin)',
        description: '',
        tags: ['Bookings'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '201',
                description: 'Booking created'
            ),
        ]
    )]
    public function route_44() {}

    // ─── Endpoint: Post /api/bookings/inquiry/ ──────────────────────────
    #[OA\Post(
        path: '/api/bookings/inquiry/',
        summary: 'Public booking inquiry (no auth required)',
        description: '',
        tags: ['Bookings'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '201',
                description: 'Booking inquiry created'
            ),
        ]
    )]
    public function route_45() {}

    // ─── Endpoint: Post /api/bookings/confirm/ ──────────────────────────
    #[OA\Post(
        path: '/api/bookings/confirm/',
        summary: 'Confirm a booking (staff/admin)',
        description: '',
        tags: ['Bookings'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Booking confirmed'
            ),
        ]
    )]
    public function route_46() {}

    // ─── Endpoint: Post /api/bookings/{id}/cancel/ ──────────────────────────
    #[OA\Post(
        path: '/api/bookings/{id}/cancel/',
        summary: 'Cancel a booking',
        description: 'Customer: Own booking.
Staff: Assigned hotel booking.
Admin: Any booking.
',
        tags: ['Bookings'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Booking cancelled'
            ),
        ]
    )]
    public function route_47() {}

    // ─── Endpoint: Get /api/reviews/ ──────────────────────────
    #[OA\Get(
        path: '/api/reviews/',
        summary: 'List all reviews (staff/admin moderation)',
        description: '',
        tags: ['Reviews'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page_size', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated review list'
            ),
        ]
    )]
    public function route_49() {}

    // ─── Endpoint: Patch /api/reviews/ ──────────────────────────
    #[OA\Patch(
        path: '/api/reviews/',
        summary: 'Moderate a review (staff/admin)',
        description: '',
        tags: ['Reviews'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Review updated'
            ),
        ]
    )]
    public function route_50() {}

    // ─── Endpoint: Delete /api/reviews/ ──────────────────────────
    #[OA\Delete(
        path: '/api/reviews/',
        summary: 'Delete a review (staff/admin)',
        description: '',
        tags: ['Reviews'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '204',
                description: 'Review deleted'
            ),
        ]
    )]
    public function route_51() {}

    // ─── Endpoint: Get /api/property-images/ ──────────────────────────
    #[OA\Get(
        path: '/api/property-images/',
        summary: 'List property images',
        description: '',
        tags: ['Images'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'property', in: 'query', required: false, description: 'Filter by hotel ID', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Image list with DRF-style pagination'
            ),
        ]
    )]
    public function route_57() {}

    // ─── Endpoint: Post /api/property-images/ ──────────────────────────
    #[OA\Post(
        path: '/api/property-images/',
        summary: 'Upload a property image',
        description: '',
        tags: ['Images'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'multipart/form-data' => new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '201',
                description: 'Image uploaded'
            ),
        ]
    )]
    public function route_58() {}

    // ─── Endpoint: Get /api/property-images/{id}/ ──────────────────────────
    #[OA\Get(
        path: '/api/property-images/{id}/',
        summary: '',
        description: 'Get image detail',
        tags: ['Images'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Image detail'
            ),
        ]
    )]
    public function route_59() {}

    // ─── Endpoint: Patch /api/property-images/{id}/ ──────────────────────────
    #[OA\Patch(
        path: '/api/property-images/{id}/',
        summary: '',
        description: 'Update image metadata or replace file',
        tags: ['Images'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Image updated'
            ),
        ]
    )]
    public function route_60() {}

    // ─── Endpoint: Delete /api/property-images/{id}/ ──────────────────────────
    #[OA\Delete(
        path: '/api/property-images/{id}/',
        summary: '',
        description: '',
        tags: ['Images'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '204',
                description: 'Image deleted'
            ),
        ]
    )]
    public function route_61() {}

    // ─── Endpoint: Get /api/room-types/ ──────────────────────────
    #[OA\Get(
        path: '/api/room-types/',
        summary: 'List room types',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'property', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page_size', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated room types'
            ),
        ]
    )]
    public function route_62() {}

    // ─── Endpoint: Post /api/room-types/ ──────────────────────────
    #[OA\Post(
        path: '/api/room-types/',
        summary: 'Create a room type (admin/staff)',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '201',
                description: 'Room type created'
            ),
        ]
    )]
    public function route_63() {}

    // ─── Endpoint: Get /api/room-types/{id}/ ──────────────────────────
    #[OA\Get(
        path: '/api/room-types/{id}/',
        summary: '',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Room type detail'
            ),
        ]
    )]
    public function route_64() {}

    // ─── Endpoint: Patch /api/room-types/{id}/ ──────────────────────────
    #[OA\Patch(
        path: '/api/room-types/{id}/',
        summary: '',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Room type updated'
            ),
        ]
    )]
    public function route_65() {}

    // ─── Endpoint: Delete /api/room-types/{id}/ ──────────────────────────
    #[OA\Delete(
        path: '/api/room-types/{id}/',
        summary: '',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '204',
                description: 'Room type deleted'
            ),
        ]
    )]
    public function route_66() {}

    // ─── Endpoint: Get /api/room-types/{id}/rates/ ──────────────────────────
    #[OA\Get(
        path: '/api/room-types/{id}/rates/',
        summary: 'Get seasonal prices for a room type',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Room type rates'
            ),
        ]
    )]
    public function route_67() {}

    // ─── Endpoint: Get /api/room-type-images/ ──────────────────────────
    #[OA\Get(
        path: '/api/room-type-images/',
        summary: '',
        description: '',
        tags: ['Images'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'room_type', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Room type images'
            ),
        ]
    )]
    public function route_68() {}

    // ─── Endpoint: Post /api/room-type-images/ ──────────────────────────
    #[OA\Post(
        path: '/api/room-type-images/',
        summary: '',
        description: 'Upload room type image',
        tags: ['Images'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'multipart/form-data' => new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Successful operation'
            ),
        ]
    )]
    public function route_69() {}

    // ─── Endpoint: Get /api/room-prices/ ──────────────────────────
    #[OA\Get(
        path: '/api/room-prices/',
        summary: 'List seasonal room prices',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'room_type', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated room prices'
            ),
        ]
    )]
    public function route_70() {}

    // ─── Endpoint: Post /api/room-prices/ ──────────────────────────
    #[OA\Post(
        path: '/api/room-prices/',
        summary: '',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '201',
                description: 'Room price created'
            ),
        ]
    )]
    public function route_71() {}

    // ─── Endpoint: Get /api/availability-blocks/ ──────────────────────────
    #[OA\Get(
        path: '/api/availability-blocks/',
        summary: 'List availability blocks (staff/admin)',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'room_type', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'reason', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated availability blocks'
            ),
        ]
    )]
    public function route_73() {}

    // ─── Endpoint: Post /api/availability-blocks/ ──────────────────────────
    #[OA\Post(
        path: '/api/availability-blocks/',
        summary: '',
        description: '',
        tags: ['Rooms'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '201',
                description: 'Availability block created'
            ),
        ]
    )]
    public function route_74() {}

    // ─── Endpoint: Get /api/service-categories/ ──────────────────────────
    #[OA\Get(
        path: '/api/service-categories/',
        summary: 'List service categories',
        description: '',
        tags: ['Services'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated categories'
            ),
        ]
    )]
    public function route_75() {}

    // ─── Endpoint: Post /api/service-categories/ ──────────────────────────
    #[OA\Post(
        path: '/api/service-categories/',
        summary: 'Create a service category (admin/staff)',
        description: '',
        tags: ['Services'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '201',
                description: 'Category created'
            ),
        ]
    )]
    public function route_76() {}

    // ─── Endpoint: Get /api/property-services/ ──────────────────────────
    #[OA\Get(
        path: '/api/property-services/',
        summary: 'List hotel services',
        description: '',
        tags: ['Services'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'property', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'pricing_type', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'currency', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_featured', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'min_price', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'max_price', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated services'
            ),
        ]
    )]
    public function route_77() {}

    // ─── Endpoint: Post /api/property-services/ ──────────────────────────
    #[OA\Post(
        path: '/api/property-services/',
        summary: '',
        description: '',
        tags: ['Services'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '201',
                description: 'Service created'
            ),
        ]
    )]
    public function route_78() {}

    // ─── Endpoint: Get /api/service-images/ ──────────────────────────
    #[OA\Get(
        path: '/api/service-images/',
        summary: '',
        description: '',
        tags: ['Images'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'service', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Service images'
            ),
        ]
    )]
    public function route_79() {}

    // ─── Endpoint: Post /api/service-images/ ──────────────────────────
    #[OA\Post(
        path: '/api/service-images/',
        summary: '',
        description: 'Upload service image',
        tags: ['Images'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'multipart/form-data' => new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Successful operation'
            ),
        ]
    )]
    public function route_80() {}

    // ─── Endpoint: Get /api/audit-logs/ ──────────────────────────
    #[OA\Get(
        path: '/api/audit-logs/',
        summary: 'List audit logs (admin only)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated audit logs'
            ),
        ]
    )]
    public function route_82() {}

    // ─── Endpoint: Get /api/contact-messages/ ──────────────────────────
    #[OA\Get(
        path: '/api/contact-messages/',
        summary: 'List contact messages (admin/staff)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, description: '', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated messages'
            ),
        ]
    )]
    public function route_83() {}

    // ─── Endpoint: Post /api/contact-messages/ ──────────────────────────
    #[OA\Post(
        path: '/api/contact-messages/',
        summary: 'Submit a contact message (public)',
        description: '',
        tags: ['Admin'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'object')
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '201',
                description: 'Message sent'
            ),
        ]
    )]
    public function route_84() {}

    // ─── Schema: ForgotPasswordResponse ────────────────────────────────
    #[OA\Schema(
        schema: 'ForgotPasswordResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'detail', type: 'string', nullable: false),
        ]
    )]
    private $ForgotPasswordResponse;

    // ─── Schema: ResetPasswordResponse ────────────────────────────────
    #[OA\Schema(
        schema: 'ResetPasswordResponse',
        type: 'object',
        properties: [
            new OA\Property(property: 'detail', type: 'string', nullable: false),
            new OA\Property(property: 'code', type: 'string', nullable: false),
        ]
    )]
    private $ResetPasswordResponse;

    // ─── Endpoint: Post /api/auth/forgot-password/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/forgot-password/',
        summary: 'Request password reset OTP',
        description: '',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'email', type: 'string', format: 'email'),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'OTP sent if email exists',
                headers: [],
                content: [
                    'application/json' => new OA\MediaType(
                        mediaType: 'application/json',
                        schema: new OA\Schema(ref: '#/components/schemas/ForgotPasswordResponse')
                    ),
                ]
            ),
        ]
    )]
    public function route_85() {}

    // ─── Endpoint: Post /api/auth/reset-password/ ──────────────────────────
    #[OA\Post(
        path: '/api/auth/reset-password/',
        summary: 'Reset password with OTP',
        description: '',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                'application/json' => new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'email', type: 'string', format: 'email'),
                            new OA\Property(property: 'code', type: 'string', description: '6-digit OTP'),
                            new OA\Property(property: 'password', type: 'string', format: 'password'),
                            new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: '200',
                description: 'Password reset successfully'
            ),
            new OA\Response(
                response: '400',
                description: 'Invalid OTP'
            ),
            new OA\Response(
                response: '429',
                description: 'Too many attempts'
            ),
        ]
    )]
    public function route_86() {}

    // ─── Endpoint: Get /api/favorites/ ──────────────────────────
    #[OA\Get(
        path: '/api/favorites/',
        summary: 'List authenticated user\'s favorite hotels',
        description: '',
        tags: ['Favorites'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page_size', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: '200',
                description: 'Paginated list of favorite hotels'
            ),
            new OA\Response(
                response: '401',
                description: 'Not authenticated'
            ),
        ]
    )]
    public function route_87() {}

    // ─── Endpoint: Post /api/favorites/{hotel}/ ──────────────────────────
    #[OA\Post(
        path: '/api/favorites/{hotel}/',
        summary: 'Add a hotel to favorites',
        description: '',
        tags: ['Favorites'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'hotel', in: 'path', required: true, description: 'Hotel ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: '201',
                description: 'Hotel added to favorites'
            ),
            new OA\Response(
                response: '401',
                description: 'Not authenticated'
            ),
            new OA\Response(
                response: '404',
                description: 'Hotel not found'
            ),
            new OA\Response(
                response: '409',
                description: 'Hotel already in favorites'
            ),
        ]
    )]
    public function route_88() {}

    // ─── Endpoint: Delete /api/favorites/{hotel}/ ──────────────────────────
    #[OA\Delete(
        path: '/api/favorites/{hotel}/',
        summary: 'Remove a hotel from favorites',
        description: '',
        tags: ['Favorites'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'hotel', in: 'path', required: true, description: 'Hotel ID', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: '204',
                description: 'Favorite removed'
            ),
            new OA\Response(
                response: '401',
                description: 'Not authenticated'
            ),
            new OA\Response(
                response: '404',
                description: 'Favorite not found'
            ),
        ]
    )]
    public function route_89() {}
}
