<nav class="navbar navbar-expand-lg navbar-light bg-light mb-3">
    <div class="container-fluid">
        <div class="navbar-brand">ARO Overview</div>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('optimization-*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Optimization
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                        <li><a class="dropdown-item {{ request()->routeIs('optimization-overview') ? 'active' : '' }}" href="{{ route('optimization-overview', ['office_id' => $officeId, 'optimization_date' => $processDate, 'execution_date' => $executionDate]) }}">Overview</a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('optimization-executions') ? 'active' : '' }}" href="{{ route('optimization-executions', ['office_id' => $officeId, 'execution_date' => $executionDate]) }}">Executions</a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('optimization-sandbox') ? 'active' : '' }}" href="{{ route('optimization-sandbox', ['office_id' => $officeId, 'optimization_date' => $processDate]) }}">Sandbox</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('scheduling-*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Scheduling
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                        <li><a class="dropdown-item {{ request()->routeIs('scheduling-overview') ? 'active' : '' }}" href="{{ route('scheduling-overview', ['office_id' => $officeId, 'scheduling_date' => $processDate, 'execution_date' => $executionDate]) }}">Overview</a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('scheduling-executions') ? 'active' : '' }}" href="{{ route('scheduling-executions', ['office_id' => $officeId, 'execution_date' => $executionDate]) }}">Executions</a></li>
                        <li><a class="dropdown-item {{ request()->routeIs('scheduling-model') ? 'active' : '' }}" href="{{ route('scheduling-model') }}">Playground</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('service-duration*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Service Duration
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                        <li><a class="dropdown-item {{ request()->routeIs('service-duration-calculations.index') ? 'active' : '' }}" href="{{ route('service-duration-calculations.index') }}">Calculator</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ request()->routeIs('notifications*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Notifications
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                        <li><a class="dropdown-item {{ request()->routeIs('notifications.recipients.index') ? 'active' : '' }}" href="{{ route('notifications.recipients.index') }}">Recipients</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
