ss-development.prompt.md 
---
name: wordpress-development
description: Sigue estas reglas para desarrollar temas de WordPress de forma profesional y organizada.
---


Cuando desarrolles temas para WordPress, debes seguir las siguientes reglas:

1. No arrojes toda la lógica en `functions.php`. Este archivo sólo debe funcionar como el archivo principal de "directivas" que sirva como entry-point de todo el desarrollo.
2. Modulariza de forma lógica todo lo que implementes, tanto en código como a nivel de estructura de directorios.
3. Override te templates dentro de directorio `template-parts/` cuando aplique.
4. Implementación de clases y lógica de negocio pura dentro del directorio `includes/`
5. Implementación de Custom Blocks de Gutenberg en directorio `custom-blocks/`
6. Sigue las convenciones de desarrollo de la comunidad de WordPress.
7. NO USES EMOJIS. Si necesitas representar algo visualmente, mejor usa flaticons o algo que sea más profesional.
8. Siempre que desarrolles para frontend, hazlo priorizando reponsive design y accesibilidad. Toda implementación debe verse bien tanto en desktop como en mobile, y debe ser accesible para personas con discapacidades.
9. Documenta tu código de forma clara y concisa, usando comentarios cuando sea necesario para explicar la lógica o decisiones de diseño.
10. Siempre actualiza el archivo `README.md` con cualquier cambio significativo que hagas en el tema, incluyendo nuevas funcionalidades, cambios en la estructura de archivos o cualquier otra información relevante para otros desarrolladores o usuarios del tema.

¡Gracias!