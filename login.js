document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const mensajeDiv = document.getElementById('mensaje');

    try {
        const respuesta = await fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const datos = await respuesta.json();

        if (respuesta.ok) {
            // Guardar el token para proteger rutas (HU-17)
            localStorage.setItem('token', datos.token); [cite: 138]
            window.location.href = 'cuentas.html'; // Redirigir al dashboard
        } else {
            mensajeDiv.innerHTML = `<div class="alert alert-danger">${datos.mensaje}</div>`; [cite: 137]
        }
    } catch (error) {
        mensajeDiv.innerHTML = `<div class="alert alert-danger">Error de conexión con el banco.</div>`;
    }
});

