let idJoc, idJugador, identitat = 0;
let vida = [10, 10];
let guanyador = null;
let paraulaActual = "";

const textEstat = document.getElementById('estat');
const divJoc = document.getElementById('joc');
const mostraParaula = document.getElementById('currentWord');
const paraula = document.getElementById('seccioParaula');
const vidaJugador1 = document.getElementById('vidaJugador1');
const healthBar1 = document.getElementById('healthBar1');
const vidaJugador2 = document.getElementById('vidaJugador2');
const healthBar2 = document.getElementById('healthBar2');
const timer = document.getElementById('time_left');

const attackButton = document.getElementById('AttackButton');
const healButton = document.getElementById('HealButton');
const blockButton = document.getElementById('BlockButton');
const buffButton = document.getElementById('BuffButton');
let buttonClickable = false;

function enableButton() {
    attackButton.disabled = false;
    healButton.disabled = false;
    blockButton.disabled = false;
    buffButton.disabled = false;
    buttonClickable = true;
    paraula.disabled = true;
}

function disableButton() {
    attackButton.disabled = true;
    healButton.disabled = true;
    blockButton.disabled = true;
    buffButton.disabled = true;
    buttonClickable = false;
    paraula.disabled = false;
}

attackButton.onclick = function() {
    if (!buttonClickable) return; // Prevent action if not clickable
    disableButton(); // Make the button unclickable after use
    // Send click to the server
    fetch(`game.php?action=attack_click&game_id=${idJoc}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } 
        });
};

healButton.onclick = function() {
    if (!buttonClickable) return; // Prevent action if not clickable
    disableButton(); // Make the button unclickable after use
    // Send click to the server
    fetch(`game.php?action=heal_click&game_id=${idJoc}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } 
        });
};

blockButton.onclick = function() {
    if (!buttonClickable) return; // Prevent action if not clickable
    disableButton(); // Make the button unclickable after use
    // Send click to the server
    fetch(`game.php?action=block_click&game_id=${idJoc}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } 
        });
};

buffButton.onclick = function() {
    if (!buttonClickable) return; // Prevent action if not clickable
    disableButton(); // Make the button unclickable after use
    // Send click to the server
    fetch(`game.php?action=Buff_click&game_id=${idJoc}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
            } 
        });
};

// Connectar al servidor del joc
function unirseAlJoc() {   
        console.log("Funció unirseAlJoc() cridada");
        fetch('game.php?action=join')
        .then(response => {
            if  (response.ok ){   
                json_resp = response.json()
                return json_resp
            } else {
                response.text()
                .then(text => 
                    console.log(text)
                )
            }
        })
        .then(data => {                                        
            idJoc = data.game_id;
            idJugador = data.player_id;
            comprovarEstatDelJoc();
        });
}

function isJson(str) {
    try {
      JSON.parse(str);
    } catch (e) {
      return false;
    }  
    return true;
  }
function handle_game(joc) {
    if (joc.error) {
        textEstat.innerText = joc.error;
        return;
    }

    vida = [joc.life[0], joc.life[1]];
    guanyador = joc.winner;

    vidaJugador1.textContent = vida[0];
    vidaJugador2.textContent = vida[1];

    updateHealthBar(healthBar1, vida[0]);
    updateHealthBar(healthBar2, vida[1]);            

    if (joc.time_left) timer.innerText = joc.time_left;

    if (guanyador) {
        if (guanyador === idJugador) {
            textEstat.innerText = 'Has guanyat!';
        } else {
            textEstat.innerText = 'Has perdut!';
        }
        return;
    }

    if (joc.player1 === idJugador) {
        if (joc.player2) {
            textEstat.innerText = `Joc en curs...   Ets el jugador ${joc.player1}`;
            divJoc.style.display = 'block';
        } else {
            textEstat.innerText = `Ets el ${joc.player1}. Esperant el Jugador 2...`;
            identitat = 1;
        }
    } else if (joc.player2 === idJugador) {
        textEstat.innerText = `Joc en curs...   Ets el jugador ${joc.player2}`;
        divJoc.style.display = 'block';
        identitat = 2;
    } else {
        textEstat.innerText = 'Espectant...';
        divJoc.style.display = 'block';
    }

    // Gestionar la visualització de la paraula a escriure
    if (joc.visible == 1) {
        paraula.placeholder = joc.word;
        paraula.style.color = 'Black';

        mostraParaula.innerText = joc.word;
        mostraParaula.style.color = 'black';

        timer.innerText = "";

    } else {
        paraula.value = "";
        mostraParaula.innerText = "";
    }

    if(joc.hihaescriptor){
        if(joc.escriptor == identitat){
            enableButton();
        }
        else{
            disableButton();
        }
    }

    setTimeout(comprovarEstatDelJoc, 500);
}

// Comprovar l'estat del joc cada mig segon i actualitzar la vida dels jugadors
function comprovarEstatDelJoc() {
	var now = new Date();
	var time = now.getTime();

    fetch(`game.php?action=status&game_id=${idJoc}&temps=${time}&player=${idJugador}`)
        .then(response => {
            //console.log(response);
            if (!response.ok){
                console.error(`Response failed: ${response.status}` ,{response})
                return}
            response.json().then(joc => { 
                if (joc.info != '') console.log(joc.info);
                handle_game(joc);
            });
        })
        
}

function verificaParaula(paraula) {
    if(event.key === 'Enter') {
        var now = new Date();
	    var time = now.getTime();
        fetch(`game.php?action=validateWord&game_id=${idJoc}&player=${idJugador}&word=${paraula.value}&temps=${time}`)
            .then(response => response.json())
            .then(data => {            
                console.log(data);
                if (data.error) {
                    alert(data.error);
                    return;
                }
                if(data.hihaescriptor){
                    if(data.escriptor == identitat){
                        enableButton();
                    }
                    else{
                        disableButton();                            
                    }
                }
            });
    }
}

function updateHealthBar(healthBar, health) {
    const healthPercentage = Math.max(0, Math.min(health , 10))*10; // Constrain health to 0-100%

    // Set health bar width based on percentage
    healthBar.style.width = healthPercentage + "%";

    // Set health bar color based on health level
    if (healthPercentage > 50) {
        healthBar.style.backgroundColor = "green";
    } else if (healthPercentage > 20) {
        healthBar.style.backgroundColor = "orange";
    } else {
        healthBar.style.backgroundColor = "red";
    }
}

// Iniciar el joc unint-se
unirseAlJoc();