<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TestimonialRequest;
use App\Http\Requests\Admin\UpdateTestimonialVisibilityRequest;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TestimonialController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['visible', 'hidden'])],
        ]);
        $filters = ['q' => $request->string('q')->trim()->toString(), 'status' => $request->query('status')];

        return view('Dashboard.Admin.testimonials.index', [
            'testimonials' => Testimonial::query()
                ->when($filters['q'] !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($filters): void {
                    $query->where('name', 'like', '%'.$filters['q'].'%')
                        ->orWhere('institution', 'like', '%'.$filters['q'].'%')
                        ->orWhere('content', 'like', '%'.$filters['q'].'%');
                }))
                ->when($filters['status'] !== null, fn (Builder $query) => $query->where('is_visible', $filters['status'] === 'visible'))
                ->latest('id')->paginate(12)->withQueryString(),
            'filters' => $filters,
            'counts' => [
                'total' => Testimonial::query()->count(),
                'visible' => Testimonial::query()->visible()->count(),
                'hidden' => Testimonial::query()->where('is_visible', false)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('Dashboard.Admin.testimonials.form', ['testimonial' => new Testimonial(['is_visible' => false])]);
    }

    public function store(TestimonialRequest $request): RedirectResponse
    {
        Testimonial::create($request->validated());

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimoni berhasil dibuat.');
    }

    public function edit(Testimonial $testimonial): View
    {
        return view('Dashboard.Admin.testimonials.form', ['testimonial' => $testimonial]);
    }

    public function update(TestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($request->validated());

        return redirect()->route('admin.testimonials.index')->with('success', 'Testimoni berhasil diperbarui.');
    }

    public function toggleStatus(UpdateTestimonialVisibilityRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($request->validated());

        return back()->with('success', $testimonial->is_visible ? 'Testimoni ditampilkan di halaman publik.' : 'Testimoni disembunyikan dari halaman publik.');
    }
}
