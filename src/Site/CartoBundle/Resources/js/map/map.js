var map, GPX, routeCreateControl,routeSaveControl,pointArray,latlngArray, polyline,tracepolyline, elevationScript, elevationChartScript,denivelep,denivelen,drawnItems,drawControl,currentLayer;
var isCreateRoute = false;
var elevationURL = "http://open.mapquestapi.com/elevation/v1/profile?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&callback=getElevation&shapeFormat=raw&unit=m";
var elevationChartURL = "http://open.mapquestapi.com/elevation/v1/chart?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&inFormat=kvp&shapeFormat=raw&width=425&height=350";
var graph = $("<img>").css("display","none");

var Point = function(lat,lng)
{
    this.lat = lat;
    this.lng = lng;
};

var TypeLieu = function(id, label,icone)
{
    this.id = id;
    this.label = label;
    this.icone = icone;
};

var Icone = function(id, path)
{
    this.id = id;
    this.path = path;
};

$(window).load(function()
{
  map = new L.map('map');
  getLocation();
  $("#ok").click(moveToCoords);
  //$("#iti").click(createRoute);
  map.on('mousemove', displayCoords);
  map.on('zoomend', refreshZoom);
  map.on('contextmenu',context);
  graph.appendTo("body");
});

/*
var eauIcone = L.icon({
    iconUrl: '../eau.png',
    iconSize:     [30, 85], // size of the icon
});
var giteIcone = L.icon({
    iconUrl: '../gite.png',
    iconSize:     [30, 85], // size of the icon
});
var bugIcone = L.icon({
    iconUrl: '../bug.png',
    iconSize:     [30, 85], // size of the icon
});

var marker = L.marker([event.latlng.lat, event.latlng.lng], {icon: eauIcone}).addTo(map);
*/

function loadLieux()
{
  var res = [];

  $.ajax({
       url : "map/getAllLieux",
       type : 'GET', 
       async : false,
       dataType : 'json',
       success : function(json, statut){
           res = json;
         },

       error : function(resultat, statut, erreur){
       }
    });
  return res;
}

function loadPoi()
{
  var res;
  $.ajax({
       url : "map/getPoi/",
       type : 'GET',
       dataType : 'json',
       success : function(json, statut){
           console.log("jsonpoi : " + json);
           res=json;
       },

       error : function(resultat, statut, erreur){
         
       },

       complete : function(resultat, statut){

       }

    });

    return res;
}

function context(event)
{
    $(function()
    {
        allLieux = loadLieux();
        var idLieu;
        var labelLieu;
        /*var pathIcone;
        var txtMarker;
        var idIcone;*/
        var lat = event.latlng.lat;
        var lng = event.latlng.lng;
        var tab = {};
        for(var i = 0; i < allLieux.length; i++)
          {
              idLieu = allLieux[i].id;
              console.log("idlieu" + idLieu);
              labelLieu = allLieux[i].label;
              /*pathIcone = allLieux[i].icone.path;
              idIcone = allLieux[i].icone.id;*/
              tab[labelLieu] = {"name": labelLieu, callback: function(idLieu){
                  txtMarker = savePoi(lat, lng, 1, idLieu);
                  var marker = L.marker([lat,lng]).addTo(map).bindPopup("<b>" + txtMarker[0] + "</b><br>" + txtMarker[1]);
              }};
          }
        var poi = {"key": {name: "Ajouter POI", "items":tab
        }};

        $.contextMenu( 'destroy' );
        $.contextMenu({
            selector: '.context-menu-one', 
            items: poi,
            position: function(opt, x, y)
            {
              opt.$menu.css({top: y - 40, left: x + 10});
            }
            });
      });

}

//Coordonnées à partir du navigateur
function getLocation() 
{
          if (navigator.geolocation) {
              navigator.geolocation.getCurrentPosition(goToPosition,showError);
          } else {
              //$("#map").html("Votre navigateur ne supporte pas la géolocalisation");
              goToPosition({"coords" : {"latitude" : 0,"longitude" : 0}});
          }
      }
function showError(error) 
{
  goToPosition({"coords" : {"latitude" : 0,"longitude" : 0}});
}

