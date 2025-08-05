<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        @if ($title)
            {{ $title }}
        @else
            ARO
        @endif
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    @yield('links')
    @yield('scripts-head')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js" integrity="sha512-2rNj2KJ+D8s1ceNasTIex6z4HWyOnEYLVC3FigGOmyQCZc2eBXKgOxQmo3oKLHyfcj53uz4QMsRCWNbLd32Q1g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://polyfill.io/v3/polyfill.min.js?features=default"></script>
    <style>
        :root {
            --bs-body-font-size: 0.9rem;  /* Overwrite global bootstrap font-size */
            font-size: var(--bs-body-font-size);
        }
        a:not([class="badge"]) {
            text-decoration: none;
        }
        .small-text {
            font-size: 0.6rem;
        }
        .timeline-box {
            height: 30px;
            background-color: #f1f1f1;
        }
        .timeline-wrapper {
            overflow-x: scroll;
            overflow-y: hidden;
        }
        .timeline-event {
            height: 20px;
            border-radius: 3px;
            border: 1px solid #000;
            display: inline-block;
            font-size: 9px;
            overflow: hidden;
            padding: 3px;
        }
        .timeline-event-start {
            width: 10px;
            background-color: #ffc500;
        }
        .timeline-event-travel {
            background-color: #df64bb;
        }
        .timeline-event-appointment {
            background-color: #86d55f;
        }
        .timeline-event-break {
            background-color: #93b6ec;
        }
        .timeline-event-meeting {
            background-color: #ff2335;
        }
        .timeline-event-waiting {
            background-color: #d9e0e8;
        }
        .timeline-event-reserved {
            background-color: #032198;
        }
        .timeline-event.selected {
            border: 2px solid red;
        }
    </style>
    @yield('styles')
</head>
<body>
<div class="container-fluid">
    @yield("content")
</div>
@yield('scripts-body')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
