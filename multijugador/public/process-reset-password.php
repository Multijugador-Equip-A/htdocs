<?php

$token = $_POST["token"];
$password = $_POST["password"];
$password_confirmation = $_POST["password_confirmation"];

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

if ($user === false) {
    die("Token not found");
}

// Check if the token has expired
if (strtotime($user["time_token_expires_at"]) <= time()) {
    die("Token has expired");
}

// Validate the new password
if (strlen($password) < 8) {
    die("Password must be at least 8 characters long");
}

if (!preg_match("/[a-z]/i", $password)) {
    die("Password must contain at least one letter");
}

if (!preg_match("/[0-9]/", $password)) {
    die("Password must contain at least one number");
}

if ($password !== $password_confirmation) {
    die("Passwords do not match");
}

// Hash the new password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// SQL query to update the user's password and reset token information
$sql = "UPDATE users 
        SET user_password = :password_hash,
            reset_token_hash = NULL,
            time_token_expires_at = NULL
        WHERE user_id = :user_id";

$query = $db->prepare($sql); // Prepare the query

// Bind the values
$query->bindValue(':password_hash', $password_hash);
$query->bindValue(':user_id', $user['user_id']); // Assuming `user_id` is the primary key

// Execute the query
if ($query->execute()) {
    echo "Password updated successfully";
} else {
    die("Failed to update password");
}

header("Location: /?page=login");
exit(); // Ensures no further processing is done
