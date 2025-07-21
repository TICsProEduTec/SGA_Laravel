<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::all();
        return view('areas.index', compact('areas'));
    }

    public function create()
    {
        return view('areas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:255',
            'id_course_moodle' => 'nullable|integer',
            'grado_id' => 'required|exists:grados,id',
        ]);

        Area::create($request->all());

        return redirect()->route('areas.index')->with('info', 'Área creada correctamente.');
    }

    public function show(Area $area)
    {
        return view('areas.show', compact('area'));
    }

    public function edit(Area $area)
    {
        return view('areas.edit', compact('area'));
    }

    public function update(Request $request, Area $area)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'shortname' => 'nullable|string|max:255',
            'id_course_moodle' => 'nullable|integer',
            'grado_id' => 'required|exists:grados,id',
        ]);

        $area->update($request->all());

        return redirect()->route('areas.index')->with('info', 'Área actualizada correctamente.');
    }

    public function destroy(Area $area)
    {
        $area->delete();
        return redirect()->route('areas.index')->with('info', 'Área eliminada correctamente.');
    }
}
