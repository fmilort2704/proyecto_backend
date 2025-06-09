<?php
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

const JWT_SECRET = 'b7e3f8c2a4d1e6f9c0b2a8e7d3c4f1b6e9a2c3d4b5f6e7a8c9d0b1e2f3a4c5d6';

// Genera un token JWT para un usuario
function generar_jwt($datos_usuario) {
    $payload = [
        "id_usuario" => $datos_usuario['id_usuario'],
        "email" => $datos_usuario['email'],
        "rol" => $datos_usuario['rol'] ?? null,
        "exp" => time() + 60*60*24 // 1 día de validez
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

// Middleware para requerir JWT en endpoints protegidos
function require_jwt() {
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        echo json_encode(["error" => "No autorizado"]);
        exit();
    }
    $matches = [];
    if (!preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        echo json_encode(["error" => "No autorizado"]);
        exit();
    }
    $jwt = $matches[1];
    try {
        $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
        // Opcional: puedes devolver los datos decodificados si los necesitas
        return (array)$decoded;
    } catch (Exception $e) {
        echo json_encode(["error" => "Token inválido"]);
        exit();
    }
}
?>