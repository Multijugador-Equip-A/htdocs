<?php

$token = $_GET["token"];

if (!$token) {
    die("No token provided");
}

$token_hash = hash("sha256", $token);

// Establish database connection
$db_connection = 'sqlite:../private/users.db'; // Ensure the correct path to the SQLite database
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
</head>

<body>
    <h1>Verificat</h1>
    <form method="post" action="process_verificarico.php">

    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

<button>Verificar</button>
</form>
</body>

</html>
