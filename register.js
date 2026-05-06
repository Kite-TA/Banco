// Espera a que todo el HTML esté cargado
document.addEventListener('DOMContentLoaded', function() {

    // Seleccionar el formulario y el div de mensajes
    const formulario = document.getElementById('registroForm');
    const mensajeDiv = document.getElementById('mensaje');

    // Escuchar el evento 'submit' del formulario
    formulario.addEventListener('submit', async function(evento) {
        // Evitar que el formulario recargue la página
        evento.preventDefault();

        // Limpiar mensaje anterior
        mensajeDiv.innerHTML = '';

        // Obtener los valores de cada campo
        const nombre = document.getElementById('nombre').value.trim();
        const apellidos = document.getElementById('apellidos').value.trim();
        const email = document.getElementById('email').value.trim();
        const telefono = document.getElementById('telefono').value.trim();
        const direccion = document.getElementById('direccion').value.trim();
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // --- Validaciones en el frontend (experiencia de usuario) ---
        if (!nombre || !apellidos || !email || !telefono || !direccion || !password) {
            mostrarMensaje('Todos los campos son obligatorios', 'danger');
            return;
        }
        if (password !== confirmPassword) {
            mostrarMensaje('Las contraseñas no coinciden', 'danger');
            return;
        }
        if (password.length < 6) {
            mostrarMensaje('La contraseña debe tener al menos 6 caracteres', 'danger');
            return;
        }

        // --- Enviar datos al servidor (PHP) usando fetch ---
        try {
            const respuesta = await fetch('registrar.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nombre: nombre,
                    apellidos: apellidos,
                    email: email,
                    telefono: telefono,
                    direccion: direccion,
                    password: password
                })
            });

            const datos = await respuesta.json();

            if (respuesta.ok) {
                mostrarMensaje(datos.mensaje, 'success');
                formulario.reset();   // Limpiar el formulario
                // CAMBIO AQUÍ: Entramos a la carpeta frontend_login
                setTimeout(() => {
                    window.location.href = 'frontend_login/login.html';
                }, 2000);
            } else {
                mostrarMensaje(datos.error || 'Error en el registro', 'danger');
            }
        } catch (error) {
            console.error('Error de red:', error);
            mostrarMensaje('Error de conexión con el servidor', 'danger');
        }
    });

    // Función auxiliar para mostrar mensajes con Bootstrap
    function mostrarMensaje(texto, tipo) {
        mensajeDiv.innerHTML = `<div class="alert alert-${tipo}" role="alert">${texto}</div>`;
    }
});