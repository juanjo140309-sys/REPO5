# Gestión de pedidos de hamburguesería

Aplicativo web en PHP + MySQL para registrar, consultar, editar y eliminar pedidos.

## Funciones
- Dirección de entrega.
- Estado: `Preparando` o `Despachado`.
- Cantidades de hamburguesas sencillas, dobles y mixtas.
- Resumen de pedidos por estado.
- Creación automática de la tabla `pedidos` si no existe.
- Consultas preparadas, escape de HTML y protección CSRF básica.

## Instalación en AlwaysData
1. Entra a tu cuenta de AlwaysData y abre el administrador de archivos o usa FTP.
2. Sube `index.php`, `config.php` y este `README.md` a la carpeta pública de tu sitio (normalmente `www/`).
3. Comprueba que la base de datos `juancxo_hamburguesasgit` existe y que el usuario `juancxo` tiene permisos sobre ella.
4. Abre la URL de tu sitio web. El archivo `config.php` creará la tabla `pedidos` automáticamente.
5. Prueba registrar un pedido, editarlo y cambiar su estado.

## Configuración de la base de datos
Las credenciales indicadas están en `config.php`. Por seguridad, después de probar el aplicativo conviene cambiar la contraseña de la base de datos y actualizar el archivo, especialmente si la contraseña se compartió en chats o se publicó en repositorios.

## Requisitos
- PHP 7.4 o superior (recomendado PHP 8.x).
- Extensión PDO MySQL habilitada.
- Base de datos MySQL creada en AlwaysData.

## Nota
El aplicativo no calcula precios porque no se proporcionaron precios por tipo de hamburguesa.
