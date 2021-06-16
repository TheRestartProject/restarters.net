                /** Google Maps GeoCoder **/
                var geocoder;
                var map;
                function initialize() {
                  geocoder = new google.maps.Geocoder();
                  var baseLat = document.getElementById('latitude').value;
                  var baseLon = document.getElementById('longitude').value;
                  // If nulls, set center in london
                  if (baseLat === '') {
                    baseLat = 51.5286416;
                  }
                  if (baseLon === '') {
                    baseLon =  -0.1015987;
                  }

                  var latlng = new google.maps.LatLng(baseLat, baseLon);
                  var mapOptions = {
                    zoom: 6,
                    center: latlng
                  }
                  map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);
                  var marker = new google.maps.Marker({
                      position: latlng,
                      map: map,
                      title: document.getElementById('location').value
                  });
                }

                function codeAddress() {
                  var address = document.getElementById('location').value;
                  //var venue   = document.getElementById('venue').value;
                  geocoder.geocode( { 'address': address }, function(results, status) {
                    if (status == google.maps.GeocoderStatus.OK) {
                      map.setCenter(results[0].geometry.location);
                      var marker = new google.maps.Marker({
                          map: map,
                          position: results[0].geometry.location
                      });

                      console.log(results[0]);

                      document.getElementById('latitude').value = results[0].geometry.location.lat();
                      document.getElementById('longitude').value = results[0].geometry.location.lng();

                      /** get city lvl info **/
                      $.each(results[0].address_components, function (i, address_component) {
                        if (address_component.types[0] == "locality"){
                          document.getElementById('area').value = address_component.long_name;
                        }
                      });


                    } else {
                      alert('Geocode was not successful for the following reason: ' + status);
                    }
                  });
                }

                google.maps.event.addDomListener(window, 'load', initialize);
