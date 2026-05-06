document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('cuentaForm');
    const mensajeDiv = document.getElementById('mensaje');
    const tipoSelect = document.getElementById('tipo');
    const tipoDescripcionDiv = document.getElementById('tipoDescripcion');

    const descripciones = {
        ahorro: {
            titulo: 'Cuenta de Ahorro',
            texto: 'La cuenta de ahorro está diseñada para guardar dinero con una rentabilidad moderada. Es ideal si quieres ahorrar y retirar fondos ocasionalmente.',
            funciones: ['Acumulación de intereses', 'Retiros limitados', 'Bajo riesgo'],
            limitaciones: ['No es ideal para pagos diarios', 'Puede tener límites de retiros mensuales']
        },
        corriente: {
            titulo: 'Cuenta Corriente',
            texto: 'La cuenta corriente es para operaciones diarias y pagos frecuentes. Permite cheques o tarjeta y suele no generar intereses.',
            funciones: ['Pagos frecuentes', 'Tarjeta de débito', 'Acceso a servicios bancarios'],
            limitaciones: ['No genera intereses o es muy bajo', 'Puede tener comisiones por mantenimiento']
        }
    };

    function actualizarDescripcion() {
        const tipo = tipoSelect.value;
        if (!tipo || !descripciones[tipo]) {
            tipoDescripcionDiv.classList.add('d-none');
            tipoDescripcionDiv.innerHTML = '';
            return;
        }

        const info = descripciones[tipo];
        tipoDescripcionDiv.classList.remove('d-none');
        tipoDescripcionDiv.innerHTML = `
            <h6>${info.titulo}</h6>
            <p class="mb-2">${info.texto}</p>
            <p class="mb-1"><strong>Funciones:</strong> ${info.funciones.join(', ')}</p>
            <p class="mb-0"><strong>Limitaciones:</strong> ${info.limitaciones.join(', ')}</p>
        `;
    }

    tipoSelect.addEventListener('change', actualizarDescripcion);
    actualizarDescripcion();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        mensajeDiv.innerHTML = '';

        const tipo = tipoSelect.value;
        const saldo = document.getElementById('saldo').value;

        if (!tipo || saldo === '') {
            mostrarMensaje('Selecciona el tipo de cuenta y el saldo inicial', 'danger');
            return;
        }

        if (saldo < 0) {
            mostrarMensaje('El saldo no puede ser negativo', 'danger');
            return;
        }

        try {
            const res = await fetch('account.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ tipo, saldo })
            });

            const data = await res.json();

            if (res.ok) {
                mostrarMensaje(data.mensaje, 'success');
                form.reset();
                actualizarDescripcion();
            } else {
                mostrarMensaje(data.error, 'danger');
            }

        } catch (err) {
            console.error(err);
            mostrarMensaje('Error de conexión con el servidor', 'danger');
        }
    });

    function mostrarMensaje(texto, tipo) {
        mensajeDiv.innerHTML = `<div class="alert alert-${tipo}">${texto}</div>`;
    }
});