var map, GPX, routeCreateControl,routeSaveControl,pointArray,latlngArray, polyline,tracepolyline, elevationScript, elevationChartScript,
denivelep,denivelen,drawnItems,drawControl,currentLayer,el,mapgeojson,editDrawControl,segmentID,fetchingElevation;
var isCreateRoute = false;
var isCreateSegment = false;
var elevationURL = "http://open.mapquestapi.com/elevation/v1/profile?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&callback=getElevation&shapeFormat=raw&unit=m";
var elevationUpdateURL = "http://open.mapquestapi.com/elevation/v1/profile?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&callback=getElevation&shapeFormat=raw&unit=m";
var graph = $("<img>").css("display","none");
var latPoi, lngPoi, altPoi, idLieuPoi, iconePoi;

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

var editDraw = L.Control.extend({
                options: {
                    position: 'topright'
                },

                onAdd: function (map) {
                    // create the control container with a particular class name
                    var container = L.DomUtil.create('div', 'editdraw');
                    $(container).html("<button type='button' class='btn btn-default' id='editdraw'>Prolonger</button></div>");
                    $("#editdraw").click(function()
                    {
                      map.fireEvent("draw:edited");
                      map.fireEvent("draw:drawstart");
                    });
                    return container;
                }
            });

            

/*$(window).load(function()
{
  init()
});*/

function init(callback,params)
{
  map = new L.map('map',{
      contextmenu: true,
      contextmenuWidth: 140,
      contextmenuItems: [{
          text: 'Ajouter POI',
          callback: function()
          {
            $("#addpoi").modal('show');
          }
      }]
  });
  loadLieux();
  if (navigator.geolocation) 
  {
    navigator.geolocation.getCurrentPosition(goToPosition,showError);
  } 
  else 
  {
    goToPosition({"coords" : {"latitude" : 0,"longitude" : 0}},callback,params);
  }
  addOverlay();
  $('#savepoi').click(savePoi);
  if(typeof callback !== "undefined" && typeof params !== "undefined")
  {
    callback.apply(null,params);
  }
}

function loadLieux()
{
  /*var res = [];

  $.ajax({
       url : Routing.generate('site_carto_getAllLieux'),
       type : 'GET', 
       async : false,
       dataType : 'json',
       success : function(json, statut){
           for(var i = 0; i < json.length; i++)
           {
            var opt = $("<option>").attr("value",json[i].id).text(json[i].label);
            opt.appendTo("#typelieu");
           }
       },

       error : function(resultat, statut, erreur){
       }
    });
  return res;*/
}

function loadPois()
{
  $.ajax({
       url : Routing.generate('site_carto_getAllPois'),
       type : 'GET', 
       dataType : 'json',
       success : function(json, statut){
          var icone;
          var marker;
           for(var i = 0; i < json.length; i++)
           {
              icone = L.icon({
                      iconUrl : "http://130.79.214.167/Images/" + json[i].typelieu.icone.path,
                      iconSize : [30, 30]
                    });
              marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<b>" + json[i].titre + "</b><br>" + json[i].description);
           }
         },

       error : function(resultat, statut, erreur){
       }
    });
}

