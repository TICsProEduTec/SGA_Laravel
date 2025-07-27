@extends('adminlte::page')

@section('title', 'Asistente IA')

@section('content_header')
    <h1>Asistente IA</h1>
@stop

@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Botones rápidos -->
    <div class="mb-3">
        <button class="btn btn-outline-secondary btn-sm" onclick="enviarMensajeIA('🧠 Genera un banco de preguntas GIFT desde el PDF')">
            🧠 Generar banco de preguntas GIFT desde el PDF
        </button>
    </div>

    <div class="card" style="height: 70vh; display: flex; flex-direction: column;">
        <div id="chat" class="card-body overflow-auto" style="flex-grow: 1; background: #f5f5f5; padding: 1rem;">
            <!-- Aquí aparecerán los mensajes del chat -->
        </div>

        <div class="card-footer">
            <form id="form-chat" enctype="multipart/form-data">
                <div class="input-group">
                    <input type="file" name="archivo" id="archivo" accept="application/pdf" class="form-control" style="max-width: 200px;">
                    <textarea name="message" id="message" rows="1" class="form-control" placeholder="Escribe tu mensaje o presiona Enter…" style="resize: none;"></textarea>
                    <button type="submit" class="btn btn-primary">Enviar</button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
<script>
    const form = document.getElementById('form-chat');
    const textarea = document.getElementById('message');
    const chatBox = document.getElementById('chat');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function appendMessage(content, sender = 'user') {
        const wrapper = document.createElement('div');
        wrapper.classList.add('mb-2', 'd-flex', sender === 'user' ? 'justify-content-end' : 'justify-content-start');

        const bubble = document.createElement('div');
        bubble.classList.add('p-2', 'rounded');
        bubble.style.maxWidth = '70%';
        bubble.style.whiteSpace = 'pre-wrap';

        if (sender === 'user') {
            bubble.classList.add('bg-primary', 'text-white');
        } else {
            bubble.classList.add('bg-light', 'border');
        }

        bubble.innerHTML = parseMarkdown(content);
        wrapper.appendChild(bubble);
        chatBox.appendChild(wrapper);
        scrollToBottom();
    }

    function appendSugerencias(sugerencias) {
        const container = document.createElement('div');
        container.classList.add('d-flex', 'flex-wrap', 'gap-2', 'mb-3');

        sugerencias.forEach(originalText => {
            const cleanText = originalText.replace(/^[^\w]*\s*/, '');

            const btn = document.createElement('button');
            btn.textContent = cleanText;
            btn.classList.add('btn', 'btn-outline-primary', 'btn-sm');
            btn.type = 'button';

            btn.addEventListener('click', () => {
                enviarMensajeIA(originalText);
            });

            container.appendChild(btn);
        });

        chatBox.appendChild(container);
        scrollToBottom();
    }

    function enviarMensajeIA(texto) {
        textarea.value = texto;
        form.dispatchEvent(new Event('submit'));
    }

    function parseMarkdown(text) {
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>')
            .replace(/^- (.*)/gm, '• $1');
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();

        const archivo = document.getElementById('archivo').files[0];
        const mensaje = textarea.value.trim();
        const formData = new FormData();

        if (mensaje) appendMessage(mensaje, 'user');
        else if (archivo) appendMessage('📤 Subiendo PDF a la IA…', 'user');
        else {
            appendMessage('⚠️ Debes escribir un mensaje o subir un archivo.', 'ia');
            return;
        }

        if (mensaje) formData.append('message', mensaje);
        if (archivo) formData.append('archivo', archivo);

        try {
            const response = await fetch("{{ route('docente.ia.procesar') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                appendMessage('❌ Error: El servidor no devolvió una respuesta válida.', 'ia');
                const errorText = await response.text();
                console.error('Respuesta no JSON:', errorText);
                return;
            }

            const data = await response.json();

            if (response.ok) {
                appendMessage(data.respuesta ?? '✅ Procesado correctamente.', 'ia');

                if (data.sugerencias && Array.isArray(data.sugerencias)) {
                    appendSugerencias(data.sugerencias);
                }

                const fileWrapper = document.createElement('div');
                fileWrapper.classList.add('d-flex', 'gap-2', 'mt-2');

                if (data.archivo) {
                    const linkTxt = document.createElement('a');
                    linkTxt.href = data.archivo;
                    linkTxt.innerText = '📥 Descargar TXT';
                    linkTxt.classList.add('btn', 'btn-outline-secondary', 'btn-sm');
                    linkTxt.target = "_blank";
                    fileWrapper.appendChild(linkTxt);
                }

                if (data.pdf) {
                    const linkPdf = document.createElement('a');
                    linkPdf.href = data.pdf;
                    linkPdf.innerText = '📄 Descargar PDF';
                    linkPdf.classList.add('btn', 'btn-outline-info', 'btn-sm');
                    linkPdf.target = "_blank";
                    fileWrapper.appendChild(linkPdf);
                }

                if (data.gift) {
                    const linkGift = document.createElement('a');
                    linkGift.href = data.gift;
                    linkGift.innerText = '🎁 Descargar archivo GIFT';
                    linkGift.classList.add('btn', 'btn-outline-success', 'btn-sm');
                    linkGift.target = "_blank";
                    fileWrapper.appendChild(linkGift);
                }

                if (fileWrapper.children.length > 0) {
                    chatBox.appendChild(fileWrapper);
                    scrollToBottom();
                }

            } else {
                appendMessage(data.respuesta ?? '❌ Error en la respuesta.', 'ia');
            }

        } catch (err) {
            appendMessage('❌ Error: No se pudo contactar con el servidor.', 'ia');
            console.error(err);
        }

        textarea.value = '';
        document.getElementById('archivo').value = '';
    });

    textarea.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    });
</script>
@stop
