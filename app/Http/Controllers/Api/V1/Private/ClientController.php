<?php

namespace App\Http\Controllers\Api\V1\Private;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $clients = Client::query()
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        return response()->json($clients);
    }

    public function show(Client $client): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $client->id,
                'type' => $client->type,
                'company_name' => $client->company_name,
                'contact_name' => $client->contact_name,
                'email' => $client->email,
                'phone' => $client->phone,
                'city' => $client->city,
                'country' => $client->country,
                'status' => $client->status,
                'updated_at' => optional($client->updated_at)->toIso8601String(),
            ],
        ]);
    }
}