/*
var eauIcone = L.icon({
    iconUrl: '../eau.png',
    iconSize:     [30, 85], // size of the icon
});

var marker = L.marker([event.latlng.lat, event.latlng.lng], {icon: eauIcone}).addTo(map);
*/

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
    if(tracepolyline !== undefined)
    {
      drawnItems.addLayer(tracepolyline);
    }

    L.Draw.SegmentFeature = L.Draw.Feature.extend({
            includes: L.Mixin.Events,

        initialize: function (map, options) {
          this._map = map;
          this._container = map._container;
          this._overlayPane = map._panes.overlayPane;
          this._popupPane = map._panes.popupPane;

          // Merge default shapeOptions options with custom shapeOptions
          if (options && options.shapeOptions) {
            options.shapeOptions = L.Util.extend({}, this.options.shapeOptions, options.shapeOptions);
          }
          L.setOptions(this, options);
        },
        enable: function () {
          if (this._enabled) { return; }

          L.Handler.prototype.enable.call(this);

          this.fire('enabled', { handler: this.type });

          this._map.fire('draw:segmentstart', { layerType: this.type });
        },

        disable: function () {
          if (!this._enabled) { return; }

          L.Handler.prototype.disable.call(this);

          this._map.fire('draw:segmentstop', { layerType: this.type });

          this.fire('disabled', { handler: this.type });
        },
        addHooks: function () {
          var map = this._map;

          if (map) {
            L.DomUtil.disableTextSelection();

            map.getContainer().focus();

            this._tooltip = new L.Tooltip(this._map);

            L.DomEvent.on(this._container, 'keyup', this._cancelDrawing, this);
          }
        },

        removeHooks: function () {
          if (this._map) {
            L.DomUtil.enableTextSelection();

            this._tooltip.dispose();
            this._tooltip = null;

            L.DomEvent.off(this._container, 'keyup', this._cancelDrawing, this);
          }
        },

        setOptions: function (options) {
          L.setOptions(this, options);
        },

        _fireCreatedEvent: function (layer) {
          this._map.fire('draw:created', { layer: layer, layerType: this.type });
        },

        // Cancel drawing when the escape key is pressed
        _cancelDrawing: function (e) {
          if (e.keyCode === 27) {
            this.disable();
          }
      }
    });

    //Création d'une polyline custom pour les segments
    L.Draw.SegmentPolyline = L.Draw.SegmentFeature.extend({
      statics: {
        TYPE: 'segmentpolyline'
      },

      Poly: L.Polyline,

      options: {
        allowIntersection: true,
        repeatMode: false,
        drawError: {
          color: '#b00b00',
          timeout: 2500
        },
        icon: new L.DivIcon({
          iconSize: new L.Point(8, 8),
          className: 'leaflet-div-icon leaflet-editing-icon'
        }),
        guidelineDistance: 20,
        maxGuideLineLength: 4000,
        shapeOptions: {
          stroke: true,
          color: 'blue',
          weight: 4,
          opacity: 0.5,
          fill: false,
          clickable: true
        },
        metric: true, // Whether to use the metric meaurement system or imperial
        showLength: true, // Whether to display distance in the tooltip
        zIndexOffset: 2000 // This should be > than the highest z-index any map layers
      },

      initialize: function (map, options) {
        // Need to set this here to ensure the correct message is used.
        this.options.drawError.message = L.drawLocal.draw.handlers.polyline.error;

        // Merge default drawError options with custom options
        if (options && options.drawError) {
          options.drawError = L.Util.extend({}, this.options.drawError, options.drawError);
        }

        // Save the type so super can fire, need to do this as cannot do this.TYPE :(
        this.type = L.Draw.Polyline.TYPE;

        L.Draw.SegmentFeature.prototype.initialize.call(this, map, options);
      },

      addHooks: function () {
        L.Draw.SegmentFeature.prototype.addHooks.call(this);
        if (this._map) {
          this._markers = [];

          this._markerGroup = new L.LayerGroup();
          this._map.addLayer(this._markerGroup);

          this._poly = new L.Polyline([], this.options.shapeOptions);

          this._tooltip.updateContent(this._getTooltipText());

          // Make a transparent marker that will used to catch click events. These click
          // events will create the vertices. We need to do this so we can ensure that
          // we can create vertices over other map layers (markers, vector layers). We
          // also do not want to trigger any click handlers of objects we are clicking on
          // while drawing.
          if (!this._mouseMarker) {
            this._mouseMarker = L.marker(this._map.getCenter(), {
              icon: L.divIcon({
                className: 'leaflet-mouse-marker',
                iconAnchor: [20, 20],
                iconSize: [40, 40]
              }),
              opacity: 0,
              zIndexOffset: this.options.zIndexOffset
            });
          }

          this._mouseMarker
            .on('mousedown', this._onMouseDown, this)
            .addTo(this._map);

          this._map
            .on('mousemove', this._onMouseMove, this)
            .on('mouseup', this._onMouseUp, this)
            .on('zoomend', this._onZoomEnd, this);
        }
      },

      removeHooks: function () {
        L.Draw.SegmentFeature.prototype.removeHooks.call(this);

        this._clearHideErrorTimeout();

        this._cleanUpShape();

        // remove markers from map
        this._map.removeLayer(this._markerGroup);
        delete this._markerGroup;
        delete this._markers;

        this._map.removeLayer(this._poly);
        delete this._poly;

        this._mouseMarker
          .off('mousedown', this._onMouseDown, this)
          .off('mouseup', this._onMouseUp, this);
        this._map.removeLayer(this._mouseMarker);
        delete this._mouseMarker;

        // clean up DOM
        this._clearGuides();

        this._map
          .off('mousemove', this._onMouseMove, this)
          .off('zoomend', this._onZoomEnd, this);
      },

      deleteLastVertex: function () {
        if (this._markers.length <= 1) {
          return;
        }

        var lastMarker = this._markers.pop(),
          poly = this._poly,
          latlng = this._poly.spliceLatLngs(poly.getLatLngs().length - 1, 1)[0];

        this._markerGroup.removeLayer(lastMarker);

        if (poly.getLatLngs().length < 2) {
          this._map.removeLayer(poly);
        }

        this._vertexChanged(latlng, false);
      },

      addVertex: function (latlng) {
        var markersLength = this._markers.length;

        if (markersLength > 0 && !this.options.allowIntersection && this._poly.newLatLngIntersects(latlng)) {
          this._showErrorTooltip();
          return;
        }
        else if (this._errorShown) {
          this._hideErrorTooltip();
        }

        this._markers.push(this._createMarker(latlng));

        this._poly.addLatLng(latlng);

        if (this._poly.getLatLngs().length === 2) {
          this._map.addLayer(this._poly);
        }

        this._vertexChanged(latlng, true);
      },

      _finishShape: function () {
        var intersects = this._poly.newLatLngIntersects(this._poly.getLatLngs()[0], true);

        if ((!this.options.allowIntersection && intersects) || !this._shapeIsValid()) {
          this._showErrorTooltip();
          return;
        }

        this._fireCreatedEvent();
        this.disable();
        if (this.options.repeatMode) {
          this.enable();
        }
      },

      //Called to verify the shape is valid when the user tries to finish it
      //Return false if the shape is not valid
      _shapeIsValid: function () {
        return true;
      },

      _onZoomEnd: function () {
        this._updateGuide();
      },

      _onMouseMove: function (e) {
        var newPos = e.layerPoint,
          latlng = e.latlng;

        // Save latlng
        // should this be moved to _updateGuide() ?
        this._currentLatLng = latlng;

        this._updateTooltip(latlng);

        // Update the guide line
        this._updateGuide(newPos);

        // Update the mouse marker position
        this._mouseMarker.setLatLng(latlng);

        L.DomEvent.preventDefault(e.originalEvent);
      },

      _vertexChanged: function (latlng, added) {
        this._updateFinishHandler();

        this._updateRunningMeasure(latlng, added);

        this._clearGuides();

        this._updateTooltip();
      },

      _onMouseDown: function (e) {
        var originalEvent = e.originalEvent;
        this._mouseDownOrigin = L.point(originalEvent.clientX, originalEvent.clientY);
      },

      _onMouseUp: function (e) {
        if (this._mouseDownOrigin) {
          // We detect clicks within a certain tolerance, otherwise let it
          // be interpreted as a drag by the map
          var distance = L.point(e.originalEvent.clientX, e.originalEvent.clientY)
            .distanceTo(this._mouseDownOrigin);
          if (Math.abs(distance) < 9 * (window.devicePixelRatio || 1)) {
            this.addVertex(e.latlng);
          }
        }
        this._mouseDownOrigin = null;
      },

      _updateFinishHandler: function () {
        var markerCount = this._markers.length;
        // The last marker should have a click handler to close the polyline
        if (markerCount > 1) {
          this._markers[markerCount - 1].on('click', this._finishShape, this);
        }

        // Remove the old marker click handler (as only the last point should close the polyline)
        if (markerCount > 2) {
          this._markers[markerCount - 2].off('click', this._finishShape, this);
        }
      },

      _createMarker: function (latlng) {
        var marker = new L.Marker(latlng, {
          icon: this.options.icon,
          zIndexOffset: this.options.zIndexOffset * 2
        });

        this._markerGroup.addLayer(marker);

        return marker;
      },

      _updateGuide: function (newPos) {
        var markerCount = this._markers.length;

        if (markerCount > 0) {
          newPos = newPos || this._map.latLngToLayerPoint(this._currentLatLng);

          // draw the guide line
          this._clearGuides();
          this._drawGuide(
            this._map.latLngToLayerPoint(this._markers[markerCount - 1].getLatLng()),
            newPos
          );
        }
      },

      _updateTooltip: function (latLng) {
        var text = this._getTooltipText();

        if (latLng) {
          this._tooltip.updatePosition(latLng);
        }

        if (!this._errorShown) {
          this._tooltip.updateContent(text);
        }
      },

      _drawGuide: function (pointA, pointB) {
        var length = Math.floor(Math.sqrt(Math.pow((pointB.x - pointA.x), 2) + Math.pow((pointB.y - pointA.y), 2))),
          guidelineDistance = this.options.guidelineDistance,
          maxGuideLineLength = this.options.maxGuideLineLength,
          // Only draw a guideline with a max length
          i = length > maxGuideLineLength ? length - maxGuideLineLength : guidelineDistance,
          fraction,
          dashPoint,
          dash;

        //create the guides container if we haven't yet
        if (!this._guidesContainer) {
          this._guidesContainer = L.DomUtil.create('div', 'leaflet-draw-guides', this._overlayPane);
        }

        //draw a dash every GuildeLineDistance
        for (; i < length; i += this.options.guidelineDistance) {
          //work out fraction along line we are
          fraction = i / length;

          //calculate new x,y point
          dashPoint = {
            x: Math.floor((pointA.x * (1 - fraction)) + (fraction * pointB.x)),
            y: Math.floor((pointA.y * (1 - fraction)) + (fraction * pointB.y))
          };

          //add guide dash to guide container
          dash = L.DomUtil.create('div', 'leaflet-draw-guide-dash', this._guidesContainer);
          dash.style.backgroundColor =
            !this._errorShown ? this.options.shapeOptions.color : this.options.drawError.color;

          L.DomUtil.setPosition(dash, dashPoint);
        }
      },

      _updateGuideColor: function (color) {
        if (this._guidesContainer) {
          for (var i = 0, l = this._guidesContainer.childNodes.length; i < l; i++) {
            this._guidesContainer.childNodes[i].style.backgroundColor = color;
          }
        }
      },

      // removes all child elements (guide dashes) from the guides container
      _clearGuides: function () {
        if (this._guidesContainer) {
          while (this._guidesContainer.firstChild) {
            this._guidesContainer.removeChild(this._guidesContainer.firstChild);
          }
        }
      },

      _getTooltipText: function () {
        var showLength = this.options.showLength,
          labelText, distanceStr;

        if (this._markers.length === 0) {
          labelText = {
            text: L.drawLocal.draw.handlers.polyline.tooltip.start
          };
        } else {
          distanceStr = showLength ? this._getMeasurementString() : '';

          if (this._markers.length === 1) {
            labelText = {
              text: L.drawLocal.draw.handlers.polyline.tooltip.cont,
              subtext: distanceStr
            };
          } else {
            labelText = {
              text: L.drawLocal.draw.handlers.polyline.tooltip.end,
              subtext: distanceStr
            };
          }
        }
        return labelText;
      },

      _updateRunningMeasure: function (latlng, added) {
        var markersLength = this._markers.length,
          previousMarkerIndex, distance;

        if (this._markers.length === 1) {
          this._measurementRunningTotal = 0;
        } else {
          previousMarkerIndex = markersLength - (added ? 2 : 1);
          distance = latlng.distanceTo(this._markers[previousMarkerIndex].getLatLng());

          this._measurementRunningTotal += distance * (added ? 1 : -1);
        }
      },

      _getMeasurementString: function () {
        var currentLatLng = this._currentLatLng,
          previousLatLng = this._markers[this._markers.length - 1].getLatLng(),
          distance;

        // calculate the distance from the last fixed point to the mouse position
        distance = this._measurementRunningTotal + currentLatLng.distanceTo(previousLatLng);

        return L.GeometryUtil.readableDistance(distance, this.options.metric);
      },

      _showErrorTooltip: function () {
        this._errorShown = true;

        // Update tooltip
        this._tooltip
          .showAsError()
          .updateContent({ text: this.options.drawError.message });

        // Update shape
        this._updateGuideColor(this.options.drawError.color);
        this._poly.setStyle({ color: this.options.drawError.color });

        // Hide the error after 2 seconds
        this._clearHideErrorTimeout();
        this._hideErrorTimeout = setTimeout(L.Util.bind(this._hideErrorTooltip, this), this.options.drawError.timeout);
      },

      _hideErrorTooltip: function () {
        this._errorShown = false;

        this._clearHideErrorTimeout();

        // Revert tooltip
        this._tooltip
          .removeError()
          .updateContent(this._getTooltipText());

        // Revert shape
        this._updateGuideColor(this.options.shapeOptions.color);
        this._poly.setStyle({ color: this.options.shapeOptions.color });
      },

      _clearHideErrorTimeout: function () {
        if (this._hideErrorTimeout) {
          clearTimeout(this._hideErrorTimeout);
          this._hideErrorTimeout = null;
        }
      },

      _cleanUpShape: function () {
        if (this._markers.length > 1) {
          this._markers[this._markers.length - 1].off('click', this._finishShape, this);
        }
      },

      _fireCreatedEvent: function () {
        var poly = new this.Poly(this._poly.getLatLngs(), this.options.shapeOptions);
        L.Draw.SegmentFeature.prototype._fireCreatedEvent.call(this, poly);
      }
    });

    L.Draw.Segment = L.Draw.SegmentPolyline.extend({
        initialize: function (map, options) {
            this.type = 'segment';

            L.Draw.SegmentFeature.prototype.initialize.call(this, map, options);
        }
    });

    L.DrawToolbar.include({
        getModeHandlers: function (map) {
            return [
                {
                    enabled: true,
                    handler: new L.Draw.Segment(map, {icon: new L.DivIcon({
                              iconSize: new L.Point(8, 8),
                              className: 'leaflet-div-icon leaflet-editing-icon'
                            })
                        }
                    ),
                    title: 'Tracer segment'
                },
                {
                    enabled: true,
                    handler: new L.Draw.Polyline(map, this.options.polyline),
                    title: 'Tracer itinéraire'
                }
            ];
        }
    });

    drawControl = new L.Control.Draw({
      draw: {
        polyline: {
             shapeOptions: {
                color: 'blue'
             },
        },
        segment:true,
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
              drawRoute(e);
          });

    map.on('draw:drawstop', function (e) {
        map.off("click");
        $("#denivp").text("");
        $("#denivn").text("");
        saveRoute();
    });

    map.on('draw:segmentstart', function (e) {
              console.log("segment");
              drawSegment(e);
          });

    map.on('draw:segmentstop', function (e) {
        map.off("click");
        saveSegment();
    });

    map.on('draw:editstart', function (e) {
          editDrawControl = new editDraw();
          map.addControl(editDrawControl);
     });

    map.on('draw:editstop', function (e) {
          map.removeControl(editDrawControl);
     });
    $("#map").css("cursor","move"); 
  loadPois();
}

