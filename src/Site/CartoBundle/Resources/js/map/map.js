var map, GPX, routeCreateControl, routeSaveControl, pointArray, latlngArray, polyline, tracepolyline, elevationScript, elevationChartScript,
    denivelep, denivelen, drawnItems, drawControl, currentLayer, el, mapgeojson, editDrawControl, segmentID, fetchingElevation, traceData, formerZoom, markerGroup, polyArray,
    radiusGroup, potentialPoly, routeButton, routeSaveButton,routeDeleteButton;
var isCreateRoute = false;
var isCreateSegment = false;
var isEditSegment = false;
var isLoadingMap = false;
var elevationURL = "http://open.mapquestapi.com/elevation/v1/profile?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&callback=getElevation&shapeFormat=raw&unit=m";
var elevationSegmentURL = "http://open.mapquestapi.com/elevation/v1/profile?key=Fmjtd%7Cluu8210720%2C7a%3Do5-94bahf&callback=getElevationSegment&shapeFormat=raw&unit=m";
var graph = $("<img>").css("display", "none");
var latPoi, lngPoi, altPoi, idLieuPoi, iconePoi;
var radius = 0;
var points_id_Group = {};
var liste_poly_detect;
var liste_poly_detect_point;
var liste_marqueur_detect;
var cursor_pos_lat;
var cursor_pos_lng;

var Point = function (lat, lng) {
    this.lat = lat;
    this.lng = lng;
};

var TypeLieu = function (id, label, icone) {
    this.id = id;
    this.label = label;
    this.icone = icone;
};

var Icone = function (id, path) {
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
        $("#editdraw").click(function () {
            map.fireEvent("draw:edited");
            map.fireEvent("draw:drawstart");
        });
        return container;
    }
});

function init(callback, params) {
    map = new L.map('map', {
        contextmenu: true,
        contextmenuWidth: 140,
        contextmenuItems: [{
            text: 'Ajouter POI',
            callback: function (data) {
                latPoi = data.latlng.lat;
                lngPoi = data.latlng.lng;
                altPoi = 1;
                $("#addpoi").modal('show');
            }
        }]
    });
    loadLieux();
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(goToPosition, showError);
    }
    else {
        goToPosition({"coords": {"latitude": 0, "longitude": 0}}, callback, params);
    }
    addOverlay();
    $('#savepoi').click(savePoi);
    if (typeof callback !== "undefined" && typeof params !== "undefined") {
        callback.apply(null, params);
    }
}

function loadLieux() {
    var res = [];

    $.ajax({
        url: Routing.generate('site_carto_getAllLieux'),
        type: 'GET',
        dataType: 'json',
        success: function (json, statut) {
            for (var i = 0; i < json.length; i++) {
                var opt = $("<option>").attr("value", json[i].id).text(json[i].label);
                opt.appendTo("#typelieu");
            }
        },

        error: function (resultat, statut, erreur) {
        }
    });
    return res;
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
                    iconUrl : json[i].typelieu.icone.path,
                    iconSize : [30, 30]
                });

                if(json[i].image != null)
                {
                    if(json[i].image.path != null)
                    {
                        marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<div id='imgPoi' class='img-size' style='background-image: url(" + json[i].image.path + ");'></div> <p><b>" + json[i].titre + "</b></p><p>" + json[i].description + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + json[i].id + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + json[i].id + ")'>Modifier le POI</button>");
                        markerGroup.addLayer(marker);
                    }
                    else
                    {
                        marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<p><b>" + json[i].titre + "</b></p> <p>" + json[i].description + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + json[i].id + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + json[i].id + ")'>Modifier le POI</button>");
                        markerGroup.addLayer(marker);
                    }
                }
                else
                {
                    marker = L.marker([json[i].coordonnees.latitude,json[i].coordonnees.longitude], {icon: icone}).addTo(map).bindPopup("<p><b>" + json[i].titre + "</b></p> <p>" + json[i].description + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + json[i].id + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + json[i].id + ")'>Modifier le POI</button>");
                    markerGroup.addLayer(marker);
                }
            }
        },

        error: function (resultat, statut, erreur) {
        }
    });
}

//Coordonnées à partir du navigateur
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(goToPosition, showError);
    } else {
        //$("#map").html("Votre navigateur ne supporte pas la géolocalisation");
        goToPosition({"coords": {"latitude": 0, "longitude": 0}});
    }
}
function showError(error) {
    goToPosition({"coords": {"latitude": 0, "longitude": 0}});
}

