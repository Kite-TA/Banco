document.addEventListener('DOMContentLoaded', () => {

    const switchCuenta = document.getElementById('switchEstadoCuenta');
    const labelCuenta = document.getElementById('labelEstadoCuenta');

    const botones = document.querySelectorAll(
        '.btn-deposito, .btn-retiro, .btn-transf'
    );

    if (switchCuenta) {

        // Obtener estado guardado
        const estadoGuardado = localStorage.getItem('estadoCuenta');

        // Si no existe estado guardado, iniciar activa
        if (estadoGuardado === null) {

            switchCuenta.checked = true;

            localStorage.setItem(
                'estadoCuenta',
                'activa'
            );

        } else {

            switchCuenta.checked =
                estadoGuardado === 'activa';

        }

        // Actualizar interfaz al cargar
        actualizarInterfaz(
            switchCuenta.checked
        );

        // Evento al cambiar switch
        switchCuenta.addEventListener(
            'change',
            async function () {

                const estaActivo = this.checked;

                const idCuenta =
                    localStorage.getItem('cuentaId') ||
                    localStorage.getItem('cuenta_id');

                if (!idCuenta) return;

                // Actualizar interfaz visual
                actualizarInterfaz(
                    estaActivo
                );

                try {

                    const respuesta = await fetch(
                        'hu26_estado_cuentas/backend_hu26/cambiar_estado.php',
                        {
                            method: 'POST',

                            headers: {
                                'Content-Type': 'application/json'
                            },

                            body: JSON.stringify({
                                cuentaId: idCuenta,
                                estado: estaActivo
                                    ? 'activa'
                                    : 'inactiva'
                            })
                        }
                    );

                    // Leer respuesta como texto
                    const texto =
                        await respuesta.text();

                    console.log(
                        'Respuesta PHP:',
                        texto
                    );

                    // Convertir a JSON
                    const resultado =
                        JSON.parse(texto);

                    // Validar éxito
                    if (!resultado.success) {

                        throw new Error(
                            resultado.error ||
                            'Error al actualizar'
                        );
                    }

                    // Guardar estado SOLO si todo salió bien
                    localStorage.setItem(
                        'estadoCuenta',
                        estaActivo
                            ? 'activa'
                            : 'inactiva'
                    );

                } catch (error) {

                    console.error(error);

                    // Revertir switch
                    this.checked =
                        !estaActivo;

                    // Revertir interfaz
                    actualizarInterfaz(
                        !estaActivo
                    );
                }
            }
        );
    }

    // Función visual
    function actualizarInterfaz(activa) {

        if (activa) {

            labelCuenta.textContent =
                'Cuenta Activa';

            labelCuenta.classList.remove(
                'text-danger'
            );

            labelCuenta.classList.add(
                'text-success'
            );

            botones.forEach(btn => {
                btn.disabled = false;
            });

        } else {

            labelCuenta.textContent =
                'Cuenta Inactiva';

            labelCuenta.classList.remove(
                'text-success'
            );

            labelCuenta.classList.add(
                'text-danger'
            );

            botones.forEach(btn => {
                btn.disabled = true;
            });
        }
    }
});