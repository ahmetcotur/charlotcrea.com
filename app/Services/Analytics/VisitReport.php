<?php

namespace App\Services\Analytics;

use App\Models\PageVisit;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VisitReport
{
    public function data(Request $request): array
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'country' => ['nullable', 'regex:/^[A-Z]{2}$/'],
            'device' => ['nullable', Rule::in(['desktop', 'mobile', 'tablet', 'unknown'])],
            'ip' => ['nullable', 'ip'],
        ]);
        $filters = array_merge(['from' => today()->subDays(29)->toDateString(), 'to' => today()->toDateString(), 'country' => null, 'device' => null, 'ip' => null], array_filter($filters, fn ($value) => $value !== null));
        $from = CarbonImmutable::parse($filters['from'])->startOfDay();
        $to = CarbonImmutable::parse($filters['to'])->endOfDay();
        if ($to->lessThan($from) || $from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['to' => 'Bitiş başlangıçtan önce olamaz; en fazla 366 günlük aralık seçin.']);
        }
        $query = PageVisit::whereBetween('visited_at', [$from, $to]);
        if ($filters['country']) {
            $filters['country'] === 'ZZ' ? $query->whereNull('country_code') : $query->where('country_code', $filters['country']);
        }
        if ($filters['device']) {
            $query->where('device', $filters['device']);
        }
        if ($filters['ip']) {
            $query->where('ip_hash', VisitRecorder::ipHash($filters['ip']));
        }
        $group = fn ($field, $limit = 10) => (clone $query)->select($field)->selectRaw('COUNT(*) as hits')->groupBy($field)->orderByDesc('hits')->orderBy($field)->limit($limit)->get();
        $dailyCounts = (clone $query)->selectRaw('DATE(visited_at) as visit_day, COUNT(*) as hits')->groupByRaw('DATE(visited_at)')->orderBy('visit_day')->get()->pluck('hits', 'visit_day');
        $daily = collect();
        for ($day = $from; $day->lessThanOrEqualTo($to); $day = $day->addDay()) {
            $daily->push(['date' => $day->toDateString(), 'hits' => (int) ($dailyCounts[$day->toDateString()] ?? 0)]);
        }

        return [
            'filters' => $filters,
            'total' => (clone $query)->count(),
            'visitors' => (clone $query)->distinct()->count('visitor_hash'),
            'ipCount' => (clone $query)->distinct()->count('ip_hash'),
            'countryCount' => (clone $query)->whereNotNull('country_code')->distinct()->count('country_code'),
            'daily' => $daily,
            'countries' => $group('country_code', 15),
            'devices' => $group('device'),
            'browsers' => $group('browser'),
            'platforms' => $group('platform'),
            'pages' => $group('path'),
            'referrers' => $group('referrer_host'),
            'recent' => (clone $query)->orderByDesc('visited_at')->orderByDesc('id')->paginate(15)->withQueryString(),
            'availableCountries' => PageVisit::whereNotNull('country_code')->distinct()->orderBy('country_code')->pluck('country_code'),
            'databaseReady' => is_file(config('analytics.country_database')),
            'databaseUpdatedAt' => is_file(config('analytics.country_database')) ? date('d.m.Y', filemtime(config('analytics.country_database'))) : null,
        ];
    }

    public static function countryName(?string $code): string
    {
        if (! $code) {
            return 'Bilinmiyor / yerel ağ';
        }

        return class_exists(\Locale::class) ? \Locale::getDisplayRegion('-'.$code, 'tr') : $code;
    }
}