//Place la map à la position récupérée dans getLocation
function goToPosition(position) {

  //Définition des attributs de la carte et positionnement
  $("#map").css("height", "100%").css("width", "100%").css("margin","auto");
  $("#controls").css("width", "20%").css("margin","auto");
  var zoom = 3;
  if(position.coords.latitude !== 0 && position.coords.longitude !== 0){zoom = 13;}
  map.setView([position.coords.latitude, position.coords.longitude], zoom);

  L.Control.geocoder().addTo(map);

  //Ajout du fond de carte Landscape obtenu sur Thunderforest
  L.tileLayer('http://tile.thunderforest.com/landscape/{z}/{x}/{y}.png', {
      attribution: 'Landscape'
  }).addTo(map);


  //Définition de l'écouteur
  $("#okVille").click(geocode);

    drawnItems = new L.FeatureGroup();
    map.addLayer(drawnItems);

    drawControl = new L.Control.Draw({
      draw: {
        polyline: {
             shapeOptions: {
                color: 'blue'
             },
        },
        polygon : false,
        rectangle : false,
        marker : false,
        circle : false,
      },
        edit: {
            featureGroup: drawnItems
        }
    });
    L.drawLocal.draw.toolbar.buttons.polyline = 'Tracer un parcours';
    map.addControl(drawControl);

    map.on('draw:created', function (e) {
        var type = e.layerType,
            layer = e.layer;
        drawnItems.addLayer(layer);
    });

    map.on('draw:drawstart', function (e) {
              if(!isCreateRoute)
              {
                //drawnItems.clearLayers();
                isCreateRoute = true;
                pointArray = [];
                latlngArray = [];
              }
          });
    map.on('draw:drawstop', function (e) {
        drawnItems.eachLayer(function (layer) {
          var list = layer.getLatLngs();
          pointArray = [];
          latlngArray = [];
          for(var i = 0; i < list.length; i++)
          {
            pointArray.push(new Point(list[i].lat,list[i].lng));
            latlngArray.push(list[i]);
          }
          if(pointArray.length > 1)
          {
              currentLayer = layer;
              var URL = elevationURL + '&latLngCollection=';
              var URLChart = elevationChartURL + '&latLngCollection=';
              for(var i = 0; i < layer.getLatLngs().length; i++)
              {
                var lat = layer.getLatLngs()[i].lat;
                var lng = layer.getLatLngs()[i].lng;
                URL += lat + "," + lng;
                URLChart += lat + "," + lng;
                if(i !== layer.getLatLngs().length - 1)
                {
                  URL += ",";
                  URLChart += ",";
                }
                  
              }
              URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
              URLChart.replace(/</g, '&lt;').replace(/>/g, '&gt;');
              elevationScript = document.createElement('script');
              elevationScript.type = 'text/javascript';
              elevationScript.src = URL;
              elevationChartScript = document.createElement('script');
              elevationChartScript.type = 'text/javascript';
              elevationChartScript.src = URLChart;
              $("body").append(elevationScript);
              graph.attr("src",elevationChartScript.src);
              graph.css("display","block");      
          }
        });
        saveRoute();
    });
  
  //$("#geocodeControl").css("width","20%");
  $("#map").css("cursor","move");

  
}

//Déplace la map aux coordonnées indiquées
function moveToCoords(lat,lng,zoom)
{
  map.setView([$("#lat").val(), $("#lng").val()], $("#zoom").val());
}

//Affiche les coordonnées
function displayCoords(event)
{
    $("#lat").val(event.latlng.lat);
    $("#lng").val(event.latlng.lng);
}

function refreshZoom()
{
  $("#zoom").val(map.getZoom());
}

function parseGPX(path)
{
  GPX = new L.GPX(path, {async: true,
  marker_options: {
    startIconUrl: 'Trail/web/pin-icon-start.png',
    endIconUrl: 'Trail/web/pin-icon-end.png',
    shadowUrl: 'Trail/web/pin-shadow.png'
  }}).on('loaded', function(e) {
    map.fitBounds(e.target.getBounds());
  }).addTo(map);
}

function geocode()
{
    var geo = MQ.geocode({ map: map })
      .search($("#ville").val());
}

function createRoute(event)
{
  //event.stopPropagation();
  if(!isCreateRoute)
  {
    isCreateRoute = true;
    pointArray = [];
    latlngArray = [];
  }
}

function drawRoute(event)
{
  pointArray.push(new Point(event.latlng.lat,event.latlng.lng));
  latlngArray.push(event.latlng);
  if(pointArray.length > 1)
  {
      var URL = elevationURL + '&latLngCollection=';
      var URLChart = elevationChartURL + '&latLngCollection=';
      for(var i = 0; i < latlngArray.length; i++)
      {
        var lat = latlngArray[i].lat;
        var lng = latlngArray[i].lng;
        URL += lat + "," + lng;
        URLChart += lat + "," + lng;
        if(i !== latlngArray.length - 1)
        {
          URL += ",";
          URLChart += ",";
        }
          
      }
      URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
      URLChart.replace(/</g, '&lt;').replace(/>/g, '&gt;');
      elevationScript = document.createElement('script');
      elevationScript.type = 'text/javascript';
      elevationScript.src = URL;
      elevationChartScript = document.createElement('script');
      elevationChartScript.type = 'text/javascript';
      elevationChartScript.src = URLChart;
      $("body").append(elevationScript);
      graph.attr("src",elevationChartScript.src);
      graph.css("display","block");      
  }
}

