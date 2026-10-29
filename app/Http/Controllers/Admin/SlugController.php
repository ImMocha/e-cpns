<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\UniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SlugController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'resource' => ['required', Rule::in(UniqueSlug::RESOURCES)],
            'value' => ['required', 'string', 'max:200'],
            'ignore_id' => ['nullable', 'integer', 'min:1'],
        ]);

        return response()->json(['slug' => UniqueSlug::resolve($data['resource'], $data['value'],
            isset($data['ignore_id']) ? (int) $data['ignore_id'] : null)])->header('Cache-Control', 'no-store');
    }
}
