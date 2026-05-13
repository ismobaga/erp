<?php

namespace App\Http\Controllers\Api\V1\Private;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 25)));

        $projects = Project::query()
            ->with('client:id,company_name,contact_name')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($projects);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load(['client:id,company_name,contact_name', 'service:id,name']);

        return response()->json([
            'data' => $project,
        ]);
    }
}
