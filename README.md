# 🛒 TiendaWeb - Sistema de Gestión y Ventas en Línea

TiendaWeb es una aplicación **Fullstack PHP + MySQL** diseñada para la administración integral de una tienda en línea. Permite a los administradores gestionar productos, ventas, métricas y usuarios, mientras que los clientes pueden navegar, añadir productos al carrito, realizar compras y ver detalles de pedidos.

---

## 📘 Descripción del Proyecto

**Objetivo:** Facilitar la gestión de productos y ventas de una tienda digital, ofreciendo un panel administrativo con reportes de métricas, descargas en Excel/PDF, y funciones de tracking de duración y cookies.

**Usuarios:**
- **Administrador:** gestiona productos, ventas, proveedores, usuarios y estadísticas.
- **Cliente:** navega por la tienda, añade artículos al carrito, compra y revisa pedidos.

---

## 🖥️ Requisitos del Sistema

| Componente | Requisito mínimo |
|-------------|------------------|
| Sistema operativo | macOS, Windows o Linux |
| PHP | 8.0+ |
| Servidor | Apache / Nginx / PHP Built-in (`php -S`) |
| Base de datos | MySQL 8.0+ |
| Composer | 2.0+ |
| Navegador | Chrome, Firefox o Edge actualizados |

---

## 🧩 Stack Tecnológico

**Frontend:**  
- HTML5, CSS3, JavaScript nativo

**Backend:**  
- PHP nativo  
- Clases en `/clases` para lógica modular (gestión de usuarios, productos, métricas, etc.)

**Base de Datos:**  
- MySQL (script: `db.sql`)

**Dependencias clave (Composer):**  
- TCPDF para generación de PDF  
- PHPSpreadsheet o equivalente para exportación de Excel  

---

## ⚙️ Instalación y Configuración

### 1. Clonar el repositorio

```bash
git clone git@github.com:Jungus80/parcial2web.git
cd parcial2
```

### 2. Instalar dependencias vía Composer

```bash
composer install
```

### 3. Configurar variables de entorno

Crea un archivo `.env` en la raíz del proyecto con el siguiente contenido:

```bash
DB_HOST=localhost
DB_NAME=tiendaweb
DB_USER=root
DB_PASS=
APP_ENV=development
```

> Asegúrate de proteger este archivo en producción (.gitignore debe incluirlo).

### 4. Importar la base de datos

Importa el archivo `db.sql` en tu servidor MySQL:

```bash
mysql -u root -p tiendaweb < db.sql
```

### 5. Ejecutar en entorno local

```bash
php -S localhost:8000
```

Luego accede en tu navegador a:  
👉 http://localhost:8000

---

## 🗄️ Base de Datos

**Motor:** MySQL 8.0  
**Nombre:** `tiendaweb`

**Tablas principales:**
- `usuarios` → datos de autenticación y rol (`admin`, `cliente`)
- `productos` → catálogo de productos
- `ventas` → registros de venta con detalle
- `detalle_venta` → relación venta-producto
- `proveedores`, `categorias`, `metricas`

**Migraciones / Seeds:**  
El script `db.sql` incluye la creación de tablas y algunos datos iniciales (usuarios administradores y productos de ejemplo).

**Relaciones (resumen):**
- Un `usuario` tiene muchas `ventas`
- Una `venta` tiene muchos `detalle_venta`
- Un `producto` pertenece a una `categoria` y `proveedor`

---

## 🚀 Implementación / Deployment

### 💻 Opción local (desarrollo rápido)
Usa **XAMPP**, **Laragon** o el servidor integrado de PHP:

```bash
php -S localhost:8000
```

Coloca el proyecto en el directorio `htdocs` (si es XAMPP) y accede desde `http://localhost/parcial2`.

### 🌐 Opción servidor (producción)
Configura un entorno con:
- Apache/Nginx apuntando al directorio raíz del proyecto.
- Variables de entorno configuradas correctamente.
- Permisos adecuados en `/clases`, `/admin` y `/lang`.

**Ejemplo con Nginx:**
```nginx
server {
    listen 80;
    server_name tiendaweb.com;
    root /var/www/tiendaweb;
    index index.php;
    location / {
        try_files $uri $uri/ =404;
    }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    }
}
```

**Seguridad recomendada:**
- No exponer `.env` ni `db.sql`
- Deshabilitar `display_errors` en producción
- Usar HTTPS y certificados válidos
- Privilegios mínimos para el usuario MySQL del sistema

---

## 🧭 Uso del Sistema

1. Los usuarios se registran o inician sesión (`register.php`, `login.php`).
2. Pueden navegar y agregar productos al carrito (`product_detail.php`, `cart.php`).
3. Completan el proceso de compra en `checkout.php`.
4. Los administradores acceden al panel `/admin/index.php` para:
   - Gestionar categorías y productos.
   - Ver métricas y analytics (`metrics_overview.php`, `cookie_metrics_dashboard.php`).
   - Exportar reportes a PDF o Excel (`sales_report.php`).

**Roles principales:**
- `Admin`: gestiona todo el sistema.
- `Cliente`: navega, compra, consulta sus pedidos.

---

## 🧰 Troubleshooting

| Problema | Causa común | Solución |
|-----------|--------------|-----------|
| Error de conexión a BD | Variables de `.env` incorrectas | Revisa `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` |
| Página en blanco | Error fatal en PHP | Revisa `error_log` o activa `display_errors=On` |
| Estilos no cargan | Rutas absolutas mal configuradas | Usa rutas relativas o revisa configuración del servidor |
| No genera PDF/Excel | Falta librería | Verifica instalación de `composer install` |

---

## 📄 Licencia y Créditos

Proyecto académico - 2025  
Licencia: [MIT](LICENSE)
