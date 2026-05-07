<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ApiDocumentationController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $openApi = json_decode(
            (string) file_get_contents(base_path('openapi/erp-api.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        return response()->json($openApi);
    }
}
