<?php

$token = $_GET["token"];

if (!$token) {
    die("No token provided");
}

$token_hash = hash("sha256", $token);

// Establish database connection
$db_connection = 'sqlite:..\private\users.db';
$db = new PDO($db_connection);

// SQL query to select the user based on the reset token hash
$sql = "SELECT * FROM users WHERE reset_token_hash = :reset_token_hash";

$query = $db->prepare($sql); // Prepare the query

// Bind the hashed token value to the query
$query->bindValue(':reset_token_hash', $token_hash);

// Execute the query
$query->execute();

// Fetch the result
$user = $query->fetch(PDO::FETCH_ASSOC);

if ($user === null) {
    die("Token not found");
}

// Check if the token has expired
if (strtotime($user["time_token_expires_at"]) <= time()) {
    die("Token has expired");
}

// Preparar el contingut dinàmic
$userName = htmlentities($user["user_name"]);
?>


<!DOCTYPE html>
<html lang="ca" color-mode="user">


<head>
    <!-- dades tècniques de la pàgina -->
    <meta charset="utf-8">
    <title>{SITE_NAME} :: Verificar email</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"><!-- per a dispositius mòbils -->
    <meta name="author" content="Antonio Bueno (UdG)">
    <!-- estètica de la pàgina -->
    <link rel="icon" href="/favicon.png">
    <link rel="stylesheet" href="mvp.css">
    <link rel="stylesheet" href="el_meu.css">
    <!-- per afegir interactivitat a la pàgina -->
    <script defer src="el_meu.js"></script>
    <style>
        /* Centrar el contingut del main */
        main {
            display: flex;
            flex-direction: column;
            align-items: center; /* Centrar horitzontalment */
            padding-top: 20px; /* Distància des de la part superior */
            text-align: center;
        }

        /* Centrar el botó dins del formulari */
        form {
            display: flex;
            flex-direction: column; /* Col·locar els elements del formulari en columna */
            align-items: center; /* Centrar horitzontalment els elements del formulari */
            margin-top: 20px; /* Espai entre el títol i el formulari */
        }

        button {
            padding: 10px 20px;
            font-size: 1rem;
            cursor: pointer;
        }
    </style>
    <script defer src="el_meu.js"></script>
    
</head>

<body>
    <main>
        <header>
            <h1>Verificant <?= $userName ?></h1>
        </header>
        <form method="post" action="process_verificarico.php">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <button>Verificar</button>
        </form>
    </main>
</body>

</html>
