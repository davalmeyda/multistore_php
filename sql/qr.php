<?php
require_once 'conexion.php';

$codeQR = $_POST['qr'];
// $codeQR = "BAUDU0KVBZW62U";

function codImg($conexion, $codeQR)
{

    $codQR = $conexion->prepare("SELECT id,original_price FROM `mk_products` WHERE codbar = ?");
    $codQR->bind_param('s',$codeQR);
    $codQR->execute();


    $resultado = $codQR->get_result();
    if ($fila = $resultado->fetch_assoc()) {
        echo json_encode($fila, JSON_UNESCAPED_UNICODE);        
        return true;
    }
    echo "no";
    return false;
}
codImg($conexion,$codeQR);