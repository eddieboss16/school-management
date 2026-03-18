<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Stream;
use App\Models\Grade;

class StreamController extends Controller
{
    public function index() {
        $streams = Stream::with('grade')
        ->orderBy('grade_id')
        ->paginate(15);

        return view('admin.streams', compact('streams'));
    }

    public function create() {
        $grades = Grade::orderBy('order')->get();
        return view('admin.streams-create', compact('grades'));
    }

    public function store(Request $request) {
        $request->validate([
            'grade_id' => ['required', 'exists:grades,id'],
            'name' => ['required', 'string', 'max:10'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        Stream::create([
            'grade_id' => $request->grade_id,
            'name' => $request->name,
            'capacity' => $request->capacity,
        ]);

        return redirect()->route('admin.streams')->with('success', 'Stream created successfully!');
    }

    public function edit($id) {
        $stream = Stream::findOrFail($id);
        $grades = Grade::orderBy('order')->get();

        return view('admin.streams-edit', compact('stream', 'grades'));
    }

    public function update(Request $request, $id) {
        $stream = Stream::findOrFail($id);

        $request->validate([
            'grade_id' => ['required', 'exists:grades,id'],
            'name' => ['required', 'string', 'max:10'],
            'capacity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $stream->update([
            'grade_id' => $request->grade_id,
            'name' => $request->name,
            'capacity' => $request->capacity,
        ]);

        return redirect()->route('admin.streams')->with('success', 'Stream updated successfully!');
    }

    public function destroy($id) {
        $stream = Stream::findOrFail($id);
        $stream->delete();

        return redirect()->route('admin.streams')->with('success', 'Stream deleted successfully!');
    }
}
