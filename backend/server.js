// Endpoint de Login (HU-02)
app.post('/api/login', async (req, res) => {
    try {
        const { email, password } = req.body;

        // 1. HU-21: Validación básica de campos
        if (!email || !password) {
            return res.status(400).json({ mensaje: "Email y contraseña son obligatorios." });
        }

        // 2. Buscar al usuario (Simulación de DB)
        const usuario = usuarios.find(u => u.email === email);
        if (!usuario) {
            return res.status(401).json({ mensaje: "Credenciales incorrectas." });
        }

        // 3. HU-21: Verificación de Hash (Innegociable)
        // Comparamos la clave ingresada con el hash
        const esValida = await bcrypt.compare(password, usuario.password);
        
        if (esValida) {
            // 4. Generación de sesión (JWT)
            const token = jwt.sign({ id: usuario.id }, process.env.JWT_SECRET, { expiresIn: '1h' });
            res.json({ mensaje: "¡Bienvenido al Banco Lupita!", token });
        } else {
            res.status(401).json({ mensaje: "Credenciales incorrectas." });
        }

    } catch (error) {
        res.status(500).json({ mensaje: "Error interno en el servidor." });
    }
});
