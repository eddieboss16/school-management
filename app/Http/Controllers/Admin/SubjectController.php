<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subject;

class SubjectController extends Controller
{
    public function index() {
        $subjects = Subject::orderBy('name')
        ->paginate(15);

        return view('admin.subjects', compact('subjects'));
    }

    public function create() {
        return view('admin.subjects-create');
    }

    public function store(Request $request) {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:subjects'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        Subject::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.subjects')->with('success', 'Subject created successfully!');
    }

    public function edit($id) {
        $subject = Subject::findOrFail($id);

        return view('admin.subjects-edit', compact('subject'));
    }

    public function update(Request $request, $id) {
        $subject = Subject::findOrFail($id);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20', 'unique:subjects,code,' . $id],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $subject->update([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
        ]);

        return redirect()->route('admin.subjects')->with('success', 'Subject updated successfully!');
    }

    public function destroy($id) {
        $subject = Subject::findOrFail($id);
        $subject->delete();

        return redirect()->route('admin.subjects')->with('success', 'Subject deleted successfully!');
    }
}
