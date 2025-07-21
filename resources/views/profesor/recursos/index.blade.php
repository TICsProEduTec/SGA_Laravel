@extends('adminlte::page')

@section('title', '📁 Mis Recursos por Curso')

@section('content_header')
    <h1>📁 Mis Recursos por Curso</h1>
@stop

@section('content')

    {{-- ✅ Éxito --}}
    @if(session('success'))
        <x-adminlte-alert theme="success" dismissable>{{ session('success') }}</x-adminlte-alert>
    @endif

    {{-- ⚠️ Errores --}}
    @if($errors->any())
        <x-adminlte-alert theme="danger" dismissable>
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>❌ {{ $error }}</li>
                @endforeach
            </ul>
        </x-adminlte-alert>
    @endif

    {{-- 📤 Formulario de subida --}}
    <div class="card">
        <div class="card-header bg-primary text-white">
            📤 Subir nuevo recurso
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('recursos.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="curso_moodle_id">Curso Moodle</label>
                    <select name="curso_moodle_id" id="curso_moodle_id" class="form-control" required>
                        <option value="">Seleccione un curso...</option>
                        @foreach($cursos as $curso)
                            <option value="{{ $curso['id'] }}">{{ $curso['fullname'] }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="curso_nombre" id="curso_nombre" required>
                </div>

                <div class="form-group">
                    <label for="archivo">Archivo PDF</label>
                    <input type="file" name="archivo" id="archivo" class="form-control-file" accept=".pdf" required>
                </div>

                <button type="submit" class="btn btn-success">
                    📤 Subir Recurso
                </button>
            </form>
        </div>
    </div>

    {{-- 🧠 Script para capturar nombre del curso --}}
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const selectCurso = document.getElementById('curso_moodle_id');
            const inputNombre = document.getElementById('curso_nombre');

            selectCurso.addEventListener('change', function () {
                const nombre = this.options[this.selectedIndex]?.text ?? '';
                inputNombre.value = nombre;
            });
        });
    </script>

    {{-- 📚 Recursos cargados --}}
    <hr>

    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            📚 Recursos subidos por ti
        </div>
        <div class="card-body">
            @if($contenidos->isEmpty())
                <p>No has subido recursos aún.</p>
            @else
                <table class="table table-bordered table-hover table-sm">
                    <thead class="thead-dark">
                        <tr>
                            <th>📘 Curso</th>
                            <th>📎 Archivo</th>
                            <th>📅 Fecha</th>
                            <th>📥 Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($contenidos as $contenido)
                            <tr>
                                <td><strong>{{ $contenido->curso_nombre }}</strong></td>
                                <td>{{ basename($contenido->archivo) }}</td>
                                <td>{{ $contenido->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @if(Storage::disk('public')->exists($contenido->archivo))
                                            <a href="{{ asset('storage/' . $contenido->archivo) }}"
                                               class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                                📥 Descargar
                                            </a>
                                        @else
                                            <span class="text-danger me-2">Archivo no disponible</span>
                                        @endif

                                        <form action="{{ route('recursos.destroy', $contenido->id) }}"
                                              method="POST"
                                              onsubmit="return confirm('¿Estás seguro de eliminar este recurso?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                🗑️ Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@stop
