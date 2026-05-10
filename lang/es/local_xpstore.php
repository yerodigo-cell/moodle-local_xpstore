<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * XP Store (local_xpstore)
 *
 * @package     local_xpstore
 * @copyright   2026 Yeison Díaz
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['action'] = 'Acción';
$string['activity'] = 'Actividad';
$string['add'] = 'Añadir';
$string['addproduct'] = 'Añadir';
$string['audit'] = 'Historial de canjes';
$string['balance'] = 'SALDO';
$string['cancel'] = 'Cancelar';
$string['canjear'] = 'Canjear';
$string['catalog'] = 'Escoge tu Beneficio';
$string['category'] = 'Categoría de la Tienda';
$string['category_placeholder'] = 'Ej: VIP...';
$string['category_short'] = 'Cat:';
$string['categoryicons'] = 'Iconos de Categorías';
$string['chooseactivity'] = 'Seleccionar Actividad';
$string['choosetype'] = 'Seleccionar Tipo';
$string['colaction'] = 'Acción';
$string['colactivity'] = 'Actividad Vinculada';
$string['colcategory'] = 'Categoría de Tienda';
$string['colcost'] = 'Costo';
$string['collabel'] = 'Etiqueta';
$string['color_cat_icon'] = 'Color Iconos Cat.';
$string['colorconfig'] = 'Personalización Visual';
$string['colorsreset'] = 'Colores restaurados a sus valores por defecto.';
$string['colorssaved'] = 'Colores actualizados correctamente.';
$string['coltype'] = 'Tipo';
$string['configtitle'] = 'Configuración de la Tienda XP';
$string['configure'] = 'Configurar';
$string['confirmdelete'] = '¿Realmente desea eliminar este producto?';
$string['confirmdeleteall'] = '¿Estás seguro de que deseas eliminar TODAS las recompensas? Esta acción no se puede deshacer.';
$string['confirmreset'] = '¿Estás seguro de borrar todo el historial del curso?';
$string['confirmresetcolors'] = '¿Estás seguro de que quieres restaurar los colores originales de la tienda?';
$string['copyalert'] = '¡Código copiado al portapapeles! Ahora ve a cualquier Etiqueta o Página de tu curso, abre el editor HTML (</>) y pégalo.';
$string['copysinglecard'] = 'Copiar Tarjeta Sola';
$string['cost'] = 'Costo';
$string['currentcatalog'] = 'Catálogo Actual';
$string['date'] = 'Fecha';
$string['defaultcategory'] = 'Main Catalog';
$string['delete'] = 'Eliminar';
$string['deleteall'] = 'Eliminar Todo';
$string['deletedall'] = 'Todas las recompensas han sido eliminadas del catálogo.';
$string['edit'] = 'Editar';
$string['error'] = 'Error al procesar el canje';
$string['exito'] = '¡Canje exitoso!';
$string['full_store'] = 'Tienda Completa';
$string['gotoactivity'] = 'Ir a la Actividad';
$string['gotogradebook'] = 'Ir al Libro de Calificaciones';
$string['gradepoints'] = 'Bonus (+)';
$string['history'] = 'Historial de Compras';
$string['history_button'] = 'Botón Historial';
$string['icon_award'] = 'Cinta de Premio';
$string['icon_bolt'] = 'Rayo';
$string['icon_cart'] = 'Carrito';
$string['icon_diamond'] = 'Diamante';
$string['icon_gamepad'] = 'Control';
$string['icon_gift'] = 'Regalo';
$string['icon_medal'] = 'Medalla';
$string['icon_money'] = 'Monedas';
$string['icon_shield'] = 'Escudo';
$string['icon_star'] = 'Estrella';
$string['icon_trophy'] = 'Trofeo';
$string['iconcolor'] = 'Color de los iconos';
$string['icons_saved'] = 'Iconos actualizados correctamente.';
$string['insuficiente'] = 'Puntos insuficientes';
$string['label'] = 'Etiqueta';
$string['limit'] = 'Límite';
$string['limitreached'] = 'Has alcanzado el límite máximo de compras para este artículo.';
$string['limitzero'] = '0 = ∞ (Infinito)';
$string['linkedactivity'] = 'Actividad Vinculada';
$string['menuhidden'] = 'Menú: Oculto';
$string['menuvisibility_help'] = 'Activa o desactiva el acceso directo a la tienda en el menú de navegación lateral del curso para los estudiantes.';
$string['menuvisible'] = 'Menú: Visible';
$string['nopurchases'] = 'Aún no se han registrado compras o canjes en este curso.';
$string['norewardscreated'] = 'No hay recompensas creadas aún.';
$string['pluginname'] = 'XP Store';
$string['points'] = 'Puntos';
$string['primarycolor'] = 'Color Principal (Tarjetas e Iconos)';
$string['productadded'] = 'Producto añadido correctamente';
$string['productdeleted'] = 'Producto eliminado';
$string['productupdated'] = 'Recompensa actualizada correctamente.';
$string['quiz_id'] = 'Lista de Productos';
$string['quiz_id_desc'] = '⚠️ <strong>RECOMENDACIÓN:</strong> No edites esta caja manualmente. Usa el panel de "Configuración XP Store" dentro de cualquier curso para agregar o eliminar recompensas fácilmente.<br><br><small><em>Formato interno: TIPOID:COSTO:NOMBRE. Ejemplo: S999:1500:VIP</em></small>';
$string['redemptions'] = 'canjes';
$string['redemptions_count'] = 'Canjes:';
$string['reportsubtitle'] = 'Seguimiento individual de beneficios XP';
$string['reporttitle'] = 'Reporte de Canjes';
$string['resetcolors'] = 'Restaurar por defecto';
$string['resetcycle'] = 'Reiniciar Ciclo';
$string['resethistory'] = 'Borrar historial de canjes';
$string['saldo'] = 'Tu Saldo actual';
$string['savecolors'] = 'Guardar Colores';
$string['saveicons'] = 'Guardar Iconos';
$string['secondarycolor'] = 'Color Secundario (Degradados)';
$string['showinmenu'] = 'Show in course menu';
$string['showinmenu_desc'] = 'If enabled, students will see the shortcut to the store in the navigation menu.';
$string['soldout'] = 'Agotado';
$string['specialcontent'] = 'Contenido Especial';
$string['storeempty_desc'] = 'Aún no hay recompensas configuradas.';
$string['storeempty_title'] = 'Tienda Vacía';
$string['storetitle'] = 'XP Store';
$string['tiendagamificada:manage'] = 'Administrar Tienda';
$string['tiendaxp'] = 'Tienda XP';
$string['type'] = 'Tipo';
$string['type_a'] = 'Tarea (Plazo 24h)';
$string['type_f'] = 'Foro (Histórico)';
$string['type_g'] = 'Nota (Puntos Extra)';
$string['type_help'] = 'Selecciona el tipo de beneficio:<br><br><b>Intento Extra:</b> Funciona con los cuestionarios (Quizzes).<br><b>Plazo 24h:</b> Funciona solo con las tareas (Assignments).<br><b>Bonus (Nota):</b> Añade puntos extra directos a la calificación.<br><b>Reward (Contenido):</b> Habilita el acceso a material oculto o grupos VIP.';
$string['type_q'] = 'Quiz (Intento Extra)';
$string['type_s'] = 'Contenido (Llave VIP)';
$string['update'] = 'Actualizar';
$string['user'] = 'Usuario';
$string['widget_panel_desc'] = 'Haz clic en un botón para copiar el código HTML. Luego pégalo en el editor HTML de cualquier Etiqueta o Página de tu curso.';
$string['widget_panel_title'] = 'Embeber Tienda o Categorías (Widgets)';
$string['widgeterror'] = 'Recompensa no disponible.';
$string['widgetunlocked'] = '¡Desbloqueado!';
$string['widgetunlockeddesc'] = 'Ya puedes usar tu recompensa.';
