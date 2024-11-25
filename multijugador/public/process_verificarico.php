<?php

$token = $_POST["token"];

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

// SQL query to update the user's password and reset token information
$sql = "UPDATE users 
        SET is_verified = 1, 
            reset_token_hash = NULL, 
            time_token_expires_at = NULL 
        WHERE user_id = :user_id";

$query = $db->prepare($sql); // Prepare the query

// Bind only the `user_id` parameter, as it is the only placeholder in the SQL query
$query->bindValue(':user_id', $user['user_id'], PDO::PARAM_INT);

// Execute the query
if ($query->execute()) {
    echo "User verification updated successfully.";
} else {
    die("Failed to update user verification.");
}

// Redirect to the desired page
header("Location: /?page=login");
exit(); // Ensures no further processing is done