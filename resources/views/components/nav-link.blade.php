{{-- A navbar link that is highlighted while the user is on that route. --}}
@props(['route'])

@php($isActive = request()->routeIs($route))

<li class="nav-item">
    <a href="{{ route($route) }}" @class(['nav-link', 'active fw-semibold' => $isActive])
       @if ($isActive) aria-current="page" @endif>{{ $slot }}</a>
</li>
