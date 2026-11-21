{{-- A navbar link, highlighted while the current route matches `active` (defaults to the link's own route). --}}
@props(['route', 'active' => null])

@php($isActive = request()->routeIs($active ?? $route))

<li class="nav-item">
    <a href="{{ route($route) }}" @class(['nav-link', 'active fw-semibold' => $isActive])
       @if ($isActive) aria-current="page" @endif>{{ $slot }}</a>
</li>
