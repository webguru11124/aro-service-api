<div id='map'></div>

@section('styles')
    <style>
        #map {
            height: 600px;
            width: 100%;
        }
    </style>
@endSection

@section('scripts-body')
    <!-- Google Maps Loader & Initialization -->
    <script>
        // Google Maps Initialization
        (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
            key: "AIzaSyAplKSDKUfUmxw_3L2VilWyr63IQNACucg",
            v: "weekly",
        });

        const routes = {!! json_encode($routes) !!};
        var routeObjects = [];

        initializeMap();

        async function initializeMap() {
            // Import Google Map Libraries
            const { Map } = await google.maps.importLibrary("maps");
            const startViewPosition = await getStartViewPosition();

            let map = new Map(document.getElementById("map"), {
                zoom: 8,
                center: startViewPosition,
                mapId: "routes-map",
            });

            addMarkersToMap(map);
        }

        async function getStartViewPosition() {
            // Get the start view position from the first appointment
            let position;
            for (let i = 0; i < routes.length; i++){
                let route = routes[i];

                for (let j = 0; j < route.schedule.length; j++){
                    let workEvent = route.schedule[j];
                    if (workEvent.work_event_type == 'Appointment') {
                        position = {
                            lat: workEvent.location ? workEvent.location.lat : workEvent.latitude,
                            lng: workEvent.location ? workEvent.location.lon : workEvent.longitude
                        };
                        break;
                    }
                }

                if(position) {
                    break;
                }
            }

            return position;
        }

        async function addMarkersToMap(map) {
            // Import Google Map Libraries
            const { InfoWindow } = await google.maps.importLibrary("maps");
            const { AdvancedMarkerElement, PinElement } = await google.maps.importLibrary("marker");

            // Create the info window for the marker event listeners
            const infoWindow = new InfoWindow();

            // Add markers for each route to the map
            routes.forEach(async (route) => {
                // Get the color of the pins for this route
                const pinColor = await generateRandomHexColor();

                let routeSelectEl = document.getElementById('route-select-' + route.id);

                // Set the color for the route
                if (routeSelectEl) {
                    routeSelectEl.querySelector('.route-color').style.color = pinColor;
                }

                await createMarker(
                    new PinElement({
                        background: pinColor,
                        borderColor: 'white',
                        glyphColor: pinColor
                    }),
                    `
                        <h5>Start Location</h5>
                        <div>Service Pro: ${route.service_pro.name}</div>
                        <div>Route ID: ${route.id}</div>
                    `,
                    { lat: route.details.start_location.lat, lng: route.details.start_location.lon },
                    infoWindow,
                    map
                );

                await createMarker(
                    new PinElement({
                        background: pinColor,
                        borderColor: 'white',
                        glyphColor: 'black'
                    }),
                    `
                        <h5>End Location</h5>
                        <div>Service Pro: ${route.service_pro.name}</div>
                        <div>Route ID: ${route.id}</div>
                    `,
                    { lat: route.details.end_location.lat, lng: route.details.end_location.lon },
                    infoWindow,
                    map
                );

                let routeGeometryPoliline = null;
                if (route.geometry != null) {
                    routeGeometryPoliline = await drawRoutePath(route.geometry, pinColor, map);
                }

                routeObjects.push({
                    id: route.id,
                    color: pinColor,
                    geometry: routeGeometryPoliline,
                });

                // Add a pin for each Appointment work event on the route
                route.schedule.forEach(async (workEvent) => {
                    if (workEvent.work_event_type == 'Meeting') {
                        await createMarker(
                            new PinElement({
                                background: 'red',
                                borderColor: pinColor,
                                glyphColor: 'black'
                            }),
                            `
                                <h5>Meeting: ${workEvent.description}</h5>
                                <div>Service Pro: ${route.service_pro.name}</div>
                            `,
                            { lat: workEvent.location.lat, lng: workEvent.location.lon },
                            infoWindow,
                            map
                        );
                    }

                    if (workEvent.work_event_type == 'Appointment') {

                        const pin = new PinElement({
                            background: pinColor,
                            borderColor: pinColor,
                            glyphColor: "white"
                        });

                        const marker = new AdvancedMarkerElement({
                            map,
                            position: {
                                lat: workEvent.location ? workEvent.location.lat : workEvent.latitude,
                                lng: workEvent.location ? workEvent.location.lon : workEvent.longitude
                            },
                            title: `
                                <h5 id="appointment-id" data-appointment-id="${workEvent.appointment_id}">Appointment #${workEvent.appointment_id}</h5>
                                <div id="route-id" data-route-id="${route.id}">Route ID: ${route.id}</div>
                                <div data-service-pro-name="${route.service_pro.name}">Service Pro: ${route.service_pro.name} (#${route.service_pro.id})</div>
                                <div>Preferred Tech ID: ${workEvent.preferred_tech_id ?? '-'}</div>
                                <div>Priority: ${workEvent.priority}</div>
                                <div>Duration: ${workEvent.service_duration}+${workEvent.setup_duration} min</div>
                                <div>Expected at: ${formatTimeWindow(workEvent.expected_time_window)}</div>
                                <div>Scheduled at: ${formatTimeWindow(workEvent.scheduled_time_window)}</div>
                            `,
                            content: pin.element
                        });

                        marker.addListener("click", ({ domEvent, latLng }) => {
                            const { target } = domEvent;

                            // Parse the html DOM from the title text
                            const parser = new DOMParser();
                            const titleDom = parser.parseFromString(marker.title, 'text/html');

                            // Get the route ID from the title
                            let routeId = titleDom.querySelector("#route-id").getAttribute('data-route-id');
                            let appointmentId = titleDom.querySelector("#appointment-id").getAttribute('data-appointment-id');

                            showRouteDetails(routeId, appointmentId);

                            // Close the other info windows and open the new one with the correct title
                            infoWindow.close();
                            infoWindow.setTitle
                            infoWindow.setContent(marker.title);
                            infoWindow.open(marker.map, marker);
                        });
                    }
                });
            });
        }

        async function createMarker(pin, title, location, infoWindow, map) {
            // Import Google Map Libraries
            const { AdvancedMarkerElement } = await google.maps.importLibrary("marker");
            const marker = new AdvancedMarkerElement({
                map,
                position: location,
                title: title,
                content: pin.element
            });

            marker.addListener("click", ({ domEvent, latLng }) => {
                infoWindow.close();
                infoWindow.setContent(marker.title);
                infoWindow.open(marker.map, marker);
            });
        }

        async function showRouteDetails(routeId, appointmentId) {
            let routeSelectEl = document.getElementById('route-select-' + routeId);
            if (!routeSelectEl.querySelector('.route-service-pro')) {
                return;
            }
            let pinColor = routeSelectEl.querySelector('.route-color').style.color;
            let serviceProName = routeSelectEl.querySelector('.route-service-pro').innerHTML;
            let routeOptimizationScore = routeSelectEl.querySelector('.route-score').innerHTML;

            // Add hide the previous route details/metrics/stats
            document.querySelectorAll('.route-details').forEach((routeDetails) => {
                routeDetails.classList.add('d-none');
            });
            document.querySelectorAll('.route-metrics').forEach((routeMetrics) => {
                routeMetrics.classList.add('d-none');
            });
            document.querySelectorAll('.route-stats').forEach((routeStats) => {
                routeStats.classList.add('d-none');
            });

            // Set the route and service pro title on the page
            document.getElementById('route-title').innerHTML = "Route: " + routeId;
            document.getElementById('service-pro-title').innerHTML = "Service Pro: " + serviceProName;

            // Display the route details/metrics for the specific route
            document.getElementById(`route-metrics-${routeId}`).classList.remove('d-none');
            document.getElementById(`route-stats-${routeId}`).classList.remove('d-none');
            document.getElementById('optimization-score').classList.remove('d-none');

            let routeTimelineEl = document.getElementById(`route-timeline-${routeId}`);

            document.getElementById(`routes-timeline`).querySelectorAll('.timeline-event-appointment').forEach((eventEl) => {
                eventEl.classList.remove('selected');
            });
            routeTimelineEl.querySelectorAll('.timeline-event-appointment').forEach((eventEl) => {
                if (eventEl.getAttribute('data-appointment-id') == appointmentId) {
                    eventEl.classList.add('selected');
                }
            });

            // Set the optimization score
            document.getElementById('optimization-score').innerHTML = routeOptimizationScore + "%";

            // Set the color for the route
            document.getElementById('route-color').style.color = pinColor;

            // Determine color of score
            if (routeOptimizationScore >= 90) {
                document.getElementById('optimization-score').classList.add('bg-success');
                document.getElementById('optimization-score').classList.remove('bg-warning');
                document.getElementById('optimization-score').classList.remove('bg-danger');
            } else if(routeOptimizationScore >= 70) {
                document.getElementById('optimization-score').classList.remove('bg-success');
                document.getElementById('optimization-score').classList.add('bg-warning');
                document.getElementById('optimization-score').classList.remove('bg-danger');
            } else {
                document.getElementById('optimization-score').classList.remove('bg-success');
                document.getElementById('optimization-score').classList.remove('bg-warning');
                document.getElementById('optimization-score').classList.add('bg-danger');
            }

            // Also display the rules list
            let lists = document.getElementsByClassName(`rules-list`);
            for(let list of lists) {
                list.classList.remove('d-none');
            }

            // hide geometry for other routes
            for (let routeObject of routeObjects) {
                if (!routeObject.geometry) {
                    continue;
                }

                routeObject.geometry.setVisible(routeObject.id == routeId);
            }
        }

        async function generateRandomHexColor() {
            return '#' + Math.floor(Math.random() * 16777215).toString(16);
        }

        function formatTimeWindow(timeObject) {
            return `${timeObject.start.substring(11)} - ${timeObject.end.substring(11)}`;
        }

        async function drawRoutePath(geometry, color, map) {
            const { encoding } = await google.maps.importLibrary("geometry");
            const { Polyline } = await google.maps.importLibrary("maps");
            let decodedPath = encoding.decodePath(geometry);

            return new Polyline({
                path: decodedPath,
                strokeColor: color,
                strokeOpacity: 1.0,
                strokeWeight: 2,
                map: map
            });
        }

    </script>
@endSection
