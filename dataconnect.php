<?php

const MYSQL_HOST ='shortline.proxy.rlwy.net';
const MYSQL_NAME ='railway';
const MYSQL_PORT =32370;
const MYSQL_USER ='root';
const MYSQL_PASSWORD ='ZpiuyniKSpJGQDyPvEDRAcpcLYmxmmpI';

try {
    $mysqlClient = new PDO(
        sprintf('mysql:host=%s;dbname=%s;port=%s;charset=utf8', MYSQL_HOST, MYSQL_NAME, MYSQL_PORT),
        MYSQL_USER,
        MYSQL_PASSWORD,
        
    );
    $mysqlClient->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $exception) {
    die('Erreur : ' . $exception->getMessage());
}
?>
