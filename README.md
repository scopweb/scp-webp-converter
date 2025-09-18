# SCP WebP Converter

Un plugin de WordPress que automáticamente convierte imágenes JPEG y PNG a formato WebP y las sirve inteligentemente según el soporte del navegador.

## Características

- **Conversión automática**: Genera archivos WebP al subir imágenes (todas las medidas)
- **Servicio inteligente**: Sirve WebP cuando el navegador lo soporta, original cuando no
- **Sin duplicación**: Solo crea archivos WebP si no existen previamente
- **Conversión por lotes**: Para biblioteca de medios existente (AJAX + WP-CLI)
- **Conversión individual**: Botones en Media Library para conversión selectiva
- **Limpieza de huérfanos**: Detecta y elimina archivos WebP sin imagen original
- **Visualización de medidas**: Muestra qué tamaños se convertirán antes del proceso
- **Acciones en lote**: Convertir múltiples imágenes seleccionadas desde Media Library
- **Configuración flexible**: Calidad separada para JPEG y PNG
- **Compatibilidad dual**: Soporte total para formatos WebP de Optimus y SCP
- **Compatible**: WordPress 5.8+, PHP 7.4+

## 🔄 Compatibilidad con Optimus WebP

### Migración Sin Pérdidas

Este plugin está **específicamente diseñado** para coexistir con archivos WebP existentes creados por Optimus, permitiendo una migración gradual sin perder contenido.

### Formatos Soportados

El plugin detecta y utiliza automáticamente **ambos formatos** WebP:

| Formato | Ejemplo | Creado por | Estado |
|---------|---------|------------|---------|
| **SCP** | `imagen.jpg.webp` | Este plugin | ✅ Nuevo formato |
| **Optimus** | `imagen.webp` | Plugin Optimus | ✅ Formato existente |

### Detección Inteligente con Prioridad

```
1. 🔍 Busca formato SCP: imagen.jpg.webp
2. 🔍 Si no existe, busca formato Optimus: imagen.webp  
3. ✅ Si encuentra cualquiera, lo sirve al navegador
4. 📷 Si no existe ninguno, sirve imagen original
```

### Proceso de Migración Recomendado

```
Situación actual: Tienes archivos imagen.webp creados por Optimus
                 
Paso 1: Instalar SCP WebP Converter
Paso 2: Configurar calidades deseadas
Paso 3: Desactivar función WebP de Optimus (mantener solo optimización)
Paso 4: ✅ Los archivos Optimus existentes seguirán funcionando automáticamente
Paso 5: Las nuevas imágenes usarán formato SCP (imagen.jpg.webp)
Paso 6: Conversión gradual opcional con "Reconvertir WebP"
```

### Ventajas de la Compatibilidad Dual

- **✅ Sin pérdida de archivos**: Respeta completamente archivos WebP de Optimus
- **✅ Detección automática**: No requiere configuración manual
- **✅ Conversión inteligente**: Solo crea archivos WebP que no existen
- **✅ Migración gradual**: Puedes mantener ambos formatos durante la transición
- **✅ Reconversión selectiva**: Opción de forzar formato SCP cuando quieras

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

### Limpieza de Archivos WebP Huérfanos

Los archivos WebP huérfanos son archivos `.webp` que ya no tienen su imagen original correspondiente (por ejemplo, cuando eliminas imágenes desde WordPress pero los WebP quedan en el servidor).

#### Vía Interface Web:
1. **Acceso**: Configuración → **WebP Huérfanos**
2. **Estadísticas**: Muestra archivos WebP totales vs huérfanos
3. **Escaneo**: Detecta automáticamente archivos sin original
4. **Vista previa**: Lista archivos antes de eliminar
5. **Limpieza segura**: Confirmación antes de eliminación

#### Vía WP-CLI:
```bash
# Escanear y limpiar interactivamente
wp scp-webp/clean-orphans

# Limpiar sin confirmación
wp scp-webp/clean-orphans --yes
```

**¿Qué detecta?**
- Archivos `imagen.jpg.webp` sin su `imagen.jpg` original
- Archivos `imagen.webp` sin su imagen original (formato Optimus)
- Medidas huérfanas: `imagen-300x200.jpg.webp` sin su thumbnail original

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
- **Detección dual con prioridad**:
  1. Busca formato SCP: `imagen.jpg.webp`
  2. Si no existe, busca formato Optimus: `imagen.webp`
  3. Si encuentra cualquiera, lo sirve al navegador
- Funciona en `src` y `srcset` de imágenes responsivas
- **Compatible con archivos WebP existentes de Optimus**

### 3. Estructura de Archivos (Formato Dual)
```
/uploads/2024/01/
   foto.jpg              # Original
   foto.jpg.webp         # Versión WebP formato SCP (nuevo)
   foto.webp             # Versión WebP formato Optimus (existente)
   foto-300x200.jpg      # Thumbnail
   foto-300x200.jpg.webp # Thumbnail WebP formato SCP
   foto-300x200.webp     # Thumbnail WebP formato Optimus
   foto-1024x768.jpg     # Large
   foto-1024x768.jpg.webp
   foto-1024x768.webp
```

