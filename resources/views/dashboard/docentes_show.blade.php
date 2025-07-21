@extends('adminlte::page')

@section('title', 'Retroalimentación automática')

@section('content_header')
    <h1 class="mb-4">Estado de los estudiantes</h1>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <style>
        .chat-message {
            padding: 8px 12px;
            border-radius: 18px;
            max-width: 75%;
            margin-bottom: 8px;
            display: inline-block;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .chat-ia {
            background-color: #f1f0f0;
            text-align: left;
        }
        .chat-docente {
            background-color: #cce5ff;
            text-align: right;
            margin-left: auto;
        }
    </style>

    @forelse($students as $student)
        <div class="card mb-4 shadow-sm border-{{ $student['estado'] === 'Aprobado' ? 'success' : 'danger' }}">
            <div class="card-header bg-{{ $student['estado'] === 'Aprobado' ? 'success' : 'danger' }} text-white d-flex justify-content-between">
                <h3 class="card-title mb-0">{{ $student['fullname'] }}</h3>
                <div>
                    Promedio: <strong>{{ number_format($student['promedio'], 2) }}/10</strong> |
                    Estado: <strong>{{ $student['estado'] }}</strong>
                </div>
            </div>

            @if($student['estado'] === 'Debe ir a recuperación')
                <div class="card-body">
                    <div class="mb-3">
                        <h5>💬 Chat de retroalimentación con IA</h5>
                        <div class="border p-3 bg-light rounded" id="chat-box-{{ $student['user_id_local'] }}"
                             style="max-height: 300px; overflow-y: auto; font-size: 0.9rem;">
                             <div class="text-muted">Chat iniciado. Escribe un mensaje para comenzar.</div>
                        </div>
                    </div>

                    <div class="input-group mb-3">
                        <input type="text"
                               id="chat-input-{{ $student['user_id_local'] }}"
                               class="form-control"
                               placeholder="Escribe un mensaje para la IA..."
                               onkeydown="if(event.key==='Enter'){ enviarMensajeIA({{ $student['user_id_local'] }}) }">
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary"
                                    type="button"
                                    onclick="enviarMensajeIA({{ $student['user_id_local'] }})">Enviar</button>
                        </div>
                    </div>

                </div>
            @endif
        </div>
    @empty
        <div class="alert alert-info">
            No se encontraron estudiantes en este curso.
        </div>
    @endforelse
@stop

@section('js')
<script>
function enviarMensajeIA(userId) {
    const input = document.querySelector(`#chat-input-${userId}`);
    const chatBox = document.querySelector(`#chat-box-${userId}`);
    const message = input.value.trim();

    if (!message) return;

    const msgDocente = document.createElement('div');
    msgDocente.classList.add('chat-message', 'chat-docente');
    msgDocente.innerHTML = `<strong>👤 Tú:</strong> ${message}`;
    chatBox.appendChild(msgDocente);
    chatBox.scrollTop = chatBox.scrollHeight;

    input.disabled = true;
    input.value = '...';

    fetch(`/dashboard/{{ $courseId }}/chat-feedback/${userId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ message })
    })
    .then(res => res.json())
    .then(data => {
        const respuesta = data.respuesta || 'Sin respuesta de IA.';

        const msgIA = document.createElement('div');
        msgIA.classList.add('chat-message', 'chat-ia');
        msgIA.innerHTML = `<strong>🤖 IA:</strong> ${respuesta}`;
        chatBox.appendChild(msgIA);
        chatBox.scrollTop = chatBox.scrollHeight;
    })
    .catch(err => {
        alert('❌ Error al contactar IA');
        console.error(err);
    })
    .finally(() => {
        input.disabled = false;
        input.value = '';
    });
}
</script>
@stop
