<?php
session_start();

$mensaje_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $password = $_POST['password'] ?? '';
    $codigo_captcha = $_POST['codigo_captcha'] ?? '';
    $captcha_sesion = $_SESSION['captcha'] ?? '';

    $conexion = mysqli_connect('localhost', 'root', '', 'encuestas');
    
    if (!$conexion) {
        die('Error de conexión a la base de datos: ' . mysqli_connect_error());
    }

    // Aplicar sha1 al password ingresado
    $hashedPassword = sha1($password);

    // Consultar la base de datos para el usuario y la contraseña cifrada
    $consulta = "SELECT * FROM usuario WHERE login = '$usuario' AND password = '$hashedPassword'";
    $resultado = mysqli_query($conexion, $consulta);

    // Verificar si se encuentra el usuario
    if (mysqli_num_rows($resultado) === 0) {
        $mensaje_error = 'Error: Usuario o contraseña incorrectos.';
    } elseif (strcasecmp($codigo_captcha, $captcha_sesion) !== 0) {
        $mensaje_error = "Error, el código de verificación es incorrecto (" . htmlspecialchars($codigo_captcha) . ")";
    } else {
        // Si todo es correcto, mostrar mensaje y redirigir
        echo "<h1>¡Acceso concedido! Bienvenido, $usuario</h1>";
        unset($_SESSION['captcha']);
        exit;
    }
}

// Generar CAPTCHA
$longitud = rand(5, 8);
$caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
$captcha_texto = substr(str_shuffle($caracteres), 0, $longitud);
$_SESSION['captcha'] = $captcha_texto;

// Configuración de la imagen CAPTCHA
$fondo = imagecreatefrompng('fondo.png');
$color_texto = imagecolorallocate($fondo, 0, 0, 0); // Negro
$fuente = 'fuentecaptcha.ttf'; // Fuente TrueType
$tamano_fuente = rand(30, 40);

// Calcular posición del texto
$box = imagettfbbox($tamano_fuente, 0, $fuente, $captcha_texto);
$ancho_texto = abs($box[4] - $box[0]);
$alto_texto = abs($box[5] - $box[1]);
$ancho_imagen = imagesx($fondo);
$alto_imagen = imagesy($fondo);

// Colocar el texto en el CAPTCHA
$x = ($ancho_imagen - $ancho_texto) / 2;
$y = ($alto_imagen + $alto_texto) / 2;
imagettftext($fondo, $tamano_fuente, 0, $x, $y, $color_texto, $fuente, $captcha_texto);

// Convertir la imagen en base64
ob_start();
imagepng($fondo);
$imagen_captcha = base64_encode(ob_get_clean());
imagedestroy($fondo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Encuestas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f0f0f0;
            margin: 0;
        }
        .container {
            text-align: center;
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            margin-bottom: 10px;
        }
        hr {
            border: none;
            height: 2px;
            background: #ccc;
            margin: 15px 0;
        }
        .error {
            color: red;
            margin-top: 10px;
        }
        input[type="text"],
        input[type="password"] {
            padding: 10px;
            width: calc(50% - 22px);
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        button {
            padding: 10px 20px;
            font-size: 16px;
            color: #fff;
            background: #007BFF;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>ACCESO APLICACIÓN ENCUESTAS</h1>
    <hr>
    <form method="post">
        <input type="text" name="usuario" placeholder="Usuario" required>
        <input type="password" name="password" placeholder="Contraseña" required>
        <input type="text" name="codigo_captcha" placeholder="Código CAPTCHA" required>
        <div>
            <br>
            <img src="data:image/png;base64,<?= $imagen_captcha ?>" alt="CAPTCHA">
        </div>
        <br>
        <button type="submit">Enviar</button>
    </form>
    <hr>
    <?php if ($mensaje_error): ?>
        <p class="error"><?= $mensaje_error ?></p>
    <?php endif; ?>
</div>
</body>
</html>
