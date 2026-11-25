<?php

namespace Postio;

/**
 * One delivery point from Postio, tidied for filling in a form.
 *
 * Postio formats up to three address lines to Royal Mail rules but leaves a bare
 * building number or range ("3-9") on its own line, and some post towns arrive in
 * capitals; both are corrected here.
 */
class Address
{
    public readonly int $udprn;
    public readonly string $postcode;
    public readonly string $organisation;
    public readonly string $town;
    public readonly string $county;
    public readonly string $district;
    public readonly string $country;
    public readonly ?float $latitude;
    public readonly ?float $longitude;
    /** @var string[] */
    private array $lines;

    /**
     * @param array<string, mixed> $data One entry of a Postio `results` array
     */
    public function __construct(public readonly array $data)
    {
        $this->udprn = (int)($data['udprn'] ?? 0);
        $this->postcode = (string)($data['postcode'] ?? '');
        $this->organisation = (string)($data['organisation_name'] ?? '');
        $this->town = self::titleCase((string)($data['post_town'] ?? ''));
        $this->district = (string)($data['district'] ?? '');
        $this->country = (string)($data['country'] ?? '');
        $this->county = CountyResolver::resolve($this->country, $this->district, $data['postcode_outward'] ?? strtok($this->postcode, ' '));
        $this->latitude = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $this->longitude = isset($data['longitude']) ? (float)$data['longitude'] : null;
        $this->lines = self::joinBareNumbers(array_values(array_filter(
            [$data['address_line_1'] ?? '', $data['address_line_2'] ?? '', $data['address_line_3'] ?? ''],
            fn ($line) => trim((string)$line) !== ''
        )));
    }

    /**
     * The address lines, folded to fit `$max` form fields by joining the first lines
     * with a comma ("Flat 2, Rose House" / "12 High Street").
     *
     * @return string[]
     */
    public function lines(int $max = 3): array
    {
        $lines = $this->lines;
        $max = max(1, $max);
        if (count($lines) > $max) {
            $head = array_splice($lines, 0, count($lines) - $max + 1);
            array_unshift($lines, implode(', ', $head));
        }
        return $lines;
    }

    /**
     * One-line description for an address picker, without the postcode.
     */
    public function label(): string
    {
        return implode(', ', array_filter([...$this->lines, $this->town]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(int $maxLines = 3): array
    {
        $lines = $this->lines($maxLines);
        return [
            'udprn' => $this->udprn,
            'organisation' => $this->organisation,
            'address1' => $lines[0] ?? '',
            'address2' => $lines[1] ?? '',
            'address3' => $lines[2] ?? '',
            'town' => $this->town,
            'county' => $this->county,
            'postcode' => $this->postcode,
            'district' => $this->district,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * @param string[] $lines
     * @return string[]
     */
    private static function joinBareNumbers(array $lines): array
    {
        $out = [];
        for ($i = 0, $n = count($lines); $i < $n; $i++) {
            $line = trim($lines[$i]);
            if ($i + 1 < $n && preg_match('/^\d+[A-Za-z]?(\s*-\s*\d+[A-Za-z]?)?$/', $line)) {
                $lines[$i + 1] = $line . ' ' . trim($lines[$i + 1]);
                continue;
            }
            $out[] = $line;
        }
        return $out;
    }

    /**
     * "NEWCASTLE UPON TYNE" => "Newcastle upon Tyne", "STOKE-ON-TRENT" => "Stoke-on-Trent".
     * Towns already in mixed case are left alone.
     */
    public static function titleCase(string $town): string
    {
        $town = trim($town);
        if ($town === '' || $town !== mb_strtoupper($town)) {
            return $town;
        }
        $town = preg_replace_callback('/(^|[\s\-])(\p{L})/u', fn ($m) => $m[1] . mb_strtoupper($m[2]), mb_strtolower($town));
        return preg_replace_callback(
            '/(?<=[\s\-])(Upon|On|In|Under|By|The|Le|La|De|Next|With|Of|And|En|Cum|Super|Sub)(?=[\s\-])/u',
            fn ($m) => strtolower($m[1]),
            $town
        );
    }
}
