// Afegeix aquí el teu codi JavaScript

//Refresh Captcha
function refreshCaptcha(){
    var img = document.images['captcha_image'];
    img.src = img.src.substring(
		0,img.src.lastIndexOf("?")
		)+"?rand="+Math.random()*1000;
}

function checkPwnedPass(passwd) {
  apiUrl = "https://api.pwnedpasswords.com/range/";
  const hash = passwd;//sha1(passwd);
  retMsg = "";

  const prefix = hash.substring(0, 5).toUpperCase();
  const suffix = hash.substring(5).toUpperCase();
  fetch(apiUrl+prefix)
      .then(response => response.text())
      .then(data => {
          const lines = data.split('\n');
          const matches = lines.filter(line => line.startsWith(suffix));
          if (matches.length > 0) {
              retMsg = `ALERTA! La teva contrassenya ha aparegut a ${matches[0].split(':')[1]} bretxes de dades!`;
          } else {
              retMsg = 'PERFECTE! La teva contrassenya no ha aparegut mai a cap bretxa de dades!.';
          }
          document.getElementById('pwned_password').textContent = retMsg;
      })
      .catch(error => {
          console.error('Error:', error); // Manejar errores
      });
}

async function sha1(message) {
  const msgBuffer = new TextEncoder().encode(message); // Codifica como (utf-8)
  const hashBuffer = await crypto.subtle.digest('SHA-1', msgBuffer); // Hash el mensaje
  const hashArray = Array.from(new Uint8Array(hashBuffer)); // Convierte el buffer a Array de bytes
  const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join(''); // Convierte a cadena hexadecimal
  return hashHex;
}

document.getElementById('register_password').addEventListener('blur', async function() { 
  const userInput = document.getElementById('register_password').value;
  const hash = await sha1(userInput);
  checkPwnedPass(hash);
});