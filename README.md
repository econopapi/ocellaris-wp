# Ocellaris Custom Astra Theme

Tema hijo de [Astra](https://wpastra.com) desarrollado a medida para el sitio de e-commerce de acuarismo [Ocellaris](https://ocellaris.com.mx). Incluye componentes personalizados de header, footer, bloques de Gutenberg y optimizaciones para WooCommerce.

**Autor:** Daniel Limón  
**Contacto:** dani@dlimon.net  
**Versión:** 1.0.0  
**Licencia:** GPL v2 o posterior

---

## Tabla de Contenidos

1. [Requisitos](#requisitos)
2. [Instalación](#instalación)
3. [Estructura del Proyecto](#estructura-del-proyecto)
4. [Características Principales](#características-principales)
5. [Bloques de Gutenberg](#bloques-de-gutenberg)
6. [Menús de Navegación](#menús-de-navegación)
7. [Personalización](#personalización)
8. [Variables CSS](#variables-css)

---

## Requisitos

- WordPress 6.0 o superior
- Tema padre [Astra](https://wpastra.com) instalado y activo o disponible
- WooCommerce (recomendado para funcionalidad completa)
- PHP 7.4 o superior

---

## Instalación

1. Asegúrate de tener el tema **Astra** instalado en tu WordPress.
2. Descarga este repositorio como archivo `.zip`.
3. En el panel de administración, ve a **Apariencia > Temas > Añadir nuevo > Subir tema**.
4. Sube el archivo `.zip` y activa el tema.

---

## Estructura del Proyecto

```
ocellaris-astra/
├── assets/
│   ├── css/
│   │   ├── custom-footer.css    # Estilos del footer personalizado
│   │   └── custom-header.css    # Estilos del header personalizado
│   ├── images/
│   │   └── reef-background.jpg  # Imagen de fondo para CTA
│   └── js/
│       ├── checkout-shipping-filter.js  # Filtro de envío en checkout
│       └── custom-header.js     # Lógica del menú lateral y búsqueda
├── blocks/
│   ├── featured-brands/         # Bloque de marcas destacadas
│   │   ├── block.js             # Registro y editor del bloque
│   │   ├── carousel.js          # Carrusel con autoplay
│   │   ├── editor.css           # Estilos para el editor
│   │   └── style.css            # Estilos para el frontend
│   └── product-categories/      # Bloque de categorías de producto
│       ├── block.js             # Registro y editor del bloque
│       ├── editor.css           # Estilos para el editor
│       └── style.css            # Estilos para el frontend
├── template-parts/
│   ├── footer-custom.php        # Template del footer
│   └── header-custom.php        # Template del header
├── functions.php                # Funciones principales del tema
├── style.css                    # Hoja de estilos principal y metadatos
├── screenshot.jpg               # Captura para el panel de temas
└── README.md
```

---

## Características Principales

### Header Personalizado
- Reemplaza completamente el header de Astra
- Logo con soporte para `custom_logo`
- Barra de búsqueda integrada
- Botón de acceso/cuenta de usuario
- Icono de carrito de WooCommerce
- Menú lateral (sidebar) con animación slide-in
- Carga dinámica de subcategorías vía AJAX

### Footer Personalizado
- Sección CTA para newsletter
- Información de contacto (teléfono, email, dirección)
- Enlaces a redes sociales (Facebook, YouTube, Instagram, TikTok)
- Columnas configurables mediante menús de WordPress
- Sección de copyright y enlaces legales

### Barra de Texto Superior (Text Bar)
- Configurable desde **Apariencia > Ocellaris Text Bar**
- Activar/desactivar desde el panel de administración
- Color de fondo personalizable
- Contenido de texto editable

### Optimizaciones de WooCommerce
- Ocultación de opciones de envío en el carrito
- Simplificación del checkout (dirección única)
- Filtro de métodos de envío
- Eliminación automática de imágenes al borrar productos

---

## Bloques de Gutenberg

### Ocellaris Product Categories
Muestra categorías de productos de WooCommerce en un grid visual con imágenes circulares.

**Atributos:**
- `title` - Título del bloque
- `subtitle` - Subtítulo opcional
- `selectedCategories` - Array de IDs de categorías

**Uso:** Añadir desde el editor de bloques buscando "Ocellaris Product Categories".

<img width="2816" height="664" alt="Image" src="https://github.com/user-attachments/assets/038c4281-ba31-498f-9b78-1b714d19f250" />

### Ocellaris Featured Brands
Carrusel de marcas destacadas con autoplay y navegación.

**Atributos:**
- `title` - Título del bloque
- `autoplaySpeed` - Velocidad del autoplay en milisegundos (default: 3000)
- `selectedBrands` - Array de IDs de marcas

**Uso:** Añadir desde el editor de bloques buscando "Ocellaris Featured Brands".

<img width="2671" height="545" alt="Image" src="https://github.com/user-attachments/assets/c3135791-f885-4ee9-a34b-526f6e78ec98" />

---

## Menús de Navegación

El tema registra los siguientes menús configurables desde **Apariencia > Menús**:

| Ubicación | Descripción |
|-----------|-------------|
| `sidebar-menu` | Menú principal del sidebar (categorías) |
| `quick-links-menu` | Enlaces rápidos en el sidebar |
| `footer-about` | Columna "Acerca de Ocellaris" en el footer |
| `footer-support` | Columna "Atención al Cliente" en el footer |
| `footer-resources` | Columna "Recursos" en el footer |

Si no se asignan menús, el tema genera contenido de fallback automáticamente.

---

## Personalización

### Logo
Configura el logo desde **Apariencia > Personalizar > Identidad del sitio**.

### Colores
Los colores principales están definidos como variables CSS en `style.css` y pueden sobreescribirse.

### Menús del Footer
Crea menús en **Apariencia > Menús** y asígnalos a las ubicaciones del footer.

---

## Variables CSS

```css
:root {
  --ocellaris-blue: #4592eb;       /* Azul principal */
  --ocellaris-deep-blue: #003866;  /* Azul oscuro */
  --ocellaris-orange: #f15a22;     /* Naranja/acento */
}
```

---

## Soporte

Para reportar bugs o solicitar nuevas características, contactame en el siguiente email:

**Daniel Limón**  
dani@dlimon.net