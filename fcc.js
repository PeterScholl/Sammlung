"use strict"

var fccdaten = {};

function init() {
  console.log("fcc-skript initialisieren...");
  var duration = 20000; //20 seconds time for alert to disappear
  //setTimeout(function () { $('#alert').hide(); }, duration);
    setTimeout(function () {   if (document.getElementById("message_info")) { document.getElementById("message_info").style.display = "none"; } }, duration);
}

function loadDocGet(url, cFunction) {
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function() {cFunction(this);}
  xhttp.open("GET", url);
  xhttp.send();
}

function holeDaten(listnum=-1) {
  holeArtikel();
  console.log("Daten der Einkaufsliste aus der DB holen - NR: "+listnum);
  var daten = {};
  loadDocGet("ajaxjsondata.php?shoplist="+listnum, function(xhttp) {
    //Wenn die Daten geholt wurden
    daten = JSON.parse(xhttp.responseText);
    console.log(JSON.stringify(daten));
    fccdaten.sl=daten;
    $("h5").text("Einkaufsliste "+daten[0].rowid+": "+daten[0].name);
    //Inhalt der Einkaufsliste holen
    if (typeof(daten[0].rowid)=='number') { // Einkaufsliste gefunden
      loadDocGet("ajaxjsondata.php?shoplistcontent="+daten[0].rowid, 
      function(xhttp) {
        var daten2 = JSON.parse(xhttp.responseText);
        console.log("daten2: "+JSON.stringify(daten2));
        fccdaten.slcontent=daten2;
        let len2 = daten2.length;
        if (len2>0) { // Tabelle leeren
          $("#sort tbody").empty();
        }
        for (let i=0; i<len2; i++) {
          //Einkaufsliste befüllen
          $("#sort tbody").append("<tr id='slrow+"+daten2[i].id+"'><td id='slrow"+(i+1)+"'>"+(i+1)+
          "</td><td id='slbez"+(i+1)+"' onclick=\"changebought(this)\">"+daten2[i].name+"</td><td>"+daten2[i].menge+"</td><td>"+daten2[i].einheit+"</td><td></td></tr>");
        }
      })      
    }
  })
  console.log("Alle-Daten schon da ?");  
}

function changebought(domobj) {
  domobj.style="text-decoration: line-through;";
  //$(domobj.parentNode).addClass("d-none");  
}

function holeArtikel() {
  console.log("Ajax-Daten zu Artikeln holen");
  var daten = {};
  loadDocGet("ajaxjsondata.php?article=1", function(xhttp) {
    daten = JSON.parse(xhttp.responseText);
    //console.log(JSON.stringify(daten));
    let len = daten.length;
    if (len>0) { // Vorschlagsliste leeren
      $("#dl_articles").empty();
    }
    for (let i=0; i<len; i++) {
      //Für Testzwecke schon mal einkaufsliste befüllen
      //$("#sort tbody").append("<tr><td></td><td>"+daten[i].name+"</td><td></td><td></td><td></td></tr>");
      //Vorschlagsliste befüllen
      $("#dl_articles").append("<option value=\""+daten[i].name+"\">");
    }
    fccdaten.artikel=daten;
  })
  console.log("Ajax-Daten da ?");  
}

function holeThemenfelder() {
  console.log("Ajax-Anfrage Themenfelder holen");
  var daten = {};
  loadDocGet("ajaxjsondata.php?themenliste=1", function(xhttp) {
    daten = JSON.parse(xhttp.responseText);
    //console.log(JSON.stringify(daten));
    let len = daten.length;
    if (len>0) { // Vorschlagsliste leeren
      $("#dl_articles").empty();
    }
    for (let i=0; i<len; i++) {
      //Für Testzwecke schon mal einkaufsliste befüllen
      //$("#sort tbody").append("<tr><td></td><td>"+daten[i].name+"</td><td></td><td></td><td></td></tr>");
      //Vorschlagsliste befüllen
      $("#dl_articles").append("<option value=\""+daten[i].name+"\">");
    }
    fccdaten.themenliste=daten;
  }
  )
  console.log("Ajax-Daten da ?");
}

function holeArtikel_orig() {
  console.log("Ajax-Daten zu Artikeln holen");
  const xmlhttp = new XMLHttpRequest();
  var daten = {};
  xmlhttp.onload = function() {
    daten = JSON.parse(this.responseText);
    console.log(JSON.stringify(daten));
    let len = daten.length;
    for (let i=0; i<len; i++) {
      $("#liste").append("<li>"+daten[i].name+"</li>");
      $("#dl_articles").append("<option value=\""+daten[i].name+"\">");
    }
  }
  xmlhttp.open("GET", "ajaxjsondata.php?article=1");
  xmlhttp.send();
  console.log("Ajax-Daten da ?");  
}

function showAddForm() {
  document.getElementById('inputNewArticle').style.display='block';
  document.getElementById('btnAdd').style.display='none';
  document.getElementById('btnHideAdd').style.display='inline';  
}
function hideAddForm() {
  document.getElementById('inputNewArticle').style.display='none';
  document.getElementById('btnAdd').style.display='inline';
  document.getElementById('btnHideAdd').style.display='none';  
}
