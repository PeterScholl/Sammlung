"use strict"

var fccdaten = {};

function init() {
  console.log("fcc-skript initialisieren...");
  var duration = 20000; //20 seconds time for alert to disappear
  //setTimeout(function () { $('#alert').hide(); }, duration);
    setTimeout(function () {   if (document.getElementById("message_info")) { document.getElementById("message_info").style.display = "none"; } }, duration);
  //Code for closing ConfirmDelRow-Modal by clicking outside the modal
  // When the user clicks anywhere outside of the modal, close it
  window.onclick = function(event) {
    var modalConfirmRowDelete = document.getElementById('confirmRowDelete');
    //console.log("var modal: "+modalConfirmRowDelete);
    if (event.target == modalConfirmRowDelete) {
      modalConfirmRowDelete.style.display = "none";
    }
  }
}

function loadDocGet(url, cFunction) {
  const xhttp = new XMLHttpRequest();
  xhttp.onload = function() {cFunction(this);}
  xhttp.open("GET", url);
  xhttp.send();
}

function genThumbnailsWithLog(nr=1,recursive=true,force=false) {
  var daten = {};
  if (nr==1) $("#wartungsoutput").html("<br>Generating thumbnails - force is: "+force+"<br>");  
  loadDocGet("ajaxjsondata.php?genThumbnail=true&fileid="+nr+"&force="+force, function(xhttp) {
    //Daten erhalten
    daten = JSON.parse(xhttp.responseText);
    if (typeof daten.resultText !== 'undefined') {
      $("#wartungsoutput").append(daten.resultText+"<br>");
    }
    if (typeof daten.error !== 'undefined') {
      $("#wartungsoutput").append("ERROR:"+daten.error+"<br>");
    }
    $("#wartungsoutput").append("NextID:"+daten.nextID+"<br>");
    console.log(JSON.stringify(daten));
    if (typeof daten.nextID !== 'undefined' && daten.nextID>0 && recursive) {
      genThumbnailsWithLog(daten.nextID,recursive,force);
    } else {
      $("#wartungsoutput").append("Last file reached - generation DONE<br>");
    }
  })
  console.log("Thumbnails - done: "+nr);
}

function checkFilesWithLog(nr=1,withMime=false, recursive=true) {
  var daten = {};
  if (nr==1) $("#wartungsoutput").html("<br>Checking Files<br>withMime: "+withMime+"<br>");  
  loadDocGet("ajaxjsondata.php?checkFiles=true&fileid="+nr+"&withMimeType="+withMime, function(xhttp) {
    //Daten erhalten
    daten = JSON.parse(xhttp.responseText);
    if (typeof daten.resultText !== 'undefined') {
      $("#wartungsoutput").append(daten.resultText+"<br>");
    }
    if (typeof daten.error !== 'undefined') {
      $("#wartungsoutput").append("ERROR:"+daten.error+"<br>");
    }
    $("#wartungsoutput").append("NextID:"+daten.nextID+"<br>");
    console.log(JSON.stringify(daten));
    if (typeof daten.nextID !== 'undefined' && daten.nextID>0 && recursive) {
      checkFilesWithLog(nr=daten.nextID,withMime=withMime);
    } else {
      $("#wartungsoutput").append("Last file reached - checks DONE<br>");
    }
  })
  console.log("FileCheck - done: "+nr);
}

function insertEditObjekt() {
  var daten = {};
  //Daten aus der Form holen
  let bez = document.forms["EditOrAddObject"]["bezeichnung"].value;
  let editid = document.forms["EditOrAddObject"]["editid"].value;
  let anz = document.forms["EditOrAddObject"]["anzahl"].value;
  let ort = document.forms["EditOrAddObject"]["ort"].value;
  let bild = document.forms["EditOrAddObject"]["bild"].value;
  let resultField = document.getElementById("insertObjResult");
  //TODO TODO - Modal anzeigen, je nach ergebnis
  if (lettersNumbersCheck(bez)) {
    console.log("Open ajaxjsondata.php?insObjekt&editid="+editid+"&bez="+bez+"&anz="+anz+"&ort="+ort+"&bild="+bild);
    loadDocGet("ajaxjsondata.php?insObjekt&editid="+editid+"&bez="+bez+"&anz="+anz+"&ort="+ort+"&bild="+bild, function(xhttp) {
      //Daten erhalten
      daten = JSON.parse(xhttp.responseText);
      console.log(JSON.stringify(daten));
      if (typeof daten.resultText !== 'undefined') {
        $("#insertObjResult").append(daten.resultText+"<br>");
      }
      if (typeof daten.error !== 'undefined') {
        $("#insertObjResult").append("ERROR:"+daten.error+"<br>");
      }
    })
    resultField.innerHTML = "ok ";
  } else {
    resultField.innerHTML = "failure ";
  }
  resultField.innerHTML += "Bezeichner: "+bez+" Ort: "+ort+" EditId: "+editid;
  //alert("Got Bezeichner: "+bez+" editid: "+editid+" Anzahl: "+anz);  
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

//Code for Countdown on Closing window 
//Time gets printed in a tag with id "CountDownTime"
var stp;
var countdownFrom; //time in seconds for countDown
function CountDownStart(sec) {
  countdownFrom = sec;
  stp = setInterval("CountDownTimer('CountDownTime')",1000)
}

function CountDownTimer(id) {
    if (countdownFrom==0) {
      clearInterval(stp); window.close(); 
    } else {
      var x
      var cntText = "Closing in "+countdownFrom+" seconds";
      if (document.getElementById) {
            x = document.getElementById(id);
            x.innerHTML = cntText;
      } else if (document.all) {
            x = document.all[id];
            x.innerHTML = cntText;
      }
    }
    countdownFrom--
}

function lettersNumbersCheck(name)
{
   var regEx = /^[0-9a-zA-Z\-\_\.]+$/;
   if(name.match(regEx))
     {
      return true;
     }
   else
     {
     alert("Please enter letters, numbers, ., _ and - only.");
     return false;
     }
}

//Functions for ConfirmRowDelete-Modal
function setRowDeleteModal(rowid, table) {
  document.getElementById("rowDeleteFormText").innerHTML="Zeile "+rowid+" in Tabelle "+table+" wirklich l&ouml;schen?";
  //document.getElementById("confirmRowDeleteBtn").onclick="location.href='?delrow="+rowid+"&table="+table+"&showtables'";
  //document.getElementById("confirmRowDeleteBtn").innerHTML="Delete neu";
  document.getElementById("confirmRowDeleteBtn").setAttribute('onclick','location.href="?delrow='+rowid+'&table='+table+'"');
}


