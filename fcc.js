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

//the following function is used to fill the table with desired output
// in the complex Objekt-view
function fillObjektTable(page=1) {
  console.log("-- in fill ObjektTable --");
  var table = document.getElementById("tableOfObjekte");
  //Delete table rows
  //probably better to delete all children of tbody?!
  var tablebody = document.getElementById("tableOfObjekteBody");
  if (tablebody === null) return;
  while (tablebody.firstChild) {
    tablebody.removeChild(tablebody.lastChild);
  }
  // get Ansicht(View)-values from form
  var mitID = document.getElementById("ObjekteSpalteID").checked;
  var mitBez = document.getElementById("ObjekteSpalteBezeichnung").checked;
  var mitBild = document.getElementById("ObjekteSpalteBild").checked;
  var mitSort = document.getElementById("ObjekteSpalteSortID").checked;
  var mitCrtd = document.getElementById("ObjekteSpaltecreated").checked;
  var mitEdit = document.getElementById("ObjekteSpalteedited").checked;
  var mitOrt = document.getElementById("ObjekteSpalteOrte").checked;
  var mitDokument = document.getElementById("ObjekteSpalteDokumente").checked;
  var limit = parseInt(document.getElementById("limit").value);
  var offset = (page-1)*limit;
  // get Filter-values from form
  var filterStr = document.getElementById("objekteFilter").value;
  console.log("FilterString: "+filterStr+" isValid: "+/^([a-z0-9A-Z%_ÄÖÜäöüß]*)$/.test(filterStr));
  if (!/^([a-z0-9A-Z%_ÄÖÜäöüß]*)$/.test(filterStr)) filterStr='';
  //generate Table Head
  var tablehead = document.getElementById("tableOfObjekteHead");
  while (tablehead.firstChild) { tablehead.removeChild(tablehead.lastChild); }
  var h = document.createElement("th");
  h.innerText="ID";
  if (!mitID) {
    h.style="display:none;";
  }
  tablehead.appendChild(h);
  h = document.createElement("th");
  h.innerText="Bezeichnung";
  if (!mitBez) {
    h.style="display:none;";
  }
  tablehead.appendChild(h);
  if (mitBild) {
    h = document.createElement("th");
    h.innerText="Bild";
    tablehead.appendChild(h);
  }
  if (mitOrt) {
    h = document.createElement("th");
    h.innerText="Ort(e)";
    tablehead.appendChild(h);
  }
  if (mitDokument) {
    h = document.createElement("th");
    h.innerText="Dokumente";
    tablehead.appendChild(h);
  }
  if (mitSort) {
    h = document.createElement("th");
    h.innerText="SortID";
    tablehead.appendChild(h);
  }
  if (mitCrtd) {
    h = document.createElement("th");
    h.innerText="created";
    tablehead.appendChild(h);
  }
  if (mitEdit) {
    h = document.createElement("th");
    h.innerText="edited";
    tablehead.appendChild(h);
  }

  //populate the table
  var daten = {};
  console.log("Try to fetch "+"ajaxjsondata.php?getObjektData&limit="+limit+"&offset="+offset+"&mitOrt="+mitOrt+"&mitDoc="+mitDokument+"&mitBild="+mitBild+"&mitSort="+mitSort
      +"&mitCrtd="+mitCrtd+"&mitEdit="+mitEdit);
  loadDocGet("ajaxjsondata.php?getObjektData&limit="+limit+"&offset="+offset+"&mitOrt="+mitOrt+"&mitDoc="+mitDokument+"&mitBild="+mitBild+"&mitSort="+mitSort
      +"&mitCrtd="+mitCrtd+"&mitEdit="+mitEdit, function(xhttp) {
    //Daten erhalten
    daten = JSON.parse(xhttp.responseText);
    var pagenr = parseInt(daten['page']);
    var pagecount = parseInt(daten['numpages']);
    delete daten.page; //page entfernen jetzt sollten nur noch Daten vorhanden sein
    delete daten.numpages; // numpages - dito
    var keys = Object.keys(daten);
    if ((typeof daten !== 'undefined') && (keys.length > 0)) {
      console.log("Daten is defined and has length: "+keys.length);
      console.log("And the keys are: "+JSON.stringify(keys));
      for (let i = 0; i<keys.length; i++) {
        var row = tablebody.insertRow();
        console.log("-"+i+"-"+keys[i]+": "+JSON.stringify(daten[keys[i]]));
        var cell = row.insertCell(0);
        cell.innerHTML = daten[keys[i]]['rowid'];
        if (!mitID) cell.style="display:none;";
        cell = row.insertCell(1);
        cell.innerHTML = daten[keys[i]]['bezeichnung'];
        if (!mitBez) cell.style="display:none;";
        if (mitBild) {
          var image = document.createElement("img");
          image.src = daten[keys[i]]['bild'];
          row.insertCell(-1).appendChild(image);
          //row.insertCell(2).innerHTML = daten[keys[i]]['bild'];
        }
        if (mitOrt)  {
          //Orte
          var ortekeys = Object.keys(daten[keys[i]]['orte']);
          var ortetext = "";
          for (let j = 0; j<ortekeys.length; j++) {
            //TODO ortid in Text umwandeln - vielleicht kann das aber auch das ajax schon machen
            ortetext+=daten[keys[i]]['orte'][ortekeys[j]][1]+"("+daten[keys[i]]['orte'][ortekeys[j]][0]+")<br>";
          }
          row.insertCell(-1).innerHTML = ortetext;
        }
        if (mitDokument) row.insertCell(-1).innerHTML = daten[keys[i]][4];
        if (mitSort) row.insertCell(-1).innerHTML = daten[keys[i]]['sort'];
        if (mitCrtd) row.insertCell(-1).innerHTML = daten[keys[i]]['created'];
        if (mitEdit) row.insertCell(-1).innerHTML = daten[keys[i]]['edited'];
        
      }
      // Fill the navigation division
        var navigation = document.getElementById("ObjekteNavigation");
        //Delete table rows
        while (navigation.firstChild) {
          navigation.removeChild(navigation.lastChild);
        }
        // Add back to page 1 link
        var l;
        if (pagenr>1) {
          l = document.createElement("a");
          l.href = "Javascript:fillObjektTable(1)";
          l.innerHTML ="<<";
          navigation.appendChild(l);
          navigation.appendChild(document.createTextNode(" "));
        }
        // Add back one page link
        if (pagenr>2) {
          l = document.createElement("a");
          l.href = "Javascript:fillObjektTable("+(pagenr-1)+")";
          l.innerHTML ="<";
          navigation.appendChild(l);
        }
        // Add Page Nr.
        navigation.appendChild(document.createTextNode(" Seite "));
        l = document.createElement("span");
        l.id="actpage";
        l.innerHTML=pagenr;
        navigation.appendChild(l);
        navigation.appendChild(document.createTextNode(" von "));
        l = document.createElement("span");
        l.id="numpages";
        l.innerHTML=pagecount;
        navigation.appendChild(l);
        navigation.appendChild(document.createTextNode(" "));
        // Add go on one page link
        if (pagenr<(pagecount-1)) {
          l = document.createElement("a");
          l.href = "Javascript:fillObjektTable("+(pagenr+1)+")";
          l.innerHTML =">";
          navigation.appendChild(l);
          navigation.appendChild(document.createTextNode(" "));
        }
        // Add go to last page link
        if (pagenr<pagecount) {
          l = document.createElement("a");
          l.href = "Javascript:fillObjektTable("+(pagecount)+")";
          l.innerHTML =">>";
          navigation.appendChild(l);
        }
    }
    console.log(JSON.stringify(daten));
  })
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

function showHideBlockElementById(id, do_show) {
    var stl;
    if (do_show) stl = 'block'
    else         stl = 'none';

    var element  = document.getElementById(id);
    element.style.display=stl;  
}

//taken from https://www.wikihow.com/Toggle-HTML-Display-With-JavaScript
function toggleContentDisplayById(id) {
  // Get the DOM reference
  var contentId = document.getElementById(id);
  // Toggle 
  contentId.style.display == "block" ? contentId.style.display = "none" : 
contentId.style.display = "block"; 
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

// taken and modified from https://stackoverflow.com/questions/7754013/hiding-columns-in-table-javascript
function show_hide_column(col_no, do_show) {

    var stl;
    if (do_show) stl = 'block'
    else         stl = 'none';

    var tbl  = document.getElementById('id_of_table');
    var rows = tbl.getElementsByTagName('tr');

    for (var row=1; row<rows.length;row++) {
      var cels = rows[row].getElementsByTagName('td')
      cels[col_no].style.display=stl;
    }
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

//Cookies
function setCookie(cname, cvalue, exdays) {
  const d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  let expires = "expires="+d.toUTCString();
  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/"+";SameSite=Lax";
}

//to check if a cookie is set: getCookie("cookiename") != ""
function getCookie(cname) {
  let name = cname + "=";
  let ca = document.cookie.split(';');
  for(let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

function mytest(event) {
  if (typeof event !=='undefined' && typeof event.checked !== 'undefined' && event.checked) {
    console.log("Checked");
  } else {
    console.log("INPUT was: "+JSON.stringify(event));
  }
  console.log(JSON.stringify(document.cookie));
}