function addOverlay()
{
    el = L.control.elevation({
    position: "topright",
    theme: "lime-theme", //default: lime-theme
    width: 600,
    height: 175,
    margins: {
        top: 10,
        right: 20,
        bottom: 30,
        left: 50
    },
    useHeightIndicator: true, //if false a marker is drawn at map position
    interpolation: "linear", //see https://github.com/mbostock/d3/wiki/SVG-Shapes#wiki-area_interpolate
    hoverNumber: {
        decimalsX: 3, //decimals on distance (always in km)
        decimalsY: 0, //deciamls on height (always in m)
        formatter: undefined //custom formatter function may be injected
    },
    xTicks: undefined, //number of ticks in x axis, calculated by default according to width
    yTicks: undefined, //number of ticks on y axis, calculated by default according to height
    collapsed: true    //collapsed mode, show chart on click or mouseover
  });
  el.addTo(map);
  

  var elevationUI = L.Control.extend({
      options: {
          position: 'topright'
      },

      onAdd: function (map) {
          // create the control container with a particular class name
          var container = L.DomUtil.create('div', 'leaflet-control-command');
              $(container).html("<p id='denivp' class='controlText'></p><p id='denivn' class='controlText'></p>" + 
                                  "<p id='long' class='controlText'></p><p id='diffiDisplay' class='controlText'></p>");
          return container;
      }
  });

  map.addControl(new elevationUI());
  //map.addControl(new drawSegmentUI());
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
  if(!isCreateRoute)
              {
                isCreateRoute = true;
                pointArray = [];
                latlngArray = [];
                map.on("click",function (ev){
                    addPointOnMap(ev);
                  });
              }
}