//Place la map à la position récupérée dans getLocation
function goToPosition(position) {
    //Définition des attributs de la carte et positionnement
    $("#map").css("height", "100%").css("width", "100%").css("margin", "auto");
    var zoom = 3;
    if (position.coords.latitude !== 0 && position.coords.longitude !== 0) {
        zoom = 13;
    }
    formerZoom = zoom;
    map.setView([position.coords.latitude, position.coords.longitude], zoom);

    L.Control.geocoder().addTo(map);

    //Ajout du fond de carte Landscape obtenu sur Thunderforest
    L.tileLayer('http://tile.thunderforest.com/landscape/{z}/{x}/{y}.png', {
        attribution: 'Landscape'
    }).addTo(map);

    markerGroup = new L.LayerGroup();
    drawnItems = new L.FeatureGroup();
	radiusGroup = new L.FeatureGroup();
    map.addLayer(drawnItems);
    map.eachLayer(function (layer) {
        if ((layer instanceof L.Polyline) && !(layer instanceof L.Polygon)) {
            drawnItems.addLayer(layer);
        }
    });


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
            if (this._enabled) {
                return;
            }

            L.Handler.prototype.enable.call(this);

            this.fire('enabled', {handler: this.type});

            this._map.fire('draw:segmentstart', {layerType: this.type});
        },

        disable: function () {
            if (!this._enabled) {
                return;
            }

            L.Handler.prototype.disable.call(this);

            this._map.fire('draw:segmentstop', {layerType: this.type});

            this.fire('disabled', {handler: this.type});
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
            this._map.fire('draw:segmentcreated', {layer: layer, layerType: this.type});
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
			
			/*
			$.each(drawnItems._layers, function(key, val) {
				
				$.each(val._latlngs, function(key, points) 
				{									
					//test de concordances entre les points 
					if(points.lat == base_lat && points.lng == base_lng)
					{				
						//on set le point correspondant 
						points.lat = base_lat;
						points.lng = base_lng;
						
						console.log("ça marche bitch");
					}
				});
			});	
			*/
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
		
			var number_point = Object.keys(this._markerGroup._layers);

			//calcul de la zone de détection en fonction du radius
			
			var number_temp = 0;
			
			number_temp = 0.0001*((15/10)*(18-map.getZoom()+1));
			
			number_temp = number_temp + (0.0001*((15/10) * (18-map.getZoom())));
			
			
			//if(number_point.length == 0)
			//{		
				//fonction de détection d'un point existant 
				
				var full_poly_tab = drawnItems;
				var base_lat = latlng.lat;
				var base_lng = latlng.lng;
				var points_tab_index = [];
				var index_save = "";
				
				$.each(full_poly_tab._layers, function(key_poly, val) {		
					points_tab_index = [];				
					$.each(val._latlngs, function(key, points) 
					{
						//si il y a une concordance (on arrondis pour éliminer les imperfections), 
						//on sauvegarde toute la polyline dans le tableau temp_redraw
						
						dif_lat = base_lat - points.lat;
						dif_lng = base_lng - points.lng;
						
						//récupération du zoom 
						
						if((dif_lat < number_temp && dif_lat > -1*number_temp ) && (dif_lng < number_temp*2 && dif_lng > -1*number_temp*2))
						{	
							latlng.lat = points.lat;
							latlng.lng = points.lng;
							
							//on stocke le point dans le groupe points_id_Group
							//segmentation du tableau : tab[idLayer][position Point dans le tableau] (plusieurs possible)
							
							//index_save = 
							//index_save = points.lat+" "+points.lng+"-"+base_lat+" "+base_lng;
							
							//points_tab_index.push(index_save);						
						}						
					});
					/*
					//inserer le tableau dans l'objet 
					if(points_tab_index.length != 0)
					{
						points_id_Group[key_poly] = points_tab_index;
					}*/
				});		
			//}	
			
		//console.log(points_tab_index);
		
		/*console.log(full_poly_tab);
		console.log(points_id_Group);*/
		
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
                .updateContent({text: this.options.drawError.message});

            // Update shape
            this._updateGuideColor(this.options.drawError.color);
            this._poly.setStyle({color: this.options.drawError.color});

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
            this._poly.setStyle({color: this.options.shapeOptions.color});
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
	
	///////////////////////////// class edit poly

/*
 * L.Edit.Poly is an editing handler for polylines and polygons.
 */

L.Edit.Poly = L.Handler.extend({
	options: {
		icon: new L.DivIcon({
			iconSize: new L.Point(8, 8),
			className: 'leaflet-div-icon leaflet-editing-icon'
		})
	},

	initialize: function (poly, options) {
		this._poly = poly;
		L.setOptions(this, options);
	},

	addHooks: function () {
		var poly = this._poly;

		if (!(poly instanceof L.Polygon)) {
			//poly.options.editing.fill = false;
		}

		poly.setStyle(poly.options.editing);

		if (this._poly._map) {
			if (!this._markerGroup) {
				this._initMarkers();
			}
			this._poly._map.addLayer(this._markerGroup);
		}
	},

	removeHooks: function () {
		var poly = this._poly;

		poly.setStyle(poly.options.original);

		if (poly._map) {
			poly._map.removeLayer(this._markerGroup);
			delete this._markerGroup;
			delete this._markers;
		}
	},

	updateMarkers: function () {
		this._markerGroup.clearLayers();
		this._initMarkers();
	},

	_initMarkers: function () {
		if (!this._markerGroup) {
			this._markerGroup = new L.LayerGroup();
		}
		this._markers = [];

		var latlngs = this._poly._latlngs,
			i, j, len, marker;

		// TODO refactor holes implementation in Polygon to support it here

		for (i = 0, len = latlngs.length; i < len; i++) {

			marker = this._createMarker(latlngs[i], i);
			marker.on('click', this._onMarkerClick, this);
			this._markers.push(marker);
		}

		var markerLeft, markerRight;

		for (i = 0, j = len - 1; i < len; j = i++) {
			if (i === 0 && !(L.Polygon && (this._poly instanceof L.Polygon))) {
				continue;
			}

			markerLeft = this._markers[j];
			markerRight = this._markers[i];

			this._createMiddleMarker(markerLeft, markerRight);
			this._updatePrevNext(markerLeft, markerRight);
		}
	},
	
	

	_createMarker: function (latlng, index) {
		var marker = new L.Marker(latlng, {
			draggable: true,
			icon: this.options.icon
		});

		marker._origLatLng = latlng;
		marker._index = index;

		marker.on('dragstart', this._onMarkerDragStart, this);
		marker.on('drag', this._onMarkerDrag, this);
		marker.on('dragend', this._fireEdit, this);

		this._markerGroup.addLayer(marker);

		return marker;
	},

	_removeMarker: function (marker) {
		var i = marker._index;

		this._markerGroup.removeLayer(marker);
		this._markers.splice(i, 1);
		this._poly.spliceLatLngs(i, 1);
		this._updateIndexes(i, -1);

		marker
			.off('drag', this._onMarkerDrag, this)
			.off('dragend', this._fireEdit, this)
			.off('click', this._onMarkerClick, this);
	},

	_fireEdit: function () {
		this._poly.edited = true;
		this._poly.fire('edit');
	},
	
	//rajout de l'evenement onMarkerStart
	_onMarkerDragStart: function (e) {
	
		var base_lat_first = e.target._latlng.lat;
		var base_lng_first = e.target._latlng.lng;
		
		
		//on crée un objet qui va contenir tout les marqueurs et tout les points qui sont à modifier
		liste_poly_detect = {};
		liste_poly_detect_point = {};
		/*
		liste_poly_detect[0] = e.target._latlng.lat;
		liste_poly_detect[1] = e.target._latlng.lng;
		*/
		
		//console.log(liste_poly_detect);
		
		//on rajoute la detection des points dans cette fonction, puis on stocke les points dans une liste 
		/*
		var markerGroup2 = $.extend(true, {}, markerGroup);
		
		$.each(markerGroup2._layers, function(key, val) { 
			if(val._latlng.lat == base_lat_first && val._latlng.lng == base_lng_first)
			{
				//on stocke les points trouvés dans l'objet liste_poly_detect
				//id du marqeur -> lat et lng
				if (!liste_poly_detect.key) {

					liste_poly_detect[key] = {};
				
					liste_poly_detect[key][0] = val._latlng.lat;
					liste_poly_detect[key][1] = val._latlng.lng;
				}
			}
		});
		*/
		
		//on detecte tout les polylines concernée par le déplacement (par les points)
		
		var full_poly_tab = $.extend(true, {}, drawnItems);
		
		//liste_poly_detect[cle]['cle'] = cle;
		//liste_poly_detect[cle]['pos'] = pos;
		
		//comparaison avec les points du tableau full_poly_tab
		
		$.each(full_poly_tab._layers, function(key, val) {
			
			$.each(val._latlngs, function(pos, points) 
			{									
				//test de concordances entre les points 
				if(points.lat == base_lat_first && points.lng == base_lng_first)
				{	
					liste_poly_detect[key] = {};
					
				
					liste_poly_detect[key]['key'] = key;
					liste_poly_detect[key]['pos'] = pos;
				}
			});
		});
		
		//on doit rechercher les points qui ont une collisions avec le point en paramètre (e)
		
		liste_marqueur_detect = {};
		
		
		var markerGroup2 = $.extend(true, {}, markerGroup);
		
		$.each(markerGroup2._layers, function(key, val) { 
			if(val._latlng.lat == base_lat_first && val._latlng.lng == base_lng_first)
			{
				if (!liste_poly_detect.key) {

					liste_marqueur_detect[key] = {};
				
					liste_marqueur_detect[key][0] = val._latlng.lat;
					liste_marqueur_detect[key][1] = val._latlng.lng;
				}
			}
		});
	},

	_onMarkerDrag: function (e) {
		var marker = e.target;
		
		//on récupère les coordonnées du point que l'on a sélectionné
		var base_lat_maj = e.target._latlng.lat;
		var base_lng_maj = e.target._latlng.lng;
	
		//var base_lat_first = liste_poly_detect[0];
		//var base_lng_first = liste_poly_detect[1];
		
		//y a un problème avec le drawnItems
		//le tableau est modifié pendant que la boucle foreach est effectué, donc il faut partir sur une copie du tableau 
		
		//pour corriger le problème il faudrait cloner la variable drawnItems sans référence.
		/*
		var full_poly_tab = $.extend(true, {}, drawnItems);
		
		//comparaison avec les points du tableau full_poly_tab
		
		$.each(full_poly_tab._layers, function(key, val) {
			
			$.each(val._latlngs, function(pos, points) 
			{									
				//test de concordances entre les points 
				if(points.lat == base_lat_first && points.lng == base_lng_first)
				{				
					//on set le point correspondant 
					points.lat = base_lat_maj;
					points.lng = base_lng_maj;
					
					//la variable key c'est l'id du layer 
					//la variable pos c'est la position du point dans le layer.
					//drawnItems._layers[key]._latlngs[pos].lat
					
					drawnItems._layers[key]._latlngs[pos].lat = base_lat_maj;
					drawnItems._layers[key]._latlngs[pos].lng = base_lng_maj;
					
					//création de la 
					
					drawnItems._layers[key].redraw();
				}
			});
		});	*/

		
		//mis à jour des points contenus dans l'object liste_poly_detect
		var point_temp;
		var point_temp_orig;
		/*
		$.each(liste_marqueur_detect, function(key, val) { 		
				//on récupère l'id du point en lecture et on le lit
					
				point_temp = markerGroup._layers[key]._latlng;					
				point_temp_orig = point_temp;

				point_temp_orig.lat = marker._origLatLng.lat;
				point_temp_orig.lng = marker._origLatLng.lng;
					
				point_temp.lat = liste_poly_detect[key][0];
				point_temp.lng = liste_poly_detect[key][1];		
		});
		*/
		/*
		console.log(drawnItems);
		console.log(this._poly);
		*/
		var latlngs_modif;
		var poly_id = this._poly._leaflet_id;
		
		//liste_poly_detect[cle] = {}
		//liste_poly_detect[cle]['cle'] = cle;
		//liste_poly_detect[cle]['pos'] = pos;
		
		$.each(liste_poly_detect, function(key, val) { 			
			//on récupère la polyline
			//si la polyline n'est pas la polyline courante 
			
			if(val != poly_id)
			{
				//on récupère les latlngs de la poyline
				latlngs_modif = $.extend(true, {}, drawnItems._layers[val['key']]._latlngs);
				//parcourir les 
				
				//on les parcourts ensuite
				$.each(latlngs_modif, function(pos, val) { 
				
					//on set les valeurs du points que l'on déplace a partir de la liste liste_poly_detect_point
					if(pos == liste_poly_detect[key]['pos'])
					{
						val.lat = base_lat_maj;
						val.lng = base_lng_maj;
					}
				});
				
				drawnItems._layers[val['key']].redraw();
			}
			
		});
		
		L.extend(marker._origLatLng, marker._latlng);
		if (marker._middleLeft) {
			marker._middleLeft.setLatLng(this._getMiddleLatLng(marker._prev, marker));
		}
		if (marker._middleRight) {
			marker._middleRight.setLatLng(this._getMiddleLatLng(marker, marker._next));
		}

		this._poly.redraw();
	},

	_onMarkerClick: function (e) {
		var minPoints = L.Polygon && (this._poly instanceof L.Polygon) ? 4 : 3,
			marker = e.target;

		// If removing this point would create an invalid polyline/polygon don't remove
		if (this._poly._latlngs.length < minPoints) {
			return;
		}

		// remove the marker
		this._removeMarker(marker);

		// update prev/next links of adjacent markers
		this._updatePrevNext(marker._prev, marker._next);

		// remove ghost markers near the removed marker
		if (marker._middleLeft) {
			this._markerGroup.removeLayer(marker._middleLeft);
		}
		if (marker._middleRight) {
			this._markerGroup.removeLayer(marker._middleRight);
		}

		// create a ghost marker in place of the removed one
		if (marker._prev && marker._next) {
			this._createMiddleMarker(marker._prev, marker._next);

		} else if (!marker._prev) {
			marker._next._middleLeft = null;

		} else if (!marker._next) {
			marker._prev._middleRight = null;
		}

		this._fireEdit();
	},

	_updateIndexes: function (index, delta) {
		this._markerGroup.eachLayer(function (marker) {
			if (marker._index > index) {
				marker._index += delta;
			}
		});
	},

	_createMiddleMarker: function (marker1, marker2) {
		var latlng = this._getMiddleLatLng(marker1, marker2),
		    marker = this._createMarker(latlng),
		    onClick,
		    onDragStart,
		    onDragEnd;

		marker.setOpacity(0.6);

		marker1._middleRight = marker2._middleLeft = marker;

		onDragStart = function () {
		
			var i = marker2._index;

			marker._index = i;

			marker
			    .off('click', onClick, this)
			    .on('click', this._onMarkerClick, this);

			latlng.lat = marker.getLatLng().lat;
			latlng.lng = marker.getLatLng().lng;
			
			this._poly.spliceLatLngs(i, 0, latlng);
			this._markers.splice(i, 0, marker);

			marker.setOpacity(1);

			this._updateIndexes(i, 1);
			marker2._index++;
			this._updatePrevNext(marker1, marker);
			this._updatePrevNext(marker, marker2);

			this._poly.fire('editstart');
		};

		onDragEnd = function () {
			marker.off('dragstart', onDragStart, this);
			marker.off('dragend', onDragEnd, this);

			this._createMiddleMarker(marker1, marker);
			this._createMiddleMarker(marker, marker2);
		};

		onClick = function () {
			onDragStart.call(this);
			onDragEnd.call(this);
			this._fireEdit();
		};

		marker
		    .on('click', onClick, this)
		    .on('dragstart', onDragStart, this)
		    .on('dragend', onDragEnd, this);

		this._markerGroup.addLayer(marker);
	},

	_updatePrevNext: function (marker1, marker2) {
		if (marker1) {
			marker1._next = marker2;
		}
		if (marker2) {
			marker2._prev = marker1;
		}
	},

	_getMiddleLatLng: function (marker1, marker2) {
		var map = this._poly._map,
		    p1 = map.project(marker1.getLatLng()),
		    p2 = map.project(marker2.getLatLng());

		return map.unproject(p1._add(p2)._divideBy(2));
	}
});

L.Polyline.addInitHook(function () {

	// Check to see if handler has already been initialized. This is to support versions of Leaflet that still have L.Handler.PolyEdit
	if (this.editing) {
		return;
	}

	if (L.Edit.Poly) {
		this.editing = new L.Edit.Poly(this);

		if (this.options.editable) {
			this.editing.enable();
		}
	}

	this.on('add', function () {
		if (this.editing && this.editing.enabled()) {
			this.editing.addHooks();
		}
	});

	this.on('remove', function () {
		if (this.editing && this.editing.enabled()) {
			this.editing.removeHooks();
		}
	});
});

/////////////////////////////

    L.DrawToolbar.include({
        getModeHandlers: function (map) {
            return [
                {
                    enabled: true,
                    handler: new L.Draw.Segment(map, {
                            icon: new L.DivIcon({
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
                }
            },
            segment: true,
            polygon: false,
            rectangle: false,
            marker: false,
            circle: false
        },
        edit: {
            featureGroup: drawnItems
        }
    });
    L.drawLocal.draw.toolbar.buttons.polyline = 'Tracer un parcours';
    map.addControl(drawControl);

    routeButton = L.easyButton('fa-pencil',
     function (){
         createRoute();
     },
        "Tracer un itinéraire"
     );

    L.control.scale().addTo(map);

    map.on('draw:created', function (e) {
        var type = e.layerType,
            layer = e.layer;
        drawnItems.addLayer(layer);
        polyline = layer;
        map.off("click");
        //console.log("poly coords : " + polyline._latlngs.length);
        for (var i = 0; i < polyline._latlngs.length; i++) {
            pointArray[i] = new Point(polyline._latlngs[i].lat, polyline._latlngs[i].lng);
        }
        //console.log("pointArray length : " +  pointArray.length);
        var URL = elevationURL + '&latLngCollection=';
        for (var i = 0; i < pointArray.length; i++) {
            var lat = pointArray[i].lat;
            var lng = pointArray[i].lng;
            URL += lat + "," + lng;
            if (i !== pointArray.length - 1) {
                URL += ",";
            }
        }
        URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        elevationScript = document.createElement('script');
        elevationScript.type = 'text/javascript';
        elevationScript.src = URL;
        $("body").append(elevationScript);
        saveRoute();
        $("#denivp").text("");
        $("#denivn").text("");
    });

    map.on('draw:segmentcreated', function (e) {
        var type = e.layerType,
            layer = e.layer;
        drawnItems.addLayer(layer);
        polyline = layer;
        map.off("click");
        //console.log("poly coords : " + polyline._latlngs.length);
        for (var i = 0; i < polyline._latlngs.length; i++) {
            pointArray[i] = new Point(polyline._latlngs[i].lat, polyline._latlngs[i].lng);
        }
        //console.log("pointArray length : " +  pointArray.length);
        var URL = elevationSegmentURL + '&latLngCollection=';
        for (var i = 0; i < pointArray.length; i++) {
            var lat = pointArray[i].lat;
            var lng = pointArray[i].lng;
            URL += lat + "," + lng;
            if (i !== pointArray.length - 1) {
                URL += ",";
            }
        }
        URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        elevationScript = document.createElement('script');
        elevationScript.type = 'text/javascript';
        elevationScript.src = URL;
        $("body").append(elevationScript);
        setTimeout(saveSegment, 5000);
        $("#denivp").text("");
        $("#denivn").text("");
    });

    map.on('draw:drawstart', function (e) {
        drawRoute(e);
    });

    map.on('draw:drawstop', function (e) {
        map.off("click");
    });

    map.on('draw:segmentstart', function (e) {
        drawSegment(e);
    });

    map.on('draw:segmentstop', function (e) {
        map.off("click");
		console.log("save");
    });

    map.on('draw:editstart', function (e) {
        isEditSegment = true;
    });
	
	map.on('draw:editstop', function (e) {
        isEditSegment = false;
		
		//dans cette fonction prévoir une requete pour envoyer tout les segments qui ont été édités
		
    });

    map.on('draw:edited', function (e) {

    });

    map.on("zoomend", function (e) {
        if (map.getZoom() < 10 && formerZoom >= 10) {
            markerGroup.eachLayer(function (layer) {
                map.removeLayer(layer);
            });
            drawnItems.eachLayer(function (layer) {
                map.removeLayer(layer);
            });

        }
        else if (map.getZoom() >= 10 && formerZoom < 10) {
            markerGroup.eachLayer(function (layer) {
                layer.addTo(map);
            });
            drawnItems.eachLayer(function (layer) {
                layer.addTo(map);
            });
            loadSegments();
        }		
        formerZoom = map.getZoom();
		
		// création des cercles 	
	//suppression de tout les marqueurs précédent.
	
	/*$.each(radiusGroup._layers, function(key, radius) {
		radius.removeLayer();
	});*/
	radiusGroup.eachLayer(function (layer){
		map.removeLayer(layer);
	});

	//création de tout les cercles des polylines 
		
	var full_poly_tab = drawnItems;
	
	//calcul du radius en fonction du zoom
	
	radius = 20*(18-map.getZoom()+1);
	
	radius = radius + (50 * (18-map.getZoom()));
	
	if(18-map.getZoom() == 1)
	{
		radius = radius - 20;
	}	
	  
	$.each(full_poly_tab._layers, function(key, val) {
		$.each(val._latlngs, function(key, points) 
		{
		//	if()
		//	{
				//console.log(radius);
				
				//commentaire temporaire !!!!
				/*
				var circle = new L.circle([points.lat, points.lng], radius).addTo(map);
				//ajout du cercle à un layer groupe
				radiusGroup.addLayer(circle);
				*/
		//	}
		});
	});			
	  ///////////FIN DU CALCUL
    })

    map.on('dragend', function () {
        if (map.getZoom() == formerZoom ) {
            markerGroup.eachLayer(function (layer) {
                map.removeLayer(layer);
            });
            drawnItems.eachLayer(function (layer) {
                map.removeLayer(layer);
            });
            loadSegments();
        }
    })
    $("#map").css("cursor", "move");
    loadPois();
    loadSegments();
    if (isLoadingMap) {
        segmentID = traceData.segment.id;
        displayTrace(traceData.segment.trace, traceData.segment.elevation);
        $("#denivp").text("Dénivelé positif : " + traceData.deniveleplus + "m");
        $("#denivn").text("Dénivelé négatif : " + traceData.denivelemoins + "m");
        $("#long").text("Longueur : " + traceData.longueur + "km");
        $("#diffiDisplay").text("Difficulté : " + traceData.difficulte.label);
        isLoadingMap = false;
    }

}

function addOverlay() {
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
function moveToCoords(lat, lng, zoom) {
    map.setView([$("#lat").val(), $("#lng").val()], $("#zoom").val());
}

//Affiche les coordonnées
function displayCoords(event) {
    $("#lat").val(event.latlng.lat);
    $("#lng").val(event.latlng.lng);
}


function parseGPX(path) {
    GPX = new L.GPX(path, {
        async: true,
        marker_options: {
            startIconUrl: 'Trail/web/pin-icon-start.png',
            endIconUrl: 'Trail/web/pin-icon-end.png',
            shadowUrl: 'Trail/web/pin-shadow.png'
        }
    }).on('loaded', function (e) {
            map.fitBounds(e.target.getBounds());
        }).addTo(map);
}

function geocode() {
    var geo = MQ.geocode({map: map})
        .search($("#ville").val());
}

function createRoute() {
    if (!isCreateRoute) {
        isCreateRoute = true;
        pointArray = [];
        latlngArray = [];
        polyline.on("click",function (e){
            console.log(e);
            buildRoute(e);
        })
    }
}

function drawRoute(event) {
    if (!isCreateRoute) {
        isCreateRoute = true;
        pointArray = [];
        latlngArray = [];
        map.on("click", function (ev) {
            addPointOnMap(ev);
        });
    }
}

function saveRoute() {
    loadDifficultes();
    loadStatus();
    loadTypechemin();
    //console.log(JSON.stringify(pointArray));
    $("#save").modal('show');
    $("#saveiti").on("click", function () {
        $.post(Routing.generate('site_carto_saveItineraire'),
            {
                points: JSON.stringify(pointArray),
                longueur: pointArray[pointArray.length - 1].distance,
                denivelep: denivelep,
                denivelen: denivelen,
                nom: $("#nom").val(),
                numero: $("#numero").val(),
                typechemin: $("#typechemin option:selected").val(),
                description: $("#description").val(),
                difficulte: $("#difficulte option:selected").val(),
                auteur: $("#auteur").val(),
                status: $("#status option:selected").val(),
                public: $("#public option:selected").val()
            },
            function (data, status) {

                $.notify("Itinéraire sauvegardé", "success");
            }
        ).fail(function () {
                $.notify("Erreur lors de la sauvegarde", "error");
            });
        $("#save").modal('hide');

    });

    isCreateRoute = false;

}

function drawSegment(event) {
    if (!isCreateSegment) {
        isCreateSegment = true;
        pointArray = [];
        latlngArray = [];
        map.on("click", function (ev) {
            addPointOnMap(ev);
        });
    }
}

function saveSegment() {  //console.log(JSON.stringify(pointArray));
    $.post(Routing.generate('site_carto_saveSegment'),
        {
            points: JSON.stringify(pointArray)
        },
        function (data, status) {
            $.notify("Segment sauvegardé", "success");
            polyline.id = data.id;
        }
    ).fail(function () {
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
            //console.log(data);
            var iconePoi = L.icon({iconUrl : data.path,iconSize : [30, 30]});
            var marker = L.marker([latPoi,lngPoi], {icon: iconePoi}).addTo(map).bindPopup("<p> <b>" + $("#titre").val() + "</b></p><p>" + $("#descriptionPoi").val() + "</p>");
            console.log(marker);
            markerGroup.addLayer(marker);
        });

    $("#addpoi").modal('hide');
}

//Afficher le modal de modification d'un poi
function modifPoiForm(idPoi)
{
    $('#modalEditPoi').children().remove();
    $('#modalEditPoi').remove();

    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_afficheEditPoi'),
        cache: false,
        data: {"idPoi" : idPoi},
        success: function(data){
            $('body').append(data);
            $("#modalEditPoi").modal('show');
        }
    });
}

//Modification d'un poi
function modifPoi(idPoi)
{
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_editPoi'),
        data: {"idPoi" : idPoi},
        cache: false,
        success: function(data){
            $("#modalEditPoi").modal('hide');
            markerSelectionne.closePopup();
            map.removeLayer(markerSelectionne);
            var iconePoi = L.icon({iconUrl : data.path,iconSize : [30, 30]});
            
            if(data.pathImagePoi != null)
            {
                var marker = L.marker([data.latPoi,data.lngPoi], {icon: iconePoi}).addTo(map).bindPopup("<div id='imgPoi' class='img-size' style='background-image: url(" + data.pathImagePoi + ");'></div> <p> <b>" + data.titrePoi + "</b></p><p>" + data.descriptionPoi + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + data.idPoi + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + data.idPoi + ")'>Modifier le POI</button>");
            }
            else
            {
                var marker = L.marker([data.latPoi,data.lngPoi], {icon: iconePoi}).addTo(map).bindPopup("<p> <b>" + data.titrePoi + "</b></p><p>" + data.descriptionPoi + "</p> <button id='supprPoi' type='button' class='btn btn-primary' onclick='supprPoiConfirm(" + data.idPoi + ")'>Supprimer le POI</button> <button id='modifPoi' type='button' class='btn btn-default' onclick='modifPoiForm(" + data.idPoi + ")'>Modifier le POI</button>");
            }
            markerGroup.addLayer(marker);
            marker.on("click", function (event) { markerSelectionne = event.target; });
        }
    });
}

//Afficher le modal de confirmation de suppression d'un poi
function supprPoiConfirm(idPoi)
{
    $('#modalWarningDeletePoi').children().remove();
    $('#modalWarningDeletePoi').remove();

    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_afficheDeletePoi'),
        cache: false,
        data: {"idPoi" : idPoi},
        success: function(data){
            $('body').append(data);
            $("#modalWarningDeletePoi").modal('show');
        }
    });
}

