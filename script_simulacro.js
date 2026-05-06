document.addEventListener('DOMContentLoaded', inicializarSimulacro);

const loadingSpinner = document.getElementById('loading-spinner');
const testForm       = document.getElementById('test-form');
const testContainer  = document.getElementById('test-container');

let numPreguntasTotal = 0;
let currentIndex      = 0;

// ── Fetch preguntas ────────────────────────────────────────────────────────
async function inicializarSimulacro() {
    try {
        const response = await fetch('obtener_simulacro.php');
        if (!response.ok) throw new Error(`Error del servidor: ${response.statusText} (${response.status})`);

        const preguntas = await response.json();
        if (preguntas.error) throw new Error(`Error PHP: ${preguntas.error}`);

        renderizarTest(preguntas);

    } catch (error) {
        console.error('Error al generar el simulacro:', error);
        loadingSpinner.innerHTML = `
            <h3 class="text-danger">Error al cargar el simulacro</h3>
            <p>${error.message}</p>
            <p>Necesitas tener una racha de 30 días para acceder.</p>
        `;
    }
}

// ── Render con paginación ─────────────────────────────────────────────────
function renderizarTest(preguntas) {
    numPreguntasTotal = preguntas.length;
    currentIndex      = 0;

    loadingSpinner.classList.add('d-none');
    testForm.classList.remove('d-none');

    let htmlPreguntas = '';

    preguntas.forEach((item, index) => {
        const nombrePregunta = `pregunta-${index}`;
        const displayClass   = index === 0 ? '' : 'd-none';
        const tieneImagen = item.imagen_url && item.imagen_url.trim() !== '';
        const imgHtml = tieneImagen ? `
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="position-relative overflow-hidden rounded-4 shadow-lg h-100" style="min-height:250px;">
                    <img src="${item.imagen_url}" alt="Situación de la pregunta"
                         class="w-100 h-100 object-fit-cover position-absolute top-0 start-0">
                    <div class="position-absolute bottom-0 start-0 m-3">
                        <span class="bg-dark bg-opacity-75 text-white fw-bold px-2 py-1 rounded"
                              style="font-size:0.65rem;border:1px solid rgba(255,255,255,0.1);">SITUACIÓN REAL</span>
                    </div>
                </div>
            </div>
        ` : '';

        const colClass = tieneImagen ? 'col-lg-6' : 'col-12';

        htmlPreguntas += `
            <div class="glass-card mb-5 p-4 p-md-5 question-card ${displayClass}"
                 data-id-pregunta-bd="${item.id_pregunta}"
                 data-respuesta-correcta="${item.respuesta_correcta_index}"
                 id="card-pregunta-${index}"
                 style="min-height:520px;display:flex;align-items:center;">

                <div class="row w-100 m-0">
                    ${imgHtml}

                    <!-- Pregunta + opciones -->
                    <div class="${colClass} d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle fw-bold"
                                 style="width:32px;height:32px;background:rgba(124,58,237,0.2);border:1px solid rgba(124,58,237,0.4);color:#a78bfa;">
                                ${index + 1}
                            </div>
                            <h4 class="fw-bold text-white m-0 lh-base">${item.pregunta}</h4>
                        </div>

                        <div class="opciones-lista mt-3">
                            <label class="d-block w-100 text-start p-4 rounded-4 border border-light border-opacity-10 option-label mb-3 cursor-pointer" style="background:rgba(255,255,255,0.05);transition:all .2s;">
                                <input class="d-none form-check-input" type="radio" name="${nombrePregunta}" value="0">
                                <span class="fw-medium text-slate-300">A) ${item.opciones[0]}</span>
                            </label>
                            <label class="d-block w-100 text-start p-4 rounded-4 border border-light border-opacity-10 option-label mb-3 cursor-pointer" style="background:rgba(255,255,255,0.05);transition:all .2s;">
                                <input class="d-none form-check-input" type="radio" name="${nombrePregunta}" value="1">
                                <span class="fw-medium text-slate-300">B) ${item.opciones[1]}</span>
                            </label>
                            <label class="d-block w-100 text-start p-4 rounded-4 border border-light border-opacity-10 option-label mb-0 cursor-pointer" style="background:rgba(255,255,255,0.05);transition:all .2s;">
                                <input class="d-none form-check-input" type="radio" name="${nombrePregunta}" value="2">
                                <span class="fw-medium text-slate-300">C) ${item.opciones[2]}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    // Botones de navegación
    const htmlNavButtons = `
        <div class="d-flex align-items-center justify-content-between mt-4 mb-5 pb-5" id="nav-footer">
            <button type="button" id="btn-anterior"
                    class="btn text-white fw-bold px-4 py-3 rounded-pill"
                    style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);"
                    disabled>
                <i class="fa-solid fa-chevron-left me-2"></i> Anterior
            </button>
            <button type="button" id="btn-siguiente"
                    class="btn text-white fw-bold px-5 py-3 rounded-pill"
                    style="background:linear-gradient(90deg,#7c3aed,#4f46e5);box-shadow:0 4px 20px rgba(124,58,237,0.4);">
                Siguiente <i class="fa-solid fa-chevron-right ms-2"></i>
            </button>
            <button type="submit" id="btn-finalizar"
                    class="btn text-white fw-bold px-5 py-3 rounded-pill d-none"
                    style="background:linear-gradient(90deg,#10b981,#059669);box-shadow:0 4px 20px rgba(16,185,129,0.4);">
                <i class="fa-solid fa-flag-checkered me-2"></i> Finalizar y Corregir
            </button>
        </div>
    `;

    // Mapa de progreso (fijo al fondo)
    const htmlDots = `
        <div class="position-fixed bottom-0 start-0 w-100 py-3"
             style="background:rgba(15,23,42,0.95);backdrop-filter:blur(12px);border-top:1px solid rgba(255,255,255,0.08);z-index:999;box-shadow:0 -10px 30px rgba(0,0,0,0.5);">
            <div class="container d-flex flex-column align-items-center">
                <div class="text-white-50 small mb-2 fw-semibold" style="letter-spacing:.08em;">MAPA DEL SIMULACRO</div>
                <div class="d-flex flex-wrap gap-1 justify-content-center" id="progress-dots-container">
                    ${preguntas.map((_, i) => `
                        <a href="javascript:void(0)" onclick="navegarAPregunta(${i})" class="text-decoration-none">
                            <div id="dot-${i}" class="rounded-pill" style="height:6px;width:16px;background:#1e293b;transition:all .3s;"></div>
                        </a>`).join('')}
                </div>
            </div>
        </div>
    `;

    testContainer.innerHTML = htmlPreguntas + htmlNavButtons;
    testForm.insertAdjacentHTML('beforeend', htmlDots);

    // Eventos de opciones (glow al seleccionar)
    document.querySelectorAll('.form-check-input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const name = e.target.name;
            document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
                const label = r.closest('.option-label');
                label.classList.toggle('glow-selected', r.checked);
            });
            actualizarDots();
        });
    });

    // Botones navegación
    document.getElementById('btn-siguiente').addEventListener('click', () => navegarAPregunta(currentIndex + 1));
    document.getElementById('btn-anterior').addEventListener('click',  () => navegarAPregunta(currentIndex - 1));

    // Submit
    testForm.addEventListener('submit', (e) => {
        e.preventDefault();
        corregirExamen(preguntas.length);
    });

    actualizarDots();
}

// ── Navegar entre preguntas ────────────────────────────────────────────────
window.navegarAPregunta = function (newIndex) {
    if (newIndex < 0 || newIndex >= numPreguntasTotal) return;

    document.getElementById(`card-pregunta-${currentIndex}`).classList.add('d-none');
    currentIndex = newIndex;
    document.getElementById(`card-pregunta-${currentIndex}`).classList.remove('d-none');

    document.getElementById('btn-anterior').disabled = (currentIndex === 0);

    const esUltima = (currentIndex === numPreguntasTotal - 1);
    document.getElementById('btn-siguiente').classList.toggle('d-none',  esUltima);
    document.getElementById('btn-finalizar').classList.toggle('d-none', !esUltima);

    actualizarDots();
};

function actualizarDots() {
    for (let i = 0; i < numPreguntasTotal; i++) {
        const radio = document.querySelector(`input[name="pregunta-${i}"]:checked`);
        const dot   = document.getElementById(`dot-${i}`);
        if (!dot) continue;
        if (radio) {
            dot.style.backgroundColor = '#7c3aed';
            dot.style.width           = '24px';
            dot.style.boxShadow       = '0 0 10px rgba(139,92,246,0.5)';
        } else {
            dot.style.backgroundColor = '#1e293b';
            dot.style.width           = '16px';
            dot.style.boxShadow       = 'none';
        }
    }
}

// ── Corregir y guardar ─────────────────────────────────────────────────────
async function corregirExamen(totalPreguntas) {
    let fallos    = 0;
    let resultados = [];

    const cards = document.querySelectorAll('.question-card[data-id-pregunta-bd]');

    cards.forEach((card, index) => {
        const idPregunta           = card.dataset.idPreguntaBd;
        const respuestaCorrectaIdx = card.dataset.respuestaCorrecta;
        const radioSeleccionado    = document.querySelector(`input[name="pregunta-${index}"]:checked`);
        let respuestaUsuarioIndex  = null;
        let respuestaUsuarioLetra  = null;
        let esCorrecta             = false;

        if (!radioSeleccionado) {
            fallos++;
        } else {
            respuestaUsuarioIndex = radioSeleccionado.value;
            esCorrecta = (respuestaUsuarioIndex === respuestaCorrectaIdx);
            if (!esCorrecta) fallos++;
        }

        if (respuestaUsuarioIndex === '0') respuestaUsuarioLetra = 'A';
        else if (respuestaUsuarioIndex === '1') respuestaUsuarioLetra = 'B';
        else if (respuestaUsuarioIndex === '2') respuestaUsuarioLetra = 'C';

        resultados.push({ id_pregunta: idPregunta, respuesta_usuario: respuestaUsuarioLetra, correcta: esCorrecta });
    });

    const btnFinalizar = document.getElementById('btn-finalizar');
    const btnSubmit    = document.querySelector('#test-form button[type="submit"]');
    if (btnFinalizar) { btnFinalizar.disabled = true; btnFinalizar.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Guardando...'; }
    if (btnSubmit)    { btnSubmit.disabled = true; }

    try {
        const response = await fetch('guardar_test.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ puntuacion: totalPreguntas - fallos, resultados })
        });

        const data = await response.json();

        if (data.success) {
            const aciertos = totalPreguntas - fallos;
            const aprobado = aciertos >= 27;
            
            let mensaje = `📋 Simulacro finalizado.\n\n` +
                `✅ Aciertos: ${aciertos} / ${totalPreguntas}\n` +
                `❌ Fallos: ${fallos}\n\n`;
            
            if (aprobado) {
                mensaje += `🎉 ¡APROBADO!\n\n`;
                mensaje += `Has demostrado que estás preparado para el examen oficial de la DGT.\n`;
                mensaje += `Tu nivel de conocimiento es equivalente al requerido en el examen real.`;
            } else {
                mensaje += `❌ No aprobado. ¡Sigue practicando!\n`;
                mensaje += `Necesitas máximo 3 fallos para aprobar.`;
            }
            
            alert(mensaje);
            setTimeout(() => { window.location.href = 'perfil.php'; }, 2000);
        } else {
            alert('Error al guardar el simulacro: ' + data.error);
            if (btnFinalizar) { btnFinalizar.disabled = false; btnFinalizar.innerHTML = '<i class="fa-solid fa-flag-checkered me-2"></i>Finalizar y Corregir'; }
        }

    } catch (error) {
        alert('Error de red: ' + error.message);
        if (btnFinalizar) { btnFinalizar.disabled = false; btnFinalizar.innerHTML = '<i class="fa-solid fa-flag-checkered me-2"></i>Finalizar y Corregir'; }
    }
}