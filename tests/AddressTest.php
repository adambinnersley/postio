<?php

namespace Postio\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Postio\Address;

class AddressTest extends TestCase
{
    public static function towns(): array
    {
        return [
            ['NEWCASTLE UPON TYNE', 'Newcastle upon Tyne'],
            ['STOKE-ON-TRENT', 'Stoke-on-Trent'],
            ['WALTON-ON-THE-NAZE', 'Walton-on-the-Naze'],
            ['ASHBY-DE-LA-ZOUCH', 'Ashby-de-la-Zouch'],
            ['WESTON-SUPER-MARE', 'Weston-super-Mare'],
            ["KING'S LYNN", "King's Lynn"],
            ['ST. ALBANS', 'St. Albans'],
            ['Inverness', 'Inverness'],
        ];
    }

    #[DataProvider('towns')]
    public function testTitleCase(string $in, string $out): void
    {
        $this->assertSame($out, Address::titleCase($in));
    }

    public function testBareBuildingRangeJoinsTheStreet(): void
    {
        $address = new Address(['address_line_1' => 'Example Ltd', 'address_line_2' => '3-9', 'address_line_3' => 'Sample Street', 'post_town' => 'LEEDS']);
        $this->assertSame(['Example Ltd', '3-9 Sample Street'], $address->lines());
        $this->assertSame('Example Ltd, 3-9 Sample Street, Leeds', $address->label());
    }

    public function testLinesFoldToFitTheForm(): void
    {
        $address = new Address(['address_line_1' => 'Flat 2', 'address_line_2' => 'Rose House', 'address_line_3' => '12 Sample Street']);
        $this->assertSame(['Flat 2', 'Rose House', '12 Sample Street'], $address->lines(3));
        $this->assertSame(['Flat 2, Rose House', '12 Sample Street'], $address->lines(2));
        $this->assertSame(['Flat 2, Rose House, 12 Sample Street'], $address->lines(1));
        $this->assertSame('12 Sample Street', $address->toArray(2)['address2']);
    }

    public function testCountyComesFromTheDistrict(): void
    {
        $address = new Address(['country' => 'England', 'district' => 'Croydon', 'postcode' => 'CR0 1AA']);
        $this->assertSame('Greater London', $address->county);
    }
}
