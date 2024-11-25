// Funció que genera una contrasenya mitjançant una crida API
function genera() {
    // Realitzar una petició POST al servidor
    fetch('http://yenalasta.duckdns.org:4300', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json' // Especificar el tipus de contingut com JSON
        },
        body: JSON.stringify({
            jsonrpc: '2.0', // Especificar la versió del protocol JSON-RPC
            method: 'generarContrassenya', // Mètode a cridar en el servidor
            params: {
                'longitud': $("#longitud").val() // Agafar la longitud especificada per l'usuari en la interfície
            },
            id: 1 // Identificador únic per la crida JSON-RPC
        })
    })
    .then(response => response.json()) // Processar la resposta com JSON
    .then(data => $("#contrassenya").text(data.result)) // Mostrar la contrasenya generada en l'element HTML
    .catch(error => console.error('Error:', error)); // Gestionar errors en la petició
}

// Funció per copiar el text de la contrasenya al portapapers
function copia() {
    navigator.clipboard.writeText($("#contrassenya").val()) // Utilitzar l'API del portapapers per copiar el text
    .then(() => {
        console.log('Texto copiado al portapapeles!'); // Confirmar que el text ha sigut copiat
    })
}