function saveRoute()
{  
  loadDifficultes();
  $("#save").modal('show');
  $("#saveiti").on("click",function()
    {
      $.post(Routing.generate('site_carto_saveItineraire'),
                            {
                                   points: JSON.stringify(pointArray),
                                   longueur : pointArray[pointArray.length - 1].distance,
                                   denivelep : denivelep,
                                   denivelen : denivelen,
                                   nom : $("#nom").val(),
                                   numero : $("#numero").val(),
                                   typechemin : $("#typechemin").val(),
                                   description : $("#description").val(),
                                   difficulte : $("#difficulte option:selected").val(),
                                   auteur : $("#auteur").val(),
                                   status : $("#status").val()
                                },
                            function(data, status){
                                console.log(data);
                            }
      );
      $("#save").modal('hide');

    });

  isCreateRoute = false;
}

function savePoi(lat, lng, alt, idLieu)
{
  $("#addpoi").modal('show');
  $("#savepoi").on("click",function()
    {
      console.log("1");
      $.post("map/createPoi",
                            {
                                   latitude: lat,
                                   longitude : lng,
                                   altitude : alt,
                                   titre : $("#titre").val(),
                                   description : $("#description").val(),
                                   idlieu : idLieu
                                   //labellieu : labelLieu,
                                   //idicone : idIcone,
                                   //pathicone : pathIcone
                                   //existLieu : new TypeLieu(idLieu, labelLieu, new Icone(idIcone, pathIcone))
                                },
                            function(data, status){
                                /*alert("Data: " + data + "\nStatus: " + status);*/
                                console.log(data);
                            });
      console.log("3");
      $("#addpoi").modal('hide');
      console.log("4");
    });
  console.log("5");
    var res = [$("#titre").val(), $("#description").val()];
    console.log("6");
    return res;
}

function getElevation(response)
{
  console.log(response);
  denivelen = 0;
  denivelep = 0;
  for(var i = 0; i < pointArray.length; i++)
  {
    pointArray[i].elevation = response.elevationProfile[i].height;
    pointArray[i].distance = response.elevationProfile[i].distance;
  }
  for(var i = 0; i < pointArray.length - 1; i++)
  {
    var diff = pointArray[i].elevation - pointArray[i + 1].elevation;
    diff < 0 ? denivelep += diff * -1 : denivelen += diff * -1;
  }
  $("#longueur").val(pointArray[pointArray.length - 1].distance);
  $("#denivp").val(denivelep);
      $("#denivn").val(denivelen);
}

function loadDifficultes()
{
  $.ajax({
       url : Routing.generate('site_carto_getDifficulteParcours'),
       type : 'GET',
       dataType : 'json',
       success : function(json, statut){
           console.log(json);
           for(var i = 0; i < json.length; i++)
           {
            var opt = $("<option>").attr("value",json[i].niveau).text(json[i].label);
            opt.appendTo("#difficulte");
           }
       },

       error : function(resultat, statut, erreur){
         
       },

       complete : function(resultat, statut){

       }

    });
}

function csvJSON(csv){
 
  var lines=csv.split("\n");
 
  var result = [];
 
  var headers=lines[0].replace(/(?:\\[r])+/g, "").split(",");
 
  for(var i=1;i<lines.length;i++){
    if(lines[i] !== "")
    {
      var obj = {};
      var currentline=lines[i].replace(/(?:\\[r])+/g, "").split(",");
   
      for(var j=0;j<headers.length;j++){
        obj[headers[j]] = currentline[j];
      }
   
      result.push(obj);
    }
 
  }
  console.log(result);
  //return result; //JavaScript object
  //return JSON.stringify(result); //JSON
  return result;
}

function displayTrace(traceJSON)
{
  var latlngArr = [];
  console.log(traceJSON);
  for(var i = 0; i < traceJSON.length - 1; i++)
  {
    var res = new L.LatLng(traceJSON[i].lat,traceJSON[i].lng);
    latlngArr.push(res);
  }
  tracepolyline = L.polyline(latlngArr, {color: 'blue'}).addTo(map);
}

function loadMap(path)
{
  $.ajax({
        type: "GET",
        url: "http://localhost/Traces/" + path,
        dataType: "text",
        success: function(data) {
          var json = csvJSON(data);
          displayTrace(json);
        },
       error : function(resultat, statut, erreur){
         console.log("Erreur : Impossible de charger le fichier de parcours");
       },
     });
}
