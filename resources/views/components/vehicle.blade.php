{{-- The vehicle of a rental: brand and type, with the vehicle type and plate number (if any) below it. --}}
@props(['rental'])

{{ $rental->vehicle_brand_type }}
<div class="small text-body-secondary">
    {{ $rental->vehicle_type->label() }}{{ $rental->plate_number ? ' · '.$rental->plate_number : '' }}
</div>
