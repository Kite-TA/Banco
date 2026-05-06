// frontend_login/login.js

document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const mensajeDiv = document.getElementById('mensaje');

    try {
        // Salimos de frontend_login y entramos a backend_login
        const respuesta = await fetch('../backend_login/login.php', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        });

        const datos = await respuesta.json();

        if (respuesta.ok) {
            mensajeDiv.innerHTML = `<div class="alert alert-success">${datos.mensaje}</div>`;
            // HU-02: Redirección tras éxito
            setTimeout(() => {
                window.location.href = '../dashboard.html'; 
            }, 2000);
        } else {
            mensajeDiv.innerHTML = `<div class="alert alert-danger">${datos.error}</div>`;
        }
    } catch (error) {
        console.error('Error:', error);
        mensajeDiv.innerHTML = `<div class="alert alert-danger">Error de conexión con el servidor</div>`;
    }
});