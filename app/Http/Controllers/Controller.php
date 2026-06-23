<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: "Almohit Hotels API",
    version: "1.0.0",
    description: "RESTful API for Almohit Hotels platform. Provides hotel listings, room types, bookings, reviews, services, and image management with role-based access control."
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local development"
)]
#[OA\SecurityScheme(
    securityScheme: "BearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Token returned from /api/auth/login/ as access field."
)]
#[OA\Get(
    path: "/api/health/",
    summary: "Health check",
    responses: [
        new OA\Response(response: 200, description: "OK")
    ]
)]
abstract class Controller
{
    //
}
