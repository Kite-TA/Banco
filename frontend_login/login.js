// frontend_login/login.js

document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const email      = document.getElementById('email').value;
    const password   = document.getElementById('password').value;
    const mensajeDiv = document.getElementById('mensaje');

    try {
        const respuesta = await fetch('../backend_login/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const datos = await respuesta.json();

        if (respuesta.ok) {
            // Guardar sesión en localStorage para que el dashboard la use
            localStorage.setItem('usuarioId',    datos.usuarioId);
            localStorage.setItem('nombre',        datos.nombre);
            localStorage.setItem('numeroCuenta',  datos.numeroCuenta);
            localStorage.setItem('saldo',         datos.saldo);
            localStorage.setItem('cuentaId',      datos.cuentaId);

            mensajeDiv.innerHTML = `<div class="alert alert-success">${datos.mensaje}</div>`;

            setTimeout(() => {
                window.location.href = '../dashboard.html';
            }, 1500);
        } else {
            mensajeDiv.innerHTML = `<div class="alert alert-danger">${datos.error}</div>`;
        }
    } catch (error) {
        console.error('Error:', error);
        mensajeDiv.innerHTML = `<div class="alert alert-danger">Error de conexión con el servidor</div>`;
    }
});