//Suppression d'un poi
function suppressionPoi(idPoi)
{
    $.ajax({
        type: "POST",
        url: Routing.generate('site_carto_deletePoi'),
        data: {"idPoi" : idPoi},
        cache: false,
        success: function(){
            console.log('map.remove(marker);');
        }
    });
}

function getElevation(response)
{
    blockItineraireSave();
    denivelen = 0;
    denivelep = 0;
    var poly = [];
    for(var i = 0; i < polyArray[polyArray.length - 1]._latlngs.length; i++)
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
    var geojson = polyline.toGeoJSON();
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
    if(isEditSegment)
    {
        updateSegment(JSON.stringify(pointArray));

    }
    blockItineraireSave();

}

function getElevationSegment(response)
{
    blockItineraireSave();
    denivelen = 0;
    denivelep = 0;
    //console.log("Taille de pointArray : " + pointArray.length);
    //console.log(response);
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
    var geojson = polyline.toGeoJSON();
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
    if(isEditSegment)
    {
        updateSegment(JSON.stringify(pointArray));

    }
    blockItineraireSave();
    map.on("click",function (ev){
        addPointOnMap(ev);
    });
}

function loadDifficultes() {
    $.ajax({
        url: Routing.generate('site_carto_getDifficulteParcours'),
        type: 'GET',
        dataType: 'json',
        success: function (json, statut) {
            for (var i = 0; i < json.length; i++) {
                var opt = $("<option>").attr("value", json[i].niveau).text(json[i].label);
                opt.appendTo("#difficulte");
            }
        },

        error: function (resultat, statut, erreur) {

        },

        complete: function (resultat, statut) {

        }

    });
}

