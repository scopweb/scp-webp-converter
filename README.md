# SCP WebP Converter

Un plugin de WordPress que automáticamente convierte imágenes JPEG y PNG a formato WebP y las sirve inteligentemente según el soporte del navegador.

## Características

- **Conversión automática**: Genera archivos WebP al subir imágenes (todas las medidas)
- **Servicio inteligente**: Sirve WebP cuando el navegador lo soporta, original cuando no
- **Sin duplicación**: Solo crea archivos WebP si no existen previamente
- **Conversión por lotes**: Para biblioteca de medios existente (AJAX + WP-CLI)
- **Conversión individual**: Botones en Media Library para conversión selectiva
- **Visualización de medidas**: Muestra qué tamaños se convertirán antes del proceso
- **Acciones en lote**: Convertir múltiples imágenes seleccionadas desde Media Library
- **Configuración flexible**: Calidad separada para JPEG y PNG
- **Compatible**: WordPress 5.8+, PHP 7.4+

## Instalación

1. Sube el plugin a `/wp-content/plugins/scp-webp-converter/`
2. Activa el plugin en WordPress
3. Ve a **Ajustes → SCP WebP Converter** para configurar

## Configuración

### Ajustes de Calidad

- **JPEG → WebP**: Calidad recomendada 80-85
- **PNG → WebP**: Para casi sin pérdidas visuales, usa 90-100

### Visualización de Medidas Activas

El plugin muestra una tabla detallada con todas las medidas de imagen que se convertirán:
- **Medidas activas**: Solo las que realmente se generan (respeta plugins de desactivación)
- **Información completa**: Dimensiones, recorte y origen de cada medida
- **Advertencias**: Si hay demasiadas medidas activas para optimizar

### Conversión en Lote

Para convertir imágenes existentes:

1. **Vía Admin**: Ajustes → SCP WebP Converter → "Escanear y convertir faltantes"
2. **Vía WP-CLI**: `wp scp-webp/convert-missing --batch=50`

### Conversión Individual desde Media Library

#### Vista Lista:
- **Botón "🖼️ Convertir a WebP"**: Para imágenes sin archivos WebP
- **Botón "🔄 Reconvertir WebP"**: Para regenerar archivos existentes

#### Modal de Edición:
- **Campo "Estado WebP"**: Muestra estado actual y contador de archivos
- **Conversión inmediata**: Botón directo en el modal de cada imagen

#### Acciones en Lote:
1. Selecciona múltiples imágenes en Media Library
2. Desplegable "Acciones en lote" → "Convertir a WebP"
3. También disponible "Reconvertir a WebP (forzar)"

## Cómo Funciona

### 1. Al Subir Imágenes
- Se detecta si es JPEG/PNG
- Se crean archivos `.webp` para la imagen original y todas las medidas activas
- Formato: `imagen.jpg` → `imagen.jpg.webp`

### 2. Al Mostrar Imágenes
- Se detecta si el navegador soporta WebP (`Accept: image/webp`)
- Se sirve `.webp` si existe y es compatible
- Funciona en `src` y `srcset` de imágenes responsivas

### 3. Estructura de Archivos
```
/uploads/2024/01/
   foto.jpg          # Original
   foto.jpg.webp     # Versión WebP
   foto-300x200.jpg  # Thumbnail
   foto-300x200.jpg.webp
   foto-1024x768.jpg # Large
   foto-1024x768.jpg.webp
```

### 4. Detección Inteligente de Medidas
- **Solo medidas activas**: Ignora medidas desactivadas por otros plugins
- **Respeta configuración**: Solo convierte las medidas que realmente se generan
- **Información transparente**: Muestra qué se convertirá antes del proceso

## Conversión Individual vs Lote

| Método | Cuándo usar | Ventajas |
|--------|-------------|----------|
| **Individual** | Imagen específica, after upload | Control total, inmediato |
| **Lote (Admin)** | Biblioteca pequeña/media | Interface visual, progreso |
| **Lote (WP-CLI)** | Biblioteca grande | Más rápido, sin timeouts |
| **Bulk Actions** | Múltiples específicas | Selectivo, desde Media Library |

