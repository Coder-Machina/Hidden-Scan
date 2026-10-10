<?php

namespace Tests\Feature;

use App\Support\UserTimezone;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserTimezoneTest extends TestCase
{
    private function useDeviceTimezone(?string $tz): void
    {
        $request = Request::create('/admin', 'GET', [], $tz ? ['hs_tz' => $tz] : []);
        $this->app->instance('request', $request);
    }

    public function test_falls_back_to_app_timezone_without_cookie(): void
    {
        $this->useDeviceTimezone(null);

        $this->assertSame(config('app.timezone'), UserTimezone::get());
    }

    public function test_invalid_cookie_is_ignored(): void
    {
        $this->useDeviceTimezone('Not/AZone');

        $this->assertSame(config('app.timezone'), UserTimezone::get());
    }

    public function test_device_timezone_is_used_by_filament_and_formatting(): void
    {
        config(['app.timezone' => 'Europe/Paris']);
        $this->useDeviceTimezone('Africa/Lagos');

        $this->assertSame('Africa/Lagos', UserTimezone::get());
        $this->assertSame('Africa/Lagos', FilamentTimezone::get());

        // 16:27 heure serveur (Paris, UTC+2 en octobre) = 15:27 sur un appareil en UTC+1
        $stored = Carbon::parse('2026-10-10 16:27:00', 'Europe/Paris');
        $this->assertSame('10/10/2026 15:27', UserTimezone::format($stored));
    }
}
