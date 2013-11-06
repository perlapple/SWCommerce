window.env = {

		widgetVersion: "0.7",
		enableLogging: true,
		mapWidth: widthForMap(),
		mapURI: "http://maps.google.com/maps/api/staticmap?",
		apiURI: "./web-server/",
		maxDistance: 1000,
		maxResults: 10,
		crawlOpeninghours: 1,
		checkAlsoOfferings: 1,
		location: { lat: 19.245782, lng: -103.733386 }
	};

// Deactivated transitions because of Styling Error with large descriptions
//$.mobile.defaultPageTransition = "none";
	
function l(word) {
	if (window.env.enableLogging) {
		$(".logBox").html($(".logBox").first().html()+word+"<br>");
	}
	return false;
}

function widthForMap(){
    if (window.innerHeight > window.innerWidth) {
        return window.innerWidth;
    } else {
        return window.innerHeight;
    }
}

function createMapImage(markers) {
	var imgurl = ""
			+ window.env.mapURI + "size=" + window.env.mapWidth + "x" + window.env.mapWidth
			+ "&mobile=true&sensor=false";
			

	if (markers) {
		for (var i = 1; i < markers.length + 1; i++) {
			imgurl = imgurl+"&markers=color:blue|label:" +  markers[i-1].listnumber  + "|" + markers[i-1].lat+","+markers[i-1].lng;
		}
	}
	
	imgurl=imgurl + "&markers=size:mid|color:red|"+window.env.location.lat+","+window.env.location.lng;
	return imgurl;
}

function openResultMapPage() {
	$("#ResultMapTopContainer").empty();
	var markers = [];
	
	$(".resultItem:not(.resultItemFiltered) a", "#ResultListResultsList").each(function(){
		var val = $(this).data("receivedData");
		markers.push({lat: val.geometry.location.lat, lng: val.geometry.location.lng, listnumber: val.listnumber});	
	});
	
	$(ich.tmp_resultHeader2({text: "Mapa de comercios y servicios", counter: $(".resultItem", "#ResultListResultsList").length})).appendTo("#ResultMapTopContainer", "#ResultMap");
	$(ich.tmp_detailsTopMap({imagesrc: createMapImage(markers), size: window.env.mapWidth})).appendTo("#ResultMapTopContainer", "#ResultMap");
	
	$("#ResultMapTopContainer").listview("refresh");
}

function createResultList(data) {
	//l("Success! Response Time: " + data.responseTime);
	
	$("#ResultListTopContainer, #ResultListResultsList").empty();

	window.env.location.lat = data.spos.lat;
	window.env.location.lng = data.spos.lng;

	var tmp_i = 0;
	
	$(ich.tmp_resultHeader2({text: "Lista de comercios", counter: data.results.length})).appendTo("#ResultListTopContainer", "#ResultList");
	
	if (data.results.length > 0) {
		$.each(data.results, function(key, val){
			tmp_i++;
			val.listnumber = tmp_i;
			var tmp_rep = checkOpeningStatus(val.openinghours);
			val.openinghoursreport = tmp_rep;

			$(ich.tmp_resultItem({
				code: tmp_rep.code,
				listnumber: tmp_i,
				name: val.name,
				distance: Math.round(val.distance),
				description: val.description,
				direction: val.formatted_address,
			})).appendTo("#ResultListResultsList", "ResultList").children("a").data("receivedData", val).click(openDetailsPage);
		});
	} else {
		$(ich.tmp_resultMessage2({message: "No se encontraron comercios"})).appendTo("#ResultListTopContainer", "#ResultList");
	}

	$("#ResultListResultsList, #ResultListTopContainer").listview("refresh");
	// $.mobile.hidePageLoadingMsg ();
	
	// fixes positioning problem
	//$("#ResultListResultsList li:first").addClass("ui-li-has-icon");
}

fakeTime = "13:30:00";

