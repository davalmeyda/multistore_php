<?php
include 'conexion.php';
//$usu_usuario=$_POST['usuario'];
//$usu_password=$_POST['password'];

//$categorias = $_POST['categorias'];
//$shop_id= "shop9159c51b2c613894d14ca09239c8a027";
$shop_id= $_POST['idtienda'];
//M$categorias = "cat9fbaecd642fa87f1327385e5a7593f48";


$sentencia = $conexion->prepare("SELECT * FROM mk_categories WHERE shop_id=? LIMIT 1");
$sentencia->bind_param('s', $shop_id);
$sentencia->execute();

$resultado = $sentencia->get_result();
if ($fila = $resultado->fetch_assoc()) {
    echo json_encode($fila, JSON_UNESCAPED_UNICODE);
}
$sentencia->close();
$conexion->close();
