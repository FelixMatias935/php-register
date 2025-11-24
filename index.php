<?php
// index.php
    include("config.php");
    session_start();
    // ...existing code...

    $message = "";
    $message_type = ""; // "sucesso" ou "erro"
 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Ler username (trim + proteção XSS) e senha RAW (sem sanitizar)
        $username = trim(filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS));
        $password_raw = filter_input(INPUT_POST, "password", FILTER_UNSAFE_RAW);

        if ($username === '' || $password_raw === '' || $username === null || $password_raw === null) {
            $message = "Por favor, preencha todos os campos.";
        } else {
            // Validar a senha antes de gerar o hash
            if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W]).{8,}$/", $password_raw)) {
                $message = "Senha fraca! Use pelo menos 8 caracteres, incluindo maiúscula, minúscula, número e símbolo.";
                $message_type = "erro";
            } else {
                $hash = password_hash($password_raw, PASSWORD_DEFAULT);
                
                // Prepared statement com checagem de erros (procedural mysqli)
                $sql = "INSERT INTO formulario (username, password) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ss", $username, $hash);
            
                    // Tentar executar - suportar ambos os modos: com e sem exceções
                    try {
                        $ok = mysqli_stmt_execute($stmt);
                    } catch (mysqli_sql_exception $e) {
                        // mysqli pode estar configurado para lançar exceções em algum local
                        $ok = false;
                        $errno = (int)$e->getCode();
                        error_log("MySQLi exception on execute ({$errno}): " . $e->getMessage());
                    }

                    if ($ok) {
                        $message = "Olá, {$username}, você está registrado agora!";
                        $message_type = "sucesso";
                    } else {
                        // Se não definido pelo catch, buscar errno via procedural
                        if (!isset($errno)) {
                            $errno = mysqli_stmt_errno($stmt);
                        }
                        if ($errno == 1062) {
                            $message = "Este nome de usuário já está sendo usado.";
                            $message_type = "erro";
                        } else {
                            error_log("Stmt execute error ({$errno}): " . mysqli_stmt_error($stmt));
                            $message = "Ocorreu um erro de banco de dados.";
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }

?>

<?php

// ...existing code...
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // ...existing code... (processamento do POST e atribuição de $message)
    } // fim do POST

    // Guardar mensagem na sessão e redirecionar (PRG) somente se veio por POST
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $_SESSION['flash_text'] = $message;
        $_SESSION['flash_type'] = $message_type;
        // Fechar conexão antes do redirect
        mysqli_close($conn);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Recuperar flash message (apenas uma vez) para mostrar no HTML
    if (isset($_SESSION['flash_text'])) {
        $message_text = $_SESSION['flash_text'];
        $message_type = $_SESSION['flash_type'];
        unset($_SESSION['flash_text'], $_SESSION['flash_type']);
    }

    // Fechamento da Conexão caso não tenha sido fechada no PRG
    if (isset($conn)) {
        mysqli_close($conn);
    }
 // ...existing code...
 ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    
    <form class="login-form" action="" method="post">
        <h1>Bem-vindo!</h1>

        <label>
            <p>Nome de Usuário </p>
            <input class="input" type="text" name="username" required>
        </label><br>

        <label>
            <p>Senha: </p>
            <input class="input" id="myInput" type="password" name="password" required>
        </label><br>
        
        <input type="checkbox" onclick="myFunction()"> Mostrar Senha</input><br><br>

        <input class="btn" type="submit" value="Registrar">

        <?php if (!empty($message_text)) : ?>
            <p class="<?= htmlspecialchars($message_type, ENT_QUOTES, 'UTF-8'); ?>">
                <?= htmlspecialchars($message_text, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        
    </form>
    <script>
        function myFunction() {
            let x = document.getElementById("myInput");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</body>
</html>