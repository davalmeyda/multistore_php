<?php
require_once 'conexion.php';


$shop_id = $_POST['shop_id'];
$cat_id = $_POST['cat_id'];
$sub_cad_id = "asdf";
$name = $_POST['nomProducto'];
$description = "asdf";
$unit_price = $_POST['nomPrecio'];
$original_price = $_POST['nomPrecio'];
$qr = $_POST['qr'];
$is_discount = 0;
$is_available = 1;
$code = "";
$status = 1;

$imagen = $_POST['imagen'];


function codImg($conexion)
{

    $codImg = $conexion->prepare("SELECT * FROM `core_images` WHERE core_images.img_id LIKE '00%' ORDER BY `img_id` DESC LIMIT 1");
    $codImg->execute();


    $resultado = $codImg->get_result();
    if ($fila = $resultado->fetch_assoc()) {
        // echo json_encode($fila, JSON_UNESCAPED_UNICODE);
        $id = $fila["img_id"];
        $id = (int) $id;
        $id++;

        if ($id >= 10) {
            $id = "0000" . $id;
            $codImg->close();
            return $id;
        } else if ($id >= 100) {
            $id = "000" . $id;
            $codImg->close();
            return $id;
        } else if ($id >= 1000) {
            $id = "00" . $id;
            $codImg->close();
            return $id;
        } else {
            $id = "00000" . $id;
            $codImg->close();
            return $id;
        }
    }
    return "000001";
}

function codProd($conexion)
{

    $codAgregar = $conexion->prepare("SELECT * FROM `mk_products` WHERE mk_products.id LIKE '00%' ORDER BY `id` DESC LIMIT 1");
    $codAgregar->execute();


    $resultado = $codAgregar->get_result();
    if ($fila = $resultado->fetch_assoc()) {
        // echo json_encode($fila, JSON_UNESCAPED_UNICODE);
        $id = $fila["id"];
        $id = (int) $id;
        $id++;

        if ($id >= 10) {
            $id = "0000" . $id;
            $codAgregar->close();
            return $id;
        } else if ($id >= 100) {
            $id = "000" . $id;
            $codAgregar->close();
            return $id;
        } else if ($id >= 1000) {
            $id = "00" . $id;
            $codAgregar->close();
            return $id;
        } else {
            $id = "00000" . $id;
            $codAgregar->close();
            return $id;
        }
    }
    return "000001";
}

function agregar(
    $conexion,
    $qr,
    $id,
    $shop_id,
    $cat_id,
    $sub_cad_id,
    $name,
    $description,
    $unit_price,
    $original_price,
    $is_discount,
    $is_available,
    $code,
    $status, $codImg, $codProd, $imagen
) {

    // // $id = "23423423423";
    // $shop_id = "asdf";
    // $cat_id = "asdf";
    // $sub_cad_id = "asdf";
    // $name = "asdf";
    // $description = "asdf";
    // $unit_price = 12.0;
    // $original_price = 15.0;
    // // $search_tag = "";
    // // $highlight_information = "";
    // $is_discount = 0;
    // // $is_featured = 0;
    // $is_available = 1;
    // $code = "";
    // $status = 1;
    // // $added_date = "2020-03-05";
    // // $added_user_id = "";
    // // $updated_date = "2020-03-05";
    // // $updated_user_id = "";
    // // $updated_flag = 0;
    // // $overall_rating = 0;
    // // $touch_count = 0;
    // // $favourite_count = 0;
    // // $like_count = 0;
    // // $featured_date = "2020-03-05";
    // // $shipping_cost = 0;
    // // $minimum_order = 0;
    // // $product_unit = "";
    // // $product_measurement = "";

    // PRODUCTOS

    
    if($qr==""){
        $agregarProducto = $conexion->prepare("INSERT INTO `mk_products`(`id`,`shop_id`,`cat_id`,`sub_cat_id`,`name`,`description`,`unit_price`,`original_price`,`is_discount`,`is_available`,`code`,`status`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $agregarProducto->bind_param('ssssssddiisi', $id, $shop_id, $cat_id, $sub_cad_id, $name, $description, $unit_price, $original_price, $is_discount, $is_available, $code, $status);
    } else {        
        $agregarProducto = $conexion->prepare("INSERT INTO `mk_products`(`id`,`codbar`,`shop_id`,`cat_id`,`sub_cat_id`,`name`,`description`,`unit_price`,`original_price`,`is_discount`,`is_available`,`code`,`status`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $agregarProducto->bind_param('sssssssddiisi', $id, $qr, $shop_id, $cat_id, $sub_cad_id, $name, $description, $unit_price, $original_price, $is_discount, $is_available, $code, $status);
    }


    $agregarProducto->execute();

    // IMAGENES

    $id = $codImg;
    $path = "../uploads/$id.jpg";
    $actualpath = "$id.jpg";

    $agregarImagen = $conexion->prepare("INSERT INTO `core_images`(`img_id`, `img_parent_id`, `img_type`, `img_path`, `img_width`, `img_height`, `img_desc`) VALUES ('$id', '$codProd', 'Product', '$actualpath', 825, 825, 'desc')");        
    $agregarImagen->execute();

    if ($agregarProducto && $agregarImagen) {
        file_put_contents($path, base64_decode($imagen));
        $agregarProducto->close();
        $agregarImagen->close();
        $conexion->close();
        echo "Agregado";
        return true;
    }
    $agregarProducto->close();
    echo "Error";
    return false;
}
$codImg = codImg($conexion);
$codProd = codProd($conexion);

agregar($conexion, $qr, $codProd, $shop_id, $cat_id, $sub_cad_id, $name, $description, $unit_price, $original_price, $is_discount,  $is_available, $code, $status, $codImg, $codProd, $imagen);

