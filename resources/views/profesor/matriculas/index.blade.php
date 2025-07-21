@extends('adminlte::page')

@section('title', 'Matrículas por Curso')

@section('content_header')
    <h1>Estudiantes Matriculados en tus Cursos</h1>
@endsection

@section('content')
    <div class="container">
        @forelse($matriculas as $grupo)
            <div class="card mb-4 shadow">
                @php
                    $total = $grupo['estudiantes']->count();
                @endphp

                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $grupo['curso'] }}</strong>
                        <span class="fw-normal">({{ $grupo['shortname'] }})</span>
                        <span class="badge bg-light text-dark ms-2">
                            {{ $total }} estudiante{{ $total !== 1 ? 's' : '' }}
                        </span>
                    </div>

                    @if($total > 0)
                        <a href="{{ route('profesor.matriculas.pdf', $grupo['id']) }}"
                           class="btn btn-danger btn-sm shadow-sm"
                           data-bs-toggle="tooltip"
                           data-bs-placement="left"
                           title="Exportar este listado como PDF"
                           target="_blank">
                            <i class="fas fa-file-pdf"></i> Descargar PDF
                        </a>
                    @endif
                </div>

                <div class="card-body">
                    @if($total === 0)
                        <p>No hay estudiantes matriculados.</p>
                    @else
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Correo</th>
                                    <th>ID Moodle</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grupo['estudiantes'] as $estudiante)
                                    <tr>
                                        <td>{{ $estudiante['fullname'] ?? $estudiante['firstname'].' '.$estudiante['lastname'] }}</td>
                                        <td>{{ $estudiante['email'] }}</td>
                                        <td>{{ $estudiante['id'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        @empty
            <p>No tienes cursos asignados o no hay estudiantes matriculados.</p>
        @endforelse
    </div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.forEach(function (el) {
            new bootstrap.Tooltip(el)
        })
    });
</script>
@endsection
