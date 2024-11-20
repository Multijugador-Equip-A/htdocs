<?php
//Import PHPMailer classes into the global namespace
//These must be at the top of your script, not inside a function
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// defaults
session_start();
$template = 'home';
$db_connection = 'sqlite:..\private\users.db';
$configuration = array(
    '{FEEDBACK}'          => '',
    '{LOGIN_LOGOUT_TEXT}' => 'Identificar-me',
    '{LOGIN_LOGOUT_URL}'  => '/multijugador/public/?page=login',
    '{METHOD}'            => 'POST',
    '{REGISTER_URL}'      => '/multijugador/public/?page=register',
    '{SITE_NAME}'         => 'La meva pàgina',
    '{DISPLAY_BUTTON}'    => 'none',
    '{NEXT_URL}'          => '/multijugador/public/?page=next',
    '{NEXT_NEXT}'         => 'none',
    '{GIF}'               => 'none',
    '{DISPLAY_REGISTER}'  => '',
    '{RECOVER_PASSWORD_URL}'  => '/multijugador/public/?page=recupPassword',
    '{PHP_SELF}'          => '<?php $_PHP_SELF ?>',
    '{PASSWORD_CHANGED}'  => '/multijugador/public/?page=correuEnviat',
    '{PASSWORD_CHANGED_TEXT}' => 'none',
    '{GAME_TEXT}' => 'GAME',
    '{GAME_URL}'  => '/multijugador/public/?page=game'
);

// Check if the user is already logged in via cookie
if (isset($_COOKIE['username'])) {
    $configuration['{FEEDBACK}'] = 'Sessió oberta com <b>' . htmlentities($_COOKIE['username']) . '</b>';
    $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar "sessió"';
    $configuration['{LOGIN_LOGOUT_URL}'] = '/multijugador/public/?page=logout';
}

