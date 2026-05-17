<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(title: "Event-Driven Notification System API", version: "1.0.0", description: "API documentation for the Insider One notification system")]
#[OA\Server(url: L5_SWAGGER_CONST_HOST, description: "API Server")]
abstract class Controller
{
    //
}
