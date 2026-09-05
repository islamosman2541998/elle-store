<?php

namespace App\Services\Carriers;

use App\Models\ShippingCompany;

/**
 * Resolves the driver for a shipping company.
 *
 * Add a carrier by implementing CarrierDriver and registering it here; the
 * dashboard's driver dropdown is built from the same list.
 */
class CarrierManager
{
    /** @var array<string, class-string<CarrierDriver>> */
    private const DRIVERS = [
        'manual' => ManualDriver::class,
        'bosta' => BostaDriver::class,
    ];

    public function driverFor(ShippingCompany $company): CarrierDriver
    {
        $class = self::DRIVERS[$company->integration_driver] ?? ManualDriver::class;

        return $class === ManualDriver::class
            ? new ManualDriver()
            : new $class($company);
    }

    /** Options for the dashboard dropdown. */
    public static function options(): array
    {
        return [
            'manual' => __('admin.carrier_driver_manual'),
            'bosta' => 'Bosta',
        ];
    }

    public static function isApiDriver(?string $driver): bool
    {
        return filled($driver) && $driver !== 'manual' && isset(self::DRIVERS[$driver]);
    }
}
