<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyProfileController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $company = app('currentCompany');

        return response()->json([
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'email' => $company->email,
                'phone' => $company->phone,
                'website' => $company->website,
                'currency' => $company->currency,
                'is_active' => (bool) $company->is_active,
            ],
        ]);
    }
}
