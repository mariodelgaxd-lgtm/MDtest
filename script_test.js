document.addEventListener('DOMContentLoaded', inicializarTest);

const CAJA_TEST = 'test_en_progreso';
const CAJA_RESPUESTAS = 'respuestas_en_progreso';

const loadingSpinner = document.getElementById('loading-spinner');
const testForm = document.getElementById('test-form');
const testContainer = document.getElementById('test-container');
const btnReiniciar = document.getElementById('reiniciar-test-btn');

async function inicializarTest() {
    
    btnReiniciar.addEventListener('click', () => {
        if (confirm('¿Seguro que quieres borrar este test y empezar uno nuevo?')) {
            localStorage.removeItem(CAJA_TEST);
            localStorage.removeItem(CAJA_RESPUESTAS);
            window.location.reload();
        }
    });

    const testGuardado = localStorage.getItem(CAJA_TEST);

    if (testGuardado) {
        console.log("Cargando test en progreso desde localStorage...");
        const preguntas = JSON.parse(testGuardado);
        renderizarTest(preguntas);
        cargarProgresoGuardado();
        
        btnReiniciar.classList.remove('d-none');
        
    } else {
        try {
            console.log("Pidiendo 30 preguntas a obtener_test.php...");
            const modo = window.MODO_TEST || 'normal';
            const response = await fetch(`obtener_test.php?modo=${modo}`);
            
            if (!response.ok) {
                throw new Error(`Error del servidor (PHP): ${response.statusText} (${response.status})`);
            }

            const preguntas = await response.json();
            
            if (preguntas.error) {
                throw new Error(`Error desde PHP: ${preguntas.error}`);
            }

            console.log("¡Preguntas recibidas! Guardando en localStorage...");
            
            localStorage.setItem(CAJA_TEST, JSON.stringify(preguntas));
            localStorage.setItem(CAJA_RESPUESTAS, JSON.stringify({}));
            
            renderizarTest(preguntas);
            btnReiniciar.classList.remove('d-none'); 

        } catch (error) {
            console.error("Error al generar el test:", error);
            loadingSpinner.innerHTML = `
                <h3 class="text-danger">Error al cargar el test</h3>
                <p>${error.message}</p>
                <p>Revisa la consola (F12) para más detalles.</p>
            `;
        }
    }
}

let numPreguntasTotal = 0;

let currentIndex = 0;