function checkOpeningStatus(openingHoursObject) {
	var returnValue = {text: "no data",	code: "na",	leftminutes: 0 };
	if (!openingHoursObject) { openingHoursObject=[] }
	if (openingHoursObject.length>0) {
		returnValue.text = "cerrado";
		returnValue.code = "no";
		
		var days = ["sunday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday"];
		//var days = ["domingo", "lunes", "martes", "miercoles", "jueves", "viernes", "sabado"];
		var now = new Date();
		var nowSeconds = now.getSeconds() + now.getMinutes() * 60 + now.getHours() * 60 * 60;
		
		// remove when real time is needed
		// nowSeconds = (fakeTime.split(":")[2] * 1) + (fakeTime.split(":")[1] * 60) + (fakeTime.split(":")[0] *60 * 60);
		
		$.each(openingHoursObject, function(key, val) {
			if (val.day.toLowerCase() == days[now.getDay()]) {
				var tmpOpens  = ((val.opens+":00:00:00").split(":"));
				var tmpCloses = ((val.closes+":00:00:00").split(":"));
				var opensSeconds = (tmpOpens[2] * 1) + (tmpOpens[1] * 60) + (tmpOpens[0] *60 * 60);
				var closesSeconds = (tmpCloses[2] * 1) + (tmpCloses[1] * 60) + (tmpCloses[0] *60 * 60);
				
				if ((nowSeconds >= opensSeconds) && (nowSeconds < closesSeconds)) {
					if (closesSeconds - nowSeconds > 1800) {
						returnValue.text = "Abierto";
						returnValue.code = "yes";
					} else {
						returnValue.text = "Apenas abierto";
						returnValue.code = "barely";
					}
				}
			}
		});
	}
	return returnValue;
}

function openOfferingsPage(data, unlimited, maxofferings) {
	$.each(data.results, function(key, val) {
		$("#offeringsContainer .placeholder").remove();
		if (!val.homepage) { val.homepage="javascript: void(0)"; }
			$(ich.tmp_offeringsOffer({label: val.name, text: unescape(val.description), price: val.price, seealso: val.homepage})).appendTo("#offeringsContainer", "#Offerings");
	});
	
	if ((!unlimited) && maxofferings>20) {
		$(ich.tmp_detailsInstantViewsOfferings({theme: "a", label: "Mostrar todos los productos o servicios", count: maxofferings-20}))
			.appendTo("#offeringsContainer", "#Offerings")
			.click(function() {
				ajaxGetResultOfferings(data, "1");
			});
	}
	
	$("#offeringsContainer").listview("refresh");
}

