@extends('layouts.master-template')

@section('scripts-head')
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <script src="https://unpkg.com/fabric@5.3.0/dist/fabric.min.js"></script>
@endSection

@section('content')
<div class="container-fluid">
    <div class="row mb-1">
        <div class="col-2">
            <form>
                <div class="mb-2">
                    <label for="totalServicePros" class="form-label">Number of Service Pros</label>
                    <input type="text" class="form-control" id="totalServicePros" aria-describedby="emailHelp" value="4">
                </div>
                <div class="mb-2">
                    <label for="totalServices" class="form-label">Number of Services</label>
                    <input type="text" class="form-control" id="totalServices" aria-describedby="emailHelp" value="200">
                </div>
                <div class="mb-2">
                    <label for="pastDueDatePercent" class="form-label">Percentage of past due date services</label>
                    <input type="text" class="form-control" id="pastDueDatePercent" aria-describedby="emailHelp" value="2">
                </div>
                <div class="mb-2">
                    <label for="totalServiceProAreaRadius" class="form-label">Service Pro Radius</label>
                    <input type="text" class="form-control" id="totalServiceProAreaRadius" aria-describedby="emailHelp" value="300">
                </div>
                <div class="mb-2">
                    <label for="expandClustersUpTo" class="form-label">Expand steps</label>
                    <input type="text" class="form-control" id="expandClustersUpTo" aria-describedby="emailHelp" value="20">
                </div>
                <div class="mb-2">
                    <button type="button" class="btn btn-primary" onclick="init()">Create Random Model</button>
                </div>
                <hr>
                @if(!empty($service_pros))
                <div class="mb-3">
                    <button type="button" class="btn btn-primary" onclick="initImport()">Import Model from State</button>
                </div>
                <hr>
                @endif
                <div class="mb-3">
                    <p>Create clusters by including most weighted service to current route</p>
                    <button type="button" class="btn btn-primary" onclick="expandClustersByMostWeightedService()">Create</button>
                    <button type="button" class="btn btn-primary" onclick="nextDay(1)">Next Day</button>
                </div>
                <hr>
                <div class="mb-3">
                    <p>Create clusters by including nearest services to cluster's fixed centroid (another high priority service or nearest to Service Pro location)</p>
                    <button type="button" class="btn btn-primary" onclick="expandClustersByNearestService()">Create</button>
                    <button type="button" class="btn btn-primary" onclick="nextDay(2)">Next Day</button>
                </div>
                <hr>
                <div class="mb-3">
                    <p>Create clusters by including nearest weighted services to cluster's calculated centroid (average location of all services in cluster)</p>
                    <button type="button" class="btn btn-primary" onclick="expandClustersWithMovingCentroid()">Create</button>
                    <button type="button" class="btn btn-primary" onclick="nextDay(3)">Next Day</button>
                </div>
                <hr>
            </form>
        </div>
        <div class="col">
            <div class="border m-1" style="width: 1500px; height: 1500px; position: relative;">
                <canvas id="scheduling-canvas" width="1500" height="1500"></canvas>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts-body')