function saveRoute()
{  
  loadDifficultes();
  loadStatus();
  loadTypechemin();
  console.log(JSON.stringify(pointArray));
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
                                   typechemin : $("#typechemin option:selected").val(),
                                   description : $("#description").val(),
                                   difficulte : $("#difficulte option:selected").val(),
                                   auteur : $("#auteur").val(),
                                   status : $("#status option:selected").val(),
                                   public : $("#public option:selected").val()
                                },
                            function(data, status){

                                $.notify("Itinéraire sauvegardé", "success");
                            }
      ).fail(function() {
        $.notify("Erreur lors de la sauvegarde", "error");
      });
      $("#save").modal('hide');
        
    });

  isCreateRoute = false;

}

function drawSegment(event)
{
  if(!isCreateSegment)
              {
                isCreateSegment = true;
                pointArray = [];
                latlngArray = [];
                map.on("click",function (ev){
                    addPointOnMap(ev);
                  });
              }
}

function saveSegment()
{  
 $.post(Routing.generate('site_carto_saveSegment'),
                            {
                                   points: JSON.stringify(pointArray)
                                },
                            function(data, status){
                                $.notify("Segment sauvegardé", "success");
                            }
      ).fail(function() {
        $.notify("Erreur lors de la sauvegarde", "error");
      });

  isCreateSegment = false;

}