function openDetailsPage() {
	//TODO: Remove alert and insert real tel instead!-
	$("#venueTopContainer, #venueMoreContainer, #venueOpeningHoursContainer").empty();

	var tmp_obj = $(this);


	$("#DetailsFooterButton1").unbind("click").click(function(){
		$("#ResultListResultsList").find(tmp_obj.parents(".resultItem")).prevAll(":not(.resultItemFiltered):first").find("a").trigger("click");
		return false;
	});
	
	$("#DetailsFooterButton3").unbind("click").click(function() {
		$("#ResultListResultsList").find(tmp_obj.parents(".resultItem")).nextAll(":not(.resultItemFiltered):first").find("a").trigger("click");
		return false;
	});
	
	var tmp = $(this).data('receivedData');

	$(ich.tmp_detailsTopHeader({label: tmp.name, listnumber: tmp.listnumber})).appendTo("#venueHeaderContainer", "#Details");
	
	
	// Start Inserting Instant-Action-Buttons
	// if (tmp.formatted_phone_number) {
	// 	$(ich.tmp_detailsTopClickToActionButton({label: "llamar", theme: ""}))
	// 		.appendTo("#venueTopContainer", "#Details")
	// 		.attr("href","tel:"+tmp.formatted_phone_number)
	// 		.button();
	// 	$(ich.tmp_detailsInstantViewsNoLogo({label: "Teléfono:", text: tmp.formatted_phone_number})).appendTo("#venueMoreContainer", "#Details");
	// }

	if (tmp.offerings>0) {
	 	$(ich.tmp_detailsInstantViewsOfferings({theme: "a", label: "Productos o Servicios", count: tmp.offerings}))
	 		.appendTo("#venueTopContainer", "#Details")
	 		.click(function() {
	 			ajaxGetResultOfferings(tmp, false);
	 		});
	 }
	if (tmp.formatted_fax_number) {
		$(ich.tmp_detailsInstantViewsNoLogo({label: "Telefax:", text: tmp.formatted_fax_number})).appendTo("#venueMoreContainer", "#Details");
	}

	if (tmp.agent) {
		$(ich.tmp_detailsInstantViewsNoLogo({label: "Agente:", text: tmp.agent})).appendTo("#venueMoreContainer", "#Details");
	}
		
	if (tmp.formatted_email) {
		$(ich.tmp_detailsInstantViewsNoLogo({label: "E-Mail:", text: tmp.formatted_email})).appendTo("#venueMoreContainer", "#Details");

		$(ich.tmp_detailsTopClickToActionButton({label: "email", theme: "a"}))
			.appendTo("#venueTopContainer", "#Details")
			.attr("href","mailto:"+tmp.formatted_email)
			.attr("data-mini","true")
			.button();
	}
	
	if (tmp.homepage) {
		$(ich.tmp_detailsInstantViewsNoLogo({label: "Página Web:", text: tmp.homepage})).appendTo("#venueMoreContainer", "#Details");

		$(ich.tmp_detailsTopClickToActionButton2({label: "Página Web", theme: "b"}))
			.appendTo("#venueTopContainer", "#Details")
			.attr("href", tmp.homepage)
			.attr("target", "_blank")
			.attr("data-icon","home")
			.attr("data-mini","true")
			.attr("data-theme","a")
			.button();
	}
	
	// Start Inserting Payment Methods
	if (tmp.paymentmethods) {
		$("<br/>").appendTo("#venueTopContainer", "#Details");
		var tmp_pmm = "";
if (tmp_pmm.length>0) {
			$(ich.tmp_detailsInstantViewsNoLogo({label: "Métodos de Pago:", text: tmp_pmm.substring(0, tmp_pmm.length-2)})).appendTo("#venueMoreContainer", "#Details");
		}

		$.each(tmp.paymentmethods, function(key, val) {
			if ($.inArray(val, ["Cash", "AmericanExpress", "DinersClub", "Discover", "JCB", "MasterCard", "VISA"])!=-1) {
				tmp_pmm = tmp_pmm + val + ", ";
				//$(ich.tmp_detailsTopPaymentMethods({paymentmethod: val})).appendTo("#venueMoreContainer", "#Details");
			}
		});

	if (tmp_pmm.length>0) {
			$(ich.tmp_detailsInstantViewsNoLogo({label: "Métodos de Pago:", text: tmp_pmm.substring(0, tmp_pmm.length-2)})).appendTo("#venueMoreContainer", "#Details");
		}

		$.each(tmp.paymentmethods, function(key, val) {
			if ($.inArray(val, ["Cash", "AmericanExpress", "DinersClub", "Discover", "JCB", "MasterCard", "VISA"])!=-1) {
				tmp_pmm = tmp_pmm + val + ", ";
				$(ich.tmp_detailsTopPaymentMethods({paymentmethod: val})).appendTo("#venueMoreContainer", "#Details");
			}
		});	
	}
	
	// Start Inserting Instant-View-Listelements
	if (tmp.logo) {
		$(ich.tmp_detailsInstantViewsWithLogo({imagesrc: tmp.logo, label:"Ubicación:", text:tmp.formatted_address})).appendTo("#venueTopContainer", "#Details");
	} else {
		$(ich.tmp_detailsInstantViewsNoLogo({label:"Ubicación:", text:tmp.formatted_address})).appendTo("#venueTopContainer", "#Details");
	}

	$(ich.tmp_detailsTopMap2({imagesrc: createMapImage([{lat: tmp.geometry.location.lat, lng: tmp.geometry.location.lng, listnumber: tmp.listnumber}]), size: window.env.mapWidth})).appendTo("#venueTopContainer", "#Details");
	cerrarMapa();

	if (tmp.category) {
		$(ich.tmp_detailsInstantViewsNoLogo({label: "Categoría:", text: tmp.category})).appendTo("#venueTopContainer", "#Details");
	}
	
	if (tmp.description) {
		$(ich.tmp_detailsInstantViewsNoLogo({label: "Descripción:", text: unescape(tmp.description)})).appendTo("#venueTopContainer", "#Details");
	}

	// if (tmp.offerings>0) {
	// 	$(ich.tmp_detailsInstantViewsOfferings({theme: "a", label: "Mostrar Productos o Servicios", count: tmp.offerings}))
	// 		.appendTo("#venueTopContainer", "#Details")
	// 		.click(function() {
	// 			ajaxGetResultOfferings(tmp, false);
	// 		});
	// }
	
	$("#venueOpeningHours b i", "#Details").text("Negocio "+tmp.openinghoursreport.text+".");
	if (tmp.openinghours) {
		var tmp_donedays = [];
		$.each(tmp.openinghours, function(key, val) {
			var tmp_day = {};
			if ($.inArray(val.day, tmp_donedays)==-1) {
				tmp_donedays.push(val.day)
					if(val.day == "sunday"){
						val.day = "Domingo";
					}
					if(val.day == "monday"){
						val.day = "Lunes";
					}
					if(val.day == "tuesday"){
						val.day = "Martes";
					}
					if(val.day == "wednesday"){
						val.day = "Miercoles";
					}
					if(val.day == "thursday"){
						val.day = "Jueves";
					}
					if(val.day == "friday"){
						val.day = "Viernes";
					}
					if(val.day == "saturday"){
						val.day = "Sabado";
					}
							
				$(ich.tmp_detailsOpeningHoursDivider({day: val.day.substr(0, 1).toUpperCase() + val.day.substr(1).toLowerCase()})).appendTo("#venueOpeningHours ul", "#Details");
			}

			

		$(ich.tmp_detailsOpeningHours(val)).appendTo("#venueOpeningHoursContainer", "#Details");
		});
		$("#venueOpeningHours", "#Details").show();
	} else {
		$("#venueOpeningHours", "#Details").hide();
	}

	if ($("#venueMoreContainer li", "#Details").length>0) {
		$("#venueMore", "#Details").show();
	} else {
		$("#venueMore", "#Details").hide();
	}
	

	//$.mobile.changePage("#Details");
	window.location.replace("#Details");
	$("#venueTopContainer, #venueMoreContainer, #venueOpeningHoursContainer").listview("refresh");
}


