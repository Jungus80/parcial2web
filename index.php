<?php
session_start();
require_once 'header.php';
require_once 'clases/SaludoTrait.php';
require_once 'clases/Tracker.php'; // Incluir la clase Tracker

// Una clase de ejemplo para demostrar el uso del Trait para saludos
class PaginaPrincipal {
    use SaludoTrait;

    public function mostrarContenido() {
        echo '<p data-translate-key="welcome_message">' . Translator::get('welcome_message') . '</p>';
        echo '<p data-translate-key="greeting">' . $this->generarSaludo() . '</p>';
        if (!isset($_SESSION['user_id'])) {
            echo '<p><a href="login.php" class="btn btn-primary">Iniciar Sesión</a> <a href="register.php" class="btn btn-info">Registrarse</a></p>';
        }
        echo '<p>Idioma actual: ' . Translator::getCurrentLanguage() . '</p>';
    }
}

$pagina = new PaginaPrincipal();

// Lógica de tracking para la página principal
// Suponiendo un userId 0 para visitantes no logueados
$userId = $_SESSION['user_id'] ?? 0;
$tracker = new Tracker();
$referrer = $_SERVER['HTTP_REFERER'] ?? null;
$requestUri = $_SERVER['REQUEST_URI'];

// Lista de extensiones a ignorar
$ignoredExtensions = ['.ico', '.css', '.js', '.png', '.jpg', '.jpeg', '.gif', '.svg'];
$isStaticAsset = false;
foreach ($ignoredExtensions as $ext) {
    if (str_ends_with($requestUri, $ext)) {
        $isStaticAsset = true;
        break;
    }
}

// Solo trackear si no es un activo estático y la cookie esté aceptada
if (!$isStaticAsset && isset($_COOKIE['cookie_accepted'])) {
    $tracker->trackPageView($userId, $requestUri, null, $referrer);
}

?>

<div class="container">
    <h2 data-translate-key="welcome_message"><?= Translator::get('welcome_message') ?></h2>

    <?php 
    $pagina->mostrarContenido(); // Muestra el mensaje de bienvenida y el saludo del Trait 
    ?>

    <h2 data-translate-key="productos_destacados"><?= Translator::get('productos_destacados') ?? 'Productos Destacados' ?></h2>
    <div class="product-grid">
        <?php
        require_once 'clases/ProductManager.php';
        $productManager = new ProductManager();
        $products = $productManager->getAllProducts();

        if (!empty($products)) {
            foreach ($products as $product) {
                $descripcionCorta = mb_strimwidth($product['pro_descripcion'] ?? '', 0, 100, '...');
                $imagen = !empty($product['pro_imagen_url']) ? $product['pro_imagen_url'] : 'https://via.placeholder.com/300x200?text=Sin+imagen';
                
                echo '<div class="product-card">';
                echo '<img src="' . htmlspecialchars($imagen) . '" alt="' . htmlspecialchars($product['pro_nombre']) . '" class="product-image">';
                echo '<div class="product-info">';
                echo '<h3 class="product-title">' . htmlspecialchars($product['pro_nombre']) . '</h3>';
                echo '<p class="product-description">' . htmlspecialchars($descripcionCorta) . '</p>';
                echo '<p class="product-price">';
                if (!empty($product["pro_precio_oferta"]) && $product["pro_precio_oferta"] > 0) {
                    $descuento = round(100 * (1 - ($product["pro_precio_oferta"] / $product["pro_precio_unitario"])));
                    echo '<span style="text-decoration: line-through; color: gray;">$' . number_format($product["pro_precio_unitario"], 2) . '</span>';
                    echo '<strong style="color: red; margin-left: 5px;">$' . number_format($product["pro_precio_oferta"], 2) . '</strong>';
                    echo '<span style="color: green; margin-left: 5px;">(' . $descuento . '% OFF)</span>';
                } else {
                    echo '<strong>$' . number_format($product["pro_precio_unitario"], 2) . '</strong>';
                }
                echo '</p>';
                echo '<a href="product_detail.php?id=' . $product['pro_id'] . '" class="btn btn-primary">Ver Detalle</a>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p data-translate-key="no_products_available">' . Translator::get('no_products_available') . '</p>';
        }
        ?>
    </div>

    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .product-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .product-info {
            padding: 15px;
            text-align: center;
        }
        .product-title {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 10px;
        }
        .product-description {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 15px;
        }
        .product-price {
            font-size: 1.1rem;
            color: #007bff;
            margin-bottom: 12px;
            font-weight: bold;
        }
        .btn.btn-primary {
            background-color: #007bff;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            transition: background-color 0.2s ease;
        }
        .btn.btn-primary:hover {
            background-color: #0056b3;
        }
    </style>
</div>
<?php require_once 'footer.php'; ?>

</body>
</html>
