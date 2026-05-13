# Banco Lupita 🏦

Sistema de registro de usuarios para un banco.
Para poder pasar los datos insertados en el formulario a una base de datos, utilice XAMPP

## Requisitos

- XAMPP instalado (PHP + MySQL)

## Instrucciones para configurar en XAMPP

### 1. Clonar el proyecto directamente en la carpeta htdocs

```
/Applications/XAMPP/xamppfiles/htdocs/Banco/
```

El proyecto ya debe estar en esta ubicación.

### 2. Crear la base de datos

1. **Abrir phpMyAdmin:**
   - Iniciar XAMPP
   - En el panel, hacer clic en "Admin" junto a MySQL
   - O acceder a: `http://localhost/phpmyadmin`

2. **Crea la base de datos:**
   - Hacer clic en la pestaña "SQL"
   - Copiar y ejecutar este código:

```sql
CREATE DATABASE banco_db;
USE banco_db;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `apellidos` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `telefono` varchar(20) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);
```

### 3. Iniciar XAMPP

1. Abrir la aplicación XAMPP
2. Hacer clic en "Start" para Apache y MySQL
3. Esperar a que ambos estén en verde

### 4. Acceder al proyecto

Abrir el navegador e ir a:
```
http://localhost/BANCO/register.html
```

### 5. Probar el formulario

1. Haz clic en "Registrarse"
2. Completa el formulario con datos de prueba
3. Si todo funciona, se guardará en la base de datos y se vera el mensaje en verde debajo
4. Puedes verificar en phpMyAdmin: `banco_db → usuarios`


## Troubleshooting

**Error: "Error de conexión a la base de datos"**
- Verifica que MySQL esté corriendo en XAMPP
- Verifica que la base de datos `banco_db` existe en phpMyAdmin

**Error: "Tabla no existe"**
- Ejecuta el código SQL nuevamente en phpMyAdmin

**El formulario no envía datos**
- Abre la consola del navegador (F12)
- Revisa si hay errores
- Verifica que el archivo `registrar.php` esté en la carpeta correcta

## Notas

- No cambien la contraseña de MySQL (por defecto XAMPP no tiene contraseña)
- La base de datos debe llamarse exactamente `banco_db`
- Asegúrense de que Apache Y MySQL estén ambos iniciados (si no se puede iniciar MySQL con XAMPP deben revisar
  si no está corriendo desde antes, por ejemplo si tienen workbench instlalado y el MySQL que usa se inicia en
  automatico al encender la pc)
- Si hay errores, revisen la carpeta `Banco` en logs de XAMPP