function cerrarMapa() {
			$("#mapaDetails").hide();
}

function startAddressSearch() {
	ajaxGetResultVenues( $("#SearchAddress").val() ); 
	//$.mobile.changePage("#ResultList");
	window.location.replace("#ResultList");
}

function geoGetLatLng() {
	if ( navigator.geolocation ) {
    	navigator.geolocation.getCurrentPosition(function(position) {
			window.env.location.lat = position.coords.latitude;
			window.env.location.lng = position.coords.longitude;
			ajaxGetResultVenues( window.env.location);
    	});
  } else {
  	alert("No Geolocation Possible! Use address search instead.");
	//$.mobile.changePage("#Search");
	window.location.replace("#Search");
  }
}

function ajaxGetResultVenues(position) {
	if (position.lat && position.lng) {
		sentData = { 
			"crawlOpeninghours": window.env.crawlOpeninghours, 
			"checkAlsoOfferings": window.env.checkAlsoOfferings,
			"maxDistance": window.env.maxDistance,
			"maxResults": window.env.maxResults,
			"baseDate": (Math.round(new Date().getTime() / 1000) - new Date().getTimezoneOffset()*60),
			"lat": position.lat,
			"lng": position.lng
		}		
	} else {
		sentData = { 
			"crawlOpeninghours": window.env.crawlOpeninghours, 
			"checkAlsoOfferings": window.env.checkAlsoOfferings,
			"maxDistance": window.env.maxDistance,
			"maxResults": window.env.maxResults,
			"baseDate": (Math.round(new Date().getTime() / 1000) - new Date().getTimezoneOffset()*60),
			"address": position
		}
	}
	
	$("#ResultListTopContainer, #ResultListResultsList").empty();
	$(ich.tmp_resultMessage({message: "Obteniendo información"})).appendTo("#ResultListTopContainer", "#ResultList");


	$.ajax({
			url: window.env.apiURI,
			dataType: 'jsonp',
			data: sentData,
			crossDomain: true,
			jsonpCallback: "jqcb",
			success: createResultList
		});
}

function ajaxGetResultOfferings(data, unlimited) {
	if (unlimited) {
		sentData = {"locationuri": data.locationuri, "unlimited": "1" };
		$("#Offerings #offeringsContainer li:last").remove();
		$(ich.tmp_resultMessage({message: "Procesando ofertas"})).appendTo("#offeringsContainer", "#Offerings");
	} else {
		$("#offeringsContainer").empty();
		$(ich.tmp_resultHeader({text: data.name, counter: data.offerings})).appendTo("#offeringsContainer", "#Offerings");
		$(ich.tmp_resultOfferingMessage({message: "Procesando ofertas"})).appendTo("#offeringsContainer", "#Offerings");
		sentData = {"locationuri": data.locationuri };
	}

	$.ajax({
		url: window.env.apiURI,
		dataType: 'jsonp',
		data: sentData,
		crossDomain: true,
		jsonpCallback: "jqcb",
		success: function(ret) {
				openOfferingsPage(ret, unlimited, data.offerings);
			}
		});
	
//	$.mobile.changePage("#Offerings");
window.location.replace("#Offerings");
	$("#offeringsContainer").listview("refresh");
}

