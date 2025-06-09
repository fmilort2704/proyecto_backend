<?php
// Lista de orígenes permitidos
$allowed_origins = [
    'http://localhost:3000',       // Desarrollo local
    'http://lvup.kesug.com'        // Producción
];

// Detectar el origen de la petición
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Si el origen está permitido, añadirlo a los headers
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
}

header("Content-Type: application/json");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Responder a preflight (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require "src/funciones_servicios.php";
require __DIR__ . '/Slim/autoload.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';
require "src/jwt_utils.php";

$app = new \Slim\App;


$app->get('/obtener_productos', function () {
    echo json_encode(obtener_productos());
});

$app->get('/obtener_producto/{id_producto}', function ($request) {
    $id_producto = $request->getAttribute("id_producto");
    echo json_encode(obtener_producto_por_id($id_producto));
});

$app->post('/crear_producto', function ($request) {
    require_jwt();
    $nombre = $request->getParam("nombre");
    $descripcion = $request->getParam("descripcion");
    $precio = $request->getParam("precio");
    $stock = $request->getParam("stock");
    $imagen_url = $request->getParam("imagen_url");
    $categoria_id = $request->getParam("categoria_id");
    $fecha_salida = $request->getParam("fecha_salida");
    $empresa = $request->getParam("empresa");
    $pegi = $request->getParam("pegi");
    $descripcion_larga = $request->getParam("descripcion_larga");

    echo json_encode(crear_producto($nombre, $descripcion, $precio, $stock, $imagen_url, $categoria_id, $fecha_salida, $empresa, $pegi, $descripcion_larga));
});

$app->post('/crear_producto_segunda_mano', function ($request) {
    require_jwt();
    $nombre = $request->getParam("nombre");
    $descripcion = $request->getParam("descripcion");
    $precio = $request->getParam("precio");
    $imagen_url = $request->getParam("imagen_url");
    $verificado = $request->getParam("verificado");
    $categoria_id = $request->getParam("categoria_id");
    $vendedor_id = $request->getParam("vendedor_id");
    $descripcion_larga = $request->getParam("descripcion_larga");
    
    echo json_encode(crear_producto_segunda_mano($nombre, $descripcion, $precio, $imagen_url, $verificado, $categoria_id, $vendedor_id, $descripcion_larga));
});

$app->delete('/borrar_producto/{id_producto}', function ($request) {
    require_jwt();
    $id_producto = $request->getAttribute("id_producto");
    echo json_encode(borrar_producto($id_producto));
});

$app->put('/actualizar_producto/{id_producto}', function ($request) {
    require_jwt();
    $id_producto = $request->getAttribute("id_producto");
    $nombre = $request->getParam("nombre");
    $descripcion = $request->getParam("descripcion");
    $descripcion_larga = $request->getParam("descripcion_larga");
    $precio = $request->getParam("precio");
    $imagen_url = $request->getParam("imagen_url");
    $categoria_id = $request->getParam("categoria_id");
    
    echo json_encode(actualizar_producto($id_producto, $nombre, $descripcion, $descripcion_larga, $precio, $imagen_url, $categoria_id));
});

$app->get('/obtener_productos_categoria/{id_categoria}', function ($request) {
    $id_categoria = $request->getAttribute("id_categoria");
    echo json_encode(obtener_productos_por_categoria($id_categoria));
});

$app->get('/obtener_productos_usuarios/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");
    echo json_encode(obtener_productos_por_usuario($id_usuario));
});

$app->post('/registrarse', function ($request) {
    $nombre = $request->getParam("nombre");
    $email = $request->getParam("email");
    $contrasenya = $request->getParam("contrasenya");
    echo json_encode(registrar_usuario($nombre, $email, $contrasenya));
});

$app->post('/login', function ($request) {
    $input = json_decode($request->getBody(), true);
    $email = $input['email'] ?? null;
    $contrasenya = $input['contrasenya'] ?? null;
    $user = login($email, $contrasenya);
    if ($user && isset($user['usuario']['id_usuario'])) {
        $token = generar_jwt($user['usuario']);
        $user['token'] = $token;
        echo json_encode($user);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "Credenciales incorrectas"
        ]);
    }
});

$app->get('/usuarios', function ($request) {
    require_jwt();
    echo json_encode(obtener_usuarios());
});


$app->get('/usuario/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");
    echo json_encode(obtener_usuario($id_usuario));
});

$app->put('/actualizar_usuario/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");

    $input = json_decode($request->getBody(), true);

    $nombre = isset($input["nombre"]) ? $input["nombre"] : null;
    $email = isset($input["email"]) ? $input["email"] : null;
    $contrasenya = isset($input["contrasenya"]) ? $input["contrasenya"] : null;

    echo json_encode(actualizar_usuario($id_usuario, $nombre, $email, $contrasenya));
});


$app->delete('/eliminar_usuario/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");
    echo json_encode(eliminar_usuario($id_usuario));
});

$app->get('/obtener_posts', function () {
    echo json_encode(obtener_posts());
});

$app->get('/obtener_post/{id_post}', function ($request) {
    $id_post = $request->getAttribute("id_post");
    echo json_encode(obtener_post($id_post));
});

$app->post('/crear_post', function ($request) {
    require_jwt();
    $titulo = $request->getParam("titulo");
    $descripcion = $request->getParam("descripcion");
    $comentario = $request->getParam("comentario");
    $imagen_url = $request->getParam("imagen_url");
    $autor_id = $request->getParam("autor_id");

    echo json_encode(crear_post($titulo, $descripcion, $comentario, $imagen_url, $autor_id));
});