function renderizarTest(preguntas) {
    numPreguntasTotal = preguntas.length;
    currentIndex = 0;
    loadingSpinner.classList.add('d-none');
    testForm.classList.remove('d-none');
    let htmlPreguntas = '';

    preguntas.forEach((item, index) => {
        const nombrePregunta = `pregunta-${index}`;
        const imgSrc = item.imagen_url ? item.imagen_url : 'https://images.unsplash.com/photo-1596727147705-61a532a65d6c?auto=format&fit=crop&q=80&w=1200';
        
        // Solo la primera pregunta es visible inicialmente
        const displayClass = index === 0 ? '' : 'd-none';
        
        htmlPreguntas += `
            <div class="glass-card mb-5 p-4 p-md-5 question-card ${displayClass}" data-id-pregunta-bd="${item.id_pregunta}" data-respuesta-correcta="${item.respuesta_correcta_index}" id="card-pregunta-${index}" style="min-height: 520px; display: flex; align-items: center;">
                
                <div class="row w-100 m-0">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <div class="position-relative overflow-hidden rounded-4 shadow-lg h-100" style="min-height: 250px;">
                            <img src="${imgSrc}" alt="Situación" class="w-100 h-100 object-fit-cover position-absolute top-0 start-0">
                            <div class="position-absolute bottom-0 start-0 m-3 d-flex gap-2">
                                <span class="bg-dark bg-opacity-75 text-white fw-bold px-2 py-1 rounded" style="font-size: 0.65rem; border: 1px solid rgba(255,255,255,0.1);">SITUACIÓN REAL</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6 d-flex flex-column justify-content-center">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle fw-bold" style="width: 32px; height: 32px; background: rgba(124, 58, 237, 0.2); border: 1px solid rgba(124, 58, 237, 0.4); color: #a78bfa;">
                                ${index + 1}
                            </div>
                            <h4 class="fw-bold text-white m-0 lh-base">${item.pregunta}</h4>
                        </div>
                        
                        <div class="opciones-lista mt-3">
                            <label class="d-block w-100 text-start p-4 rounded-4 transition-all border border-light border-opacity-10 option-label mb-3 cursor-pointer">
                                <input class="d-none form-check-input" type="radio" name="${nombrePregunta}" value="0">
                                <span class="fw-medium text-slate-300">A) ${item.opciones[0]}</span>
                            </label>
                            
                            <label class="d-block w-100 text-start p-4 rounded-4 transition-all border border-light border-opacity-10 option-label mb-3 cursor-pointer">
                                <input class="d-none form-check-input" type="radio" name="${nombrePregunta}" value="1">
                                <span class="fw-medium text-slate-300">B) ${item.opciones[1]}</span>
                            </label>
                            
                            <label class="d-block w-100 text-start p-4 rounded-4 transition-all border border-light border-opacity-10 option-label mb-0 cursor-pointer">
                                <input class="d-none form-check-input" type="radio" name="${nombrePregunta}" value="2">
                                <span class="fw-medium text-slate-300">C) ${item.opciones[2]}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    // Navegación Inferior (Anterior / Siguiente / Finalizar)
    let htmlNavButtons = `
        <div class="d-flex align-items-center justify-content-between mt-4 mb-5 pb-5" id="nav-footer">
            <button type="button" id="btn-anterior" class="btn text-white fw-bold px-4 py-3 rounded-pill transition-all border border-white border-opacity-10" style="background: rgba(255,255,255,0.05);" disabled>
                <i class="fa-solid fa-chevron-left me-2"></i> Anterior
            </button>
            <button type="button" id="btn-siguiente" class="btn text-white fw-bold px-5 py-3 rounded-pill transition-all" style="background: linear-gradient(90deg, #7c3aed 0%, #4f46e5 100%); box-shadow: 0 4px 20px rgba(124, 58, 237, 0.4);">
                Siguiente <i class="fa-solid fa-chevron-right ms-2"></i>
            </button>
            <button type="submit" id="btn-finalizar" class="btn text-white fw-bold px-5 py-3 rounded-pill transition-all d-none" style="background: linear-gradient(90deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);">
                <i class="fa-solid fa-flag-checkered me-2"></i> Finalizar y Corregir
            </button>
        </div>
    `;

    // Barra de progreso y dots de navegación adaptada
    let htmlProgressDots = `
        <div class="position-fixed bottom-0 start-0 w-100 py-3" style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(12px); border-top: 1px solid rgba(255,255,255,0.1); z-index: 1000; box-shadow: 0 -10px 30px rgba(0,0,0,0.5);">
            <div class="container d-flex flex-column align-items-center justify-content-center">
                <div class="text-white-50 small mb-2 fw-semibold tracking-wide">MAPA DEL TEST</div>
                <div class="d-flex flex-wrap gap-1 justify-content-center" id="progress-dots-container">
                    ${preguntas.map((_, i) => `<a href="javascript:void(0)" onclick="navegarAPregunta(${i})" class="text-decoration-none"><div id="dot-${i}" class="rounded-pill" style="height: 6px; width: 16px; background-color: #1e293b; transition: all 0.3s;"></div></a>`).join('')}
                </div>
            </div>
        </div>
    `;

    testContainer.innerHTML = htmlPreguntas + htmlNavButtons;
    
    // Añadir el panel de navegacion de los dots
    testForm.insertAdjacentHTML('beforeend', htmlProgressDots);

    document.querySelectorAll('.form-check-input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            guardarRespuestaParcial(e);
            actualizarProgressDots();
        });
    });
    
    // Eventos de botones
    document.getElementById('btn-siguiente').addEventListener('click', () => navegarAPregunta(currentIndex + 1));
    document.getElementById('btn-anterior').addEventListener('click', () => navegarAPregunta(currentIndex - 1));
    
    testForm.addEventListener('submit', (event) => {
        event.preventDefault();
        corregirExamen(preguntas.length);
    });
    
    actualizarProgressDots();
}

window.navegarAPregunta = function(newIndex) {
    if (newIndex < 0 || newIndex >= numPreguntasTotal) return;
    
    document.getElementById(`card-pregunta-${currentIndex}`).classList.add('d-none');
    currentIndex = newIndex;
    document.getElementById(`card-pregunta-${currentIndex}`).classList.remove('d-none');
    
    document.getElementById('btn-anterior').disabled = (currentIndex === 0);
    
    if (currentIndex === numPreguntasTotal - 1) {
        document.getElementById('btn-siguiente').classList.add('d-none');
        document.getElementById('btn-finalizar').classList.remove('d-none');
    } else {
        document.getElementById('btn-siguiente').classList.remove('d-none');
        document.getElementById('btn-finalizar').classList.add('d-none');
    }
    
    actualizarProgressDots();
}

function guardarRespuestaParcial(event) {
    const nombrePregunta = event.target.name; 
    const valorRespuesta = event.target.value;

    let respuestas = JSON.parse(localStorage.getItem(CAJA_RESPUESTAS));
    respuestas[nombrePregunta] = valorRespuesta;
    localStorage.setItem(CAJA_RESPUESTAS, JSON.stringify(respuestas));
    console.log(`Guardada respuesta: ${nombrePregunta} = ${valorRespuesta}`);

    // Update UI for the selected option across all browsers
    document.querySelectorAll(`input[name="${nombrePregunta}"]`).forEach(radio => {
        const label = radio.closest('.option-label');
        if (radio.checked) {
            label.classList.add('glow-selected');
        } else {
            label.classList.remove('glow-selected');
        }
    });
}

function cargarProgresoGuardado() {
    const respuestas = JSON.parse(localStorage.getItem(CAJA_RESPUESTAS));
    
    if (respuestas) {
        for (const [nombrePregunta, valorRespuesta] of Object.entries(respuestas)) {
            const radio = document.querySelector(`input[name="${nombrePregunta}"][value="${valorRespuesta}"]`);
            if (radio) {
                radio.checked = true;
                const label = radio.closest('.option-label');
                if (label) label.classList.add('glow-selected');
            }
        }
    }
    
    if (typeof actualizarProgressDots === 'function') {
        actualizarProgressDots();
    }
}

function actualizarProgressDots() {
    let respuestas = JSON.parse(localStorage.getItem(CAJA_RESPUESTAS)) || {};
    for (let i = 0; i < numPreguntasTotal; i++) {
        let dot = document.getElementById(`dot-${i}`);
        if(dot) {
            if (respuestas[`pregunta-${i}`] !== undefined) {
                dot.style.backgroundColor = '#7c3aed';
                dot.style.width = '24px';
                dot.style.boxShadow = '0 0 10px rgba(139, 92, 246, 0.5)';
            } else {
                dot.style.backgroundColor = '#1e293b';
                dot.style.width = '16px';
                dot.style.boxShadow = 'none';
            }
        }
    }
}

let tiempoInicio = Date.now();

async function corregirExamen(totalPreguntas) {
    let tiempoFin = Date.now();
    let tiempoSegundos = Math.floor((tiempoFin - tiempoInicio) / 1000);
    
    let fallos = 0;
    let resultados = []; 
    
    const cardsPreguntas = document.querySelectorAll('.question-card[data-id-pregunta-bd]');
    
    cardsPreguntas.forEach((cardPregunta, index) => {
        const idPregunta = cardPregunta.dataset.idPreguntaBd;
        const respuestaCorrectaIndex = cardPregunta.dataset.respuestaCorrecta;
        let respuestaUsuarioIndex = null;
        let respuestaUsuarioLetra = null;
        const radioSeleccionado = document.querySelector(`input[name="pregunta-${index}"]:checked`);
        let esCorrecta = false;
        
        cardPregunta.classList.remove('border-success', 'border-danger');

        if (!radioSeleccionado) {
            fallos++;
            cardPregunta.classList.add('border-danger', 'border-2');
        } else {
            respuestaUsuarioIndex = radioSeleccionado.value;
            if (respuestaUsuarioIndex === respuestaCorrectaIndex) {
                esCorrecta = true;
                cardPregunta.classList.add('border-success', 'border-2');
            } else {
                fallos++;
                cardPregunta.classList.add('border-danger', 'border-2');
            }
        }
        
        if (respuestaUsuarioIndex === "0") respuestaUsuarioLetra = "A";
        else if (respuestaUsuarioIndex === "1") respuestaUsuarioLetra = "B";
        else if (respuestaUsuarioIndex === "2") respuestaUsuarioLetra = "C";

        resultados.push({
            id_pregunta: idPregunta,
            respuesta_usuario: respuestaUsuarioLetra,
            correcta: esCorrecta
        });
    });

    document.querySelector('#test-form button[type="submit"]').disabled = true;
    document.querySelector('#test-form button[type="submit"]').innerHTML = "Guardando...";

    try {
        const response = await fetch('guardar_test.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                puntuacion: totalPreguntas - fallos,
                resultados: resultados,
                tiempo_segundos: tiempoSegundos
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            
            localStorage.removeItem(CAJA_TEST);
            localStorage.removeItem(CAJA_RESPUESTAS);
            
            let mensaje = `Examen guardado.\nFallos: ${data.fallos}\nNueva racha: ${data.nueva_racha}`;
            
            if (data.logros_nuevos && data.logros_nuevos.length > 0) {
                mensaje += `\n\n🏆 Logros desbloqueados:`;
                data.logros_nuevos.forEach(logro => {
                    mensaje += `\n• ${logro}`;
                });
            }
            
            alert(mensaje);
            
            if (data.alerta_30_dias === true) {
                alert("¡¡¡FELICIDADES!!!\nHas llegado a los 30 exámenes en racha.\n\n¡Has desbloqueado el Simulacro de Examen! Podrás encontrarlo en tu perfil.");
            }
            
            if (data.alerta_50_dias === true) {
                alert("¡¡¡INCREÍBLE!!!\nHas llegado a los 50 exámenes en racha.\n\n¡Eres una leyenda!");
            }
            
            setTimeout(() => {
                window.location.href = 'perfil.php';
            }, 2000);
            
        } else {
            alert('Error al guardar el test: ' + data.error);
            document.querySelector('#test-form button[type="submit"]').disabled = false;
            document.querySelector('#test-form button[type="submit"]').innerHTML = "Corregir Examen";
        }

    } catch (error) {
        alert('Error de red al guardar el test. ' + error.message);
        document.querySelector('#test-form button[type="submit"]').disabled = false;
        document.querySelector('#test-form button[type="submit"]').innerHTML = "Corregir Examen";
    }
}