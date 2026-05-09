# XP Store (local_xpstore)

Un plugin de gamificación para Moodle que transforma la experiencia de aprendizaje permitiendo a los estudiantes canjear sus puntos de experiencia (obtenidos a través de `Level Up XP`) por recompensas, mejoras de calificación o contenido especial dentro de cada curso.

## ✨ Características Principales

* **Catálogos Independientes por Curso:** Cada curso tiene su propia configuración, catálogo de productos y paleta de colores de forma aislada.
* **Límites de Compra:** Control total sobre cuántas veces un estudiante puede adquirir un ítem o beneficio específico.
* **Diseño Moderno y Responsivo:** Interfaz limpia con amplios espacios de respiración, tipografía Montserrat nativa y soporte para iconos FontAwesome 6 (estilo *Solid*).
* **Personalización Visual (UI):** Ajuste de color primario, secundario (gradientes interactivos), color de iconos de producto y color de iconos de categoría, gestionables desde la interfaz del curso.
* **Reportes:** Panel completo para profesores con el historial detallado de canjes por estudiante, avatares y enlaces directos a las actividades o al libro de calificaciones.
* **Ecosistema de Widgets Embebidos:** Vistas especializadas listas para integrarse en lecciones o etiquetas mediante Iframes (Tarjetas individuales, Categorías completas y Botón de historial flotante).

---

## 🚀 Instalación

1. Descarga el código fuente del plugin.
2. Extrae o copia la carpeta `xpstore` dentro del directorio `local` de tu instalación de Moodle (la ruta final debe ser `moodle/local/xpstore`).
3. Inicia sesión en Moodle con una cuenta de Administrador.
4. Ve a **Administración del sitio > Notificaciones** (o simplemente carga la página principal) para que Moodle detecte los nuevos archivos.
5. Haz clic en **Actualizar base de datos de Moodle ahora**.

---

## ⚙️ Configuración de la Tienda

Una vez instalado, debe activarse el bloque `Level Up XP`. De esta manera los administradores o profesores con permisos de edición pueden configurar la tienda accediendo al panel de administración exclusivo del curso:

**Ruta de acceso:** `tusitio.com/local/xpstore/config.php?id=ID_DEL_CURSO`

Desde este panel se puede:

1. Definir la paleta de colores de la interfaz.
2. Asignar visualmente los iconos de FontAwesome a las categorías creadas.
3. Construir la cadena de configuración del catálogo de productos (definiendo costos, límites y categorías).

---

## 🧩 Uso de Widgets (Iframes)

Puedes incrustar partes de la tienda directamente dentro de recursos interactivos, páginas o etiquetas de Moodle usando el formato Iframe y las siguientes URLs (reemplazando los valores en mayúsculas por los de tu curso):

### 1. Widget de Tienda Principal

Muestra todas las categorías y tarjetas de recompensas disponibles.

`<iframe src="https://moodle52.missionenglish.net/local/xpstore/widget_category.php?id=2" width="100%" height="700" frameborder="0"></iframe>`

### 2. Widget de Categoría Completa

Muestra todas las tarjetas correspondientes a una categoría específica, con diseño fluido y centrado.

`<iframe src="https://tusitio.com/local/xpstore/widget_category.php?id=ID_CURSO&cat=NombreCategoria" width="100%" height="650" frameborder="0"></iframe>`

### 3. Widget de Tarjeta Individual

Muestra un único producto o recompensa disponible para compra directa.

`<iframe src="https://tusitio.com/local/xpstore/widget.php?id=ID_CURSO&tipo=LETRA&cmid=ID_MODULO" width="100%" height="350" frameborder="0"></iframe>`

### 4. Botón de Historial Flotante

Un acceso directo limpio y minimalista para que el estudiante revise sus canjes previos.

`<iframe src="https://tusitio.com/local/xpstore/widget_history.php?id=ID_CURSO" width="100%" height="100" frameborder="0"></iframe>`

---

## 📝 Notas de Versión

- **v2.0:** Migración a catálogos aislados por curso, implementación de FontAwesome 6, variables CSS dinámicas, rediseño de UI (sin bordes de categoría, títulos centrados) y soporte total responsivo para dispositivos móviles.