<script>
    const canvas = new fabric.Canvas('scheduling-canvas', {selection: false});
    fabric.Object.prototype.originX = fabric.Object.prototype.originY = 'center';

    const EPS = 1e-7;
    const earthRadius = 6371;
    const fieldSizeX = 1500;
    const fieldSizeY = 1500;

    const graphLinkColor = 'grey';
    const servicePointColor = 'red';
    const servicePointSize = 4;
    const servicePointPriorityColor = ["27D600","32CE02","3EC604","49BF06","54B709","60AF0B","6BA70D","779F0F","829811","8D9013","998816","A48018","AF791A","BB711C","C6691E","D26120","DD5923","E85225","F44A27","FF4229","FF0000"];

    const serviceProPointColor = 'blue';
    const serviceProPointSize = 6;
    const heatPointRadius = 100;

    const maxServicePriority = 100;
    const boundaryPaddingPercent = 0.1; // 10%
    const serviceProBoundaryPaddingPercent = 0.25; // 25%
    const heatMultiply = 2.0;

    let totalServices;
    let totalServicePros;
    let totalServiceProAreaRadius;
    let pastDueDatePercent;

    let cosF;
    let minPoint;
    let maxPoint;

    let services = [];
    let servicePros = [];
    let heatMap = [];
    let clusters = [];

    const pointsImport = {!! json_encode(!empty($service_points) ? $service_points : []) !!};
    const serviceProImport = {!! json_encode(!empty($service_pros) ? $service_pros : []) !!};

    function initVariables() {
        totalServices = document.getElementById('totalServices').value;
        totalServicePros = document.getElementById('totalServicePros').value;
        totalServiceProAreaRadius = document.getElementById('totalServiceProAreaRadius').value;
        pastDueDatePercent = document.getElementById('pastDueDatePercent').value / 100.0;
    }

    function resetCanvas() {
        canvas.getObjects().forEach((element) => canvas.remove(element));
    }

    function init() {
        initVariables();
        resetCanvas();

        let highPriorityNumber = totalServices * pastDueDatePercent;
        highPriorityNumber = getRandomInt(0, highPriorityNumber);

        generateServicePros(totalServicePros);
        generateServices(totalServices - highPriorityNumber);
        generateHighPriorityServices(highPriorityNumber);

        buildGraph();
        initClusters();
        // initHeatmap();

        // drawServiceLinks();
        drawServicePros();
        drawServices();

        // drawServiceProsArea();
        // drawHeatmap();
    }

    function initImport() {
        initVariables();
        resetCanvas();

        resolveMaxMinCoordinates();
        resolveXYCoordinates();

        importServices();
        importServicePros();

        // resetServices();
        // buildGraph();

        initClusters();
        // initHeatmap();

        // drawServiceLinks();
        drawServicePros();
        drawServices();

        // drawHeatmap();
    }

    function getReservedServiceIds() {
        let reservedServiceIds = [];
        services.forEach((service) => {
            if (service.reserved) {
                reservedServiceIds.push(service.id);
            }
        });

        return reservedServiceIds;
    }

    function deleteServicesByIds(ids) {
        services = services.filter((service) => ids.indexOf(service.id) === -1);
    }

    function nextDay(algorithmType) {
        resetCanvas();
        drawServicePros();

        let reservedServiceIds = getReservedServiceIds();
        deleteServicesByIds(reservedServiceIds);

        services.forEach((service, index) => {
            service.id = index;
            if (service.priority > 0 && service.priority < maxServicePriority) {
                service.priority += 10;
            } else {
                service.priority = 100;
            }
        });

        let deltaOfNewServices = (reservedServiceIds.length * 0.1);
        let constNumberOfNewServices = (reservedServiceIds.length * 0.9);
        deltaOfNewServices = getRandomInt(0 ,deltaOfNewServices * 2);
        addNewServices(constNumberOfNewServices + deltaOfNewServices, services.length);
        resetServices();
        buildGraph();

        // initHeatmap();
        // drawHeatmap();

        drawServices();

        initClusters();

        if (algorithmType === 1) {
            expandClustersByMostWeightedService();
        } else if (algorithmType === 2) {
            expandClustersByNearestService();
        } else if (algorithmType === 3) {
            expandClustersWithMovingCentroid();
        }
    }

    function removeGraphElementsByType(type) {
        canvas.getObjects().forEach((element) => {
            if (element.objectType === type) {
                canvas.remove(element);
            }
        });
    }

    function removeGraphElementsByTypeAndIds(type, ids) {
        canvas.getObjects().forEach((element) => {
            if (element.objectType === type && ids.indexOf(element.objectId) !== -1) {
                canvas.remove(element);
            }
        });
    }

    function getHighPriorityServices() {
        return services.filter((service) => service.priority === maxServicePriority);
    }

    function resetServices() {
        services.forEach((service) => {
            service.reserved = false;
            service.nearestServices = [];
        });
    }

    function getServicesSortedByPriority() {
        let sortedServices = services.filter((service) => service.priority < maxServicePriority);
        return sortedServices.sort((a, b) => b.priority - a.priority);
    }

    function initClusters() {
        initVariables();
        removeGraphElementsByType('service-link');

        clusters = [];

        let highPriorityServices = getHighPriorityServices();
        let serviceProCluster = [];

        servicePros.forEach((servicePro, index) => {
            serviceProCluster.push([]);
        });

        highPriorityServices.forEach((service) => {
            let minDistance = 1000000;
            let minServiceProIndex = 0;
            servicePros.forEach((servicePro, index) => {
                let distance = getDistance(service, servicePro);
                if (distance < minDistance) {
                    minDistance = distance;
                    minServiceProIndex = index;
                }
            });
            serviceProCluster[minServiceProIndex].push(service.id);
        });

        let sortedServices = getServicesSortedByPriority();

        sortedServices.forEach((service) => {
            if (service.priority < maxServicePriority * 0.75) {
                return;
            }
            let minDistance = 1000000;
            let minServiceProIndex = null;
            serviceProCluster.forEach((clusterServiceIds, index) => {
                let servicePro = servicePros[index];
                let distance = getDistance(service, servicePro);
                if (distance < minDistance) {
                    minDistance = distance;
                    minServiceProIndex = index;
                }
            });
            if (minServiceProIndex === null) {
                return;
            }
            if (serviceProCluster[minServiceProIndex].length !== 0) {
                return;
            }
            serviceProCluster[minServiceProIndex].push(service.id);
            services[service.id].reserved = true;
        });

        serviceProCluster.forEach((clusterServiceIds, serviceProId) => {
            if (clusterServiceIds.length !== 0) {
                return;
            }
            let serviceId = findNearestService(servicePros[serviceProId].x, servicePros[serviceProId].y).id;

            services[serviceId].reserved = true;
            clusterServiceIds.push(serviceId);
        });

        serviceProCluster.forEach((clusterServiceIds, serviceProId) => {
            let servicesInCluster = [];
            clusterServiceIds.forEach((serviceId) => {
                services[serviceId].reserved = true;
                servicesInCluster.push({
                    id: serviceId,
                    weight: 0,
                });
                canvas.add(makeGraphLink(servicePros[serviceProId].x, servicePros[serviceProId].y, services[serviceId].x, services[serviceId].y))
            });
            clusters.push({
                centroid: clusterServiceIds[0],
                services: servicesInCluster,
            });
        });
    }

    function findNearestService(x, y) {
        let nearestService = null;
        let minDistance = 1000000;
        services.forEach((service) => {
            let distance = getDistance({x: x, y: y}, service);
            if (distance < minDistance) {
                minDistance = distance;
                nearestService = service;
            }
        });

        return nearestService;
    }

    function calcWeight(serviceId) {
        let weight = 0;
        let service = services[serviceId];

        service.nearestServices.forEach((nextServiceId) => {
            let nextService = services[nextServiceId];
            if (nextService === undefined) {
                return;
            }
            if (nextService.reserved) {
                return;
            }
            weight += nextService.priority / getDistance(service, nextService);
        });

        weight /= service.nearestServices.length;

        return weight;
    }

    function getBetterNodeByWeight(serviceId) {
        let service = services[serviceId];
        let betterServiceId = null;
        let betterWeight = 0;
        service.nearestServices.forEach((nextServiceId) => {
            let nextService = services[nextServiceId];
            if (nextService.reserved) {
                return;
            }
            let weight = nextService.priority / getDistance(service, nextService);
            if (weight > betterWeight) {
                betterWeight = weight;
                betterServiceId = nextServiceId;
            }
        });

        return betterServiceId;
    }

    function expandClustersByMostWeightedService() {
        for (let i = 0; i < document.getElementById('expandClustersUpTo').value; i++) {
            clusters.forEach((cluster) => {
                let weights = [];
                cluster.services.forEach((service) => {
                    let weight = calcWeight(service.id);
                    if (weight === 0) {
                        return;
                    }
                    weights.push({
                        id: service.id,
                        weight: calcWeight(service.id),
                    });
                });
                if (weights.length === 0) {
                    return;
                }
                weights.sort((a, b) => b.weight - a.weight);

                let serviceId = weights[0].id;
                let betterNodeId = getBetterNodeByWeight(serviceId);
                if (betterNodeId === null) {
                    return;
                }
                cluster.services.push({
                    id: betterNodeId,
                    weight: calcWeight(betterNodeId),
                });
                services[betterNodeId].reserved = true;

                let nextService = services[betterNodeId];
                let parent = services[serviceId];
                canvas.add(makeGraphLink(parent.x, parent.y, nextService.x, nextService.y))
            });
        }
    }

    function getNearestNode(parentId, serviceId) {
        let nearestService = null;
        let minDistance = 1000000;
        let parent = services[parentId];
        let service = services[serviceId];
        service.nearestServices.forEach((nearestServiceId) => {
            if (services[nearestServiceId].reserved) {
                return;
            }
            let distance = getDistance(parent, services[nearestServiceId]);
            if (distance < minDistance) {
                minDistance = distance;
                nearestService = nearestServiceId;
            }
        });

        return nearestService ? {
            id: nearestService,
            distance: minDistance,
        } : null;
    }

    function expandClustersByNearestService() {
        for (let i = 0; i < document.getElementById('expandClustersUpTo').value; i++) {
            clusters.forEach((cluster) => {
                let nearestServices = [];
                cluster.services.forEach((service) => {
                    let nearestService = getNearestNode(cluster.centroid, service.id);

                    if (nearestService === null) {
                        return;
                    }

                    nearestServices.push(nearestService);
                });

                if (nearestServices.length === 0) {
                    return;
                }

                nearestServices.sort((a, b) => a.distance - b.distance);
                let nearestService = nearestServices[0];
                cluster.services.push({
                    id: nearestService.id,
                    weight: calcWeight(nearestService.id),
                });
                services[nearestService.id].reserved = true;

                let nextService = services[nearestService.id];
                let parent = services[cluster.centroid];
                canvas.add(makeGraphLink(parent.x, parent.y, nextService.x, nextService.y))
            });
        }
    }

    function expandClustersWithMovingCentroid() {
        findClusterCenter();
        for (let i = 0; i < document.getElementById('expandClustersUpTo').value; i++) {
            addServiceToCluster();
            findClusterCenter();
            removeServiceFromClusterThatCloserToOtherCluster();
            drawClusterLinks2();
        }
    }

     function findClusterCenter() {
        removeGraphElementsByType('cluster-point');
        // find cluster center point
        clusters.forEach((cluster) => {
            let point = {
                x: 0,
                y: 0
            };
            cluster.services.forEach((service) => {
                point.x += services[service.id].x;
                point.y += services[service.id].y;
            });

            point.x /= cluster.services.length;
            point.y /= cluster.services.length;

            cluster.center = point;

            canvas.add(makeClusterPoint(point.x, point.y))
        });
    }

    function addServiceToCluster() {
        clusters.forEach((cluster) => {
            let nearestService = null;
            let maxScore = -1;
            let centerPoint = cluster.center;

            // find most weighted nearest service
            services.forEach((service) => {
                if (service.reserved) {
                    return;
                }

                // get nearest distance to other clusters
                let minDistanceToOtherClusters = getDistance(centerPoint, service);
                let isNearToAnotherCluster = false;

                clusters.forEach((otherCluster) => {
                    if (otherCluster === cluster) {
                        return;
                    }
                    let distance = getDistance(otherCluster.center, service);
                    if (distance < minDistanceToOtherClusters) {
                        minDistanceToOtherClusters = distance;
                        isNearToAnotherCluster = true;
                    }
                });
                let distance = getDistance(centerPoint, service);
                let score = service.priority / (distance * distance);
                if (score > maxScore && !isNearToAnotherCluster) {
                    maxScore = score;
                    nearestService = service.id;
                }
            });

            if (nearestService === null) {
                return;
            }

            cluster.services.push({
                id: nearestService,
                weight: maxScore,
            });
            services[nearestService].reserved = true;
        });
    }

    function removeServiceFromClusterThatCloserToOtherCluster() {
        clusters.forEach((cluster) => {
            let centerPoint = cluster.center;
            let removeServiceIds = [];

            cluster.services.forEach((service) => {
                let currentScore = services[service.id].priority / getDistance(centerPoint, services[service.id]);
                let removeService = false;

                clusters.forEach((otherCluster) => {
                    if (otherCluster === cluster) {
                        return;
                    }
                    let otherScore = services[service.id].priority / getDistance(otherCluster.center, services[service.id]);
                    if (otherScore > currentScore) {
                        removeService = true;
                    }
                });

                if (removeService) {
                    removeServiceIds.push(service.id);
                    services[service.id].reserved = false;
                }
            });

            cluster.services = cluster.services.filter((service) => removeServiceIds.indexOf(service.id) === -1);
        });
    }

    function getRandomInt(min, max) {
        return min + Math.floor(Math.random() * (max - min));
    }

    function generateServicePros(amount) {
        servicePros = [];

        for (let i = 0; i < amount; i++) {
            let position;
            let distanceOk;

            do {
                position = getRandomPosition();
                distanceOk = true;

                servicePros.forEach(function (servicePro) {
                    let distance = getDistance(servicePro, {x: position.x, y: position.y});
                    if (distance < totalServiceProAreaRadius / 2) {
                        distanceOk = false;
                    }
                });
            } while (!distanceOk);

            servicePros.push({
                id: i,
                range: totalServiceProAreaRadius,
                x: position.x,
                y: position.y
            })
        }
    }

    function importServicePros() {
        servicePros = [];

        for (let i = 0; i < serviceProImport.length; i++) {
            const servicePro = serviceProImport[i];

            servicePros.push({
                id: i,
                range: totalServiceProAreaRadius,
                x: servicePro.x,
                y: servicePro.y
            })
        }
    }

    function generateServices(amount) {
        services = [];
        for (let i = 0; i < amount; i++) {
            const position = getRandomPosition();
            services.push({
                id: i,
                priority: getRandomInt(0, maxServicePriority - 1),
                nearestServices: [],
                x: position.x,
                y: position.y
            })
        }
    }

    function importServices() {
        services = [];
        for (let i = 0; i < pointsImport.length; i++) {
            const point = pointsImport[i];
            services.push({
                id: i,
                priority: point.priority,
                nearestServices: [],
                x: point.x,
                y: point.y,
            })
        }
    }

    function addNewServices(amount, startId) {
        for (var i = 0; i < amount; i++) {
            const position = getRandomPosition();
            services.push({
                id: startId + i,
                priority: getRandomInt(1, 5) * 10,
                nearestServices: [],
                x: position.x,
                y: position.y
            })
        }
    }

    function generateHighPriorityServices(amount) {
        const serviceAmount = services.length;
        for (var i = 0; i < amount; i++) {
            const position = getRandomPosition();
            services.push({
                id: i + serviceAmount,
                priority: maxServicePriority,
                nearestServices: [],
                x: position.x,
                y: position.y
            })
        }
    }

    function getRandomPosition() {
        const minX = Math.floor(fieldSizeX * boundaryPaddingPercent);
        const maxX = Math.floor(fieldSizeX - fieldSizeX * boundaryPaddingPercent);
        const minY = Math.floor(fieldSizeY * boundaryPaddingPercent);
        const maxY = Math.floor(fieldSizeY - fieldSizeY * boundaryPaddingPercent);

        return {
            x: getRandomInt(minX, maxX),
            y: getRandomInt(minY, maxY)
        }
    }

    function makeServicePoint(x, y, color) {
        return new fabric.Circle({
            left: x,
            top: y,
            strokeWidth: 1,
            radius: servicePointSize,
            fill: '#' + color,
            selectable: false,
            objectType: 'service-point',
        });
    }

    function makeGraphLink(x1, y1, x2, y2) {
        return new fabric.Line([x1, y1, x2, y2], {
            stroke: graphLinkColor,
            strokeWidth: 1,
            opacity: 0.35,
            selectable: false,
            objectType: 'service-link',
            evented: false,
        });
    }

    function makeClusterPoint(x, y) {
        return new fabric.Circle({
            left: x,
            top: y,
            strokeWidth: 1,
            radius: 6,
            fill: 'yellow',
            selectable: false,
            objectType: 'cluster-point',
        });
    }

    function makeServiceProPoint(x, y) {
        return new fabric.Circle({
            left: x,
            top: y,
            strokeWidth: 1,
            radius: serviceProPointSize,
            fill: serviceProPointColor,
            selectable: false,
            objectType: 'service-pro-point',
        });
    }

    function makeServiceProArea(x, y, r) {
        return new fabric.Circle({
            left: x,
            top: y,
            strokeWidth: 0,
            radius: r,
            fill: serviceProPointColor,
            opacity: 0.05,
            selectable: false,
            objectType: 'service-pro-area',
        });
    }

    function calcColor(value) {
        const normalizedValue = value < 100 ? value / 100 : 1;

        return valueToColor(normalizedValue);
    }

    function calcRadius(value) {
        const normalizedValue = value < 100 ? value / 100 : 1;

        return Math.round(normalizedValue * heatPointRadius);
    }

    function makeHeatPoint(x, y, value, objectId) {
        return new fabric.Circle({
            left: x,
            top: y,
            strokeWidth: 0,
            radius: calcRadius(value),
            fill: calcColor(value),
            opacity: 0.15,
            selectable: false,
            objectType: 'heat-point',
            objectId: objectId,
        });
    }

    function valueToColor(value) {
        const hue = (1 - value) * 120;
        return `hsl(${hue}, 100%, 50%)`;
    }

    function drawServices() {
        services.forEach(function (service) {
            const color = servicePointPriorityColor[Math.round(service.priority / 5)];
            canvas.add(makeServicePoint(service.x, service.y, color));
        });
    }

    function drawHeatmap() {
        heatMap.forEach(function (heatPoint, index) {
            const service = services[index];
            canvas.add(makeHeatPoint(service.x, service.y, heatPoint, index));
        });
    }

    function drawClusterLinks() {
        clusters.forEach(function (cluster) {
            let parent = services[cluster.centroid];
            cluster.services.forEach((service) => {
                let nextService = services[service.id];
                canvas.add(makeGraphLink(parent.x, parent.y, nextService.x, nextService.y))
            });
        });
    }

    function drawClusterLinks2() {
        removeGraphElementsByType('service-link');
        clusters.forEach(function (cluster) {
            cluster.services.forEach((service) => {
                let nextService = services[service.id];
                canvas.add(makeGraphLink(cluster.center.x, cluster.center.y, nextService.x, nextService.y))
            });
        });
    }

    function drawServiceLinks() {
        services.forEach(function (service) {
            service.nearestServices.forEach(
                (nextServiceId) => canvas.add(makeGraphLink(service.x, service.y, services[nextServiceId].x, services[nextServiceId].y))
            );
        });
    }

    function drawServicePros() {
        servicePros.forEach(function (servicePro) {
            canvas.add(makeServiceProPoint(servicePro.x, servicePro.y));
        });
    }

    function drawServiceProsArea() {
        servicePros.forEach(function (servicePro) {
            canvas.add(makeServiceProArea(servicePro.x, servicePro.y, servicePro.range));
        });
    }

    function big_triangle(points) {
        let minx = 1000000, maxx = -1000000, miny = 1000000, maxy = -1000000;
        for (let i = 0; i < points.length; i++) {
            minx = Math.min(minx, points[i].x);
            miny = Math.min(miny, points[i].y);
            maxx = Math.max(maxx, points[i].x);
            maxy = Math.max(maxy, points[i].y);
        }
        let dx = maxx - minx, dy = maxy - miny;
        let dxy = Math.max(dx, dy);
        let midx = dx * 0.5 + minx, midy = dy * 0.5 + miny;
        return [
            {x: midx - 10 * dxy, y: midy - 10 * dxy},
            {x: midx, y: midy + 10 * dxy},
            {x: midx + 10 * dxy, y: midy - 10 * dxy}
        ]
    }

    function circumcircle_of_triangle(points, v1, v2, v3) {
        let x1 = points[v1].x, y1 = points[v1].y;
        let x2 = points[v2].x, y2 = points[v2].y;
        let x3 = points[v3].x, y3 = points[v3].y;
        let dy12 = Math.abs(y1 - y2), dy23 = Math.abs(y2 - y3);
        let xc, yc;

        if (dy12 < EPS) {
            let m2  = -((x3 - x2) / (y3 - y2));
            let mx2 = (x2 + x3) / 2, my2 = (y2 + y3) / 2;
            xc  = (x1 + x2) / 2;
            yc  = m2 * (xc - mx2) + my2;
        } else if (dy23 < EPS) {
            let m1  = -((x2 - x1) / (y2 - y1));
            let mx1 = (x1 + x2) / 2, my1 = (y1 + y2) / 2;
            xc  = (x2 + x3) / 2;
            yc  = m1 * (xc - mx1) + my1;
        } else {
            let m1  = -((x2 - x1) / (y2 - y1)), m2  = -((x3 - x2) / (y3 - y2));
            let mx1 = (x1 + x2) / 2, my1 = (y1 + y2) / 2;
            let mx2 = (x2 + x3) / 2, my2 = (y2 + y3) / 2;
            xc  = (m1 * mx1 - m2 * mx2 + my2 - my1) / (m1 - m2);
            if (dy12 > dy23) {
                yc =  m1 * (xc - mx1) + my1;
            } else {
                yc = m2 * (xc - mx2) + my2;
            }
        }

        let dx = x2 - xc, dy = y2 - yc;

        return {'a': v1, 'b': v2, 'c': v3, 'x': xc, 'y': yc, 'r': dx*dx + dy*dy};
    }

    function delete_multiples_edges(edges) {
        for (let j = edges.length - 1; j >= 0;) {
            let b = edges[j]; j--;
            let a = edges[j]; j--;
            let n, m;
            for (let i = j; i >= 0;) {
                n = edges[i]; i--;
                m = edges[i]; i--;
                if (a === m && b === n) {
                    edges.splice(j + 1, 2);
                    edges.splice(i + 1, 2);
                    break;
                }
                if (a === n && b === m) {
                    edges.splice(j + 1, 2);
                    edges.splice(i + 1, 2);
                    break;
                }
            }
        }
    }

    function triangulate(points) {
        let n = points.length;
        if (n < 3) {
            return [];
        }

        points = points.slice(0);

        let ind = [];
        for (let i = 0; i < n; i++) {
            ind.push(i);
        }
        ind.sort(function(l, r) {
            return points[r].x - points[l].x;
        })

        let big = big_triangle(points);
        points.push(big[0]);
        points.push(big[1]);
        points.push(big[2]);

        let cur_points = [circumcircle_of_triangle(points, n, n + 1, n + 2)];
        let ans = [];
        let edges = [];
        let tr = []

        for (let i = ind.length - 1; i >= 0; i--) {
            for (let j = cur_points.length - 1; j >= 0; j--) {
                let dx = points[ind[i]].x - cur_points[j].x;
                if (dx > 0 && dx * dx > cur_points[j].r) {
                    ans.push(cur_points[j]);
                    cur_points.splice(j, 1);
                    continue;
                }

                let dy = points[ind[i]].y - cur_points[j].y;
                if (dx * dx + dy * dy - cur_points[j].r > EPS) {
                    continue;
                }

                edges.push(
                    cur_points[j].a, cur_points[j].b,
                    cur_points[j].b, cur_points[j].c,
                    cur_points[j].c, cur_points[j].a
                );
                cur_points.splice(j, 1);
            }

            delete_multiples_edges(edges);

            for (let j = edges.length - 1; j >= 0;) {
                let b = edges[j]; j--;
                if (j < 0) break;
                let a = edges[j]; j--;
                cur_points.push(circumcircle_of_triangle(points, a, b, ind[i]));
            }
            edges = [];
        }

        for (let i = cur_points.length - 1; i >= 0; i--) {
            ans.push(cur_points[i]);
        }

        for (let i = 0; i < ans.length; i++) {
            if (ans[i].a < n && ans[i].b < n && ans[i].c < n) {
                tr.push({
                    a: ans[i].a,
                    b: ans[i].b,
                    c: ans[i].c
                });
            }
        }

        return tr;
    }

    function buildGraph() {
        let triangles = triangulate(services);

        triangles.forEach((triangle) => {
            let serviceId1 = triangle.a;
            let serviceId2 = triangle.b;
            let serviceId3 = triangle.c;

            if (services[serviceId1].nearestServices.indexOf(serviceId2) === -1) {
                services[serviceId1].nearestServices.push(serviceId2);
            }
            if (services[serviceId1].nearestServices.indexOf(serviceId3) === -1) {
                services[serviceId1].nearestServices.push(serviceId3);
            }
            if (services[serviceId2].nearestServices.indexOf(serviceId1) === -1) {
                services[serviceId2].nearestServices.push(serviceId1);
            }
            if (services[serviceId3].nearestServices.indexOf(serviceId1) === -1) {
                services[serviceId3].nearestServices.push(serviceId1);
            }
        });
    }

    function getDistance(service1, service2) {
        const dx = Math.abs(service1.x - service2.x);
        const dy = Math.abs(service1.y - service2.y);

        return Math.sqrt(dx * dx + dy * dy);
    }

    function initHeatmap() {
        heatMap = [];
        services.forEach(function (service) {
            heatMap[service.id] = service.priority * heatMultiply;
        });
    }

    function resolveXYCoordinates() {
        pointsImport.forEach((servicePoint) => {
            let point = getLocalPointXYFromCoordinates(servicePoint.lat, servicePoint.lng);
            servicePoint.x = point.x;
            servicePoint.y = point.y;
        });
        serviceProImport.forEach((servicePro) => {
            let point = getLocalPointXYFromCoordinates(servicePro.lat, servicePro.lng);
            servicePro.x = point.x;
            servicePro.y = point.y;
        });
    }

    function resolveMaxMinCoordinates() {
        let points = pointsImport.concat(serviceProImport);

        let maxLat = points.reduce((max, point) => point.lat > max ? point.lat : max, points[0].lat);
        let minLat = points.reduce((min, point) => point.lat < min ? point.lat : min, points[0].lat);
        let maxLng = points.reduce((max, point) => point.lng > max ? point.lng : max, points[0].lng);
        let minLng = points.reduce((min, point) => point.lng < min ? point.lng : min, points[0].lng);

        cosF = Math.cos((minLat + maxLat) / 2);
        minPoint = getGlobalPointXYFromCoordinates(minLat, minLng);
        maxPoint = getGlobalPointXYFromCoordinates(maxLat, maxLng);
    }

    function getLocalPointXYFromCoordinates(lat, lng) {
        let globalPoint = getGlobalPointXYFromCoordinates(lat, lng);

        let dx = Math.abs(maxPoint.x - minPoint.x);
        let dy = Math.abs(maxPoint.y - minPoint.y);

        return {
            x: dx > 0 ? Math.abs(globalPoint.x - minPoint.x) / dx * fieldSizeX : 0,
            y: dy > 0 ? Math.abs(maxPoint.y - globalPoint.y) / dy * (fieldSizeX * dy / dx) : 0
        };
    }

    function getGlobalPointXYFromCoordinates(lat, lng) {
        return {
            x: earthRadius * lat * cosF,
            y: earthRadius * lng
        };
    }
</script>
@endsection