function loadStatus() {
    $.ajax({
        url: Routing.generate('site_carto_getStatus'),
        type: 'GET',
        dataType: 'json',
        success: function (json, statut) {
            for (var i = 0; i < json.length; i++) {
                var opt = $("<option>").attr("value", json[i].id).text(json[i].label);
                opt.appendTo("#status");
            }
        },

        error: function (resultat, statut, erreur) {

        },

        complete: function (resultat, statut) {

        }

    });
}

function loadTypechemin() {
    $.ajax({
        url: Routing.generate('site_carto_getTypechemin'),
        type: 'GET',
        dataType: 'json',
        success: function (json, statut) {
            for (var i = 0; i < json.length; i++) {
                var opt = $("<option>").attr("value", json[i].id).text(json[i].label);
                opt.appendTo("#typechemin");
            }
        },

        error: function (resultat, statut, erreur) {

        },

        complete: function (resultat, statut) {

        }

    });
}

function csvJSON(csv) {

    var lines = csv.split("\n");

    var result = [];

    var headers = lines[0].replace(/(?:\\[r])+/g, "").split(",");

    for (var i = 1; i < lines.length; i++) {
        if (lines[i] !== "") {
            var obj = {};
            var currentline = lines[i].replace(/(?:\\[r])+/g, "").split(",");

            for (var j = 0; j < headers.length; j++) {
                obj[headers[j]] = currentline[j];
            }

            result.push(obj);
        }

    }
    //return result; //JavaScript object
    //return JSON.stringify(result); //JSON
    return result;
}

