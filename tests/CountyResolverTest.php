<?php

namespace Postio\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Postio\CountyResolver;

class CountyResolverTest extends TestCase
{
    /**
     * The county names used by adamb/uk-counties, which the resolver must return verbatim.
     */
    private const COUNTIES = [
        'Bedfordshire', 'Berkshire', 'Bristol', 'Buckinghamshire', 'Cambridgeshire', 'Cheshire', 'City of London', 'Cornwall',
        'Cumbria', 'Derbyshire', 'Devon', 'Dorset', 'Durham', 'East Riding of Yorkshire', 'East Sussex', 'Essex',
        'Gloucestershire', 'Greater London', 'Greater Manchester', 'Hampshire', 'Herefordshire', 'Hertfordshire',
        'Isle of Wight', 'Kent', 'Lancashire', 'Leicestershire', 'Lincolnshire', 'Merseyside', 'Norfolk', 'North Yorkshire',
        'Northamptonshire', 'Northumberland', 'Nottinghamshire', 'Oxfordshire', 'Rutland', 'Shropshire', 'Somerset',
        'South Yorkshire', 'Staffordshire', 'Suffolk', 'Surrey', 'Tyne and Wear', 'Warwickshire', 'West Midlands',
        'West Sussex', 'West Yorkshire', 'Wiltshire', 'Worcestershire',
        'Aberdeenshire', 'Angus', 'Argyll', 'Ayrshire', 'Banffshire', 'Berwickshire', 'Bute', 'Caithness', 'Clackmannanshire',
        'Cromartyshire', 'Dumfriesshire', 'Dunbartonshire', 'East Lothian', 'City of Edinburgh', 'Fife', 'Inverness-shire',
        'Kincardineshire', 'Kinross-shire', 'Kirkcudbrightshire', 'Lanarkshire', 'Midlothian', 'Moray', 'Nairnshire', 'Orkney',
        'Peeblesshire', 'Perthshire', 'Renfrewshire', 'Ross-shire', 'Roxburghshire', 'Selkirkshire', 'Shetland',
        'Stirlingshire', 'Sutherland', 'West Lothian', 'Wigtownshire',
        'Blaenau Gwent', 'Bridgend', 'Caerphilly', 'Cardiff', 'Carmarthenshire', 'Ceredigion', 'Conwy', 'Denbighshire',
        'Flintshire', 'Gwynedd', 'Isle of Anglesey', 'Merthyr Tydfil', 'Monmouthshire', 'Neath Port Talbot', 'Newport',
        'Pembrokeshire', 'Powys', 'Rhondda Cynon Taf', 'Swansea', 'Torfaen', 'Vale of Glamorgan', 'Wrexham',
    ];

    public static function places(): array
    {
        return [
            ['England', 'Leeds', 'LS1', 'West Yorkshire'],
            ['England', 'Kingston upon Hull, City of', 'HU1', 'East Riding of Yorkshire'],
            ['England', 'St. Helens', 'WA10', 'Merseyside'],
            ['England', 'Stockton-on-Tees', 'TS18', 'Durham'],
            ['England', 'Stockton-on-Tees', 'TS17', 'North Yorkshire'],
            ['England', 'Isles of Scilly', 'TR21', 'Cornwall'],
            ['Wales', 'Rhondda Cynon Taf', 'CF37', 'Rhondda Cynon Taf'],
            ['England', 'City of Westminster London Boro', 'SW1A', 'Greater London'],
            ['England', 'City and County of the City of London', 'EC2V', 'City of London'],
            ['England', 'Leeds District (B)', 'LS1', 'West Yorkshire'],
            ['England', 'Medway (B)', 'ME4', 'Kent'],
            ['England', '"Bournemouth', 'BH6', 'Dorset'],
            ['England', 'The City of Brighton and Hove (B)', 'BN2', 'East Sussex'],
            ['England', 'City of Kingston upon Hull (B)', 'HU7', 'East Riding of Yorkshire'],
            ['England', 'County of Herefordshire', 'HR1', 'Herefordshire'],
            ['Wales', 'Caerdydd - Cardiff', 'CF10', 'Cardiff'],
            ['Wales', 'Bro Morgannwg - the Vale of Glamorgan', 'CF62', 'Vale of Glamorgan'],
            ['Scotland', 'Highland', 'IV1', 'Inverness-shire'],
            ['Scotland', 'Highland', 'KW1', 'Caithness'],
            ['Scotland', 'Highland', 'IV99', 'Inverness-shire'],
            ['Scotland', 'City of Edinburgh', 'EH1', 'City of Edinburgh'],
            ['Scotland', 'Midlothian', 'EH22', 'Midlothian'],
            ['Scotland', 'Na h-Eileanan an Iar', 'HS1', 'Ross-shire'],
            ['Northern Ireland', 'Belfast', 'BT1', ''],
            ['England', 'Nowhere', 'ZZ1', ''],
        ];
    }

    #[DataProvider('places')]
    public function testResolve(string $country, string $district, string $outcode, string $county): void
    {
        $this->assertSame($county, CountyResolver::resolve($country, $district, $outcode));
    }

    public function testEveryCountyIsOnTheCountyList(): void
    {
        $england = require __DIR__ . '/../data/england.php';
        $scotland = require __DIR__ . '/../data/scotland.php';
        $targets = array_merge(
            array_keys($england['districts']),
            array_merge(...array_map('array_values', array_values($england['split']))),
            CountyResolver::WALES,
            [$scotland['edinburgh']],
            array_values($scotland['outcodes']),
            array_values($scotland['councils'])
        );
        $this->assertSame([], array_values(array_diff(array_unique($targets), self::COUNTIES)));
    }

    public function testNoDistrictIsListedTwice(): void
    {
        $england = require __DIR__ . '/../data/england.php';
        $all = array_merge(...array_values($england['districts']));
        $this->assertSame(count($all), count(array_unique($all)));
    }
}
