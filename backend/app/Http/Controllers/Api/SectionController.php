<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Section\StoreSectionRequest;
use App\Http\Resources\SectionResource;
use App\Models\Course;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function store(StoreSectionRequest $request, Course $course)
    {
        $position = $request->integer('position') ?: $course->sections()->max('position') + 1;

        $section = $course->sections()->create([
            'title' => $request->string('title'),
            'position' => $position,
        ]);

        return response()->json(new SectionResource($section), 201);
    }

    public function update(Request $request, Course $course, Section $section)
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        $section->update($data);

        return new SectionResource($section);
    }

    public function destroy(Request $request, Course $course, Section $section)
    {
        $this->authorize('update', $course);

        $section->delete();

        return response()->json(status: 204);
    }

    public function reorder(Request $request, Course $course)
    {
        $this->authorize('update', $course);

        $data = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.id' => ['required', 'integer', 'exists:sections,id'],
            'sections.*.position' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['sections'] as $item) {
            $course->sections()->where('id', $item['id'])->update(['position' => $item['position']]);
        }

        return response()->json(['message' => 'Orden actualizado.']);
    }
}
