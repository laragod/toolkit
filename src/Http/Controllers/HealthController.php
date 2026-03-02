<?php

declare(strict_types=1);

namespace Laragod\Toolkit\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

final class HealthController extends Controller
{
    public function ping(): JsonResponse
    {
        return response()->json(['message' => 'pong']);
    }

    public function healthCheck(): Response
    {
        return response('OK', 200);
    }

    public function status(): JsonResponse
    {
        return response()->json(['status' => 'available']);
    }
}
