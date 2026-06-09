<?php

namespace App\Http\Controllers;

use App\Models\trusted_website;
use App\Models\TrustedWebsite;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrustedWebsiteController extends Controller
{
    public function index()
    {
        $trustedWebsites = trusted_website::latest()->paginate(10);

        return view('admin.trusted.index', [
            'menu' => 'trusted',
            'title' => 'Trusted Websites',
            'trustedWebsites' => $trustedWebsites,
        ]);
    }

    public function create()
    {
        return view('admin.trusted.create');
    }

    public function store(Request $request)
    {
        $messages = [
            'name.required'        => 'Please enter the website name.',
            'name.max'             => 'Website name may not be greater than :max characters.',
            'url.required'         => 'Please enter the website URL.',
            'url.url'              => 'Please enter a valid URL.',
            'url.max'              => 'Website URL may not be greater than :max characters.',
            'description.string'   => 'Description must be text.',
            'is_active.required'   => 'Please specify if the website is active.',
            'is_active.boolean'    => 'Is active must be true or false.',
        ];
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'url'         => ['required', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['required', 'boolean'],
        ], $messages);

        trusted_website::create($validated);

        return redirect()
            ->route('trusted.index')
            ->with('success', 'Trusted website created successfully.');
    }

    public function show(trusted_website $trusted)
    {
        return view('admin.trusted.show', [
            'menu'    => 'trusted',
            'title'   => 'Trusted Website Detail',
            'trusted' => $trusted,
        ]);
    }

    public function edit(trusted_website $trusted)
    {
        return view('admin.trusted.edit', [
            'trusted' => $trusted,
        ]);
    }

    public function update(Request $request, trusted_website $trusted)
    {
        $messages = [
            'name.required'        => 'Please enter the website name.',
            'name.max'             => 'Website name may not be greater than :max characters.',
            'url.required'         => 'Please enter the website URL.',
            'url.url'              => 'Please enter a valid URL.',
            'url.max'              => 'Website URL may not be greater than :max characters.',
            'description.string'   => 'Description must be text.',
            'is_active.required'   => 'Please specify if the website is active.',
            'is_active.boolean'    => 'Is active must be true or false.',
        ];
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'url'         => ['required', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['required', 'boolean'],
        ], $messages);

        $trusted->update($validated);

        return redirect()
            ->route('trusted.index')
            ->with('success', 'Trusted website updated successfully.');
    }

    public function destroy(trusted_website $trusted)
    {
        $trusted->delete();

        return redirect()
            ->route('trusted.index')
            ->with('success', 'Trusted website deleted successfully.');
    }
}
