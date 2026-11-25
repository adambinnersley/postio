<?php

namespace Postio;

/**
 * Works out the county Postio leaves out of its address data.
 *
 * England resolves by district to its ceremonial county, Wales by principal area, and
 * Scotland by postcode outcode to its traditional county, because Scottish council
 * areas cut across the traditional counties. Northern Ireland is not covered and
 * resolves to an empty string, as does anything unrecognised.
 *
 * Postio names districts the Ordnance Survey way ("City of Westminster London Boro",
 * "Leeds District (B)", "Caerdydd - Cardiff"), so each name is tried in several
 * spellings against the ONS names in data/.
 *
 * The county names returned are the ones used by `adamb/uk-counties`.
 */
class CountyResolver
{
    /** @var array<string, string>|null */
    private static ?array $england = null;
    /** @var array<string, array<string, string>> */
    private static array $englandSplit = [];
    /** @var array<string, string>|null */
    private static ?array $wales = null;
    /** @var array{edinburgh: string, outcodes: array<string, string>, councils: array<string, string>}|null */
    private static ?array $scotland = null;

    public static function resolve(?string $country, ?string $district, ?string $outcode): string
    {
        $names = self::candidates((string)$district);
        $outcode = strtoupper(trim((string)$outcode));
        switch (strtolower(trim((string)$country))) {
            case 'england':
                self::loadEngland();
                foreach ($names as $name) {
                    if (isset(self::$england[$name])) {
                        return self::$englandSplit[$name][$outcode] ?? self::$england[$name];
                    }
                }
                return '';
            case 'wales':
                if (self::$wales === null) {
                    $areas = require __DIR__ . '/../data/wales.php';
                    self::$wales = array_combine(array_map([self::class, 'key'], $areas), $areas);
                }
                return self::first(self::$wales, $names);
            case 'scotland':
                self::loadScotland();
                if (in_array(self::key(self::$scotland['edinburgh']), $names, true)) {
                    return self::$scotland['edinburgh'];
                }
                return self::$scotland['outcodes'][$outcode] ?? self::first(self::$scotland['councils'], $names);
        }
        return '';
    }

    /**
     * The district name in each spelling worth looking up, most literal first.
     *
     * @return string[]
     */
    private static function candidates(string $district): array
    {
        $name = trim((string)preg_replace('/\s+/', ' ', $district));
        $names = [$name];
        if (str_contains($name, ' - ')) {
            $names[] = $name = trim(substr($name, strrpos($name, ' - ') + 3));
        }
        $names[] = $name = trim((string)preg_replace('/\s+(London Boro|District \(B\)|District|City \(B\)|\(B\))$/i', '', $name));
        $names[] = (string)preg_replace('/^(The|City and County of the|County of) /i', '', $name);
        $names[] = (string)preg_replace('/^(The )?City of /i', '', $name);
        return array_values(array_unique(array_map([self::class, 'key'], $names)));
    }

    /**
     * @param array<string, string> $map
     * @param string[] $names
     */
    private static function first(array $map, array $names): string
    {
        foreach ($names as $name) {
            if (isset($map[$name])) {
                return $map[$name];
            }
        }
        return '';
    }

    /**
     * Names are compared without case, full stops ("St. Helens" / "St Helens") or double
     * quotes: Postio cuts "Bournemouth, Christchurch and Poole" at its comma and sends
     * the district as `"Bournemouth`.
     */
    private static function key(string $name): string
    {
        return strtolower(trim((string)preg_replace('/\s+/', ' ', str_replace(['.', '"'], '', $name))));
    }

    private static function loadEngland(): void
    {
        if (self::$england !== null) {
            return;
        }
        $data = require __DIR__ . '/../data/england.php';
        self::$england = [];
        foreach ($data['districts'] as $county => $districts) {
            foreach ($districts as $district) {
                self::$england[self::key($district)] = $county;
            }
        }
        foreach ($data['split'] as $district => $outcodes) {
            self::$englandSplit[self::key($district)] = $outcodes;
        }
    }

    private static function loadScotland(): void
    {
        if (self::$scotland !== null) {
            return;
        }
        $data = require __DIR__ . '/../data/scotland.php';
        $councils = [];
        foreach ($data['councils'] as $council => $county) {
            $councils[self::key($council)] = $county;
        }
        self::$scotland = ['edinburgh' => $data['edinburgh'], 'outcodes' => $data['outcodes'], 'councils' => $councils];
    }
}