function savePoi()
{
      $.post(Routing.generate('site_carto_savePoi'),
                            {
                                   lat: latPoi,
                                   lng : lngPoi,
                                   alt : altPoi,
                                   idLieu : $("#typelieu option:selected").val(),
                                   titre : $("#titre").val(),
                                   description : $("#descriptionPoi").val()
                                   //labellieu : labelLieu,
                                   //idicone : idIcone,
                                   //pathicone : pathIcone
                                   //existLieu : new TypeLieu(idLieu, labelLieu, new Icone(idIcone, pathIcone))
                                },
                            function(data, status){
                                /*alert("Data: " + data + "\nStatus: " + status);*/
                                console.log(data);
                            });
      var marker = L.marker([latPoi,lngPoi], {icon: iconePoi}).addTo(map).bindPopup("<b>" + $("#titre").val() + "</b><br>" + $("#descriptionPoi").val());
      $("#addpoi").modal('hide');
}

function getElevation(response)
{  
  blockItineraireSave();
  denivelen = 0;
  denivelep = 0;
  console.log("Taille de pointArray : " + pointArray.length);
  console.log(response.elevationProfile);
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
  $("#longueur").val(pointArray[pointArray.length - 1].distance + "km");
  $("#denivp").text("Dénivelé positif : " + denivelep + "m");
  $("#denivn").text("Dénivelé négatif : " + denivelen + "m");
  if(isCreateRoute || isCreateSegment)
  {
    var geojson = polyline.toGeoJSON();
  }
  else
  {
    var geojson = tracepolyline.toGeoJSON();
  }
  for(var i = 0; i < geojson.geometry.coordinates.length; i++)
  {
    geojson.geometry.coordinates[i].push(pointArray[i].elevation);
  }
  if(mapgeojson !== undefined)
  {
    map.removeLayer(mapgeojson);
  }
  el.clear();
  mapgeojson = L.geoJson(geojson,{
      onEachFeature: el.addData.bind(el) //working on a better solution
  });
  if(!isCreateRoute && !isCreateSegment)
  {
    updateSegment(JSON.stringify(pointArray));
  }
  blockItineraireSave();
  map.on("click",function (ev){
    addPointOnMap(ev);
  });
}