$app->put('/actualizar_post/{id_post}', function ($request) {
    require_jwt();
    $id_post = $request->getAttribute("id_post");
    $titulo = $request->getParam("titulo");
    $descripcion = $request->getParam("descripcion");
    $comentario = $request->getParam("comentario");
    $img_publicacion = $request->getParam("img_publicacion");

    echo json_encode(actualizar_post($id_post, $titulo, $descripcion, $comentario, $img_publicacion));
});

$app->put('/editar_valoracion_publicacion/{id_post}', function ($request) {
    require_jwt();
    $id_post = $request->getAttribute("id_post");
    $puntuacion = $request->getParam("puntuacion");
    $numVal = $request->getParam("numVal");

    echo json_encode(editar_valoracion_publicacion($id_post, $puntuacion, $numVal));
});

$app->delete('/eliminar_post/{id_post}', function ($request) {
    require_jwt();
    $id_post = $request->getAttribute("id_post");
    echo json_encode(eliminar_post($id_post));
});

$app->get('/obtener_post_por_usuario/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");
    echo json_encode(obtener_post_por_usuario($id_usuario));
});

$app->get('/comentarios', function ($request){
    require_jwt();
    echo json_encode(obtener_comentarios());
});

$app->get('/obtener_comentario_de_post/{id_post}', function ($request) {
    $id_post = $request->getAttribute("id_post");
    echo json_encode(obtener_comentario_de_post($id_post));
});

$app->get('/obtener_comentarios_usuario/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");
    echo json_encode(obtener_comentario_usuario($id_usuario));
});


$app->post('/crear_comentario', function ($request) {
    require_jwt();
    $contenido = $request->getParam("contenido");
    $post_id = $request->getParam("post_id");
    $autor_id = $request->getParam("autor_id");

    echo json_encode(crear_comentario($contenido, $post_id, $autor_id));
});

$app->delete('/eliminar_comentario/{id_comentario}', function ($request) {
    require_jwt();
    $id_comentario = $request->getAttribute("id_comentario");
    echo json_encode(eliminar_comentario($id_comentario));
});

$app->post('/introducir_carrito', function ($request) {
    require_jwt();
    $usuario_id = $request->getParam("usuario_id");
    $producto_id = $request->getParam("producto_id");
    echo json_encode(aniadir_al_carrito($usuario_id, $producto_id));
});

$app->get('/obtener_productos_carrito/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");
    echo json_encode(obtener_productos_carrito($id_usuario));
});

$app->delete('/eliminar_producto_carrito/{id_producto}', function ($request) {
    require_jwt();
    $id_producto = $request->getAttribute("id_producto");
    echo json_encode(eliminar_producto_carrito($id_producto));
});

$app->put('/incrementar_carrito', function ($request) {
    require_jwt();
    $usuario_id = $request->getParam("usuario_id");
    $producto_id = $request->getParam("producto_id");
    echo json_encode(incrementar_cantidad_carrito($usuario_id, $producto_id));
});

$app->put('/decrementar_carrito', function ($request) {
    require_jwt();
    $usuario_id = $request->getParam("usuario_id");
    $producto_id = $request->getParam("producto_id");
    echo json_encode(decrementar_cantidad_carrito($usuario_id, $producto_id));
});

$app->get('/ultima_venta', function ($request){
    require_jwt();
    echo json_encode(ultima_venta());
});

$app->post('/producir_venta', function ($request) {
    require_jwt();
    $id_venta = $request->getParam("id_venta");
    $total = $request->getParam("total");
    $comprador_id = $request->getParam("comprador_id");
    echo json_encode(producir_venta($id_venta, $total, $comprador_id));
});

$app->post('/producir_venta_detalle', function ($request) {
    require_jwt();
    $id_venta = $request->getParam("id_venta");
    $producto_id = $request->getParam("producto_id");
    $cantidad = $request->getParam("cantidad");
    $vendedor_id = $request->getParam("vendedor_id");
    echo json_encode(producir_venta_detalle($id_venta, $producto_id, $cantidad, $vendedor_id));
});

$app->get('/ver_puntos_usuario/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");
    echo json_encode(ver_puntos_usuario($id_usuario));
});

$app->put('/actualizar_puntos_usuario/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");
    $puntos = $request->getParam("puntos");
    
    echo json_encode(actualizar_puntos_usuario($id_usuario, $puntos));
});

$app->get('/obtener_valoraciones_usuario/{id_usuario}', function ($request) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");
    echo json_encode(obtener_valoraciones_usuario($id_usuario));
});

$app->post('/crear_valoracion', function ($request) {
    require_jwt();
    $puntuacion = $request->getParam("puntuacion");
    $comentario = $request->getParam("comentario");
    $valorador_id = $request->getParam("valorador_id");
    $valorado_id = $request->getParam("valorado_id");

    echo json_encode(crear_valoracion($puntuacion, $comentario, $valorador_id, $valorado_id));
});

$app->put('/procesar_carrito/{id_usuario}', function ($request, $response) {
    require_jwt();
    $id_usuario = $request->getAttribute("id_usuario");

    echo json_encode(procesar_carrito($id_usuario));
});

$app->get('/obtener_stock/{id_producto}', function($request, $response) {
    require_jwt();
    $id_producto = $request->getAttribute("id_producto");

    echo json_encode(obtener_stock($id_producto));
});

$app->run();
?>