function displayTrace(trace, elevation) {
    //On convertit les coordonnées JSON en LatLng utilisables pour la polyline
    var LSCoords = trace.split(",");
    var latlngArr = [];
    for (var i = 0; i < LSCoords.length; i++) {
        var coords = LSCoords[i].split(" ");
        var res = new L.LatLng(coords[1], coords[0]);
        latlngArr.push(res);
    }

    //On crée la polyline et on ajoute les points

    polyline = L.polyline(latlngArr, {color: 'blue'});
    drawnItems.addLayer(polyline);
    console.log(drawnItems.getLayers());
    surbrillance(polyline);
    polyline.markers = [];
    for (var i = 0; i < latlngArr.length; i++) {
        var marker = new L.Marker([latlngArr[i].lat, latlngArr[i].lng], {
            icon: new L.DivIcon({
                iconSize: new L.Point(8, 8),
                className: 'leaflet-div-icon leaflet-editing-icon'
            })
        });
        //surbrillance(marker);
        marker.addTo(map);
        markerGroup.addLayer(marker);
        polyline.markers.push(marker);
    }

    //On ajoute le profil altimétrique
    var geojson = polyline.toGeoJSON();
    var elevations = elevation.split(";");
    for (var i = 0; i < geojson.geometry.coordinates.length; i++) {
        geojson.geometry.coordinates[i].push(elevations[i]);
    }
    mapgeojson = L.geoJson(geojson, {
        onEachFeature: el.addData.bind(el)
    });

    polyline.addTo(map);


    //Events de la polyline, on retire les points selon les différents cas
    map.on('draw:editstart', function (e) {
        for (var i = 0; i < polyline.markers.length; i++) {
            map.removeLayer(polyline.markers[i]);
        }
    });
    map.on("draw:editstop", function () {
        for (var i = 0; i < polyline.markers.length; i++) {
            polyline.markers[i].addTo(map);
        }
    })
    map.on('draw:edited', function (e) {
        polyline.markers = [];
        pointArray = [];
        var latlngs = polyline.getLatLngs();
        var URL = elevationUpdateURL + '&latLngCollection=';
        for (var i = 0; i < latlngs.length; i++) {
            var marker = new L.Marker([latlngArr[i].lat, latlngArr[i].lng], {
                icon: new L.DivIcon({
                    iconSize: new L.Point(8, 8),
                    className: 'leaflet-div-icon leaflet-editing-icon'
                })
            });
            marker.addTo(map);
            polyline.markers.push(marker);
            markerGroup.addLayer(marker);
            pointArray.push(new Point(latlngs[i].lat, latlngs[i].lng));
            URL += latlngs[i].lat + "," + latlngs[i].lng;
            if (i !== latlngs.length - 1) {
                URL += ",";
            }
        }
        URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        elevationScript = document.createElement('script');
        elevationScript.type = 'text/javascript';
        elevationScript.src = URL;
        $("body").append(elevationScript);

    });
    map.on('draw:deletestart', function (e) {
        polyline.on("click", function () {
            for (var i = 0; i < polyline.markers.length; i++) {
                map.removeLayer(polyline.markers[i]);
            }
        })
    });

    map.fitBounds(polyline.getBounds());//On centre la map sur la polyline
}

