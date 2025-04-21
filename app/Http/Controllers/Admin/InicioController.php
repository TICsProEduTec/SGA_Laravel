<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inicio;
use App\Models\Plantilla;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Whoops\Handler\PlainTextHandler;

class InicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    private $token = 'e6f6ed7d0ff55a2aa71113ffd37629a9';
    private $domainname = 'https://lms.e-toolsacademy.com/';


    public function index()
    {
        //
        $inicios = Inicio::all();
        return view('admin.inicio.index', compact('inicios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $plantillas = Plantilla::all();
        return view('admin.inicio.create', compact('plantillas'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        //validacion
        $request->validate(([
            'name' => 'required',
            'shortname' => 'required|unique:inicios|alpha_dash',
        ]));
        $inicio = Inicio::create($request->all());
        //Crear inicio en moodle - categoria
        $name = $request->input('shortname');
        $plantilla_id = $request->input('plantilla_id');
        $parent = 0;
        $idnumber = null;
        $description = 'Esto es una categoria del sistema laravel';
        $descriptionformat = 1;
        //declarar funcion
        $functionname = 'core_course_create_categories';
        $serverurl = $this->domainname . '/webservice/rest/server.php' . '?wstoken=' . $this->token
        .'&wsfunction='.$functionname.'&moodlewsrestformat=json&categories[0][name]='.$name
        .'&categories[0][parent]='.$parent
        .'&categories[0][idnumber]='.$idnumber
        .'&categories[0][description]='.$description
        .'&categories[0][descriptionformat]='.$descriptionformat;
        Http::get($serverurl);
        //Obtener información de la categoria creada
        $functionname = 'core_course_get_categories';
        $serverurl2 = $this->domainname . '/webservice/rest/server.php'
        . '?wstoken=' . $this->token 
        . '&wsfunction='.$functionname
        .'&moodlewsrestformat=json&addsubcategories=0&criteria[0][key]=name&criteria[0][value]='.$name;
        $categoria = Http::get($serverurl2);
        foreach(json_decode($categoria) as $cat){

        }
        //crear los cursos en base a  la plantilla solicitada
        $functionname = 'core_course_create_courses';
        $plantilla = Plantilla::find($plantilla_id);
        foreach ($plantilla->cursos as $curso){
            $serverurl3 = $this->domainname . '/webservice/rest/server.php'
            . '?wstoken=' . $this->token
            .'&wsfunction='.$functionname
            .'&moodlewsrestformat=json&courses[0][fullname]='.$curso->name
            .'&courses[0][shortname]='.$name.'-'.$curso->name
            .'&courses[0][categoryid]='.$cat->id;
            $crearcurso = Http::get($serverurl3);
        }
        return redirect()->route('admin.inicios.index')->with('info', 'El inicio se creo correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Inicio $inicio)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inicio $inicio)
    {
        //
        $plantillas = Plantilla::all();
        return view('admin.inicio.edit', compact('inicio', 'plantillas'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inicio $inicio)
    {
        //
        $request->validate(([
            'name' => 'required',
            'shortname' => 'required|unique:inicios,shortname,'.$inicio->id.'|alpha_dash',
        ]));
        //Obtener informacion de la categoria
        $functionname = 'core_course_get_categories';
        $serverurl2 = $this->domainname . '/webservice/rest/server.php'
        . '?wstoken=' . $this->token 
        . '&wsfunction='.$functionname
        .'&moodlewsrestformat=json&addsubcategories=0&criteria[0][key]=name&criteria[0][value]='.$inicio->shortname;
        $categoria = Http::get($serverurl2);
        foreach(json_decode($categoria) as $cat){
        }
        //Actualizar la categoria seleccionada
        $functionname = 'core_course_update_categories';
        $serverurl = $this->domainname . '/webservice/rest/server.php'
        .'?wstoken=' . $this->token
        .'&wsfunction='.$functionname
        .'&moodlewsrestformat=json&categories[0][id]='.$cat->id.'&categories[0][name]='.$request->input('shortname');
        $categoria = Http::get($serverurl);
        $inicio->update($request->all());
        return redirect()->route('admin.inicios.index')->with('info', 'El inicio se actualizo correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inicio $inicio)
    {
        //Obtener informacion de la categoria que se va a eliminar
        $functionname = 'core_course_get_categories';
        $serverurl2 = $this->domainname . '/webservice/rest/server.php'
        . '?wstoken=' . $this->token 
        . '&wsfunction='.$functionname
        .'&moodlewsrestformat=json&addsubcategories=0&criteria[0][key]=name&criteria[0][value]='.$inicio->shortname;
        $categoria = Http::get($serverurl2);
        foreach(json_decode($categoria) as $cat){
        }
        //Eliminar la categoria obtenida
        $functionname = 'core_course_delete_categories';
        $serverurl = $this->domainname. '/webservice/rest/server.php'
        . '?wstoken='.$this->token
        .'&wsfunction='.$functionname
        .'&moodlewsrestformat=json&categories[0][id]='.$cat->id
        .'&categories[0][newparent]=0&categories[0][recursive]=1';
        $categoria = Http::get($serverurl);
        $inicio->delete();
        return redirect()->route('admin.inicios.index')->with('info', 'El inicio se elimino correctamente');
    }
}