// parameter processing
$parameters = $_GET;
$info = $_POST;
if (isset($parameters['page'])) {
    if ($parameters['page'] == 'register') {
        $template = 'register';
        $configuration['{REGISTER_USERNAME}'] = '';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
    } else if ($parameters['page'] == 'login') {
        $template = 'login';
        $configuration['{LOGIN_USERNAME}'] = '';
    } else if ($parameters['page'] == 'recupPassword'){
        $template = 'recupPassword';
        $configuration['{FEEDBACK}'] = '';
        $configuration['{LOGIN_USERNAME}'] = '';
    } else if ($parameters['page'] == 'logout' || $parameters['page'] == 'correuEnviat') {
        // Clear the username cookie
        setcookie('username', '', time() - 3600, "/"); // Expire the cookie
        $configuration['{FEEDBACK}'] = 'Sessió tancada.';
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Identificar-me';
        $configuration['{LOGIN_LOGOUT_URL}'] = '/multijugador/public/?page=login';
    } else if ($parameters['page'] == 'next') {
        $template = 'next';
        // If the user is found, verify the password
        if (isset($_COOKIE['username'])) {
            
                $configuration['{FEEDBACK}'] = 'Sessió oberta com <b>' . htmlentities($_COOKIE['username']) . '</b>';
                $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar "sessió"';
                $configuration['{LOGIN_LOGOUT_URL}'] = '/multijugador/public/?page=logout';
                $configuration['{DISPLAY_BUTTON}'] = 'block';
                $configuration['{NEXT_TEXT}'] = 'Torna a inici';
                $configuration['{NEXT_URL}'] = '/multijugador/public/?';
                $configuration['{GIF}'] = './recursos/patata.gif';
            
        } else {
            // If no user is found with that username
            $configuration['{FEEDBACK}'] = '<mark>ERROR: Usuari desconegut o contrasenya incorrecta</mark>';
        }
    } 
    else if ($parameters['page'] == 'game') {
        $template = 'game';
    }

} else if (isset($info['register'])) {
    $min_password_length = 8;

    if (!isset($info['user_name']) || $info['user_name'] == '') { // No username entered
        $template = 'register'; // Stay on the registration page
        $configuration['{FEEDBACK}'] = '<mark>ERROR: Has d\'introduir un nom d\'usuari</mark>';
        $configuration['{REGISTER_USERNAME}'] = htmlentities($info['user_name']);
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
    } 
    else if (strlen($info['user_password']) < $min_password_length) { // Password too short
        $template = 'register'; // Stay on the registration page
        $configuration['{FEEDBACK}'] = '<mark>ERROR: La contrasenya ha de tenir almenys ' . $min_password_length . ' caràcters.</mark>';
        $configuration['{REGISTER_USERNAME}'] = htmlentities($info['user_name']);
        $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
    } else if (isset($info['captcha']) && ($info['captcha'] == '' || $_SESSION['captcha'] != $info['captcha'])) { //Captcha requirements not fulfilled
        if ($info['captcha'] == '') { //Nothing in Captcha textbox
            // Stay on the registration page and show an error message
            $template = 'register'; // Stay on the registration page
            $configuration['{FEEDBACK}'] = '<mark>ERROR: Has d\'omplir el Captcha</mark>';
            $configuration['{REGISTER_USERNAME}'] = htmlentities($info['user_name']);
            $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
        }
        else if ($_SESSION['captcha'] != $info['captcha']) { //Captcha does not match
            // Stay on the registration page and show an error message
            $template = 'register'; // Stay on the registration page
            $configuration['{FEEDBACK}'] = '<mark>ERROR: El Captcha no coincideix</mark>';
            $configuration['{REGISTER_USERNAME}'] = htmlentities($info['user_name']);
            $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Ja tinc un compte';
        }
    }
    else { //There is a username, a password and the Captcha has been filled correctly
        // Use password_hash to securely hash the password
        $hashed_password = password_hash($info['user_password'], PASSWORD_DEFAULT);

        try {
            $db = new PDO($db_connection);
                $sql = 'INSERT INTO users (user_name, user_password, user_email) VALUES (:user_name, :user_password, :user_email)';
                $query = $db->prepare($sql);
                $query->bindValue(':user_name', $info['user_name']);
                $query->bindValue(':user_password', $hashed_password); 
                $query->bindValue(':user_email', $info['user_email']);

            if ($query->execute()) {
                setcookie('username', $info['user_name'], time() + (100000), "/"); 
                $configuration['{FEEDBACK}'] = 'Creat el compte <b>' . htmlentities($info['user_name']) . '</b>';
                $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar sessió';
                $configuration['{DISPLAY_BUTTON}'] = 'block';
                $configuration['{NEXT_TEXT}'] = 'Avança';
                $configuration['{DISPLAY_REGISTER}'] = 'none';
                $configuration['{NEXT_URL}'] = '/multijugador/public/?page=home';
            } else {
                $configuration['{FEEDBACK}'] = "<mark>ERROR: No s'ha pogut crear el compte <b>"
                    . htmlentities($info['user_name']) . '</b></mark>';
            }
        } catch (PDOException $e) {
            // Handle database error (optional logging can be added here)
            $configuration['{FEEDBACK}'] = "<mark>ERROR: Hi ha hagut un problema amb la base de dades.</mark>";
        }

        //required files
        require 'phpmailer/src/Exception.php';
        require 'phpmailer/src/PHPMailer.php';
        require 'phpmailer/src/SMTP.php';

        //Create an instance; passing `true` enables exceptions
        if (isset($_POST["register"])) {

            $email = $_POST["user_email"];
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
                Verificar el correu fes click  <a href="http://localhost:8000/enter_verificationToken.php?token=$token">aqui</a>.
                END;

                // Send the email
                $mail->send();
            }
        }
    }

} else if (isset($info['login'])) {
    $db = new PDO($db_connection);
    
    // Fetch the user by username first, don't check the password in the SQL query
    $sql = 'SELECT * FROM users WHERE user_name = :user_name';
    $query = $db->prepare($sql);
    $query->bindValue(':user_name', $info['user_name']);
    $query->execute();
    $result_row = $query->fetchObject();

    // If the user is found, verify the password
    if ($result_row) {
        // Verify the password with the hashed password stored in the database
        if (password_verify($info['user_password'], $result_row->user_password)) {
            // Set a cookie for the logged-in user
            setcookie('username', $info['user_name'], time() + (100000), "/"); 
            $configuration['{FEEDBACK}'] = '"Sessió" iniciada com <b>' . htmlentities($info['user_name']) . '</b>';
            $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar "sessió"';
            $configuration['{LOGIN_LOGOUT_URL}'] = '/multijugador/public/?page=logout';
            $configuration['{DISPLAY_BUTTON}'] = 'block';
            $configuration['{NEXT_URL}'] = '/multijugador/public/?page=next';
            $configuration['{NEXT_TEXT}'] = 'Avança';
            $configuration['{DISPLAY_REGISTER}'] = 'none';
        } else {
            // If the password is incorrect
            $configuration['{FEEDBACK}'] = '<mark>ERROR: Usuari desconegut o contrasenya incorrecta</mark>';
        }
    } else {
        // If no user is found with that username
        $configuration['{FEEDBACK}'] = '<mark>ERROR: Usuari desconegut o contrasenya incorrecta</mark>';
    }
} else if (isset($info['game'])) {
    $template = 'game';
} else if (isset($info['RecPasswoord'])){
    //required files
    require 'phpmailer/src/Exception.php';
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';
}
else { // home
    
    $configuration['{LOGIN_LOGOUT_TEXT}'] = 'Tancar "sessió"';
    $configuration['{LOGIN_LOGOUT_URL}'] = '/multijugador/public/?page=logout';
    $configuration['{DISPLAY_BUTTON}'] = 'block';
    $configuration['{NEXT_URL}'] = '/multijugador/public/?page=next';
    $configuration['{NEXT_TEXT}'] = 'Avança';
    $configuration['{DISPLAY_REGISTER}'] = 'none';
}


// process template and show output
$html = file_get_contents('plantilla_' . $template . '.html', true);
$html = str_replace(array_keys($configuration), array_values($configuration), $html);
echo $html;
