<?php
/**
 * Interactive map — blocks & PHCs
 * Client data loaded per-block from map/data/{slug}.php
 * Jammu has no client pack yet (placeholder remains).
 */
declare(strict_types=1);

$jfPhc = static function (
    string $slug,
    string $name,
    string $query,
    string $mo,
    string $phone,
    string $blockLabel
): array {
    return [
        'slug' => $slug,
        'name' => $name,
        'hero' => null,
        'maps_url' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($query),
        'incharge' => [
            'name' => $mo,
            'title' => 'Medical Officer In-charge',
            'phone' => $phone,
            'photo' => null,
        ],
        'gallery' => [],
        'infrastructure' => "{$name} has an OPD block, a 6-bed observation ward, labour room, immunization room, and a medicine store. Building is single-storey with ramps at the main entrance. Power backup via 10 KVA generator. Water supply from a dedicated borewell with overhead tank (5,000 L).",
        'equipments' => "BP apparatus, glucometer, nebulizer, oxygen cylinder (2), autoclave, weighing scale, vaccine carrier, delivery kit, and basic laboratory microscope. ECG machine shared with the block hospital on call.",
        'roadmap' => "Next year: upgrade labour room, add a new observation bay, install solar backup, and start weekly specialist outreach (gynecology / pediatrics) from the block hospital.",
        'staff' => [
            ['name' => $mo, 'role' => 'Medical Officer In-charge', 'phone' => $phone],
            ['name' => 'Staff Nurse (ANM)', 'role' => 'Nursing', 'phone' => '0191-2500101'],
            ['name' => 'Pharmacist', 'role' => 'Pharmacy', 'phone' => '0191-2500102'],
            ['name' => 'MPHW (F)', 'role' => 'Field worker', 'phone' => '0191-2500103'],
        ],
        'details' => "{$name} is a Primary Health Centre under Block {$blockLabel}. It provides OPD, maternal & child health, immunization, and basic emergency care. Catchment covers nearby panchayats. Timings: 9:00 AM – 4:00 PM (Mon–Sat). Emergency referral to the Block hospital.",
    ];
};

return [
    'pallanwala' => require __DIR__ . DIRECTORY_SEPARATOR . 'pallanwala.php',
    'chowki-choura' => require __DIR__ . DIRECTORY_SEPARATOR . 'chowki-choura.php',
    'akhnoor' => require __DIR__ . DIRECTORY_SEPARATOR . 'akhnoor.php',
    'kot-bhalwal' => require __DIR__ . DIRECTORY_SEPARATOR . 'kot-bhalwal.php',
    'dansal' => require __DIR__ . DIRECTORY_SEPARATOR . 'dansal.php',
    'marh' => require __DIR__ . DIRECTORY_SEPARATOR . 'marh.php',
    'jammu' => [
        'name' => 'Jammu',
        'marker' => ['x' => 70.5, 'y' => 53.5],
        'hero' => null,
        'maps_url' => 'https://www.google.com/maps/search/?api=1&query=Jammu+City+Health',
        'bmo' => [
            'name' => 'Dr. Manoj Gupta',
            'title' => 'Block Medical Officer',
            'phone' => '9419101701',
            'photo' => null,
        ],
        'gallery' => [],
        'infrastructure' => "Urban Block Jammu: network of PHCs/UPHCs with a 40-bed referral unit. High footfall OPD, NCD clinic, and immunization sessions six days a week. Located with easy city access.",
        'equipments' => "Digital X-ray, 3 ambulances, 10 oxygen concentrators, ECG, ultrasound (daily), fully equipped labour room, and NCD screening lab.",
        'roadmap' => "New UPHC in the southern ward, e-Sanjeevani kiosks, and expansion of the evening clinic.",
        'staff' => [
            ['name' => 'Dr. Manoj Gupta', 'role' => 'Block Medical Officer', 'phone' => '9419101701'],
            ['name' => 'Dr. Shalini Raina', 'role' => 'Medical Officer', 'phone' => '9419101702'],
            ['name' => 'Dr. Imtiaz Ahmed', 'role' => 'Medical Officer', 'phone' => '9419101703'],
            ['name' => 'Smt. Neelam Kumari', 'role' => 'Nursing Sister', 'phone' => '9419101704'],
        ],
        'phcs' => [
            $jfPhc('phc-jammu-city', 'PHC Jammu City', 'PHC Jammu', 'Dr. Reena Kotwal', '9419101711', 'Jammu'),
            $jfPhc('phc-gandhi-nagar', 'UPHC Gandhi Nagar', 'UPHC Gandhi Nagar Jammu', 'Dr. Sahil Verma', '9419101712', 'Jammu'),
        ],
    ],
    'sohanjana' => require __DIR__ . DIRECTORY_SEPARATOR . 'sohanjana.php',
    'rs-pura' => require __DIR__ . DIRECTORY_SEPARATOR . 'rs-pura.php',
    'bishnah' => require __DIR__ . DIRECTORY_SEPARATOR . 'bishnah.php',
];