function displaySegment(trace,id) {
    //On convertit les coordonnées JSON en LatLng utilisables pour la polyline
    var LSCoords = trace.split(",");
    var latlngArr = [];
    for (var i = 0; i < LSCoords.length; i++) {
        var coords = LSCoords[i].split(" ");
        var res = new L.LatLng(coords[1], coords[0]);
        latlngArr.push(res);
    }

    //On crée la polyline et on ajoute les points

    polyline = L.polyline(latlngArr, {color: 'blue'});
    polyline.id = id;
    if(!segmentLoaded(polyline)) {
        drawnItems.addLayer(polyline);
    }
    //surbrillance(polyline);
    polyline.markers = [];
    for (var i = 0; i < latlngArr.length; i++) {
        var marker = new L.Marker([latlngArr[i].lat, latlngArr[i].lng], {
            icon: new L.DivIcon({
                iconSize: new L.Point(8, 8),
                className: 'leaflet-div-icon leaflet-editing-icon'
            })
        });
        //surbrillance(marker);
        marker.overlaped = [];
        marker.addTo(map);
        markerGroup.eachLayer(function(layer){
            if(latlngEquality(layer.getLatLng(),marker.getLatLng()))
            {
                marker.overlaped.push(layer);
                layer.overlaped.push(marker);
            }
        });
        markerGroup.addLayer(marker);

        polyline.markers.push(marker);
    }



    polyline.addTo(map);
}