## Compatibilidad con Navegadores

- **Con WebP**: Chrome, Firefox, Safari 14+, Edge, Opera
- **Sin WebP**: IE, Safari antiguo → Se sirve imagen original

## Requisitos Técnicos

- WordPress 5.8+
- PHP 7.4+
- Extensión GD o Imagick con soporte WebP
- Permisos de escritura en `/wp-content/uploads/`

## Verificación de Soporte

El plugin incluye verificación automática de:
- Soporte WebP del servidor (GD/Imagick)
- Capacidades de conversión
- Permisos de archivos
- Estado detallado en página de configuración

## WP-CLI

```bash
# Convertir toda la biblioteca (recomendado para sitios grandes)
wp scp-webp/convert-missing --batch=50

# Ajustar el batch según recursos del servidor
wp scp-webp/convert-missing --batch=100  # Más rápido
wp scp-webp/convert-missing --batch=25   # Más conservativo
```

## Casos de Uso

### Conversión Selectiva
1. Subes una imagen nueva → se convierte automáticamente
2. Cambias una imagen existente → "Reconvertir WebP" desde Media Library
3. Quieres convertir solo algunas específicas → Seleccionar + Bulk Action

### Optimización de Medidas
1. Ve a Ajustes → SCP WebP Converter
2. Revisa la tabla "Medidas activas"
3. Si hay muchas medidas innecesarias, usa plugins como "Disable Media Sizes"
4. El plugin solo convertirá las medidas realmente activas

## Resolución de Problemas

### Imágenes WebP no se generan
1. Verifica que GD o Imagick tenga soporte WebP
2. Comprueba permisos de escritura en `/uploads/`
3. Revisa logs de WordPress para errores específicos

### WebP no se sirve en el frontend
1. Verifica que existan los archivos `.webp`
2. Comprueba que el navegador envíe `Accept: image/webp`
3. Asegúrate de que las URLs sean correctas

### Problemas de rendimiento
- Usa WP-CLI para conversiones grandes
- Ajusta el batch size según recursos del servidor
- Considera procesamiento en horarios de baja actividad

### Botones no aparecen en Media Library
1. Verifica que las imágenes sean JPEG o PNG
2. Comprueba que tengas permisos de "upload_files"
3. Limpia cache del navegador

## Seguridad

- Validación estricta de tipos MIME
- Sanitización de parámetros de entrada
- Verificación de permisos de usuario
- Uso de nonces para operaciones AJAX

## Desarrollo

### Estructura del Código
- `scp-webp-converter.php`: Clase principal
- `scp-webp-admin.js`: JavaScript para panel admin
- `scp-webp-media.js`: JavaScript para Media Library
- Hooks de WordPress para integración seamless

### Filtros Disponibles
```php
// Personalizar calidad por imagen específica
add_filter('scp_webp_quality', function($quality, $attachment_id, $mime) {
    return $quality;
}, 10, 3);
```

## Changelog

### 1.2.0
- **Nueva funcionalidad**: Conversión individual desde Media Library
- **Nueva funcionalidad**: Visualización de medidas activas vs desactivadas
- **Nueva funcionalidad**: Acciones en lote para imágenes seleccionadas
- **Mejora**: Detección inteligente de medidas realmente activas
- **Mejora**: Botones contextuales (Convertir/Reconvertir)
- **Mejora**: Estado WebP en modal de edición de medios
- **UI**: Tabla completa con información de medidas
- **UI**: Advertencias automáticas para optimización

### 1.1.0
- Mejoras en documentación
- JavaScript extraído a archivo separado
- Verificación de capacidades del servidor
- Logs de errores mejorados
- Preparación para soporte AVIF futuro

### 1.0.0
- Versión inicial
- Conversión automática WebP
- Servicio inteligente por navegador
- Conversión por lotes

## Soporte

Para reportar bugs o solicitar características:
- Revisa la documentación
- Verifica requisitos técnicos
- Proporciona logs de error cuando sea relevante

## Licencia

GPL v2 o posterior, compatible con WordPress.
