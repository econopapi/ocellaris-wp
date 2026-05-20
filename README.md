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
├── includes/
│   ├── admin/
│   │   ├── ocellaris-admin-hub.php  # Menú admin unificado Ocellaris
│   │   └── text-bar.php             # Lógica modular de Text Bar
│   ├── blocks/
│   │   ├── brands-categories.php    # Registro/render de bloques de categorías y marcas
│   │   └── featured-products.php     # Registro/render del bloque Featured Products
│   ├── msi-promotions/
│       └── admin-page.php           # Configuración MSI MercadoPago
│   ├── theme/
│   │   └── layout.php               # Header/footer, menús y AJAX del header
│   └── woocommerce/
│       ├── checkout.php             # Checkout, shipping y cuenta (hooks WooCommerce)
│       ├── catalog-layout.php       # Layout/estilos del catálogo y hooks del loop
│       └── catalog-filters.php      # Filtros de catálogo y pre_get_posts
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
- Indicadores visuales tipo flecha para entradas con más contenido en categorías
- Carga dinámica de subcategorías vía AJAX

### Footer Personalizado
- Sección CTA para newsletter
- Información de contacto (teléfono, email, dirección)
- Enlaces a redes sociales (Facebook, YouTube, Instagram, TikTok)
- Columnas configurables mediante menús de WordPress
- Badges de métodos de pago aceptados (Visa, Mastercard, American Express, Mercado Pago)
- Sección de copyright y enlaces legales

Los íconos SVG de pago se cargan desde:
- `assets/images/payments/visa.svg`
- `assets/images/payments/mastercard.svg`
- `assets/images/payments/amex.svg`
- `assets/images/payments/mercadopago.svg`

Puedes reemplazar esos archivos por tus SVG finales manteniendo los mismos nombres.

### Barra de Texto Superior (Text Bar)
- Configurable desde **Ocellaris > Text Bar**
- Activar/desactivar desde el panel de administración
- Color de fondo personalizable
- Contenido de texto editable

### Hub Administrativo Ocellaris
- Menú principal único en el dashboard: **Ocellaris**
- Submenús incluidos:
  - **Dashboard** (resumen de módulos)
  - **MSI MercadoPago** (configuración de productos MSI)
  - **Text Bar** (configuración de barra superior)
  - **Documentación** (inventario técnico del tema)
  - **Health Check** (validaciones rápidas no destructivas)

### Optimizaciones de WooCommerce
- Ocultación de opciones de envío en el carrito
- Simplificación del checkout (dirección única)
- Filtro de métodos de envío
- Eliminación automática de imágenes al borrar productos

## Actualizaciones Recientes
- Se movió la limpieza de imágenes al borrar productos desde `functions.php` a `includes/woocommerce/checkout.php`.
- Se modularizó el bloque Featured Products en `includes/blocks/featured-products.php` para continuar desacoplando `functions.php`.
- Se modularizaron los bloques de categorías y marcas (product categories, featured brands, all brands) en `includes/blocks/brands-categories.php`.
- Se modularizaron las customizaciones de layout de catálogo en `includes/woocommerce/catalog-layout.php`.
- Se modularizaron personalizaciones de checkout, envío y cuenta en `includes/woocommerce/checkout.php`.
- Se modularizó header/footer, menús de navegación y AJAX de subcategorías en `includes/theme/layout.php`.
- Se modularizaron los filtros de catálogo y su integración con `pre_get_posts` en `includes/woocommerce/catalog-filters.php`.
- Se modularizó la lógica frontend/checkout de MSI en `includes/msi-promotions/frontend.php` para reducir acoplamiento en `functions.php`.
- Se modularizó la implementación de Text Bar en `includes/admin/text-bar.php` para mantener `functions.php` como entry-point progresivo.
- Se consolidó la administración custom en un único menú principal **Ocellaris** y se retiraron los accesos legacy separados.
- Se agregó un dashboard administrativo de Ocellaris con accesos rápidos a MSI, Text Bar, Documentación y Health Check.
- Se añadieron badges de métodos de pago en el footer: Visa, Mastercard, American Express y Mercado Pago.
- Se añadieron flechas laterales en el menú de categorías para reforzar la indicación de contenido adicional.
- Se ajustó el bloque Featured Brands reduciendo padding y aumentando el tamaño visible de los logos.
- Se ajustó el bloque Featured Products (`blocks/featured-products/style.css`) para reducir espacios entre tarjetas y optimizar altura de imagen en desktop y mobile.
- Se ajustó el bloque Product Categories (`blocks/product-categories/style.css`) para que las imágenes sobresalgan del contenedor circular, manteniendo comportamiento responsive en desktop y mobile.
- Se refactorizó el bloque Featured Products para que `productsToShow` funcione como layout visible (cards por vista), y se active carrusel automáticamente cuando hay más productos que columnas visibles.
- Se mejoró la UX del editor de Featured Products agregando una lista de productos seleccionados con opción de remover rápidamente sin tener que buscarlos otra vez en todo el catálogo.

- Se corrigió el menú lateral en mobile (iPhone): ahora el sidebar respeta el `safe-area-inset-bottom` y tiene padding inferior adicional para que las últimas categorías sean accesibles desde el menú principal.

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

### Ocellaris Featured Products
Bloque de productos destacados con selección manual o filtros automáticos, y carrusel condicional en frontend.

**Atributos:**
- `title` - Título del bloque
- `productsToShow` - Número de tarjetas visibles por vista (layout)
- `filterType` - Tipo de filtro (`manual`, `tags`, `sale`, `featured`)
- `selectedProducts` - Array de IDs de productos (modo manual, sin límite estricto)
- `selectedTags` - Array de IDs de etiquetas (modo tags)
- `randomizeProducts` - Orden aleatorio en frontend

**Comportamiento clave:**
- Si el total de productos es menor o igual a `productsToShow`, se renderiza grid normal.
- Si el total supera `productsToShow`, se activa carrusel con navegación lateral e indicadores por posición.
- En el editor, el panel de selección manual muestra una lista de seleccionados con botón para quitar cada producto rápidamente.

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
  --ocellaris-blue: #1790fa;       /* Azul principal */
  --ocellaris-deep-blue: #003866;  /* Azul oscuro */
  --ocellaris-orange: #f15a22;     /* Naranja/acento */
}
```

---

## Soporte

Para reportar bugs o solicitar nuevas características, contactame en el siguiente email:

**Daniel Limón**  
dani@dlimon.net