function loadMap(json) {
    isLoadingMap = true;
    traceData = json;
}

function surbrillance(object) {
    object.on("mouseover", function () {
        object.setStyle({color: 'yellow'});
        object.redraw();
    });
    object.on("mouseout", function () {
        object.setStyle({color: 'blue'});
        object.redraw();
    });
}

function updateSegment(points) {
    $.post(Routing.generate('site_carto_segment_update'),
        {
            points: points,
            id: segmentID
        },
        function (data, status) {

            $.notify("Segment mis à jour", "success");
        }
    ).fail(function () {
            $.notify("Erreur lors de la mise à jour", "error");
        });
    isEditSegment = false;
}

function blockItineraireSave() {
    if (!$("#saveiti").prop("disabled")) {
        $("#saveiti").prop("disabled", true).change();
    }
    else {
        $("#saveiti").prop("disabled", false).change();
    }
}

function addPointOnMap(ev) {

    pointArray.push(new Point(ev.latlng.lat, ev.latlng.lng));
    //console.log("Nouveau point ajouté, nouvelle taille : " + pointArray.length);
    latlngArray.push(ev.latlng);
    if (pointArray.length > 1) {
        polyline = L.polyline(latlngArray);
        var URL = elevationSegmentURL + '&latLngCollection=';
        for (var i = 0; i < latlngArray.length; i++) {
            var lat = latlngArray[i].lat;
            var lng = latlngArray[i].lng;
            URL += lat + "," + lng;
            if (i !== latlngArray.length - 1) {
                URL += ",";
            }
        }
        URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
        elevationScript = document.createElement('script');
        elevationScript.type = 'text/javascript';
        elevationScript.src = URL;
        $("body").append(elevationScript);
        map.off("click");
    }
}

