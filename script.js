let grigliaGioco = Array(9).fill("");
let turnoAttuale = "X";
let partitaConclusa = false;
let puntiUtenteX = 0;
let puntiAvversarioO = 0;
let nomeGiocatore1 = "";
let nomeGiocatore2 = "";
let modalitaScelta = "pvp";

const lineeVincenti = [
  [0, 1, 2], [3, 4, 5], [6, 7, 8], 
  [0, 3, 6], [1, 4, 7], [2, 5, 8], 
  [0, 4, 8], [2, 4, 6]
];

const areaTabellone = document.getElementById("board");
const testoStato = document.getElementById("status");
const riquadroPunteggio = document.getElementById("score");
const intestazioneSfida = document.getElementById("vs");

function renderingTabellone() {
  if (!areaTabellone) return;
  areaTabellone.innerHTML = "";
  
  grigliaGioco.forEach((segno, posizione) => {
    let casella = document.createElement("div");
    casella.className = "cell";
    
    if (segno === "X") {
      let contenitoreX = document.createElement("div");
      contenitoreX.className = "cell-x";
      let l1 = document.createElement("div"); l1.className = "chalk-line line1";
      let l2 = document.createElement("div"); l2.className = "chalk-line line2";
      contenitoreX.appendChild(l1);
      contenitoreX.appendChild(l2);
      casella.appendChild(contenitoreX);
    } else if (segno === "O") {
      let contenitoreO = document.createElement("div");
      contenitoreO.className = "cell-o";
      casella.appendChild(contenitoreO);
    }
    
    casella.addEventListener("click", () => mossaGiocatore(posizione));
    areaTabellone.appendChild(casella);
  });
}

function mossaGiocatore(posizione) {
  if (grigliaGioco[posizione] !== "" || partitaConclusa) return;
  
  grigliaGioco[posizione] = turnoAttuale;
  renderingTabellone();
  controlloStatoIncontro();
  
  if (!partitaConclusa) {
    if (modalitaScelta === "cpu" && turnoAttuale === "O") {
      testoStato.textContent = "Il Computer sta pensando...";
      setTimeout(mossaMenteArtificiale, 600);
    }
  }
}

function mossaMenteArtificiale() {
  if (partitaConclusa) return;
  let caselleVuote = [];
  grigliaGioco.forEach((valore, indice) => {
    if (valore === "") caselleVuote.push(indice);
  });
  
  if (caselleVuote.length > 0) {
    let mossaScelta = caselleVuote[Math.floor(Math.random() * caselleVuote.length)];
    grigliaGioco[mossaScelta] = "O";
    renderingTabellone();
    controlloStatoIncontro();
  }
}

/* Coordinate ricalcolate perfettamente per la nuova dimensione da 480px */
function evidenziaCelleVincenti(combinazione) {
  const rigaVincente = document.getElementById("winning-line");
  if (!rigaVincente) return;

  const segnoVincente = grigliaGioco[combinazione[0]]; 
  rigaVincente.className = "winning-line " + (segnoVincente === "X" ? "win-x" : "win-o");

  const combinazioneStr = combinazione.join(",");
  let top = 0, left = 0, width = 0, rotate = 0;

  switch (combinazioneStr) {
    case "0,1,2": top = 80;  left = 15;  width = 450; rotate = -0.5; break;
    case "3,4,5": top = 240; left = 15;  width = 450; rotate = 0.3;  break;
    case "6,7,8": top = 400; left = 15;  width = 450; rotate = -0.2; break;
    case "0,3,6": top = 15;  left = 80;  width = 450; rotate = 89.4; break;
    case "1,4,7": top = 15;  left = 240; width = 450; rotate = 90.5; break;
    case "2,5,8": top = 15;  left = 400; width = 450; rotate = 89.7; break;
    case "0,4,8": top = 25;  left = 25;  width = 590; rotate = 45;   break;
    case "2,4,6": top = 25;  left = 455; width = 590; rotate = 135;  break;
  }

  rigaVincente.style.top = top + "px";
  rigaVincente.style.left = left + "px";
  rigaVincente.style.transform = `rotate(${rotate}deg)`;
  rigaVincente.style.width = width + "px";
}

