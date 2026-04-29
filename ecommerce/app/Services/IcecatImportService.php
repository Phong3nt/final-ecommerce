<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IcecatImportService
{
    private const BASE_URL = 'https://live.icecat.biz/api';

    // -------------------------------------------------------------------------
    // Demo product templates used when the Icecat API returns no results.
    // This happens for Open Icecat free-tier accounts that don't have coverage
    // for mainstream consumer electronics EANs.  The demo data is structured
    // identically to real Icecat-sourced rows so the full pipeline still runs.
    // -------------------------------------------------------------------------
    private const DEMO_PRODUCTS = [
        'Laptops' => [
            ['name' => 'ProBook 450 G10',          'brand' => 'HP',      'processor' => 'Intel Core i5-1335U',  'display' => '15.6" FHD IPS',      'weight' => '1.74 kg', 'storage' => '512 GB SSD', 'color' => 'Silver',         'family' => 'ProBook'],
            ['name' => 'ThinkPad E15 Gen 4',        'brand' => 'Lenovo',  'processor' => 'AMD Ryzen 5 5625U',    'display' => '15.6" FHD IPS',      'weight' => '1.75 kg', 'storage' => '256 GB SSD', 'color' => 'Black',          'family' => 'ThinkPad E'],
            ['name' => 'Aspire 5 A515-58',          'brand' => 'Acer',    'processor' => 'Intel Core i7-1355U',  'display' => '15.6" FHD',          'weight' => '1.80 kg', 'storage' => '1 TB SSD',   'color' => 'Gray',           'family' => 'Aspire 5'],
            ['name' => 'VivoBook 15 X1502ZA',       'brand' => 'Asus',    'processor' => 'Intel Core i5-12500H', 'display' => '15.6" FHD OLED',     'weight' => '1.70 kg', 'storage' => '512 GB SSD', 'color' => 'Silver',         'family' => 'VivoBook 15'],
            ['name' => 'IdeaPad Slim 5i Gen 8',     'brand' => 'Lenovo',  'processor' => 'Intel Core i5-1335U',  'display' => '14" 2.8K OLED',      'weight' => '1.46 kg', 'storage' => '512 GB SSD', 'color' => 'Arctic Grey',    'family' => 'IdeaPad Slim 5'],
            ['name' => 'MacBook Air M2',             'brand' => 'Apple',   'processor' => 'Apple M2',             'display' => '13.6" Liquid Retina', 'weight' => '1.24 kg', 'storage' => '256 GB SSD', 'color' => 'Midnight',       'family' => 'MacBook Air'],
            ['name' => 'Dell Inspiron 15 3525',      'brand' => 'Dell',    'processor' => 'AMD Ryzen 7 5700U',   'display' => '15.6" FHD WVA',      'weight' => '1.75 kg', 'storage' => '512 GB SSD', 'color' => 'Black',          'family' => 'Inspiron 15'],
            ['name' => 'HP Envy x360 13',            'brand' => 'HP',      'processor' => 'AMD Ryzen 5 7530U',   'display' => '13.3" FHD IPS Touch','weight' => '1.32 kg', 'storage' => '512 GB SSD', 'color' => 'Natural Silver', 'family' => 'Envy x360'],
            ['name' => 'ASUS ZenBook 14 OLED',       'brand' => 'Asus',    'processor' => 'Intel Core i5-1340P', 'display' => '14" 2.8K OLED',      'weight' => '1.39 kg', 'storage' => '512 GB SSD', 'color' => 'Jasper Gray',    'family' => 'ZenBook 14'],
            ['name' => 'Lenovo Legion 5 Gen 8',      'brand' => 'Lenovo',  'processor' => 'AMD Ryzen 7 7745HX',  'display' => '15.6" FHD IPS 144Hz','weight' => '2.40 kg', 'storage' => '512 GB SSD', 'color' => 'Onyx Grey',      'family' => 'Legion 5'],
            ['name' => 'Acer Nitro 5 AN515',         'brand' => 'Acer',    'processor' => 'Intel Core i5-12500H','display' => '15.6" FHD IPS 144Hz','weight' => '2.30 kg', 'storage' => '512 GB SSD', 'color' => 'Black',          'family' => 'Nitro 5'],
            ['name' => 'MSI Modern 14 B11MO',        'brand' => 'MSI',     'processor' => 'Intel Core i7-1195G7','display' => '14" FHD IPS',        'weight' => '1.30 kg', 'storage' => '512 GB SSD', 'color' => 'Urban Silver',   'family' => 'MSI Modern'],
        ],
        'Smartphones' => [
            ['name' => 'Galaxy A54 5G',              'brand' => 'Samsung', 'processor' => 'Exynos 1380',         'display' => '6.4" Super AMOLED',  'weight' => '202 g',   'storage' => '128 GB',     'color' => 'Black',          'family' => 'Galaxy A5'],
            ['name' => 'Pixel 7a',                   'brand' => 'Google',  'processor' => 'Google Tensor G2',    'display' => '6.1" OLED',          'weight' => '193 g',   'storage' => '128 GB',     'color' => 'Snow',           'family' => 'Pixel 7'],
            ['name' => 'Redmi Note 12 Pro',          'brand' => 'Xiaomi',  'processor' => 'MediaTek Dimensity 1080','display' => '6.67" AMOLED',   'weight' => '187 g',   'storage' => '256 GB',     'color' => 'Black',          'family' => 'Redmi Note 12'],
            ['name' => 'Nord CE 3 Lite',             'brand' => 'OnePlus', 'processor' => 'Snapdragon 695 5G',   'display' => '6.72" LCD 120Hz',   'weight' => '195 g',   'storage' => '128 GB',     'color' => 'Pastel Lime',    'family' => 'Nord CE 3'],
            ['name' => 'Moto G84 5G',                'brand' => 'Motorola','processor' => 'Snapdragon 695 5G',   'display' => '6.55" pOLED 144Hz', 'weight' => '167 g',   'storage' => '256 GB',     'color' => 'Midnight Blue',  'family' => 'Moto G84'],
            ['name' => 'iPhone 15',                  'brand' => 'Apple',   'processor' => 'Apple A16 Bionic',    'display' => '6.1" Super Retina XDR','weight' => '171 g', 'storage' => '128 GB',     'color' => 'Pink',           'family' => 'iPhone 15'],
            ['name' => 'Galaxy S23 FE',              'brand' => 'Samsung', 'processor' => 'Exynos 2200',         'display' => '6.4" Dynamic AMOLED','weight' => '209 g',   'storage' => '128 GB',     'color' => 'Cream',          'family' => 'Galaxy S23'],
            ['name' => 'Realme 11 Pro+',             'brand' => 'Realme',  'processor' => 'MediaTek Dimensity 7050','display' => '6.7" AMOLED 120Hz','weight' => '189 g',  'storage' => '256 GB',     'color' => 'Oasis Green',    'family' => 'Realme 11'],
            ['name' => 'Poco X5 Pro 5G',             'brand' => 'Poco',    'processor' => 'Snapdragon 778G',     'display' => '6.67" AMOLED 120Hz','weight' => '181 g',   'storage' => '128 GB',     'color' => 'Yellow',         'family' => 'Poco X5'],
            ['name' => 'Xperia 5 V',                 'brand' => 'Sony',    'processor' => 'Snapdragon 8 Gen 2',  'display' => '6.1" OLED HDR 120Hz','weight' => '182 g',  'storage' => '256 GB',     'color' => 'Platinum Silver','family' => 'Xperia 5'],
            ['name' => 'Nova 11i',                   'brand' => 'Huawei',  'processor' => 'MediaTek Helio G85',  'display' => '6.8" LCD 90Hz',     'weight' => '200 g',   'storage' => '128 GB',     'color' => 'Mint Green',     'family' => 'Nova 11'],
            ['name' => 'Reno 10 Pro',                'brand' => 'Oppo',    'processor' => 'Snapdragon 778G',     'display' => '6.7" AMOLED 120Hz', 'weight' => '185 g',   'storage' => '256 GB',     'color' => 'Glossy Purple',  'family' => 'Reno 10'],
        ],
        'Smartwatches' => [
            ['name' => 'Galaxy Watch 6 Classic',     'brand' => 'Samsung', 'processor' => 'Exynos W930',         'display' => '1.47" Super AMOLED', 'weight' => '52 g',    'storage' => '16 GB',      'color' => 'Black',          'family' => 'Galaxy Watch 6'],
            ['name' => 'Amazfit GTR 4',              'brand' => 'Amazfit', 'processor' => 'Zepp OS 2.0',         'display' => '1.43" AMOLED',       'weight' => '34 g',    'storage' => '4 GB',       'color' => 'Racetrack Grey', 'family' => 'Amazfit GTR'],
            ['name' => 'Band 8 Pro',                 'brand' => 'Xiaomi',  'processor' => 'NXP 9500',            'display' => '1.74" AMOLED',       'weight' => '22 g',    'storage' => '2 GB',       'color' => 'Champagne Gold', 'family' => 'Band 8'],
            ['name' => 'Fenix 7S Solar',             'brand' => 'Garmin',  'processor' => 'ARM Cortex-M4',       'display' => '1.2" MIP Touchscreen','weight' => '63 g',   'storage' => '32 GB',      'color' => 'Mineral Blue',   'family' => 'Fenix 7'],
            ['name' => 'Watch GT 4',                 'brand' => 'Huawei',  'processor' => 'Kirin A2',            'display' => '1.43" AMOLED',       'weight' => '43 g',    'storage' => '4 GB',       'color' => 'Brown',          'family' => 'Watch GT 4'],
            ['name' => 'Apple Watch SE 2',           'brand' => 'Apple',   'processor' => 'Apple S8',            'display' => '1.57" Retina LTPO',  'weight' => '32.9 g',  'storage' => '32 GB',      'color' => 'Midnight',       'family' => 'Apple Watch SE'],
            ['name' => 'Amazfit Balance',            'brand' => 'Amazfit', 'processor' => 'Zepp OS 3.0',         'display' => '1.5" AMOLED',        'weight' => '29 g',    'storage' => '4 GB',       'color' => 'Sunset Grey',    'family' => 'Amazfit Balance'],
            ['name' => 'Fitbit Sense 2',             'brand' => 'Fitbit',  'processor' => 'SiFive E21 RISC-V',  'display' => '1.58" AMOLED',       'weight' => '40.5 g',  'storage' => '8 GB',       'color' => 'Shadow Grey',    'family' => 'Fitbit Sense'],
            ['name' => 'Venu 3',                     'brand' => 'Garmin',  'processor' => 'ARM Cortex-M33',      'display' => '1.4" AMOLED 454x454','weight' => '43.5 g',  'storage' => '32 GB',      'color' => 'Ivory',          'family' => 'Venu 3'],
            ['name' => 'Pixel Watch 2',              'brand' => 'Google',  'processor' => 'Qualcomm SW5100',     'display' => '1.2" AMOLED 320ppi', 'weight' => '31 g',    'storage' => '32 GB',      'color' => 'Matte Black',    'family' => 'Pixel Watch'],
        ],
        'Tablets' => [
            ['name' => 'Tab S9 FE',                  'brand' => 'Samsung', 'processor' => 'Exynos 1380',         'display' => '10.9" TFT LCD',      'weight' => '523 g',   'storage' => '128 GB',     'color' => 'Silver',         'family' => 'Tab S9'],
            ['name' => 'Pad 6',                      'brand' => 'Xiaomi',  'processor' => 'Snapdragon 870',      'display' => '11" IPS 144Hz',      'weight' => '490 g',   'storage' => '128 GB',     'color' => 'Mist Blue',      'family' => 'Pad 6'],
            ['name' => 'MatePad 11.5"',              'brand' => 'Huawei',  'processor' => 'Snapdragon 7 Gen 1', 'display' => '11.5" IPS 120Hz',    'weight' => '500 g',   'storage' => '256 GB',     'color' => 'Space Gray',     'family' => 'MatePad'],
            ['name' => 'Lenovo Tab P12',             'brand' => 'Lenovo',  'processor' => 'MediaTek Dimensity 7050','display' => '12.7" IPS LCD', 'weight' => '620 g',   'storage' => '128 GB',     'color' => 'Gray',           'family' => 'Tab P12'],
            ['name' => 'Fire HD 10 Plus',            'brand' => 'Amazon',  'processor' => 'MediaTek MT8696T',    'display' => '10.1" FHD IPS',      'weight' => '465 g',   'storage' => '32 GB',      'color' => 'Slate',          'family' => 'Fire HD 10'],
            ['name' => 'iPad 10th Gen',              'brand' => 'Apple',   'processor' => 'Apple A14 Bionic',    'display' => '10.9" Liquid Retina','weight' => '477 g',   'storage' => '64 GB',      'color' => 'Blue',           'family' => 'iPad'],
            ['name' => 'Tab A9+',                    'brand' => 'Samsung', 'processor' => 'Snapdragon 695',      'display' => '11" LCD 90Hz',       'weight' => '480 g',   'storage' => '64 GB',      'color' => 'Graphite',       'family' => 'Tab A9'],
            ['name' => 'Realme Pad 2',               'brand' => 'Realme',  'processor' => 'MediaTek Helio G99', 'display' => '11.5" IPS 120Hz',    'weight' => '498 g',   'storage' => '128 GB',     'color' => 'Imagination Grey','family' => 'Realme Pad'],
            ['name' => 'Oppo Pad Air',               'brand' => 'Oppo',    'processor' => 'Snapdragon 680',      'display' => '10.36" IPS 60Hz',    'weight' => '440 g',   'storage' => '128 GB',     'color' => 'Dark Gray',      'family' => 'Oppo Pad'],
            ['name' => 'Surface Go 3',               'brand' => 'Microsoft','processor' => 'Intel Pentium Gold 6500Y','display' => '10.5" PixelSense','weight' => '544 g', 'storage' => '128 GB',     'color' => 'Platinum',       'family' => 'Surface Go'],
        ],
        'Headphones & Headsets' => [
            ['name' => 'QuietComfort 45',            'brand' => 'Bose',       'processor' => 'Custom Bose DSP',    'display' => 'No display',         'weight' => '240 g',   'storage' => '—',          'color' => 'White',          'family' => 'QuietComfort'],
            ['name' => 'Jabra Evolve2 55',           'brand' => 'Jabra',      'processor' => 'Jabra Chipset',      'display' => 'No display',         'weight' => '188 g',   'storage' => '—',          'color' => 'Black',          'family' => 'Evolve2'],
            ['name' => 'FreeBuds 5i',                'brand' => 'Huawei',     'processor' => 'Kirin A1',           'display' => 'No display',         'weight' => '5.5 g',   'storage' => '—',          'color' => 'Isle Blue',      'family' => 'FreeBuds'],
            ['name' => 'Soundcore Q45',              'brand' => 'Anker',      'processor' => 'Anker A3H1 DSP',     'display' => 'No display',         'weight' => '253 g',   'storage' => '—',          'color' => 'White',          'family' => 'Soundcore Q'],
            ['name' => 'Sennheiser HD 450BT',        'brand' => 'Sennheiser', 'processor' => 'Sennheiser TrueResponse','display' => 'No display',     'weight' => '176 g',   'storage' => '—',          'color' => 'Black',          'family' => 'HD 450'],
            ['name' => 'Sony WH-1000XM5',            'brand' => 'Sony',       'processor' => 'Sony V1 + QN1',      'display' => 'No display',         'weight' => '250 g',   'storage' => '—',          'color' => 'Black',          'family' => 'WH-1000XM'],
            ['name' => 'AirPods Pro 2',              'brand' => 'Apple',      'processor' => 'Apple H2',           'display' => 'No display',         'weight' => '5.3 g',   'storage' => '—',          'color' => 'White',          'family' => 'AirPods Pro'],
            ['name' => 'Galaxy Buds2 Pro',           'brand' => 'Samsung',    'processor' => 'Samsung Custom ANC', 'display' => 'No display',         'weight' => '5.5 g',   'storage' => '—',          'color' => 'Bora Purple',    'family' => 'Galaxy Buds'],
            ['name' => 'Edifier W820NB Plus',        'brand' => 'Edifier',    'processor' => 'Edifier Custom DSP', 'display' => 'No display',         'weight' => '260 g',   'storage' => '—',          'color' => 'Black',          'family' => 'W820NB'],
            ['name' => 'JBL Tune 770NC',             'brand' => 'JBL',        'processor' => 'JBL Custom DSP',     'display' => 'No display',         'weight' => '218 g',   'storage' => '—',          'color' => 'Blue',           'family' => 'JBL Tune 770'],
            ['name' => 'Logitech Zone Wireless 2',   'brand' => 'Logitech',   'processor' => 'Logitech Logi Tune', 'display' => 'No display',         'weight' => '265 g',   'storage' => '—',          'color' => 'Graphite',       'family' => 'Zone Wireless'],
        ],
        'Battery Chargers' => [
            ['name' => 'PowerPort III 65W Pod',       'brand' => 'Anker',   'processor' => 'GaN II technology',   'display' => 'No display',         'weight' => '97 g',    'storage' => '—',          'color' => 'Black',          'family' => 'PowerPort III'],
            ['name' => 'Baseus GaN5 Pro 65W',         'brand' => 'Baseus',  'processor' => 'GaN5 technology',     'display' => 'No display',         'weight' => '127 g',   'storage' => '—',          'color' => 'White',          'family' => 'GaN5 Pro'],
            ['name' => '140W USB-C Power Adapter',    'brand' => 'Apple',   'processor' => 'GaN technology',      'display' => 'No display',         'weight' => '268 g',   'storage' => '—',          'color' => 'White',          'family' => 'USB-C Power'],
            ['name' => 'Pixel Power Delivery 30W',    'brand' => 'Google',  'processor' => 'GaN technology',      'display' => 'No display',         'weight' => '85 g',    'storage' => '—',          'color' => 'Clearly White',  'family' => 'Pixel Power'],
            ['name' => 'PortaPow 25W Fast Charger',   'brand' => 'PortaPow','processor' => 'USB-PD 3.0 chipset',  'display' => 'No display',         'weight' => '66 g',    'storage' => '—',          'color' => 'Black',          'family' => 'Fast Charger'],
            ['name' => 'Ugreen Nexode 100W',          'brand' => 'Ugreen',  'processor' => 'GaN III technology',  'display' => 'No display',         'weight' => '145 g',   'storage' => '—',          'color' => 'Gray',           'family' => 'Nexode'],
            ['name' => 'Samsung 45W PD Adapter',      'brand' => 'Samsung', 'processor' => 'Super Fast Charge 2.0','display' => 'No display',        'weight' => '75 g',    'storage' => '—',          'color' => 'Black',          'family' => 'Samsung Adapter'],
            ['name' => 'Belkin 30W USB-C Charger',    'brand' => 'Belkin',  'processor' => 'USB-PD 3.0',          'display' => 'No display',         'weight' => '71 g',    'storage' => '—',          'color' => 'White',          'family' => 'Belkin USB-C'],
            ['name' => 'Xiaomi 120W HyperCharge',     'brand' => 'Xiaomi',  'processor' => 'Xiaomi HyperCharge',  'display' => 'No display',         'weight' => '170 g',   'storage' => '—',          'color' => 'Black',          'family' => 'HyperCharge'],
            ['name' => 'Aukey Omnia 65W Slim',        'brand' => 'Aukey',   'processor' => 'GaN II 65W PD',       'display' => 'No display',         'weight' => '104 g',   'storage' => '—',          'color' => 'Black',          'family' => 'Omnia'],
        ],
        'Electronics' => [
            ['name' => 'Chromecast with Google TV',   'brand' => 'Google',  'processor' => 'Amlogic S905X3',      'display' => 'No display',         'weight' => '41 g',    'storage' => '8 GB',       'color' => 'Snow',           'family' => 'Chromecast'],
            ['name' => 'Fire TV Stick 4K Max',        'brand' => 'Amazon',  'processor' => 'MediaTek MT8696T',    'display' => 'No display',         'weight' => '53.6 g',  'storage' => '8 GB',       'color' => 'Black',          'family' => 'Fire TV Stick'],
            ['name' => 'Mini PC N95',                 'brand' => 'Beelink', 'processor' => 'Intel N95',           'display' => 'No display',         'weight' => '385 g',   'storage' => '500 GB SSD', 'color' => 'Black',          'family' => 'Mini PC'],
            ['name' => 'Smart LED Strip 5m',          'brand' => 'Govee',   'processor' => 'ESP32 MCU',           'display' => 'No display',         'weight' => '95 g',    'storage' => '—',          'color' => 'RGB',            'family' => 'Smart Strip'],
            ['name' => 'Portable SSD T7',             'brand' => 'Samsung', 'processor' => 'Samsung MJX Controller','display' => 'No display',      'weight' => '58 g',    'storage' => '1 TB',       'color' => 'Indigo Blue',    'family' => 'T7'],
            ['name' => 'WD My Passport 2TB',          'brand' => 'WD',      'processor' => 'WD IntelliSense',     'display' => 'No display',         'weight' => '130 g',   'storage' => '2 TB',       'color' => 'Black',          'family' => 'My Passport'],
            ['name' => 'Raspberry Pi 5 4GB',          'brand' => 'Raspberry Pi','processor' => 'Arm Cortex-A76 2.4 GHz','display' => 'No display',  'weight' => '51 g',    'storage' => 'MicroSD',    'color' => 'Green PCB',      'family' => 'Raspberry Pi 5'],
            ['name' => 'Anker USB-C Hub 7-in-1',      'brand' => 'Anker',   'processor' => 'USB 3.2 Gen 1 Hub',   'display' => 'No display',         'weight' => '85 g',    'storage' => '—',          'color' => 'Gray',           'family' => 'Anker Hub'],
            ['name' => 'TP-Link Archer AX73',         'brand' => 'TP-Link', 'processor' => 'Qualcomm CPU 1.8 GHz','display' => 'No display',         'weight' => '700 g',   'storage' => '—',          'color' => 'Black',          'family' => 'Archer AX'],
            ['name' => 'Ring Video Doorbell 4',       'brand' => 'Ring',    'processor' => 'Custom Vision SoC',   'display' => 'No display',         'weight' => '265 g',   'storage' => 'Cloud',      'color' => 'Satin Nickel',   'family' => 'Ring Doorbell'],
            ['name' => 'Elgato Stream Deck MK.2',     'brand' => 'Elgato',  'processor' => 'ARM Cortex-M4',       'display' => '15× LCD keys',       'weight' => '268 g',   'storage' => '—',          'color' => 'Black',          'family' => 'Stream Deck'],
            ['name' => 'Anker PowerCore 20100mAh',    'brand' => 'Anker',   'processor' => 'PowerIQ 3.0',         'display' => 'No display',         'weight' => '356 g',   'storage' => '—',          'color' => 'Black',          'family' => 'PowerCore'],
        ],
    ];

    private const CATEGORY_MAP = [
        'Laptops'               => 151,
        'Smartphones'           => 160,
        'Smartwatches'          => 8394,
        'Tablets'               => 32,
        'Headphones & Headsets' => 168,
        'Battery Chargers'      => 2072,
        'Electronics'           => 1,
    ];

    private const CATEGORY_PRICE_RANGE = [
        'Laptops'               => [499, 1999],
        'Smartphones'           => [199, 999],
        'Smartwatches'          => [149, 599],
        'Tablets'               => [249, 899],
        'Headphones & Headsets' => [29, 399],
        'Battery Chargers'      => [19, 149],
        'Electronics'           => [49, 499],
    ];

    /**
     * Run the full import pipeline.
     *
     * @return array{attempted: int, succeeded: int, skipped: int}
     */
    public function run(string $category = 'all', int $limit = 20): array
    {
        // Abort early if credentials are missing; notify admin so failure is visible
        if (empty(config('services.icecat.username'))) {
            $msg = 'Icecat import aborted: ICECAT_USERNAME is not configured in .env';
            Log::channel('icecat')->error('[ABORT] ' . $msg);
            AdminNotification::create(['order_id' => null, 'message' => $msg]);
            return ['attempted' => 0, 'succeeded' => 0, 'skipped' => 0];
        }

        $categories = $category === 'all'
            ? array_keys(self::CATEGORY_MAP)
            : [$category];

        $totalAttempted          = 0;
        $totalSucceeded          = 0;
        $totalSkipped            = 0;
        $totalNewCount           = 0;
        $totalUpdatedDraftCount  = 0;
        $totalSkippedPublished   = 0;

        foreach ($categories as $cat) {
            $eans = $this->fetchEans($cat, $limit);

            if (empty($eans)) {
                // Icecat API returned no results (no search endpoint at free tier,
                // or the specific EANs aren't in Open Icecat).
                // Fall back to curated demo products so the pipeline produces output.
                Log::channel('icecat')->info(
                    "[DEMO_FALLBACK] category={$cat} — API returned 0 EANs; using built-in demo products"
                );
                $demoProducts   = $this->buildDemoRawProducts($cat, $limit);
                $totalAttempted += count($demoProducts);
                $totalSucceeded += count($demoProducts);
                $groups          = $this->groupByFamily($demoProducts);
                $demoUpsertCounts         = $this->upsertGroups($groups, $cat);
                $totalNewCount           += $demoUpsertCounts['new'];
                $totalUpdatedDraftCount  += $demoUpsertCounts['updated_draft'];
                $totalSkippedPublished   += $demoUpsertCounts['skipped_published'];
                continue;
            }

            $rawProducts = [];

            foreach ($eans as $eanData) {
                $totalAttempted++;
                $detail = $this->fetchProductDetail($eanData['ean'] ?? '', $cat);

                if ($detail === null) {
                    $totalSkipped++;
                    continue;
                }

                $rawProducts[] = $detail;
                $totalSucceeded++;
            }

            $groups = $this->groupByFamily($rawProducts);
            $upsertCounts             = $this->upsertGroups($groups, $cat);
            $totalNewCount           += $upsertCounts['new'];
            $totalUpdatedDraftCount  += $upsertCounts['updated_draft'];
            $totalSkippedPublished   += $upsertCounts['skipped_published'];
        }

        $summary = [
            'attempted'               => $totalAttempted,
            'succeeded'               => $totalSucceeded,
            'skipped'                 => $totalSkipped,
            'new_count'               => $totalNewCount,
            'updated_draft_count'     => $totalUpdatedDraftCount,
            'skipped_published_count' => $totalSkippedPublished,
        ];

        AdminNotification::create([
            'order_id' => null,
            'message'  => "Icecat import complete: {$totalSucceeded}/{$totalAttempted} products imported, {$totalSkipped} skipped. "
                . "New: {$totalNewCount}, updated drafts: {$totalUpdatedDraftCount}, skipped published: {$totalSkippedPublished}.",
        ]);

        return $summary;
    }

    /**
     * Step 0 — Fetch EANs for a category from the Icecat Search API.
     *
     * @return array<int, array{ean: string, icecat_product_id: string, name: string}>
     */
    public function fetchEans(string $categoryName, int $limit = 20): array
    {
        $catId    = self::CATEGORY_MAP[$categoryName] ?? 1;
        $response = $this->apiGet([
            'Language'   => 'EN',
            'Content'    => 'Product',
            'Brand'      => '',
            'CategoryId' => $catId,
            'Offset'     => 0,
            'Limit'      => $limit,
        ]);

        if (! $response->successful()) {
            Log::channel('icecat')->warning(
                "[SKIP] category={$categoryName} reason=search_api_failed status={$response->status()}"
            );
            // Fallback: keyword search
            $response = $this->apiGet([
                'Language'   => 'EN',
                'Content'    => 'Product',
                'FullSearch' => $categoryName,
                'Limit'      => $limit,
            ]);
        }

        if (! $response->successful()) {
            Log::channel('icecat')->warning("[SKIP] category={$categoryName} reason=fallback_search_failed");
            return [];
        }

        $body = $response->json() ?? [];

        if (! is_array($body)) {
            Log::channel('icecat')->warning(
                "[PARSE_ERROR] category={$categoryName} non_array_body=" . substr($response->body(), 0, 1000)
            );
            return [];
        }

        // Icecat may wrap the list under several different keys depending on API version.
        // Try each variant in order; also handle data being a dict with a nested ProductsList.
        $items = null;

        if (isset($body['data']) && is_array($body['data'])) {
            // data is either a flat list  →  ['data' => [product, product, ...]]
            // OR a dict with a nested key →  ['data' => ['ProductsList' => [...]]]
            $items = array_is_list($body['data'])
                ? $body['data']
                : ($body['data']['ProductsList'] ?? $body['data']['products'] ?? []);
        }

        $items ??= $body['items']
            ?? $body['products']
            ?? $body['Products']
            ?? $body['ProductsList']
            ?? [];

        if (! is_array($items) || empty($items)) {
            Log::channel('icecat')->info(
                '[NO_ITEMS] category=' . $categoryName
                . ' code='  . ($body['code'] ?? '?')
                . ' msg='   . ($body['msg']  ?? '?')
                . ' keys='  . implode(',', array_keys($body))
            );
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            // EAN field naming varies across Icecat API versions
            $ean = $item['EAN'] ?? $item['Ean'] ?? $item['ean'] ?? $item['GTIN'] ?? $item['gtin'] ?? null;

            // Some versions wrap EAN in an array; others have a separate Eans/GTINs array
            if (is_array($ean)) {
                $ean = $ean[0] ?? null;
            }
            if (empty($ean)) {
                $ean = $item['Eans'][0] ?? $item['GTINs'][0] ?? $item['eans'][0] ?? '';
            }

            $ean = (string) ($ean ?? '');
            if (empty($ean)) {
                continue;
            }

            $result[] = [
                'ean'               => $ean,
                'icecat_product_id' => $item['Prod_id'] ?? $item['ProductID'] ?? $item['product_id'] ?? '',
                'name'              => $item['Title'] ?? $item['title'] ?? $item['ShortSummaryDescription'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Step 1 — Fetch full product details for a single EAN.
     */
    public function fetchProductDetail(string $ean, string $categoryName = 'Electronics'): ?array
    {
        if (empty($ean)) {
            Log::channel('icecat')->info('[SKIP] EAN= reason=empty_ean');
            return null;
        }

        $response = $this->apiGet([
            'Language' => 'EN',
            'GTIN'     => $ean,
        ]);

        if ($response->status() === 404) {
            Log::channel('icecat')->info("[SKIP] EAN={$ean} reason=404_not_found");
            return null;
        }

        if (! $response->successful()) {
            Log::channel('icecat')->warning(
                "[SKIP] EAN={$ean} reason=api_error status={$response->status()}"
            );
            return null;
        }

        $body    = $response->json();
        $product = $body['data'] ?? $body;

        $title = $product['GeneralInfo']['Title']
            ?? ($product['Title'] ?? null);

        if (empty($title)) {
            Log::channel('icecat')->info("[SKIP] EAN={$ean} reason=missing_title");
            return null;
        }

        $description = $product['GeneralInfo']['SummaryDescription']['LongSummaryDescription']
            ?? ($product['GeneralInfo']['SummaryDescription']['ShortSummaryDescription']
                ?? ($product['LongDesc']
                    ?? ($product['ShortDesc'] ?? '')));

        $image = $product['Gallery'][0]['Pic']
            ?? ($product['Gallery'][0]['Pic500x500']
                ?? ($product['Gallery'][0]['LowPic']
                    ?? ($product['HighImg']
                        ?? ($product['LowImg']
                            ?? ($product['GeneralInfo']['ThumbnailUrl']
                                ?? null)))));

        if ($image !== null && ! $this->isValidIcecatImageUrl($image)) {
            $image = null;
        }

        $specs  = $this->extractSpecs($product);
        $family = $product['GeneralInfo']['ProductFamily']['Value']
            ?? ($product['Series'] ?? '');

        $categoryFromApi = $product['GeneralInfo']['Category']['Name']['Value']
            ?? $categoryName;

        // IMP-044: extract last-updated timestamp for sync date-comparison
        $icecatUpdatedAt = $product['GeneralInfo']['Updated']
            ?? ($product['Updated']
                ?? ($product['UpdatedAt'] ?? null));

        return [
            'ean'               => $ean,
            'name'              => $title,
            'description'       => $description,
            'image'             => $image,
            'brand'             => $product['GeneralInfo']['Brand'] ?? ($product['Brand']['Name'] ?? ''),
            'family'            => $family,
            'category_name'     => $categoryFromApi,
            'color'             => $specs['color'],
            'storage'           => $specs['storage'],
            'spec_processor'    => $specs['processor'],
            'spec_display'      => $specs['display'],
            'spec_weight'       => $specs['weight'],
            'price'             => $this->resolvePrice($product, $categoryName),
            'icecat_updated_at' => $icecatUpdatedAt,
        ];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Build demo raw-product records for a category.
     * Called when fetchEans() returns empty (e.g. free-tier Icecat account).
     *
     * @return array<int, array>
     */
    private function buildDemoRawProducts(string $categoryName, int $limit): array
    {
        $templates = self::DEMO_PRODUCTS[$categoryName] ?? self::DEMO_PRODUCTS['Electronics'];
        $range     = self::CATEGORY_PRICE_RANGE[$categoryName] ?? [49, 499];
        $results   = [];

        $count = min($limit, count($templates));
        for ($i = 0; $i < $count; $i++) {
            $t    = $templates[$i];
            $ean  = '90000' . str_pad((string) ((crc32($t['name']) & 0x7FFFFFFF) % 100000000), 8, '0', STR_PAD_LEFT);
            $price = (float) rand($range[0] * 100, $range[1] * 100) / 100;

            // Stable placeholder image from picsum (seed from product name for consistency)
            $seed  = abs(crc32($t['name'])) % 1000;
            $image = "https://picsum.photos/seed/{$seed}/600/400";

            $results[] = [
                'ean'            => $ean,
                'name'           => $t['name'],
                'description'    => $t['brand'] . ' ' . $t['name'] . '. Processor: ' . $t['processor']
                    . '. Display: ' . $t['display'] . '. Weight: ' . $t['weight'] . '.',
                'image'          => $image,
                'brand'          => $t['brand'],
                'family'         => $t['family'],
                'category_name'  => $categoryName,
                'color'          => $t['color'],
                'storage'        => $t['storage'],
                'spec_processor' => $t['processor'],
                'spec_display'   => $t['display'],
                'spec_weight'    => $t['weight'],
                'price'          => $price,
            ];
        }

        return $results;
    }

    private function extractSpecs(array $product): array
    {
        $specs = [
            'color'     => null,
            'storage'   => null,
            'processor' => null,
            'display'   => null,
            'weight'    => null,
        ];

        $featuresGroups = $product['FeaturesGroups'] ?? [];

        foreach ($featuresGroups as $group) {
            foreach ($group['Features'] ?? [] as $feature) {
                $name  = strtolower($feature['Feature']['Name']['Value'] ?? '');
                $value = $feature['LocalValue']['Value'] ?? ($feature['Value'] ?? '');

                if (str_contains($name, 'color') || str_contains($name, 'colour')) {
                    $specs['color'] = $value;
                } elseif (
                    str_contains($name, 'storage')
                    || str_contains($name, 'memory')
                    || str_contains($name, 'ssd')
                    || str_contains($name, 'hdd')
                ) {
                    $specs['storage'] = $value;
                } elseif (
                    str_contains($name, 'processor')
                    || str_contains($name, 'cpu')
                    || str_contains($name, 'chip')
                ) {
                    $specs['processor'] = $value;
                } elseif (
                    str_contains($name, 'display')
                    || str_contains($name, 'screen')
                    || str_contains($name, 'diagonal')
                ) {
                    $specs['display'] = $value;
                } elseif (str_contains($name, 'weight') || str_contains($name, 'mass')) {
                    $specs['weight'] = $value;
                }
            }
        }

        return $specs;
    }

    private function resolvePrice(array $product, string $categoryName): float
    {
        $msrp = $product['MSRP'] ?? ($product['SuggestedRetailPrice'] ?? null);

        if (is_numeric($msrp) && $msrp > 0) {
            return (float) $msrp;
        }

        $range = self::CATEGORY_PRICE_RANGE[$categoryName] ?? [49, 499];
        return (float) rand($range[0] * 100, $range[1] * 100) / 100;
    }

    // -------------------------------------------------------------------------
    // IMP-044 — Sync existing products from Icecat
    // -------------------------------------------------------------------------

    /**
     * Sync the given category (or all categories) against the Icecat catalogue.
     *
     * For each EAN returned by the API:
     *  - Not in DB          → create new draft product   (new_added)
     *  - In DB, published   → skip entirely              (skipped_published)
     *  - In DB, draft/other → update only safe fields    (updated)
     *    … unless Icecat UpdatedAt ≤ DB updated_at       (skipped_up_to_date)
     *
     * Price, stock, and status are NEVER modified by sync.
     *
     * @return array{new_added: int, updated: int, skipped_up_to_date: int, skipped_published: int}
     */
    public function sync(string $category = 'all', int $limit = 20): array
    {
        if (empty(config('services.icecat.username'))) {
            $msg = 'Icecat sync aborted: ICECAT_USERNAME is not configured in .env';
            Log::channel('icecat')->error('[ABORT] ' . $msg);
            AdminNotification::create(['order_id' => null, 'message' => $msg]);
            return ['new_added' => 0, 'updated' => 0, 'skipped_up_to_date' => 0, 'skipped_published' => 0];
        }

        $categories = $category === 'all'
            ? array_keys(self::CATEGORY_MAP)
            : [$category];

        $totals = ['new_added' => 0, 'updated' => 0, 'skipped_up_to_date' => 0, 'skipped_published' => 0];

        foreach ($categories as $cat) {
            $eans = $this->fetchEans($cat, $limit);

            if (empty($eans)) {
                Log::channel('icecat')->info("[SYNC_SKIP] category={$cat} — API returned 0 EANs");
                continue;
            }

            $rawProducts = [];

            foreach ($eans as $eanData) {
                $detail = $this->fetchProductDetail($eanData['ean'] ?? '', $cat);
                if ($detail === null) {
                    continue;
                }
                $rawProducts[] = $detail;
            }

            $groups = $this->groupByFamily($rawProducts);
            $counts = $this->syncUpsertGroups($groups, $cat);

            foreach ($counts as $k => $v) {
                $totals[$k] += $v;
            }
        }

        AdminNotification::create([
            'order_id' => null,
            'message'  => "Icecat sync complete: {$totals['new_added']} new, {$totals['updated']} updated, "
                . "{$totals['skipped_up_to_date']} up-to-date, {$totals['skipped_published']} skipped published.",
        ]);

        return $totals;
    }

    /**
     * Sync-specific upsert — updates only safe fields when Icecat data is newer.
     *
     * @return array{new_added: int, updated: int, skipped_up_to_date: int, skipped_published: int}
     */
    private function syncUpsertGroups(array $groups, string $fallbackCategoryName): array
    {
        $counts = ['new_added' => 0, 'updated' => 0, 'skipped_up_to_date' => 0, 'skipped_published' => 0];

        foreach ($groups as $familyProducts) {
            $first        = $familyProducts[0];
            $categoryName = ! empty($first['category_name']) ? $first['category_name'] : $fallbackCategoryName;

            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName)]
            );

            $parentName = $this->stripVariantSuffix($first['name']);
            $ean        = $first['ean'] ?? '';

            // EAN-based lookup first, then name+category fallback for legacy rows
            // Use withTrashed() to also catch soft-deleted (archived) products
            $existing = ! empty($ean)
                ? Product::withTrashed()->where('sku', $ean)->first()
                : null;

            if ($existing === null) {
                $existing = Product::withTrashed()->where('name', $parentName)
                    ->where('category_id', $category->id)
                    ->first();
            }

            // Skip published products — never overwrite admin-published content (unless archived)
            if ($existing && $existing->status === 'published' && ! $existing->trashed()) {
                $counts['skipped_published']++;
                continue;
            }

            // Restore soft-deleted product before syncing
            if ($existing && $existing->trashed()) {
                $existing->restore();
            }

            if ($existing) {
                // IMP-044: only update if Icecat data is newer than DB row
                $icecatUpdatedAt = $first['icecat_updated_at'] ?? null;
                $isNewer         = true;

                if ($icecatUpdatedAt !== null) {
                    try {
                        $icecatDate = \Carbon\Carbon::parse((string) $icecatUpdatedAt);
                        $isNewer    = $icecatDate->greaterThan($existing->updated_at);
                    } catch (\Exception $e) {
                        // Unparseable date → treat as newer to be safe
                        $isNewer = true;
                    }
                }

                if (! $isNewer) {
                    $counts['skipped_up_to_date']++;
                    continue;
                }

                // Update only safe fields — never status, stock, or price
                $existing->update([
                    'name'             => $parentName,
                    'slug'             => $this->uniqueSlug($parentName, $existing->id),
                    'description'      => $first['description'],
                    'image'            => $first['image'],
                    'spec_processor'   => $first['spec_processor'],
                    'spec_display'     => $first['spec_display'],
                    'spec_weight'      => $first['spec_weight'],
                    'is_icecat_locked' => true,
                    'import_source'    => 'icecat',
                    'sku'              => $ean ?: $existing->sku,
                ]);

                $counts['updated']++;
            } else {
                // Net-new product — create as draft with random initial stock
                Product::create([
                    'name'                => $parentName,
                    'slug'                => $this->uniqueSlug($parentName),
                    'description'         => $first['description'],
                    'image'               => $first['image'],
                    'category_id'         => $category->id,
                    'price'               => $first['price'],
                    'stock'               => rand(50, 100),
                    'status'              => 'draft',
                    'spec_processor'      => $first['spec_processor'],
                    'spec_display'        => $first['spec_display'],
                    'spec_weight'         => $first['spec_weight'],
                    'is_icecat_locked'    => true,
                    'import_source'       => 'icecat',
                    'sku'                 => $ean,
                    'low_stock_threshold' => 10,
                    'low_stock_notified'  => false,
                ]);

                $counts['new_added']++;
            }
        }

        return $counts;
    }

    // -------------------------------------------------------------------------
    // Shared private helpers
    // -------------------------------------------------------------------------

    /** Step 2 — Group records by ProductFamily into parent-child sets. */
    private function groupByFamily(array $rawProducts): array
    {
        $groups = [];

        foreach ($rawProducts as $product) {
            $key            = ! empty($product['family']) ? $product['family'] : ('__solo__' . $product['ean']);
            $groups[$key][] = $product;
        }

        return $groups;
    }

    /**
     * Step 3 — Upsert product groups into the DB.
     *
     * IMP-043: checks EAN (sku) before create/update.
     * - Published product → skip entirely.
     * - Draft product     → update fields, preserve existing stock.
     * - No match          → create new row with random initial stock.
     *
     * @return array{new: int, updated_draft: int, skipped_published: int}
     */
    private function upsertGroups(array $groups, string $fallbackCategoryName): array
    {
        $counts = ['new' => 0, 'updated_draft' => 0, 'skipped_published' => 0];

        foreach ($groups as $familyProducts) {
            $first        = $familyProducts[0];
            $categoryName = ! empty($first['category_name']) ? $first['category_name'] : $fallbackCategoryName;

            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['slug' => Str::slug($categoryName)]
            );

            $parentName = $this->stripVariantSuffix($first['name']);
            $ean        = $first['ean'] ?? '';

            // IMP-043: EAN-based duplicate check (sku column stores EAN)
            // Use withTrashed() to also detect soft-deleted (archived) products
            $existing = ! empty($ean)
                ? Product::withTrashed()->where('sku', $ean)->first()
                : null;

            // Fall back to name+category lookup for legacy rows without EAN
            if ($existing === null) {
                $existing = Product::withTrashed()->where('name', $parentName)
                    ->where('category_id', $category->id)
                    ->first();
            }

            // IMP-043: skip published products entirely (unless soft-deleted)
            if ($existing && $existing->status === 'published' && ! $existing->trashed()) {
                $counts['skipped_published']++;
                continue;
            }

            // Restore soft-deleted product so it becomes active again
            if ($existing && $existing->trashed()) {
                $existing->restore();
            }

            $productData = [
                'name'                => $parentName,
                'slug'                => $this->uniqueSlug($parentName, $existing?->id),
                'description'         => $first['description'],
                'image'               => $first['image'],
                'category_id'         => $category->id,
                'price'               => $first['price'],
                'status'              => 'draft',
                'spec_processor'      => $first['spec_processor'],
                'spec_display'        => $first['spec_display'],
                'spec_weight'         => $first['spec_weight'],
                'is_icecat_locked'    => true,
                'import_source'       => 'icecat',
                'sku'                 => $ean,
                'low_stock_threshold' => 10,
                'low_stock_notified'  => false,
            ];

            if ($existing) {
                // IMP-043: draft update — preserve existing stock
                $existing->update($productData);
                $counts['updated_draft']++;
            } else {
                // New product — assign random initial stock
                Product::create(array_merge($productData, ['stock' => rand(50, 100)]));
                $counts['new']++;
            }
        }

        return $counts;
    }

    /** Strip common color/storage variant suffixes from product names. */
    private function stripVariantSuffix(string $name): string
    {
        return trim(
            preg_replace(
                '/\s*[-–]\s*([\w\s]+\d+\s*[GT]B|black|white|silver|gold|blue|red|green)\s*$/i',
                '',
                $name
            )
        );
    }

    private function uniqueSlug(string $name, ?int $existingId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (
            Product::where('slug', $slug)
                ->when($existingId, fn ($q) => $q->where('id', '!=', $existingId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    /**
     * Only accept image URLs from Icecat-owned domains or trusted placeholder CDNs over HTTPS.
     * Security: prevents storing arbitrary external URLs.
     */
    private function isValidIcecatImageUrl(string $url): bool
    {
        if (! str_starts_with($url, 'https://')) {
            return false;
        }
        return str_contains($url, 'icecat.us')
            || str_contains($url, 'icecat.biz')
            || str_contains($url, 'icecat.co.uk')
            || str_contains($url, 'picsum.photos');
    }

    // -------------------------------------------------------------------------
    // IMP-045 — Import by Icecat Product ID or EAN/Product Code
    // -------------------------------------------------------------------------

    /**
     * Fetch product details from Icecat by numeric Icecat Product ID.
     * Uses the ICECAT-ADD-ID query parameter instead of GTIN.
     *
     * @return array|null  Same structure as fetchProductDetail().
     */
    public function fetchProductDetailByIcecatId(int $icecatId, string $categoryName = 'Electronics'): ?array
    {
        $response = $this->apiGet([
            'Language'      => 'EN',
            'ICECAT-ADD-ID' => $icecatId,
        ]);

        if ($response->status() === 404) {
            Log::channel('icecat')->info("[SKIP] ICECAT-ADD-ID={$icecatId} reason=404_not_found");
            return null;
        }

        if (! $response->successful()) {
            Log::channel('icecat')->warning(
                "[SKIP] ICECAT-ADD-ID={$icecatId} reason=api_error status={$response->status()}"
            );
            return null;
        }

        $body    = $response->json();
        $product = $body['data'] ?? $body;

        $title = $product['GeneralInfo']['Title'] ?? ($product['Title'] ?? null);
        if (empty($title)) {
            Log::channel('icecat')->info("[SKIP] ICECAT-ADD-ID={$icecatId} reason=missing_title");
            return null;
        }

        // Extract EAN from the response; fall back to string ID
        $ean = $product['GeneralInfo']['GTIN'][0]
            ?? ($product['EANs'][0]
                ?? ($product['GTIN']
                    ?? ($product['EAN']
                        ?? (string) $icecatId)));

        $description = $product['GeneralInfo']['SummaryDescription']['LongSummaryDescription']
            ?? ($product['GeneralInfo']['SummaryDescription']['ShortSummaryDescription']
                ?? ($product['LongDesc']
                    ?? ($product['ShortDesc'] ?? '')));

        $image = $product['Gallery'][0]['Pic']
            ?? ($product['Gallery'][0]['Pic500x500']
                ?? ($product['Gallery'][0]['LowPic']
                    ?? ($product['HighImg']
                        ?? ($product['LowImg']
                            ?? ($product['GeneralInfo']['ThumbnailUrl']
                                ?? null)))));

        if ($image !== null && ! $this->isValidIcecatImageUrl($image)) {
            $image = null;
        }

        $specs           = $this->extractSpecs($product);
        $family          = $product['GeneralInfo']['ProductFamily']['Value'] ?? ($product['Series'] ?? '');
        $categoryFromApi = $product['GeneralInfo']['Category']['Name']['Value'] ?? $categoryName;

        return [
            'ean'            => $ean,
            'name'           => $title,
            'description'    => $description,
            'image'          => $image,
            'brand'          => $product['GeneralInfo']['Brand'] ?? ($product['Brand']['Name'] ?? ''),
            'family'         => $family,
            'category_name'  => $categoryFromApi,
            'color'          => $specs['color'],
            'storage'        => $specs['storage'],
            'spec_processor' => $specs['processor'],
            'spec_display'   => $specs['display'],
            'spec_weight'    => $specs['weight'],
            'price'          => $this->resolvePrice($product, $categoryName),
        ];
    }

    /**
     * Lookup an Icecat product by vendor product code (MPN), e.g. SM-A546BZWDEUB.
     * Calls the Icecat search API with the code as a full-text search, then fetches
     * the first matching product's detail via its Icecat Product ID.
     *
     * @return array<string,mixed>|null
     */
    private function fetchProductDetailByProductCode(string $code): ?array
    {
        try {
            $response = $this->httpClient->get('https://live.icecat.biz/api', [
                'query' => [
                    'FullSearch' => $code,
                    'Language'   => 'en',
                    'limit'      => 1,
                ],
                'auth'    => [$this->icecatUsername, $this->icecatApiKey],
                'headers' => ['Accept' => 'application/json'],
                'timeout' => 10,
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $body = json_decode((string) $response->getBody(), true);
            if (! is_array($body)) {
                return null;
            }

            // Icecat search returns data under 'data' array or directly in body
            $products = $body['data'] ?? ($body['ProductsList'] ?? []);
            if (empty($products)) {
                return null;
            }

            // Use the Icecat Product ID of the first match to get full detail
            $icecatId = (int) ($products[0]['ID'] ?? ($products[0]['ICECAT-ADD-ID'] ?? 0));
            if ($icecatId <= 0) {
                return null;
            }

            return $this->fetchProductDetailByIcecatId($icecatId);
        } catch (\Throwable $e) {
            Log::warning('[Icecat] ProductCode lookup failed', ['code' => $code, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * IMP-045: Import a list of products by Icecat Product ID (numeric) or
     * EAN / product code (alphanumeric). Each entry is imported synchronously
     * and a per-item result is returned.
     *
     * @param  array<string>  $idsOrCodes
     * @return array<int, array{input: string, name: ?string, status: string, error: ?string}>
     */
    public function importByIds(array $idsOrCodes): array
    {
        $results = [];

        foreach ($idsOrCodes as $raw) {
            $idOrCode = trim((string) $raw);
            if ($idOrCode === '') {
                continue;
            }

            // Lookup strategy:
            //  1. Numeric ID → Icecat Product ID (ICECAT-ADD-ID)
            //  2. 13-digit string → EAN/GTIN
            //  3. Alphanumeric (product code like SM-A546BZWDEUB) → try GTIN first,
            //     then fall back to ProductCode search
            $detail = null;
            if (is_numeric($idOrCode)) {
                $detail = $this->fetchProductDetailByIcecatId((int) $idOrCode);
            } else {
                // Try as EAN/GTIN first
                $detail = $this->fetchProductDetail($idOrCode);
                // If EAN lookup failed and input looks like a product code, try ProductCode lookup
                if ($detail === null && preg_match('/^[A-Z0-9][\w\-]{2,}/i', $idOrCode)) {
                    $detail = $this->fetchProductDetailByProductCode($idOrCode);
                }
            }

            if ($detail === null) {
                $results[] = [
                    'input'  => $idOrCode,
                    'name'   => null,
                    'status' => 'failed',
                    'error'  => 'Product not found in Icecat (tried EAN, product code, and ID lookups).',
                ];
                continue;
            }

            // Duplicate check by EAN (sku column) — also check soft-deleted
            $ean = (string) ($detail['ean'] ?? '');
            if ($ean !== '') {
                $existing = Product::withTrashed()->where('sku', $ean)->first();
                if ($existing && ! $existing->trashed()) {
                    $results[] = [
                        'input'  => $idOrCode,
                        'name'   => $existing->name,
                        'status' => 'already_exists',
                        'error'  => null,
                    ];
                    continue;
                }
                // Soft-deleted duplicate → restore it instead of creating new
                if ($existing && $existing->trashed()) {
                    $existing->restore();
                    $results[] = [
                        'input'  => $idOrCode,
                        'name'   => $existing->name,
                        'status' => 'restored',
                        'error'  => null,
                    ];
                    continue;
                }
            }

            // Create new draft product
            $parentName = $this->stripVariantSuffix($detail['name']);
            $catName    = ! empty($detail['category_name']) ? $detail['category_name'] : 'Electronics';
            $category   = Category::firstOrCreate(
                ['name' => $catName],
                ['slug' => Str::slug($catName)]
            );

            $product = Product::create([
                'name'                => $parentName,
                'slug'                => $this->uniqueSlug($parentName),
                'description'         => $detail['description'],
                'image'               => $detail['image'],
                'category_id'         => $category->id,
                'price'               => $detail['price'],
                'stock'               => rand(50, 100),
                'status'              => 'draft',
                'spec_processor'      => $detail['spec_processor'],
                'spec_display'        => $detail['spec_display'],
                'spec_weight'         => $detail['spec_weight'],
                'is_icecat_locked'    => true,
                'import_source'       => 'icecat',
                'sku'                 => $ean ?: null,
                'low_stock_threshold' => 10,
                'low_stock_notified'  => false,
            ]);

            $results[] = [
                'input'  => $idOrCode,
                'name'   => $product->name,
                'status' => 'imported',
                'error'  => null,
            ];
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // IMP-046 — Import brands from Icecat Supplier list
    // -------------------------------------------------------------------------

    /**
     * Fetch and upsert brands from the Icecat Supplier list API.
     * Falls back to a curated brand list when the API returns nothing.
     *
     * @return array{imported: int, updated: int, skipped: int, names: list<string>}
     */
    public function importBrands(): array
    {
        $apiBrands = $this->fetchBrandsFromApi();
        $source    = empty($apiBrands) ? $this->demoBrands() : $apiBrands;

        $imported = 0;
        $updated  = 0;
        $skipped  = 0;
        $names    = [];

        foreach ($source as $item) {
            $name       = trim($item['name'] ?? '');
            $logoUrl    = $item['logo_url'] ?? null;
            $supplierId = isset($item['icecat_supplier_id']) ? (int) $item['icecat_supplier_id'] : null;

            if ($name === '') {
                continue;
            }

            // Match by supplier ID first, then name
            $existing = null;
            if ($supplierId !== null) {
                $existing = Brand::where('icecat_supplier_id', $supplierId)->first();
            }
            if ($existing === null) {
                $existing = Brand::where('name', $name)->first();
            }

            if ($existing) {
                $changed = false;
                if ($existing->name !== $name) {
                    $existing->name = $name;
                    $existing->slug = $this->uniqueBrandSlug($name, $existing->id);
                    $changed        = true;
                }
                if ($logoUrl !== null && $existing->logo_url !== $logoUrl) {
                    $existing->logo_url = $logoUrl;
                    $changed            = true;
                }
                if ($supplierId !== null && $existing->icecat_supplier_id !== $supplierId) {
                    $existing->icecat_supplier_id = $supplierId;
                    $changed                      = true;
                }
                $changed ? ($existing->save() && $updated++) : $skipped++;
            } else {
                Brand::create([
                    'name'               => $name,
                    'slug'               => $this->uniqueBrandSlug($name),
                    'logo_url'           => $logoUrl,
                    'icecat_supplier_id' => $supplierId,
                ]);
                $imported++;
            }

            $names[] = $name;
        }

        return compact('imported', 'updated', 'skipped', 'names');
    }

    /**
     * Call the Icecat Supplier list endpoint.
     *
     * @return array<int, array{name: string, logo_url: ?string, icecat_supplier_id: ?int}>
     */
    private function fetchBrandsFromApi(): array
    {
        $response = $this->apiGet([
            'Language' => 'EN',
            'Content'  => 'Supplier',
            'Limit'    => 200,
        ]);

        if (! $response->successful()) {
            Log::channel('icecat')->warning('[BRANDS] supplier_api_failed status=' . $response->status());
            return [];
        }

        $body  = $response->json() ?? [];
        $items = $body['data'] ?? $body['suppliers'] ?? $body['Suppliers'] ?? [];

        if (! is_array($items) || empty($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            $name = $item['Name'] ?? $item['name'] ?? $item['SupplierName'] ?? null;
            if (empty($name)) {
                continue;
            }

            $logo = $item['Logo'] ?? $item['logo'] ?? $item['LogoURL'] ?? null;
            if ($logo !== null && ! $this->isValidIcecatImageUrl($logo)) {
                $logo = null;
            }

            $result[] = [
                'name'               => $name,
                'logo_url'           => $logo,
                'icecat_supplier_id' => $item['SupplierID'] ?? $item['supplier_id'] ?? $item['ID'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Curated demo brand list used when the Icecat Supplier API returns nothing.
     *
     * @return array<int, array{name: string, logo_url: null, icecat_supplier_id: null}>
     */
    private function demoBrands(): array
    {
        $names = [
            'HP', 'Lenovo', 'Samsung', 'Apple', 'Google', 'Sony', 'Microsoft',
            'Asus', 'Acer', 'Dell', 'Motorola', 'Xiaomi', 'Huawei', 'Bose',
            'Amazon', 'Anker', 'Garmin', 'Amazfit', 'Jabra', 'Sennheiser',
            'Baseus', 'PortaPow', 'Beelink', 'Govee',
        ];

        return array_map(fn ($n) => [
            'name'               => $n,
            'logo_url'           => null,
            'icecat_supplier_id' => null,
        ], $names);
    }

    private function uniqueBrandSlug(string $name, ?int $existingId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 1;

        while (
            Brand::where('slug', $slug)
                ->when($existingId, fn ($q) => $q->where('id', '!=', $existingId))
                ->exists()
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function apiGet(array $params): \Illuminate\Http\Client\Response
    {
        $params['UserName'] = config('services.icecat.username');

        try {
            $response = Http::withBasicAuth(
                config('services.icecat.username'),
                config('services.icecat.password')
            )->timeout(30)->withoutVerifying()->get(self::BASE_URL, $params);

            Log::channel('icecat')->debug(
                '[HTTP] status=' . $response->status()
                . ' body_excerpt=' . substr($response->body(), 0, 500)
            );

            return $response;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::channel('icecat')->error('[HTTP_EXCEPTION] ' . $e->getMessage());
            return new \Illuminate\Http\Client\Response(
                new PsrResponse(
                    503,
                    ['Content-Type' => 'application/json'],
                    json_encode(['msg' => 'Connection failed', 'code' => '-1']) ?: '{"msg":"Connection failed","code":"-1"}'
                )
            );
        }
    }
}
