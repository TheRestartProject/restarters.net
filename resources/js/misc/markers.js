var map;
var infowindows = Array();
var markers = Array();
function initHomeMap() {
    var centerLatLng = new google.maps.LatLng(20, 0); 
    var mapOptions = {
        zoom: 2,
        center: centerLatLng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    
    map = new google.maps.Map(document.getElementById('groupWorldMap'), mapOptions);
    
    /** get & set markers **/
    $.getJSON('/ajax/group_locations', {}, function(data){
        data.forEach(function(g){
            infowindows[g.id] = new google.maps.InfoWindow({
                content: '<h4>' + g.name + '</h4><p>' + g.location + ', ' + g.area + '</p>'      
            });
            
            markers[g.id] = new google.maps.Marker({
                        position: new google.maps.LatLng( g.latitude, g.longitude),
                        map: map,
                        title: g.name
            });
            
            google.maps.event.addListener(markers[g.id], 'click', function() {
                infowindows[g.id].open(map,markers[g.id]);
            });
        });
    });
}
google.maps.event.addDomListener(window, 'load', initHomeMap);