function controlloStatoIncontro() {
  let vittoriaRilevata = false;
  
  for (let i = 0; i < lineeVincenti.length; i++) {
    const [a, b, c] = lineeVincenti[i];
    if (grigliaGioco[a] && grigliaGioco[a] === grigliaGioco[b] && grigliaGioco[a] === grigliaGioco[c]) {
      vittoriaRilevata = true;
      partitaConclusa = true;
      evidenziaCelleVincenti(lineeVincenti[i]);
      
      if (grigliaGioco[a] === "X") {
        testoStato.textContent = `Ha vinto ${nomeGiocatore1}!`;
        sincronizzaPunteggiDatabase('vittoria');
      } else {
        testoStato.textContent = `Ha vinto ${nomeGiocatore2}!`;
        sincronizzaPunteggiDatabase('sconfitta');
      }
      break;
    }
  }
  
  if (!vittoriaRilevata && !grigliaGioco.includes("")) {
    partitaConclusa = true;
    testoStato.textContent = "Incontro terminato in Pareggio!";
    sincronizzaPunteggiDatabase('pareggio');
  }
  
  if (!partitaConclusa) {
    turnoAttuale = (turnoAttuale === "X") ? "O" : "X";
    let nomeTurno = (turnoAttuale === "X") ? nomeGiocatore1 : nomeGiocatore2;
    testoStato.textContent = `Tocca a: ${nomeTurno}`;
  }
}

function sincronizzaPunteggiDatabase(esito) {
  let parametriPost = new URLSearchParams();
  parametriPost.append('result', esito);
  
  fetch('gestore_dati.php', {
    method: 'POST',
    body: parametriPost
  })
  .then(res => res.json())
  .then(datiAggiornati => {
    if (datiAggiornati.vittorie !== undefined) document.getElementById('stat-vittorie').textContent = datiAggiornati.vittorie;
    if (datiAggiornati.sconfitte !== undefined) document.getElementById('stat-sconfitte').textContent = datiAggiornati.sconfitte;
    if (datiAggiornati.pareggi !== undefined) document.getElementById('stat-pareggi').textContent = datiAggiornati.pareggi;
  }).catch(err => console.error(err));
}

const btnStart = document.getElementById("startBtn");
if (btnStart) {
    btnStart.onclick = () => {
      modalitaScelta = document.getElementById("mode").value;
      nomeGiocatore1 = document.getElementById("p1").value;
      let visualizzaNomeO = document.getElementById("p2").value.trim();
      if (!visualizzaNomeO) {
          visualizzaNomeO = (modalitaScelta === "cpu" ? "COMPUTER" : "AVVERSARIO");
      }
      nomeGiocatore2 = visualizzaNomeO;

      document.getElementById("startScreen").classList.add("hidden");
      document.getElementById("actualGame").classList.remove("hidden");
      
      if (intestazioneSfida) {
        intestazioneSfida.innerHTML = `<span class="vs-x">X</span> ${nomeGiocatore1.toUpperCase()} vs <span class="vs-o">O</span> ${nomeGiocatore2.toUpperCase()}`;
      }
      riavviaIncontroCompleto();
    };
}

const btnRestart = document.getElementById("restartRound");
if (btnRestart) {
    btnRestart.onclick = () => { riavviaIncontroCompleto(); };
}

function riavviaIncontroCompleto() {
  grigliaGioco = Array(9).fill("");
  turnoAttuale = "X";
  partitaConclusa = false;

  const rigaVincente = document.getElementById("winning-line");
  if (rigaVincente) {
    rigaVincente.style.width = "0px";
    rigaVincente.className = "winning-line";
  }

  renderingTabellone();
  testoStato.textContent = `Tocca a: ${nomeGiocatore1}`;
}