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
$tracker->trackPageView($userId, $_SERVER['REQUEST_URI'], null, $referrer);

?>

<div class="container">
    <h2 data-translate-key="welcome_message"><?= Translator::get('welcome_message') ?></h2>

    <?php 
    $pagina->mostrarContenido(); // Muestra el mensaje de bienvenida y el saludo del Trait 
    ?>

    <h2 data-translate-key="productos_destacados"><?= Translator::get('productos_destacados') ?? 'Productos Destacados' ?></h2>
    <div class="product-list">
        <?php
        require_once 'clases/ProductManager.php';
        $productManager = new ProductManager();
        $products = $productManager->getAllProducts();

        if (!empty($products)) {
            foreach ($products as $product) {
                echo '<div>';
                echo '<h3><a href="product_detail.php?id=' . $product['pro_id'] . '">' . $product['pro_nombre'] . '</a></h3>';
                echo '<p><strong data-translate-key="price">'. Translator::get('price') . ':</strong> $' . $product['pro_precio_unitario'] . '</p>';
                echo '<p><strong data-translate-key="stock">'. Translator::get('stock') . ':</strong> ' . $product['pro_cantidad_stock'] . '</p>';
                echo '</div>';
            }
        } else {
            echo '<p data-translate-key="no_products_available">'. Translator::get('no_products_available') .'</p>';
        }
        ?>
    </div>
</div>
<?php require_once 'footer.php'; ?>

</body>
</html>
