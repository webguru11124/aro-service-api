@extends('layouts.master-template')

@php
$pendingServices = $state['pending_services'];
$scheduledRoutes = $state['scheduled_routes'];
$stats = $state['stats'];
@endphp

@section('content')
    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
        <div class="container-fluid">
            <div class="navbar-brand">Scheduling</div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                <div class="navbar-nav">
                    <a class="nav-link" href="{{ URL::previous() }}">Back to Overview</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="d-flex justify-content-between">
                    <h1>Scheduling Map</h1>
                    <h2>Date: {{ $state['as_of_date'] }}</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-4">
                <table class="table table-sm">
                    <tbody>
                    <tr>
                        <td>Pending Services in pool</td>
                        <td>{{ $stats['pending_services_count'] }}</td>
                    </tr>
                    <tr>
                        <td>Pending Appointments on reschedule routes</td>
                        <td>{{ $stats['pending_rescheduled_services'] ?? 0 }}</td>
                    </tr>
                    </tbody>
                </table>
                <table class="table table-sm">
                    <thead>
                    <tr>
                        <th scope="col">Route #</th>
                        <th scope="col">Service Pro</th>
                        <th scope="col">Capacity <i class="bi bi-info-circle-fill text-info" title="Capacity: before / after / spots on route"></i></th>
                        <th scope="col">Appt <i class="bi bi-info-circle-fill text-info" title="Number of already assigned appointments"></i></th>
                        <th scope="col">Scheduled <i class="bi bi-info-circle-fill text-info" title="Number of (re)scheduled services by ARO"></i></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($scheduledRoutes as $route)
                        @php $collapseId = 'CS-' . $route['id'] @endphp
                        <tr id="route-select-{{ $route['id'] }}">
                            <td>
                                <a href="#{{ $collapseId }}" data-bs-toggle="collapse">
                                    <i style="color:lightgray" class="bi bi-square-fill route-color" title=""></i> {{ $route['id'] }}
                                </a>
                                @if(!empty($route['details']['route_type']))
                                    <span class="badge bg-primary me-2">{{ $route['details']['route_type'] }}</span>
                                @endif
                            </td>
                            <td><span class="route-service-pro">{{ $route['service_pro']['name'] }}</span></td>
                            <td>{{ $route['details']['capacity'] + count($route['pending_services']) }} / {{ $route['details']['capacity'] }} / <span class="text-secondary">{{ $route['details']['actual_capacity'] ?? '-' }}</span></td>
                            <td>{{ !empty($route['appointments']) ? count($route['appointments']) : '-' }}</td>
                            <td>{{ !empty($route['pending_services']) ? count($route['pending_services']) : '-' }}</td>
                        </tr>
                        <tr class="collapse" id="{{ $collapseId }}">
                            <td colspan="5">
                                <table class="table table-bordered table-sm caption-top">
                                    <caption>Already assigned appointments</caption>
                                    <thead>
                                    <tr>
                                        <th scope="col">Appointment #</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Duration</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($route['appointments'] as $appointment)
                                        <tr>
                                            <td>{{ $appointment['id'] }}</td>
                                            <td>
                                                @if(!empty($appointment['customer']))
                                                    {{ $appointment['customer']['name'] }} (#{{ $appointment['customer']['id'] }})
                                                @endif
                                            </td>
                                            <td>{{ $appointment['duration'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                @if(!empty($route['pending_services']))
                                    <table class="table table-bordered table-sm caption-top">
                                        <caption>Scheduled pending services</caption>
                                        <thead>
                                        <tr>
                                            <th scope="col">Subscription</th>
                                            <th scope="col">Customer</th>
                                            <th scope="col">Priority</th>
                                            <th scope="col">Reschedule</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($route['pending_services'] as $pendingService)
                                            <tr>
                                                <td>{{ $pendingService['subscription']['plan_name'] }} (#{{ $pendingService['subscription']['id'] }})</td>
                                                <td>{{ $pendingService['customer']['name'] }} (#{{ $pendingService['customer']['id'] }})</td>
                                                <td>
                                                    {{ $pendingService['priority'] }}
                                                    <i class="bi bi-info-circle-fill text-info" title="Previous Appointment: {{ $pendingService['previous_appointment']['date'] }} (#{{ $pendingService['previous_appointment']['id'] }})"></i>
                                                </td>
                                                <td>
                                                    @if(!empty($pendingService['is_rescheduled']))
                                                        {{ $pendingService['next_appointment']['date'] }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr>
                        <td>Total:</td>
                        <td></td>
                        <td>{{ $stats['capacity_before_scheduling'] }} / {{ $stats['capacity_after_scheduling'] }}</td>
                        <td>{{ $stats['appointments_count'] }}</td>
                        <td>{{ $stats['scheduled_services_count'] + ($stats['rescheduled_services_count'] ?? 0) }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div class="col">
                <div id='map'></div>
                <p class="font-italic">Click on a map marker to see details for the service</p>
            </div>
        </div>
    </div

@endSection

@section('styles')
    <style>
        #map {
            height: 600px;
            width: 100%;
        }
        .tab-pane {
            margin: 2%
        }
    </style>
@endSection

@section('scripts-body')
    <script>
        // Google Maps Initialization
        (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
            key: "AIzaSyAplKSDKUfUmxw_3L2VilWyr63IQNACucg",
            v: "weekly",
        });

        const pendingServices = {!! json_encode($pendingServices) !!};
        const scheduledRoutes = {!! json_encode($scheduledRoutes) !!};

        initializeMap();

        async function initializeMap() {
            const { Map } = await google.maps.importLibrary("maps");
            const startViewPosition = await getStartViewPosition(scheduledRoutes);

            let map = new Map(document.getElementById("map"), {
                zoom: 8,
                center: startViewPosition,
                mapId: "scheduling-state-map",
            });

            addMarkersToMap(map);
        }

        async function getStartViewPosition(routes) {
            // Get the start view position from the first route
            let position;

            for (let i = 0; i < routes.length; i++) {
                let route = routes[i];
                if (route.service_pro.location.lat !== 0) {
                    position = {lat: route.service_pro.location.lat, lng: route.service_pro.location.lng};
                }
                if (position) {
                    break;
                }
            }

            return position;
        }

        async function addMarkersToMap(map) {
            const { InfoWindow } = await google.maps.importLibrary("maps");
            const { PinElement } = await google.maps.importLibrary("marker");

            // Create the info window for the marker event listeners
            const infoWindow = new InfoWindow();

            // Add a pin for each Pending Service
            pendingServices.forEach(async (pendingService) => {
                await createMarker(
                    new PinElement({
                        background: 'lightgray',
                        borderColor: 'gray',
                        glyphColor: pendingService.is_high_priority ? 'red' : 'gray',
                    }),
                    `
                        <div data-customer-id="${pendingService.customer.id}">Customer: ${pendingService.customer.name} (#${pendingService.customer.id})</div>
                        <div data-subscription-id="${pendingService.subscription.id}">Subscription ID: ${pendingService.subscription.id}</div>
                        <div>Priority: ${pendingService.priority}</div>
                    `,
                    { lat: pendingService.location.lat, lng: pendingService.location.lng },
                    infoWindow,
                    map
                );
            });

            scheduledRoutes.forEach(async (route) => {
                const pinColor = await generateRandomHexColor();

                // Set the color for the route
                let routeSelectEl = document.getElementById('route-select-' + route.id);
                routeSelectEl.querySelector('.route-color').style.color = pinColor;

                // Add a pin for each Appointment on the route
                route.appointments.forEach(async (appointment) => {
                    await createMarker(
                        new PinElement({
                            background: pinColor,
                            borderColor: pinColor,
                            glyphColor: 'white'
                        }),
                        `
                            <div id="service-pro-name" data-service-pro-name="${route.service_pro.name}">Service Pro: ${route.service_pro.name}</div>
                            <div id="route-id" data-route-id="${route.id}">Route ID: ${route.id}</div>
                            <div id="appointment-id" data-appointment-id="${appointment.id}">Appointment ID: ${appointment.id}</div>
                        `,
                        { lat: appointment.location.lat, lng: appointment.location.lng },
                        infoWindow,
                        map
                    );
                });

                // Add a pin for each Pending Service on the route
                route.pending_services.forEach(async (pendingService) => {
                    await createMarker(
                        new PinElement({
                            background: 'white',
                            borderColor: pinColor,
                            glyphColor: pinColor
                        }),
                        `
                            <div id="service-pro-name" data-service-pro-name="${route.service_pro.name}">Service Pro: ${route.service_pro.name}</div>
                            <div id="route-id" data-route-id="${route.id}">Route ID: ${route.id}</div>
                            <div data-customer-id="${pendingService.customer.id}">Customer: ${pendingService.customer.name} (#${pendingService.customer.id})</div>
                            <div data-subscription-id="${pendingService.subscription.id}">Subscription ID: ${pendingService.subscription.id}</div>
                            <div>Priority: ${pendingService.priority}</div>
                        `,
                        { lat: pendingService.location.lat, lng: pendingService.location.lng },
                        infoWindow,
                        map
                    );
                });

                // Add marker of Service Pro
                await createMarker(
                    new PinElement({
                        background: pinColor,
                        borderColor: 'white',
                        glyphColor: pinColor
                    }),
                    `
                        <div id="service-pro-name" data-service-pro-name="${route.service_pro.name}">Service Pro: ${route.service_pro.name}</div>
                        <div id="route-id" data-route-id="${route.id}">Route ID: ${route.id}</div>
                    `,
                    { lat: route.service_pro.location.lat, lng: route.service_pro.location.lng },
                    infoWindow,
                    map
                );
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

        async function generateRandomHexColor() {
            return '#' + Math.floor(Math.random() * 16777215).toString(16);
        }

    </script>
@endSection
