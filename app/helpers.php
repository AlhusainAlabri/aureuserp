<?php

use App\Support\Branding;

if (! function_exists('brand_name')) {
    function brand_name(?int $companyId = null): string
    {
        return Branding::displayName($companyId);
    }
}
