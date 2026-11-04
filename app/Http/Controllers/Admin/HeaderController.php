<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Header;
use App\Models\HeaderFeature;
use Illuminate\Http\Request;

class HeaderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $headers = Header::with('features')->latest()->get();

        return view('admin.header.index', compact('headers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tagline' => 'required|string',
            'status' => 'required|in:active,inactive',
            'features' => 'required|array|min:1',
            'features.*.feature_heading' => 'required|string',
        ]);

        $header = Header::create([
            'tagline' => $request->tagline,
            'status' => $request->status
        ]);

        foreach ($request->features as $index => $feature) {
            HeaderFeature::create([
                'header_id' => $header->id,
                'feature_heading' => $feature['feature_heading'],
                'order' => $index
            ]);
        }

        return redirect()->route('admin.header.index')
            ->with('success', 'Header created successfully.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Header $header)
    {
        $request->validate([
            'tagline' => 'required|string',
            'status' => 'required|in:active,inactive',
            'features' => 'required|array|min:1',
            'features.*.feature_heading' => 'required|string',
        ]);

        $header->update([
            'tagline' => $request->tagline,
            'status' => $request->status
        ]);

        $header->features()->delete();

        foreach ($request->features as $index => $feature) {
            HeaderFeature::create([
                'header_id' => $header->id,
                'feature_heading' => $feature['feature_heading'],
                'order' => $index
            ]);
        }

        return redirect()->route('admin.header.index')
            ->with('success', 'Header updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Header $header)
    {
        $header->delete();

        return redirect()->route('admin.header.index')
            ->with('success', 'Header deleted successfully.');
    }
}
