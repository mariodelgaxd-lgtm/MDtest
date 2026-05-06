# Guía de Despliegue - MDTest

## Despliegue en Render (Gratuito)

### Opción 1: Usando Docker Compose (Recomendado para desarrollo)

1. Clona el repositorio
2. Ejecuta:
   ```bash
   docker-compose up -d
   ```
3. Accede a http://localhost:8080

### Opción 2: Despliegue en Render.com

1. Crea una cuenta en [Render](https://render.com)
2. Crea un nuevo Blueprint desde el repositorio de GitHub
3. Render detectará automáticamente el `render.yaml`
4. El despliegue creará:
   - Un servicio web (PHP + Apache)
   - Un servicio de base de datos (MySQL)
5. Espera a que ambos servicios estén activos

### Configuración manual en Render

Si prefieres configurar manualmente:

**Web Service:**
- Runtime: Docker
- Dockerfile Path: `./Dockerfile`
- Variables de entorno:
  - `DB_HOST`: [host de tu BD en Render]
  - `DB_USER`: mdtest_user
  - `DB_PASS`: [tu contraseña]
  - `DB_NAME`: mdtest2

**PostgreSQL Service (si usas MySQL externo):**
- Usa un servicio MySQL de Render o una BD externa

## Desarrollo Local con XAMPP

1. Coloca el proyecto en `C:\xampp\htdocs\proyectosin`
2. Importa `proyecto2.sql` en phpMyAdmin
3. Accede a http://localhost/proyectosin

## Subir Imágenes de Preguntas

1. Coloca las imágenes en `/imagenes_preguntas/`
2. Actualiza el campo `imagen_url` en la tabla `preguntas`:
   ```sql
   UPDATE preguntas SET imagen_url = 'imagenes_preguntas/preg_001.jpg' WHERE id_pregunta = 1;
   ```

## Notas Importantes

- El plan gratuito de Render "duerme" después de 15 minutos de inactividad
- La primera carga puede tardar 30-60 segundos
- Para mantener el servicio activo, usa un servicio de ping gratuito como UptimeRobot

## Estructura de Archivos

```
proyectosin/
├── index.php          # Página principal
├── test.php           # Tests de práctica
├── simulacro.php      # Simulacro final (30 días de racha)
├── perfil.php         # Perfil de usuario
├── editar_perfil.php  # Editar perfil
├── login.php          # Login
├── register.php       # Registro
├── obtener_test.php   # API de preguntas
├── guardar_test.php   # API de resultados
├── style.css          # Estilos principales
├── Dockerfile         # Imagen Docker
├── docker-compose.yml # Config Docker Compose
└── render.yaml        # Config Render
```
