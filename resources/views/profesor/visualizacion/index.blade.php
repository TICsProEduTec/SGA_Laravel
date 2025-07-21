@extends('adminlte::page')

@section('title', 'Visualización de Datos')

@section('content_header')
    <h1 class="mb-3">Visualización de Datos por Curso</h1>
@endsection

@section('content')
<div class="container-fluid">
    <div class="form-group d-flex align-items-end gap-2">
        <div class="flex-grow-1">
            <label for="curso-select"><strong>Selecciona un curso:</strong></label>
            <select id="curso-select" class="form-control select2">
                <option value="" selected disabled>-- Selecciona un curso --</option>
                @foreach($cursos as $curso)
                    <option value="{{ $curso['id'] }}">{{ $curso['fullname'] }}</option>
                @endforeach
            </select>
        </div>
        <button id="btn-extraer" class="btn btn-primary" style="height: 38px;">
            <i class="fas fa-database"></i> Extraer Datos
        </button>
    </div>

    <div class="row mt-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="small-box bg-success">
                <div class="inner text-center">
                    <h3 id="aprobados">0</h3>
                    <p>Estudiantes Aprobados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="small-box bg-danger">
                <div class="inner text-center">
                    <h3 id="reprobados">0</h3>
                    <p>Estudiantes Reprobados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-times-circle"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="small-box bg-info">
                <div class="inner text-center">
                    <h3 id="promedio">0</h3>
                    <p>Promedio del Curso</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-3">
            <div class="small-box bg-secondary">
                <div class="inner text-center">
                    <h3 id="tareas-activas">0</h3>
                    <p>Tareas Activas</p>
                </div>
                <div class="icon">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-4 mb-4">
            <div class="card card-outline card-primary h-100">
                <div class="card-header">
                    <h3 class="card-title">Distribución Aprobados / Reprobados</h3>
                </div>
                <div class="card-body">
                    <canvas id="grafico-pie" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card card-outline card-info h-100">
                <div class="card-header">
                    <h3 class="card-title">Promedio del Curso</h3>
                </div>
                <div class="card-body">
                    <canvas id="grafico-barra" height="250"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4 mb-4">
            <div class="card card-outline card-secondary h-100">
                <div class="card-header">
                    <h3 class="card-title">Tareas del Curso</h3>
                </div>
                <div class="card-body">
                    <canvas id="grafico-tareas" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        $('#curso-select').select2();

        const btn = document.getElementById('btn-extraer');
        const select = document.getElementById('curso-select');

        let pieChart = new Chart(document.getElementById('grafico-pie'), {
            type: 'pie',
            data: {
                labels: ['Aprobados', 'Reprobados'],
                datasets: [{
                    data: [0, 0],
                    backgroundColor: ['#28a745', '#dc3545']
                }]
            }
        });

        let barChart = new Chart(document.getElementById('grafico-barra'), {
            type: 'bar',
            data: {
                labels: ['Promedio'],
                datasets: [{
                    label: 'Promedio del Curso',
                    data: [0],
                    backgroundColor: ['#17a2b8']
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        let tareasChart = new Chart(document.getElementById('grafico-tareas'), {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Tareas',
                    data: [],
                    backgroundColor: []
                }]
            },
            options: {
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 1,
                        ticks: {
                            stepSize: 1,
                            callback: value => value === 1 ? 'Activa' : 'Oculta'
                        }
                    }
                }
            }
        });

        btn.addEventListener('click', () => {
            const cursoId = select.value;
            if (!cursoId) {
                alert("⚠️ Debes seleccionar un curso primero.");
                return;
            }

            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cargando...';
            btn.disabled = true;

            // Notas
            fetch(`/visualizacion/datos/${cursoId}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('aprobados').textContent = data.aprobados ?? 0;
                    document.getElementById('reprobados').textContent = data.reprobados ?? 0;
                    document.getElementById('promedio').textContent = data.promedio ?? 0;

                    pieChart.data.datasets[0].data = [data.aprobados, data.reprobados];
                    pieChart.update();

                    barChart.data.datasets[0].data = [data.promedio];
                    barChart.update();
                })
                .catch(err => console.error("❌ Error en notas:", err));

            // Tareas
            fetch(`/visualizacion/tareas/${cursoId}`)
                .then(res => res.json())
                .then(data => {
                    const labels = data.map(t => t.nombre);
                    const values = data.map(t => t.visible === 'Activa' ? 1 : 0);
                    const colors = data.map(t => t.visible === 'Activa' ? '#28a745' : '#6c757d');

                    tareasChart.data.labels = labels;
                    tareasChart.data.datasets[0].data = values;
                    tareasChart.data.datasets[0].backgroundColor = colors;
                    tareasChart.update();

                    const totalActivas = data.filter(t => t.visible === 'Activa').length;
                    document.getElementById('tareas-activas').textContent = totalActivas;
                })
                .catch(err => console.error("❌ Error en tareas:", err))
                .finally(() => {
                    btn.innerHTML = '<i class="fas fa-database"></i> Extraer Datos';
                    btn.disabled = false;
                });
        });
    });
</script>
@endsection