function loadDifficultes()
{
  $.ajax({
       url : Routing.generate('site_carto_getDifficulteParcours'),
       type : 'GET',
       dataType : 'json',
       success : function(json, statut){
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

function loadStatus()
{
  $.ajax({
       url : Routing.generate('site_carto_getStatus'),
       type : 'GET',
       dataType : 'json',
       success : function(json, statut){
           for(var i = 0; i < json.length; i++)
           {
            var opt = $("<option>").attr("value",json[i].id).text(json[i].label);
            opt.appendTo("#status");
           }
       },

       error : function(resultat, statut, erreur){
         
       },

       complete : function(resultat, statut){

       }

    });
}

function loadTypechemin()
{
  $.ajax({
       url : Routing.generate('site_carto_getTypechemin'),
       type : 'GET',
       dataType : 'json',
       success : function(json, statut){
           for(var i = 0; i < json.length; i++)
           {
            var opt = $("<option>").attr("value",json[i].id).text(json[i].label);
            opt.appendTo("#typechemin");
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
  //return result; //JavaScript object
  //return JSON.stringify(result); //JSON
  return result;
}

function displayTrace(trace,elevation)
{
  //On convertit les coordonnées JSON en LatLng utilisables pour la polyline
  var LSCoords = trace.split(",");
  var latlngArr = [];
  for(var i = 0; i < LSCoords.length; i++)
  {
    var coords = LSCoords[i].split(" ");
    var res = new L.LatLng(coords[1],coords[0]);
    latlngArr.push(res);
  }

  //On crée la polyline et on ajoute les points
  tracepolyline = L.polyline(latlngArr, {color: 'blue'});
  surbrillance(tracepolyline);
  tracepolyline.markers = [];
  for(var i = 0; i < latlngArr.length; i++)
  {
    var marker = L.circleMarker([latlngArr[i].lat, latlngArr[i].lng]);
    surbrillance(marker);
    map.addLayer(marker);
    tracepolyline.markers.push(marker);
  }

  //On ajoute le profil altimétrique
  var geojson = tracepolyline.toGeoJSON();
  var elevations = elevation.split(";");
  for(var i = 0; i < geojson.geometry.coordinates.length; i++)
  {
    geojson.geometry.coordinates[i].push(elevations[i]);
  }
  mapgeojson = L.geoJson(geojson,{
      onEachFeature: el.addData.bind(el)
  }); 

  tracepolyline.addTo(map);
  
  
  
  //Events de la polyline, on retire les points selon les différents cas
  map.on('draw:editstart', function (e) {
        for(var i = 0; i < tracepolyline.markers.length; i++)
        {
          map.removeLayer(tracepolyline.markers[i]);
        }
    });
  map.on('draw:editstop', function (e) {
        tracepolyline.markers = [];
        pointArray = [];
        var latlngs = tracepolyline.getLatLngs();
        var URL = elevationUpdateURL + '&latLngCollection=';
        for(var i = 0; i < latlngs.length; i++)
        {
          var marker = L.circleMarker([latlngs[i].lat, latlngs[i].lng]);
          surbrillance(marker);
          map.addLayer(marker);
          tracepolyline.markers.push(marker);
          pointArray.push(new Point(latlngs[i].lat,latlngs[i].lng));
          URL += latlngs[i].lat + "," + latlngs[i].lng;
          if(i !== latlngs.length - 1){ URL += ","; }
        }
        URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        elevationScript.type = 'text/javascript';
        elevationScript.src = URL;
        $("body").append(elevationScript);   
        
    });
  map.on('draw:deletestart', function (e) {
      tracepolyline.on("click",function(){
          for(var i = 0; i < tracepolyline.markers.length; i++)
          {
            map.removeLayer(tracepolyline.markers[i]);
          }
      })
    });

  map.fitBounds(tracepolyline.getBounds());//On centre la map sur la polyline
}

function loadMap(json)
{
  segmentID = json.segment.id;
  displayTrace(json.segment.trace,json.segment.elevation);
  $("#denivp").text("Dénivelé positif : " + json.deniveleplus + "m");
  $("#denivn").text("Dénivelé négatif : " + json.denivelemoins + "m");
  $("#long").text("Longueur : " + json.longueur + "km");
  $("#diffiDisplay").text("Difficulté : " + json.difficulte.label);
}

function surbrillance(object)
{
    object.on("mouseover",function()
    {
        object.setStyle({color: 'yellow'});
        object.redraw();
    });
    object.on("mouseout",function()
    {
        object.setStyle({color: 'blue'});
        object.redraw();
    });
}

function updateSegment(points)
{
  $.post(Routing.generate('site_carto_segment_update'),
                            {
                                   points: points,
                                   id : segmentID
                                },
                            function(data, status){

                                $.notify("Segment mis à jour", "success");
                            }
      ).fail(function() {
        $.notify("Erreur lors de la mise à jour", "error");
      });
}

function blockItineraireSave()
{
  if(!$("#saveiti").prop("disabled"))
  {
    $("#saveiti").prop("disabled",true).change();
  }
  else
  {
    $("#saveiti").prop("disabled",false).change();
  }
}

function addPointOnMap(ev)
{

  pointArray.push(new Point(ev.latlng.lat,ev.latlng.lng));
  console.log("Nouveau point ajouté, nouvelle taille : " + pointArray.length);
  latlngArray.push(ev.latlng);
  if(pointArray.length > 1)
  {
      polyline = L.polyline(latlngArray);
      var URL = elevationURL + '&latLngCollection=';
      for(var i = 0; i < latlngArray.length; i++)
      {
        var lat = latlngArray[i].lat;
        var lng = latlngArray[i].lng;
        URL += lat + "," + lng;
        if(i !== latlngArray.length - 1){ URL += ","; }                            
      }
      URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
      elevationScript = document.createElement('script');
      elevationScript.type = 'text/javascript';
      elevationScript.src = URL;
      $("body").append(elevationScript);
      map.off("click");     
  }
}