function loadSegments() {
    var bounds = map.getBounds();
    $.post(Routing.generate('site_carto_loadSegment'),
        {
            northeast: JSON.stringify(new Point(bounds._northEast.lat, bounds._northEast.lng)),
            southwest: JSON.stringify(new Point(bounds._southWest.lat, bounds._southWest.lng)),
            northwest: JSON.stringify(new Point(bounds._northEast.lat, bounds._southWest.lng)),
            southeast: JSON.stringify(new Point(bounds._southWest.lat, bounds._northEast.lng))
        },
        function (data, status) {
            var json = JSON.parse(data);
            jQuery.each(json.searchResults, function (k, v) {
                displaySegment(v.trace, v.id);
            });
            //$.notify("Segment mis à jour", "success");
        }
    ).fail(function () {
            $.notify("Erreur lors du chargement du graphe des chemins", "error");
        });
}

function buildRoute(e)
 {
 var oldSize = polyArray.length;
     var selectedPoly = e.target;
     polyArray.push(selectedPoly);

     if(oldSize === 0)
     {
         routeDeleteButton = L.easyButton('fa-eraser',
             function (){
                 deleteLastSegment();
             },
             "Retirer le dernier segment de l'itinéraire"
         );
     }

     var URL = elevationURL + '&latLngCollection=';
     for (var i = 0; i <selectedPoly._latlngs.length; i++) {
         var lat = selectedPoly._latlngs[i].lat;
         var lng = selectedPoly._latlngs[i].lng;
         URL += lat + "," + lng;
         if (i !== selectedPoly._latlngs.length - 1) {
             URL += ",";
         }
     }
     URL.replace(/</g, '&lt;').replace(/>/g, '&gt;');
     elevationScript = document.createElement('script');
     elevationScript.type = 'text/javascript';
     elevationScript.src = URL;
     $("body").append(elevationScript);

     bestChoices(selectedPoly);

 }

//Brillance de la polyline
function glow(object)
{
    object.setStyle({color: 'yellow',dashArray : "1"});
    object.redraw();
}

//Polyline normale
function unglow(object)
{
    object.setStyle({color: 'blue',dashArray : "1"});
    object.redraw();
}

//Mise en évidence d'une polyline (pour les segments du graphe)
function attention(object)
{
    object.setStyle({color: 'red', dashArray : "5, 5" });
    object.redraw();
}

//Suppression du dernier segment de l'itinéraire
function deleteLastSegment()
{
        unglow(polyArray[polyArray.length - 1]);
        polyArray.pop();
        bestChoices(polyArray[polyArray.length - 1]);
        if(polyArray.length === 0)
        {
            routeDeleteButton.removeFrom(map);
        }
}

//Proposition des segments contigus
function bestChoices(selectedPoly)
{
    if(potentialPoly.length !== 0)
    {
        jQuery.each(potentialPoly,function(index,value){
            unglow(value);
        })
    }
    jQuery.each(polyArray,function(index,value){
        glow(value);
    });
    potentialPoly = [];
    if(selectedPoly !== undefined)
    {
        drawnItems.eachLayer(function(layer){
            if(layer !== selectedPoly)
            {
                var pog1SelectedPoly = selectedPoly._latlngs[0]; //POG1 selectedPoly
                var pog2SelectedPoly = selectedPoly._latlngs[selectedPoly._latlngs.length - 1]; //POG2 selectedPoly
                var pog1Layer = layer._latlngs[0]; //POG1 layer
                var pog2Layer = layer._latlngs[layer._latlngs.length - 1]; //POG2 layer

                if(latlngEquality(pog1Layer,pog2SelectedPoly) ||
                    latlngEquality(pog2Layer,pog2SelectedPoly) ||
                    latlngEquality(pog1Layer,pog1SelectedPoly) ||
                    latlngEquality(pog2Layer,pog1SelectedPoly)
                )
                {
                    if(polyArray.indexOf(layer) === -1)
                    {
                        attention(layer);
                        potentialPoly.push(layer);
                    }

                }
            }

        });
    }

}

function latlngEquality(latlngA, latlngB)
{
    return latlngA.lat === latlngB.lat && latlngA.lng === latlngB.lng;
}

function segmentLoaded(poly)
{
    var res = false;
    drawnItems.eachLayer(function (layer) {
        if(layer.id === poly.id)
        {
            res = true;
        }
    });
    return res;
}