**Nota**: Ambos formatos pueden coexistir. El plugin prioriza SCP pero detecta y usa Optimus automáticamente.

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

# Limpiar archivos WebP huérfanos
wp scp-webp/clean-orphans              # Con confirmación interactiva
wp scp-webp/clean-orphans --yes        # Sin confirmación

# Ajustar el batch según recursos del servidor
wp scp-webp/convert-missing --batch=100  # Más rápido
wp scp-webp/convert-missing --batch=25   # Más conservativo
```

## Casos de Uso

### Migración desde Optimus
```
Situación: Tienes archivos imagen.webp creados por Optimus (formato extensión reemplazada)
Solución: 
1. Instala SCP WebP Converter
2. El plugin detecta automáticamente archivos Optimus existentes
3. Desactiva función WebP de Optimus (mantén solo optimización si la necesitas)
4. ✅ Los archivos Optimus seguirán funcionando normalmente
5. Las nuevas imágenes usarán formato SCP (imagen.jpg.webp)
6. Ambos formatos coexisten sin conflictos
```

### Conversión Selectiva
1. Subes una imagen nueva → se convierte automáticamente al formato SCP
2. Cambias una imagen existente → "Reconvertir WebP" desde Media Library
3. Quieres convertir solo algunas específicas → Seleccionar + Bulk Action
4. **Migración gradual**: Usa "Reconvertir WebP (forzar)" para cambiar de Optimus a SCP

### Optimización de Medidas
1. Ve a Ajustes → SCP WebP Converter
2. Revisa la tabla "Medidas activas"
3. Si hay muchas medidas innecesarias, usa plugins como "Disable Media Sizes"
4. El plugin solo convertirá las medidas realmente activas
5. **Detección inteligente**: Respeta medidas existentes en formato Optimus

### Limpieza de Archivos Huérfanos
```
Situación: Has eliminado imágenes desde WordPress pero los archivos WebP quedan en el servidor
Problema: Espacio desperdiciado, archivos innecesarios
Solución:
1. Ve a Configuración → WebP Huérfanos
2. Haz clic en "Escanear archivos huérfanos"
3. Revisa la lista de archivos detectados
4. Confirma la eliminación para liberar espacio
5. Alternativamente usa: wp scp-webp/clean-orphans
```

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

### Archivos WebP acumulándose sin control
1. Ve a Configuración → WebP Huérfanos
2. Usa la herramienta de limpieza para eliminar archivos sin imagen original
3. Considera usar WP-CLI `wp scp-webp/clean-orphans` para sitios grandes
4. Programa limpiezas periódicas si eliminas imágenes frecuentemente

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

### 1.3.0 - Limpieza de Archivos Huérfanos
- **🗑️ NUEVA CARACTERÍSTICA**: Utilidad de limpieza de archivos WebP huérfanos
- **🔍 Detección inteligente**: Identifica archivos WebP sin imagen original correspondiente
- **📊 Interfaz web completa**: Página dedicada en Configuración → WebP Huérfanos
- **📈 Estadísticas detalladas**: Muestra archivos totales, huérfanos y espacio desperdiciado
- **👀 Vista previa segura**: Lista archivos antes de eliminar con información detallada
- **⚡ Comando WP-CLI**: `wp scp-webp/clean-orphans` para sitios grandes
- **🛡️ Confirmaciones**: Protección contra eliminación accidental
- **🔄 Soporte dual**: Detecta tanto formato SCP como Optimus huérfanos
- **📁 Escaneo recursivo**: Busca en toda la estructura de uploads automáticamente

### 1.2.0 - Compatibilidad Dual con Optimus
- **🔄 NUEVA CARACTERÍSTICA**: Compatibilidad dual con formatos WebP de Optimus
- **🔍 Detección inteligente**: Busca formato SCP primero, luego Optimus automáticamente
- **🛡️ Migración sin pérdidas**: Respeta completamente archivos WebP existentes de Optimus
- **⚡ Conversión inteligente**: Solo crea archivos WebP que no existen previamente
- **🔧 Reconversión selectiva**: Opción de forzar migración de Optimus a SCP
- **📋 Conversión individual**: Botones contextuales desde Media Library
- **📊 Visualización de medidas**: Tabla con medidas activas vs desactivadas
- **🎯 Acciones en lote**: Convertir múltiples imágenes seleccionadas
- **🧠 Detección inteligente**: Solo convierte medidas realmente activas
- **🎨 UI mejorada**: Panel con 5 pestañas organizadas (Configuración, Estado, Medidas, Conversión, Ayuda)
- **⚠️ Advertencias automáticas**: Notificaciones para optimización de medidas innecesarias

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