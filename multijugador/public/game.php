<?php
session_start();

$fitxer = './catala.dic';

// Connectar a la base de dades SQLite
try {
    $db = new PDO('sqlite:../private/games.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Configura el mode Write-Ahead Logging (WAL)
    $db->exec('PRAGMA journal_mode = WAL;');

    // Configura un temps de reintentar (timeout) de 1 segon
    $db->setAttribute(PDO::ATTR_TIMEOUT, 1);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Connexió amb la base de dades fallida: ' . $e->getMessage()]);
    exit();
}

$accio = isset($_GET['action']) ? $_GET['action'] : '';
function imprimir($esJugador1, $game_id, $db){
    if($esJugador1){
        $stmt = $db->prepare('UPDATE games SET temps1 = NULL, temps2 = NULL, hihaescriptor = 1, escriptor = 1 WHERE game_id = :game_id');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $stmt = null; // Tancar el statement
    }
    else{
        $stmt = $db->prepare('UPDATE games SET temps1 = NULL, temps2 = NULL, hihaescriptor = 1, escriptor = 2 WHERE game_id = :game_id');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $stmt = null; // Tancar el statement
    }
}

function postbotons($game_id, $db, $quantitat){
    $retard = $quantitat;
    $next_word_time = time() + $retard;
    $stmt = $db->prepare('UPDATE games SET paraula_visible = 0, next_word_time = :next_word_time, hihaescriptor = 0, escriptor = 0 WHERE game_id = :game_id');
    $stmt->bindValue(':game_id', $game_id);
    $stmt->bindValue(':next_word_time', $next_word_time);
    $stmt->execute();
    $stmt = null; // Tancar el statement
}

switch ($accio) {
    case 'join':
        if (!isset($_COOKIE['username'])) {
            $_COOKIE['username'] = uniqid();
        }

        $player_id = $_COOKIE['username'];
        $game_id = null;

        // Intentar unir-se a un joc existent on player2 sigui null
        $stmt = $db->prepare('SELECT game_id FROM games WHERE player2 IS NULL LIMIT 1');
        $stmt->execute();
        $joc_existent = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null; // Tancar el statement

        if ($joc_existent) {
            // Unir-se al joc existent com a player2
            $game_id = $joc_existent['game_id'];
            $stmt = $db->prepare('UPDATE games SET player2 = :player_id WHERE game_id = :game_id');
            $stmt->bindValue(':player_id', $player_id);
            $stmt->bindValue(':game_id', $game_id);
            $stmt->execute();
            $stmt = null; // Tancar el statement
        } else {
            // Crear un nou joc com a player1
            $game_id = uniqid();
            $stmt = $db->prepare('INSERT INTO games (game_id, player1) VALUES (:game_id, :player_id)');
            $stmt->bindValue(':game_id', $game_id);
            $stmt->bindValue(':player_id', $player_id);
            $stmt->execute();
            $stmt = null; // Tancar el statement
        }

        echo json_encode(['game_id' => $game_id, 'player_id' => $player_id]);
        break;

    case 'status':
        $game_id = $_GET['game_id'];
        $stmt = $db->prepare('SELECT * FROM games WHERE game_id = :game_id');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $joc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null; // Tancar el statement

        $idJugador = $_COOKIE['username'];
        $esJugador1 = $joc['player1'] == $idJugador;
        $esJugador2 = $joc['player2'] == $idJugador;

        //$temps = $_GET['temps'];
        $temps_servidor = floor(microtime(true) * 1000);

        $ping = 0;
        $joc['info'] = ''; //TODO
        if (!$joc) {
            echo json_encode(['error' => 'Joc no trobat']);
        } else {
            // Actualitzar la latencia dels jugadors
            $joc['joc'] = json_encode($joc);
            if($esJugador1){
                $ping = $joc['tempsPAnterior1'] === null ? 0 : $temps_servidor - $joc['tempsPAnterior1'] - 500; // lo que ha tardao en llegar el paquetón 1
                $stmt_update = $db->prepare('UPDATE games SET latencia1 = :latencia, tempsPAnterior1 = :tempsPAnterior WHERE game_id = :game_id');
                $stmt_update->bindValue(':game_id', $game_id);
                $stmt_update->bindValue(':latencia', $ping);
                $stmt_update->bindValue(':tempsPAnterior', $temps_servidor);
                $stmt_update->execute();
            }
            else{
                $ping = $joc['tempsPAnterior2'] === null ? 0 : $temps_servidor - $joc['tempsPAnterior2'] - 500; // lo que ha tardao en llegar el paquetón 2
                $stmt_update = $db->prepare('UPDATE games SET latencia2 = :latencia, tempsPAnterior2 = :tempsPAnterior WHERE game_id = :game_id');
                $stmt_update->bindValue(':game_id', $game_id);
                $stmt_update->bindValue(':latencia', $ping);
                $stmt_update->bindValue(':tempsPAnterior', $temps_servidor);
                $stmt_update->execute();
            }

            // Comprovar si algú ha escrit la paraula i qui ha sigut el més ràpid
            
            if ($joc['player1'] && $joc['player2'] && !$joc['winner']) {
                if($joc['temps1'] || $joc['temps2']) {
                    if($joc['temps1'] && !$joc['temps2']){
                        $periode = $temps_servidor - $joc['temps1'];
                        if($periode > $joc['latencia2']*1.5){
                            imprimir(true, $game_id, $db);
                        }
                    }
                    elseif($joc['temps2'] && !$joc['temps1']){
                        $periode = $temps_servidor - $joc['temps2'];
                        if($periode > $joc['latencia1']*1.5){
                            imprimir(false, $game_id, $db);
                        }
                    }
                    else{
                        if ($joc['temps1'] < $joc['temps2']) {
                            // El jugador 1 ha estat més ràpid
                            imprimir(true, $game_id, $db);
                        } elseif ($joc['temps2'] < $joc['temps1']) {
                            // El jugador 2 ha estat més ràpid
                            imprimir(false, $game_id, $db);
                        } else {
                            if ($joc['latencia1'] > $joc['latencia2']){
                                imprimir(true, $game_id, $db);
                            }
                            elseif ($joc['latencia1'] < $joc['latencia2']){
                                imprimir(true, $game_id, $db);
                            }
                        }
                    }
                } 
            }
                
            // Comprovar si hi ha un guanyador
            if ($joc['life_player2'] <= 0) {
                $stmt = $db->prepare('UPDATE games SET winner = :player_id WHERE game_id = :game_id');
                $stmt->bindValue(':player_id', $joc['player1']);
                $stmt->bindValue(':game_id', $game_id);
                $stmt->execute();
                $stmt = null; // Tancar el statement
            }
            elseif ($joc['life_player1'] <= 0) {
                $stmt = $db->prepare('UPDATE games SET winner = :player_id WHERE game_id = :game_id');
                $stmt->bindValue(':player_id', $joc['player2']);
                $stmt->bindValue(':game_id', $game_id);
                $stmt->execute();
                $stmt = null; // Tancar el statement
            }

            if (!$joc) {
                echo json_encode(['error' => 'Joc no trobat']);
                break;
            }

            $temps_actual = time();
            if (!$joc['paraula_visible'] && ($joc['next_word_time'] === null || $temps_actual >= $joc['next_word_time'])) {

                // Llegeix totes les línies del fitxer en un array
                $linies = file($fitxer, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

                // Tria una línia aleatòria
                $word = $linies[array_rand($linies)];

                // Actualitzar la paraula
                $stmt_update = $db->prepare('UPDATE games SET paraula_visible = 1, word = :word WHERE game_id = :game_id');

                $stmt_update->bindValue(':word', $word);
                $stmt_update->bindValue(':game_id', $game_id);
                $stmt_update->execute();
                
                $joc['word'] = $word;
                $joc['paraula_visible'] = 1;
            }
        }

        echo json_encode([
            'player1' => $joc['player1'],
            'player2' => $joc['player2'],
            'tempsPAnterior1' => $joc['tempsPAnterior1'],
            'tempsPAnterior2' => $joc['tempsPAnterior2'],
            'life' => [$joc['life_player1'], $joc['life_player2']],
            'winner' => $joc['winner'],
            'word' => $joc['word'],
            'visible' => $joc['paraula_visible'],
            'ping' => $ping,
            'time_left' => $joc['next_word_time'] - $temps_actual,
            'hihaescriptor' => $joc['hihaescriptor'], 
            'escriptor' => $joc['escriptor'],
            'info' => $joc['info']
        ]);

    break;

    case 'validateWord':
        $game_id = $_GET['game_id'];
        $word = $_GET['word'];
        $player_id = $_COOKIE['username'];

        $stmt = $db->prepare('SELECT * FROM games WHERE game_id = :game_id AND word = :word');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->bindValue(':word', $word);
        $stmt->execute();
        $joc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null; // Tancar el statement

        
        if (!$joc['word']) {
            echo json_encode(['error' => 'No hi ha cap paraula']);
            break;
        }
        
        if($joc['hihaescriptor'] == 1){
            echo json_encode(['error' => 'L\'altre jugador està pensant']);
            break;
        }
        
        $temps_servidor = floor(microtime(true) * 1000);

        // Determinar quin jugador ha entrat la paraula correcta i actualitzar vida
        if ($joc['player1'] === $player_id && $joc['word'] === $word) {
            $temps = $temps_servidor - $joc['latencia1'];
            $stmt = $db->prepare('UPDATE games SET temps1 = :temps1 WHERE game_id = :game_id');
            $stmt->bindValue(':game_id', $game_id);
            $stmt->bindValue(':temps1', $temps);
            $stmt->execute();
            $stmt = null; // Tancar el statement
        } elseif ($joc['player2'] === $player_id && $joc['word'] === $word) {
            $temps = $temps_servidor - $joc['latencia2'];
            $stmt = $db->prepare('UPDATE games SET temps2 = :temps2 WHERE game_id = :game_id');
            $stmt->bindValue(':game_id', $game_id);
            $stmt->bindValue(':temps2', $temps);
            $stmt->execute();
            $stmt = null; // Tancar el statement
        } else {
            echo json_encode(['error' => 'Jugador invàlid']);
            break;
        }

        $stmt = $db->prepare('SELECT * FROM games WHERE game_id = :game_id');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $joc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null; // Tancar el statement

        postbotons($game_id, $db, 60);

        echo json_encode([
            'hihaescriptor' => $joc['hihaescriptor'], 
            'escriptor' => $joc['escriptor'], 
            'temps1' => $joc['temps1'],
            'temps2' => $joc['temps2'],
            'temps_servidor' => time()
        ]);
        
        break;

    case 'attack_click':
        $game_id = $_GET['game_id'];
        $player_id = $_COOKIE['username'];

        $stmt = $db->prepare('SELECT * FROM games WHERE game_id = :game_id');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $joc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null; // Tancar el statement

        // Logica de vida i atac
        if ($player_id == $joc['player1']) {
            $stmt = $db->prepare('UPDATE games SET life_player2 = life_player2 - player1_damage WHERE game_id = :game_id');
            $stmt->bindValue(':game_id', $game_id);
            $stmt->execute();
            $stmt = null; // Tancar el statement
        }
        else {
            $stmt = $db->prepare('UPDATE games SET life_player1 = life_player1 - player2_damage WHERE game_id = :game_id');
            $stmt->bindValue(':game_id', $game_id);
            $stmt->execute();
            $stmt = null; // Tancar el statement
        }
        
        $stmt = $db->prepare('SELECT life_player1, life_player2 FROM games WHERE game_id = :game_id');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $updatedGame = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null; // Tancar el statement

        // Comprovar si hi ha un guanyador
        if ($joc['life_player2'] <= 0) {
            $stmt = $db->prepare('UPDATE games SET winner = :player_id WHERE game_id = :game_id');
            $stmt->bindValue(':player_id', $joc['player1']);
            $stmt->bindValue(':game_id', $game_id);
            $stmt->execute();
            $stmt = null; // Tancar el statement

        } elseif ($joc['life_player1'] <= 0) {
            $stmt = $db->prepare('UPDATE games SET winner = :player_id WHERE game_id = :game_id');
            $stmt->bindValue(':player_id', $joc['player2']);
            $stmt->bindValue(':game_id', $game_id);
            $stmt->execute();
            $stmt = null; // Tancar el statement

        }

        if (!$joc) {
            echo json_encode(['error' => 'Joc no trobat']);
            break;
        }

        //Mostra nova paraula
        postbotons($game_id, $db, 4);
        echo json_encode([
            'success' => true,
            'life_player1' => $updatedGame['life_player1'],
            'life_player2' => $updatedGame['life_player2']
        ]);
        break;

        if ($joc['winner']) {
            echo json_encode(['error' => 'Joc finalitzat']);
            break;
        }

    case 'heal_click':
        $game_id = $_GET['game_id'];
        $player_id = $_COOKIE['username'];

        $stmt = $db->prepare('SELECT * FROM games WHERE game_id = :game_id');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $joc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null; // Tancar el statement

        

        // Logica de vida i cura
        if ($player_id == $joc['player1']) {
            $stmt = $db->prepare('UPDATE games SET life_player1 = life_player1 + 2 WHERE game_id = :game_id');
        }
        else {
            $stmt = $db->prepare('UPDATE games SET life_player2 = life_player2 + 2 WHERE game_id = :game_id');
        }

        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $stmt = null; // Tancar el statement

        if (!$joc) {
            echo json_encode(['error' => 'Joc no trobat']);
            break;
        }

        if ($joc['winner']) {
            echo json_encode(['error' => 'Joc finalitzat']);
            break;
        }
        
        //Mostra nova paraula
        postbotons($game_id, $db, 4);
        echo json_encode(['success' => true]);
        break;

    case 'block_click':
        $game_id = $_GET['game_id'];
        $player_id = $_COOKIE['username'];

        $stmt = $db->prepare('SELECT * FROM games WHERE game_id = :game_id');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $joc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null; // Tancar el statement

        // Logica de vida i blocqueig
        if ($player_id == $joc['player1']) {
            $stmt = $db->prepare('UPDATE games SET player2_damage = player2_damage - 1 WHERE game_id = :game_id');
        }
        else {
            $stmt = $db->prepare('UPDATE games SET player1_damage = player1_damage - 1 WHERE game_id = :game_id');
        }

        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $stmt = null; // Tancar el statement

        if (!$joc) {
            echo json_encode(['error' => 'Joc no trobat']);
            break;
        }

        if ($joc['winner']) {
            echo json_encode(['error' => 'Joc finalitzat']);
            break;
        }
        
        //Mostra nova paraula
        postbotons($game_id, $db, 4);
        echo json_encode(['success' => true]);
        break;
        
    case 'Buff_click':
        $game_id = $_GET['game_id'];
        $player_id = $_COOKIE['username'];
    
        $stmt = $db->prepare('SELECT * FROM games WHERE game_id = :game_id');
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $joc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = null; // Tancar el statement

        // Logica de vida i blocqueig
        if ($player_id == $joc['player1']) {
            $stmt = $db->prepare('UPDATE games SET player1_damage = player1_damage + 1 WHERE game_id = :game_id');
        }
        else {
            $stmt = $db->prepare('UPDATE games SET player2_damage = player2_damage + 1 WHERE game_id = :game_id');
        }
    
        $stmt->bindValue(':game_id', $game_id);
        $stmt->execute();
        $stmt = null; // Tancar el statement


        if (!$joc) {
            echo json_encode(['error' => 'Joc no trobat']);
            break;
        }

        if ($joc['winner']) {
            echo json_encode(['error' => 'Joc finalitzat']);
            break;
        }
            
        //Mostra nova paraula
        postbotons($game_id, $db, 4);
        echo json_encode(['success' => true]);
        break;

    case '':
        json_encode(['error' => 'Cas no definit']);
        break;

}