function actionFilteringOptions(){
	// Eine Schleife die alle Filter durchläuft wann immer ein Filter aktiviert oder deaktiviert wurde.
	// Am Anfang erhält jedes Element die Flag 'show' und trifft ein Filter zu erhält es 'hide'. 
	// Am Ende werden alle Elemete durchlaufen und ihre Sichtbarkeit in Abhängigkeit von der Flag gesetzt. 
	
	$(".resultItem", "#ResultListResultsList").each(function() {
		var resultItem = this;
		var tmp_notyetfiltered = true;
		var tmp_allfilteroverride = false;
		
		$(".filterItem", "#ResultListFilter").each(function() {
			var filterItem = this;
			
			if (filterItem.name == "filterAllOverride" && filterItem.checked) {
				tmp_allfilteroverride = true;
			}
			
			if (filterItem.name == "filterMaximumDistance" && filterItem.checked && tmp_notyetfiltered && !tmp_allfilteroverride) {
				if ($(resultItem).find("a").data("receivedData").distance > $(filterItem).data("filterValue")) {
					tmp_notyetfiltered = false;
				}		
			}
			
			if (filterItem.name == "filterClosed" && filterItem.checked && tmp_notyetfiltered && !tmp_allfilteroverride) {
				if ($(resultItem).find("a").data("receivedData").openinghoursreport.code == "no") {
					tmp_notyetfiltered = false;
				}		
			}
			
			if (filterItem.name == "filterBarelyOpen" && filterItem.checked && tmp_notyetfiltered && !tmp_allfilteroverride) {
				if ($(resultItem).find("a").data("receivedData").openinghoursreport.code == "barely") {
					tmp_notyetfiltered = false;
				}		
			}
			
			if (filterItem.name == "filterNoOpeningInformation" && filterItem.checked && tmp_notyetfiltered && !tmp_allfilteroverride) {
				if ($(resultItem).find("a").data("receivedData").openinghoursreport.code == "na") {
					tmp_notyetfiltered = false;
				}		
			}
			
			if (filterItem.name == "filterOpen" && filterItem.checked && tmp_notyetfiltered && !tmp_allfilteroverride) {
				if ($(resultItem).find("a").data("receivedData").openinghoursreport.code == "yes") {
					tmp_notyetfiltered = false;
				}		
			}
			
			if (tmp_notyetfiltered || tmp_allfilteroverride) {
				$(resultItem).removeClass("resultItemFiltered");
			} else {

				$(resultItem).addClass("resultItemFiltered");
			}


		});
	});	
}

function cerrar() {
			$("#ResultListFilter").hide();
}



function setFilteringOptions() {
	if (this.name == "filterMaximumDistance") {
		if (this.checked) {
			var tmp_maxdist = parseInt(window.prompt("Distancia máxima en metros", 500))
			if (!isNaN(tmp_maxdist)) {
				$(this).data("filterValue", tmp_maxdist);
				$("#filerMaximumDistanceSpan", "#ResultListFilter").text("( "+tmp_maxdist+"m )").show();		
			} else {
				$(this).attr("checked",false).checkboxradio("refresh"); 
			}
		} else {
			$("#filerMaximumDistanceSpan", "#ResultListFilter").hide();
		}
	}
	
	actionFilteringOptions();
}
	
$(function(){
	if (window.env.enableLogging) {
		$("<div class='logBox'></div>").appendTo("div[data-role='page']");
	}
	
	// Action before the 'ResultMap' page is shown
	$("#ResultMap").live("pagebeforeshow", function() {
		// $.mobile.showPageLoadingMsg ();
		openResultMapPage();
		// $.mobile.hidePageLoadingMsg ();
	});
	
	$("#StartButtonAroundMe").click(function(){
		geoGetLatLng();
		//$.mobile.changePage("#ResultList");
		window.location.replace("#ResultList");
	});
	
	// Action for the Filter Button on the ResultList-Page
	$("#ResultListFooterButton1").click(function(){
		$("#ResultListFilter").toggle();
		return false;
	});

	$("#ResultListMapaButton1").click(function(){

		if( $("#mapaDetails").is(":visible"))
				$("#mapaDetails").hide();
		else
				$("#mapaDetails").show();
		return false;
	});

	

	// Action for the "First Venue" Button on the ResultList-Page
	$("#ResultListFooterButton3").click(function(){
		$("#ResultListResultsList .resultItem").not(".resultItemFiltered").first().find("a").trigger("click");
		return false;
	});	
	
	$("#ResultListFilter input").change( setFilteringOptions );
	
});