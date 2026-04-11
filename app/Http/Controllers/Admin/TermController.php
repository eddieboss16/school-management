<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Term;

class TermController extends Controller
{
    public function index()
    {
        $terms = Term::orderBy('start_date', 'desc')->get();
        return view('admin.terms', compact('terms'));
    }

    public function create()
    {
        return view('admin.terms-create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'is_active'  => ['sometimes', 'boolean'],
        ]);

        // Only one active term at a time
        if ($request->boolean('is_active')) {
            Term::where('is_active', true)->update(['is_active' => false]);
        }

        Term::create([
            'name'       => $request->name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.terms')->with('success', 'Term created successfully!');
    }

    public function edit($id)
    {
        $term = Term::findOrFail($id);
        return view('admin.terms-edit', compact('term'));
    }

    public function update(Request $request, $id)
    {
        $term = Term::findOrFail($id);

        $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after:start_date'],
            'is_active'  => ['sometimes', 'boolean'],
        ]);

        // Only one active term at a time
        if ($request->boolean('is_active') && !$term->is_active) {
            Term::where('is_active', true)->update(['is_active' => false]);
        }

        $term->update([
            'name'       => $request->name,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
            'is_active'  => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.terms')->with('success', 'Term updated successfully!');
    }

    public function destroy($id)
    {
        $term = Term::findOrFail($id);

        if ($term->is_active) {
            return redirect()->route('admin.terms')->with('error', 'Cannot delete the active term. Set another term as active first.');
        }

        $term->delete();
        return redirect()->route('admin.terms')->with('success', 'Term deleted successfully!');
    }
}
