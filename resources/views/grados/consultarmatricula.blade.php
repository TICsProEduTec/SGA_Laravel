@extends('adminlte::page')

@section('title', 'Consultar Matrícula')

@section('content_header')
    <h1>{{ $grado->periodo->name . " - " . $grado->name }}</h1>
@stop

@section('content')
    @if (session('info'))
        <div class="mb-3 alert alert-success">
            <strong>{{ session('info') }}</strong>
        </div>
    @endif

    {{-- ========== FORMULARIO DE MATRÍCULA MASIVA ========== --}}
    <div class="container mb-4">
        <form action="{{ route('grado.matricular', $grado->id) }}" method="POST">
            @csrf
            @if ($users->count() > 0)
                <div class="mb-3">
                    <p><strong>Selecciona uno o varios estudiantes para matricular:</strong></p>
                    <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                        <table class="table table-bordered mb-0">
                            <thead class="table-dark" style="position: sticky; top: 0; z-index: 1;">
                                <tr>
                                    <th><input type="checkbox" id="checkAllMatricular"></th>
                                    <th>Nombre Completo</th>
                                    <th>Cédula</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach ($users as $user)
                                    <tr>
                                        <td><input type="checkbox" name="user_ids[]" value="{{ $user->id }}"></td>
                                        <td>{{ $user->ap_paterno . " " . $user->ap_materno . " " . $user->name }}</td>
                                        <td>{{ $user->cedula }}</td>
                                        <td>{{ $user->email }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Matricular Estudiantes</button>
                    <button type="submit" class="btn btn-primary" name="asignar_docente" value="1"><i class="fas fa-chalkboard-teacher"></i> Asignar como Docente</button>
                    <a href="{{ route('periodo.consultargrados', $grado->periodo->id) }}" class="btn btn-info">Regresar</a>
                </div>
            @else
                <div class="alert alert-info">
                    No hay estudiantes pendientes de matricular en este grado.
                </div>
            @endif
        </form>
    </div>

    {{-- ========== LISTADO DE MATRICULADOS + DESMATRICULACIÓN MASIVA ========== --}}
    <div class="container">
        <form action="{{ route('grado.desmatricular', $grado->id) }}" method="POST">
            @csrf
            <h5>Estudiantes ya matriculados:</h5>
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                <table class="table table-bordered mb-0">
                    <thead class="table-dark" style="position: sticky; top: 0; z-index: 1;">
                        <tr>
                            <th><input type="checkbox" id="checkAllDesmatricular"></th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach ($grado->users as $estudiante)
                            <tr>
                                <td><input type="checkbox" name="user_ids[]" value="{{ $estudiante->id }}"></td>
                                <td>{{ $estudiante->ap_paterno . " " . $estudiante->ap_materno . " " . $estudiante->name }}</td>
                                <td>{{ $estudiante->email }}</td>
                                <td>
                                    <a href="{{ route('grado.consultarnotas', [$grado->id, $estudiante->id]) }}" class="btn btn-info btn-sm">Notas</a>
                                    <a href="{{ route('grado.desmatricular.individual', [$grado->id, $estudiante->id]) }}" class="btn btn-danger btn-sm">Desmatricular</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($grado->users->count())
                <div class="mt-3">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-user-minus"></i> Desmatricular Seleccionados</button>
                </div>
            @endif
        </form>
    </div>
@stop

@section('js')
<script>
    document.getElementById('checkAllMatricular')?.addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    document.getElementById('checkAllDesmatricular')?.addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
@stop
