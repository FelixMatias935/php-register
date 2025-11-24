<?php
    mysqli_report(MYSQLI_REPORT_OFF); 


    $db_serve = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "bussinesdb_2";
    $conn = "";

    $conn = mysqli_connect($db_serve,
                            $db_user,
                            $db_pass,
                            $db_name);
    if (!$conn) {
        error_log("DB connect error: " . mysqli_connect_error());
        die("Erro ao conectar ao banco de dados.");
    }
    mysqli_set_charset($conn, "utf8mb4");
    
?>