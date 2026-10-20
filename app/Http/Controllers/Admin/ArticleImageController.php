<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ArticleImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ArticleImageController extends Controller
{
    public function store(ArticleImageRequest $request): JsonResponse
    {
        $path = $request->file('image')->store('articles/content', 'public');
        abort_if($path === false, 500, 'Gambar gagal disimpan. Silakan coba lagi.');

        return response()->json(['url' => Storage::disk('public')->url($path)], 201);
    }
}
