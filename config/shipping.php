<?php
// Include settings từ database
require_once __DIR__ . '/settings.php';

if (!function_exists('haversine_distance_km')) {
    function haversine_distance_km(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}

if (!function_exists('calculate_shipping_fee')) {
    function calculate_shipping_fee(float $distanceKm): int
    {
        if ($distanceKm <= 0) {
            return SHIPPING_BASE_FEE;
        }

        $fee = SHIPPING_BASE_FEE + (int) ceil($distanceKm * SHIPPING_PER_KM_FEE);
        return min($fee, SHIPPING_MAX_FEE);
    }
}
