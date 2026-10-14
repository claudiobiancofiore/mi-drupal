# Slider administrable (slider_admin)

Módulo para Drupal 10.3+ / 11 que permite gestionar el slider del sitio desde el panel de administración, sin tocar código. Usa solo módulos del núcleo (File, Image, Link): no hace falta instalar nada con Composer.

## Qué hace

- **Contenido › Slider** (`/admin/content/slider`): listado de diapositivas con miniatura, estado y orden por arrastrar y soltar.
- Cada diapositiva tiene: título, texto, imagen (con texto alternativo obligatorio), enlace con texto del botón y casilla "Visible en el sitio".
- **Bloque "Slider"**: se ubica en cualquier región desde *Estructura › Diseño de bloques*. Se configura la rotación automática, los segundos por diapositiva y el nombre para lectores de pantalla.
- Permiso propio **"Administrar las diapositivas del slider"**, para dárselo a editores sin darles acceso de administrador.
- Recorte automático de imágenes a 1600×600 (estilo de imagen *Slider*).
- Caché correcta: al guardar o reordenar diapositivas, el slider se actualiza solo.

## Accesibilidad

Sigue el patrón de carrusel de WAI-ARIA (W3C):

- Botón de pausa/reproducción siempre visible cuando hay rotación automática.
- La rotación se detiene con el mouse encima o con el foco del teclado dentro del slider.
- Si el sistema tiene activado "reducir movimiento", arranca en pausa y sin animación.
- Las diapositivas ocultas no reciben foco ni las leen los lectores de pantalla (`inert`).
- Anuncio del cambio de diapositiva solo cuando lo provoca el usuario (`aria-live`).
- Textos alternativos obligatorios, contraste y foco visible.

## Instalación

1. Copiá la carpeta `slider_admin` dentro de `web/modules/custom/` (o `modules/custom/` si tu Drupal no usa la carpeta `web`). Si `custom` no existe, creala.
2. Entrá a **Ampliar** (`/admin/modules`), buscá "Slider administrable" (o "Slider Admin"), tildalo e instalalo.
   Con Drush: `drush en slider_admin -y`
3. Andá a **Contenido › Slider** y cargá 2 o 3 diapositivas.
4. En **Estructura › Diseño de bloques**, en la región que quieras (por ejemplo *Destacado* o *Contenido*), hacé clic en **Colocar bloque** y elegí **Slider**. Para que aparezca solo en la portada, en *Páginas* poné `<front>`.

## Traducción

Los textos del código están en inglés (la convención de Drupal) y el módulo trae su traducción al español en `translations/es.po`. Si el sitio está en español y tiene el módulo *Interface Translation* activo, se importa sola al instalar.

Si ves textos en inglés: *Configuración › Regional e idioma › Traducción de la interfaz de usuario › Importar*, elegí `translations/es.po`, idioma Español, y vaciá la caché.

## Personalizar

- **Colores**: en el CSS del tema, redefiní las variables, por ejemplo:
  ```css
  .slider-admin { --slider-accent: #37bbed; --slider-ratio: 16 / 6; }
  ```
- **HTML**: copiá `templates/slider-admin.html.twig` a la carpeta `templates` del tema y vaciá la caché.
- **Tamaño del recorte**: *Configuración › Multimedia › Estilos de imagen › Slider (1600×600)*.

## Estructura

```
slider_admin/
├── slider_admin.info.yml          Definición del módulo
├── slider_admin.module            hook_help() y hook_theme()
├── slider_admin.permissions.yml   Permiso "Administrar las diapositivas del slider"
├── slider_admin.links.*.yml       Menú, pestaña y botón "Agregar diapositiva"
├── slider_admin.libraries.yml     CSS y JS del slider
├── config/install/                Estilos de imagen
├── config/schema/                 Esquema de la configuración del bloque
├── src/Entity/Slide.php           Entidad de contenido "Diapositiva"
├── src/SlideListBuilder.php       Listado con arrastrar y soltar
├── src/SlideAccessControlHandler.php  Quién puede ver y editar
├── src/Form/SlideForm.php         Formulario de alta y edición
├── src/Plugin/Block/SliderBlock.php   Bloque que muestra el slider
├── templates/slider-admin.html.twig   HTML del slider
├── css/slider.css · js/slider.js  Estilos y comportamiento (sin librerías externas)
└── translations/es.po             Traducción al español
```

## Desinstalar

Drupal pide eliminar primero las diapositivas: *Ampliar › Desinstalar* muestra el enlace para borrarlas. Los estilos de imagen del módulo se eliminan solos.
