<?php

namespace App\Http\Controllers;

use App\Models\Generation;
use App\Services\Money;
use App\Services\Pixelforge\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminReportController extends Controller
{
    public function __invoke(Request $request, Client $client)
    {
        abort_unless($request->user()->is_admin, 403);

        $end = now('UTC')->toDateString();
        $start = now('UTC')->subDays(29)->toDateString();
        $usage = $client->usage($start, $end);

        $generationsByDay = Generation::query()
            ->where('status', 'succeeded')
            ->whereBetween('provider_completed_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->with('user')
            ->get()
            ->groupBy(fn (Generation $generation) =>
                $generation->provider_completed_at?->utc()->toDateString()
                ?? $generation->updated_at->utc()->toDateString()
            );

        $days = collect($usage['data'] ?? [])
            ->map(function (array $providerDay) use ($generationsByDay): array {
                $date = $providerDay['date'];
                $generations = $generationsByDay->get($date, collect());

                $users = $generations
                    ->groupBy('user_id')
                    ->map(function ($userGenerations): array {
                        $charged = (int) $userGenerations->sum('actual_customer_cost_units');
                        $providerCost = (int) $userGenerations->sum('actual_provider_cost_units');

                        return [
                            'user' => $userGenerations->first()->user->email,
                            'charged' => Money::unitsToDecimal($charged),
                            'provider_cost' => Money::unitsToDecimal($providerCost),
                            'profit' => Money::unitsToDecimal($charged - $providerCost),
                        ];
                    })
                    ->values();

                $charged = (int) $generations->sum('actual_customer_cost_units');
                $localProviderCost = (int) $generations->sum('actual_provider_cost_units');
                $reportedProviderCost = Money::decimalToUnits($providerDay['cost_usd']);

                return [
                    'date' => $date,
                    'users' => $users,
                    'charged' => Money::unitsToDecimal($charged),
                    'provider_cost' => Money::unitsToDecimal($reportedProviderCost),
                    'local_provider_cost' => Money::unitsToDecimal($localProviderCost),
                    'provider_delta' => Money::unitsToDecimal($localProviderCost - $reportedProviderCost),
                    'profit' => Money::unitsToDecimal($charged - $reportedProviderCost),
                    'provider_generations' => (int) $providerDay['generations'],
                ];
            })
            ->values();

        return Inertia::render('Admin/Report', [
            'days' => $days,
            'range' => ['start' => $start, 'end' => $end],
            'provider_total' => $usage['total']['cost_usd'] ?? '0.000000000',
        ]);
    }
}
