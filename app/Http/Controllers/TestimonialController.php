<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreTestimonialRequest;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;

class TestimonialController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->to(route('home').'#testimoni');
    }

    public function store(StoreTestimonialRequest $request): RedirectResponse
    {
        Testimonial::create([...$request->validated(), 'name' => $request->user()->name, 'is_visible' => false]);

        return redirect()->to(route('home').'#testimoni')->with('testimonial_success', 'Terima kasih! Testimoni kamu tersimpan dan akan ditinjau admin sebelum ditampilkan.');
    }
}
