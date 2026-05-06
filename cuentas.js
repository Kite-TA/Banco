document.addEventListener('DOMContentLoaded', async () => {
    const mensajeDiv = document.getElementById('mensaje');
    const cuentasBody = document.getElementById('cuentasBody');
    const cuentasTabla = document.getElementById('cuentasTabla');

    function mostrarMensaje(texto, tipo = 'info') {
        mensajeDiv.innerHTML = `<div class="alert alert-${tipo}">${texto}</div>`;
    }

    try {
        const response = await fetch('cuentas.php');
        if (!response.ok) {
            throw new Error('No se pudo cargar la lista de cuentas');
        }

        const data = await response.json();
        if (!Array.isArray(data.cuentas) || data.cuentas.length === 0) {
            cuentasTabla.classList.add('d-none');
            mostrarMensaje('No hay cuentas registradas todavía.', 'warning');
            return;
        }

        cuentasTabla.classList.remove('d-none');
        cuentasBody.innerHTML = '';

        data.cuentas.forEach(cuenta => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${cuenta.numero_cuenta}</td>
                <td>${cuenta.tipo.charAt(0).toUpperCase() + cuenta.tipo.slice(1)}</td>
                <td>$${parseFloat(cuenta.saldo).toFixed(2)}</td>
                <td>${cuenta.fecha_creacion}</td>
            `;
            cuentasBody.appendChild(row);
        });
    } catch (error) {
        cuentasTabla.classList.add('d-none');
        mostrarMensaje('Error al cargar las cuentas. Intenta de nuevo más tarde.', 'danger');
        console.error(error);
    }
});
