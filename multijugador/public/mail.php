<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//required files
require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

//Create an instance; passing `true` enables exceptions
if (isset($_POST["send"])) {

  $email = $_POST["email"];
  $token = bin2hex(random_bytes(16)); // Generate random token
  $token_hash = hash("sha256", $token); // Hash the token
  $expiry = date("Y-m-d H:i:s", time() + 30 * 60); // Set expiry 30 minutes from now
  
  // Establish database connection
  $db_connection = 'sqlite:../private/users.db'; // Ensure the correct path to the SQLite database
  $db = new PDO($db_connection);
  
  // Correct SQL query to update reset_token_hash and time_token_expires_at
  $sql = 'UPDATE users SET reset_token_hash = :reset_token_hash, time_token_expires_at = :time_token_expires_at WHERE user_email = :user_email';
  
  $query = $db->prepare($sql); // Prepare the query
  $query->bindValue(':reset_token_hash', $token_hash); // Bind token hash
  $query->bindValue(':time_token_expires_at', $expiry); // Bind token expiry time
  $query->bindValue(':user_email', $email); // Bind email
  
  $query->execute(); // Execute the query
  
  if ($query->rowCount() > 0) {  // Use rowCount to check for affected rows
    $mail = new PHPMailer(true);

    //Server settings
    $mail->isSMTP();                                
    $mail->Host       = 'smtp.gmail.com';           
    $mail->SMTPAuth   = true;                       
    $mail->Username   = 'multijugadorgddv@gmail.com';  
    $mail->Password   = 'rixgizmjndqnbqjn';          
    $mail->SMTPSecure = 'ssl';                      
    $mail->Port       = 465;                        

    //Recipients
    $mail->setFrom('multijugadorgddv@gmail.com', 'Treball_1');
    $mail->addAddress($email);  
    $mail->addReplyTo('multijugadorgddv@gmail.com', 'Treball_1'); 

    //Content
    $mail->isHTML(true);                          
    $mail->Subject = 'Recuperacio de contrasenya';  
    $mail->Body    = <<<END
    Per reestablir la contrassenya fes click  <a href="http://localhost:8000/reset-password.php?token=$token">aqui</a>.
    END;

    // Send the email
    $mail->send();
  }
}

$html = file_get_contents('plantilla_correuEnviat.html', true);
echo $html;

?>