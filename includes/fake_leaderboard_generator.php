<?php
/**
 * Fake Leaderboard Generator
 * ──────────────────────────
 * Generates deterministic fake leaderboard entries to pad the leaderboard
 * during bootstrap phase (not enough real Step-2 members yet).
 *
 * - Fake users live ONLY in PHP memory during request — never written to DB.
 * - Negative leadid guarantees no collision with real (positive) PK values.
 * - Seed (crc32 of month + id) → consistent numbers within a month,
 *   rotates on month change.
 * - Output shape matches leaderboard.php SQL columns exactly.
 */

if (!function_exists('getLbSetting')) {
    function getLbSetting($link, $key) {
        static $cache = [];
        if (isset($cache[$key])) return $cache[$key];
        $k = mysqli_real_escape_string($link, $key);
        $r = @mysqli_fetch_assoc(@mysqli_query($link,
            "SELECT setting_value FROM settings WHERE setting_key = '$k' LIMIT 1"));
        return $cache[$key] = ($r ? $r['setting_value'] : '');
    }
}

// Name pool — generic international first names + last-name initials.
// COMPLIANCE: fully generic, no brand/company references.
const FAKE_LEADERBOARD_NAMES = [
    'Michael K.','Sarah M.','Thomas W.','Emma L.','David R.','Anna H.','Jonas B.','Lisa F.','Daniel S.','Laura G.',
    'Markus P.','Julia N.','Stefan D.','Nina T.','Christian V.','Sophie O.','Andreas Z.','Katharina A.','Matthias E.','Hannah I.',
    'Florian U.','Lena C.','Sebastian Y.','Marie J.','Alexander Q.','Clara X.','Philipp W.','Mia B.','Tobias L.','Lara R.',
    'Benjamin H.','Elena S.','Maximilian K.','Sofia P.','Julian M.','Isabelle T.','Simon D.','Leonie V.','Nico F.','Amelie G.',
    'Fabian N.','Charlotte E.','Moritz Z.','Jana A.','Lukas I.','Mila O.','Felix Q.','Ella U.','Oscar J.','Greta Y.',
    'Noah C.','Frida X.','Liam H.','Ida W.','Elias B.','Romy L.','Paul R.','Mara S.','Jakob M.','Leni P.',
    'Emil D.','Ronja V.','Theo K.','Helena T.','Mats F.','Zoe N.','Anton G.','Juna E.','Carl A.','Rosa Z.',
    'Henry I.','Lia O.','Victor U.','Nele Q.','Milo J.','Alma Y.','Oskar C.','Malina X.','Ben H.','Thea W.',
    'Leo B.','Fiona L.','Rafael R.','Linnea S.','Raphael M.','Johanna P.','Valentin D.','Inga V.','Gabriel K.','Svenja T.',
    'Enzo F.','Mara N.','Vincent G.','Carolin E.','Arthur A.','Svea Z.','Theodor I.','Leah O.','Louis U.','Enya Q.',
    'Johann J.','Maja Y.','Konstantin C.','Luise X.','Niklas H.','Paula W.','Julius B.','Merle L.','Maurice R.','Pia S.',
    'Fynn M.','Lotta P.','Milan D.','Felicia V.','Jaron K.','Linda T.','Aaron F.','Marlene N.','Kilian G.','Annika E.',
    'Ludwig A.','Henriette Z.','Frederik I.','Solveig O.','Aron U.','Josefine Q.','Magnus J.','Melina Y.','Kasper C.','Amira X.',
    'Silas H.','Aylin W.','Tim B.','Alina L.','Lion R.','Hanna S.','Finn M.','Lilly P.','Hendrik D.','Milena V.',
    'Jannis K.','Luna T.','Erik F.','Stella N.','Aaron G.','Chiara E.','Adrian A.','Nora Z.','Till I.','Mira O.',
    'Matteo U.','Antonia Q.','Bastian J.','Tessa Y.','Mika C.','Luisa X.','Toni H.','Amelie W.','Kenan B.','Naomi L.',
    'Jon R.','Laila S.','Iver M.','Livia P.','Merlin D.','Celina V.','Taj K.','Hedi T.','Tarek F.','Ayla N.',
    'Bilal G.','Sila E.','Omar A.','Deniz Z.','Emir I.','Yasmin O.','Enis U.','Leyla Q.','Kerem J.','Melek Y.',
    'Mert C.','Arya X.','Yusuf H.','Nisa W.','Baran B.','Aisha L.','Kaya R.','Rania S.','Demir M.','Lamia P.',
    'Rayan D.','Samira V.','Milo K.','Lina T.','Hugo F.','Mathilda N.','Bruno G.','Elli E.','Frederick A.','Birte Z.',
    'Joris I.','Svenja O.','Nilo U.','Feli Q.','Ronan J.','Jette Y.','Damian C.','Smilla X.','Quentin H.','Runa W.',
    'Yannick B.','Eileen L.','Lennard R.','Irma S.','Corvin M.','Tilda P.','Jarne D.','Pauline V.','Mads K.','Rosalie T.',
    'Malte F.','Theresa N.','Taavi G.','Gudrun E.','Eren A.','Seraphina Z.','Ilias I.','Svetlana O.','Rasmus U.','Galina Q.',
    'Ivan J.','Natalia Y.','Sergei C.','Irina X.','Alexei H.','Tatiana W.','Dmitri B.','Anastasia L.','Mikhail R.','Olga S.',
    'Viktor M.','Ekaterina P.','Pavel D.','Valentina V.','Yuri K.','Svetlana T.','Boris F.','Vera N.','Nikolai G.','Ludmilla E.',
    'Mateusz A.','Aleksandra Z.','Bartosz I.','Zofia O.','Krzysztof U.','Agnieszka Q.','Wojciech J.','Malgorzata Y.','Grzegorz C.','Beata X.',
    'Marek H.','Ewa W.','Piotr B.','Katarzyna L.','Jakub R.','Monika S.','Adam M.','Halina P.','Dawid D.','Urszula V.',
    'Luca K.','Giulia T.','Giorgio F.','Francesca N.','Leonardo G.','Sofia E.','Matteo A.','Martina Z.','Nicolo I.','Valentina O.',
    'Alessandro U.','Alessia Q.','Marco J.','Giada Y.','Federico C.','Sara X.','Riccardo H.','Elena W.','Andrea B.','Beatrice L.',
    'Carlos R.','Isabella S.','Diego M.','Lucia P.','Javier D.','Carmen V.','Miguel K.','Elena T.','Pablo F.','Maria N.',
    'Rafael G.','Ines E.','Tomas A.','Beatriz Z.','Sergio I.','Pilar O.','Antonio U.','Rosario Q.','Vicente J.','Angeles Y.',
    'Jean C.','Chloe X.','Etienne H.','Camille W.','Lucas B.','Manon L.','Hugo R.','Zoe S.','Arthur M.','Lea P.',
    'Louis D.','Jade V.','Gabriel K.','Lou T.','Nathan F.','Agathe N.','Raphael G.','Juliette E.','Antoine A.','Romane Z.',
    'Theo I.','Anais O.','Martin U.','Pauline Q.','Adam J.','Louise Y.','Eliott C.','Inès X.','Enzo H.','Alice W.',
    'Sam B.','Olivia L.','Oliver R.','Amelia S.','Jack M.','Grace P.','Harry D.','Isla V.','George K.','Poppy T.',
    'William F.','Freya N.','James G.','Daisy E.','Charlie A.','Ivy Z.','Ethan I.','Ava O.','Alfie U.','Florence Q.',
    'Archie J.','Willow Y.','Henry C.','Evie X.','Theo H.','Rosie W.','Arthur B.','Sophia L.','Joshua R.','Lily S.',
    'Elijah M.','Harper P.','Mason D.','Mia V.','Logan K.','Madison T.','Aiden F.','Scarlett N.','Caleb G.','Aurora E.',
    'Ryan A.','Penelope Z.','Nathan I.','Layla O.','Samuel U.','Riley Q.','Isaac J.','Zoey Y.','Connor C.','Nora X.',
    'Owen H.','Hazel W.','Dylan B.','Violet L.','Chase R.','Aurora S.','Landon M.','Savannah P.','Carter D.','Hannah V.',
    'Elliot K.','Brooklyn T.','Easton F.','Leah N.','Levi G.','Audrey E.','Grayson A.','Natalia Z.','Nolan I.','Quinn O.',
    'Hudson U.','Ariana Q.','Kai J.','Bella Y.','Axel C.','Skylar X.','Miles H.','Paisley W.','Beckett B.','Autumn L.',
    'Rowan R.','Hailey S.','Silas M.','Cora P.','Callan D.','Everly V.','Declan K.','Alaia T.','Finnegan F.','Sage N.',
    'Casper G.','Ember E.','Tobias A.','Wren Z.','Atlas I.','Juniper O.','Reid U.','Briar Q.','Bodhi J.','Meadow Y.',
    'Jasper C.','Raven X.','Soren H.','Blair W.','Zephyr B.','Dune L.','Onyx R.','Lark S.','Rune M.','Vale P.',
    'Sven D.','Astrid V.','Bjorn K.','Saga T.','Odin F.','Freya N.','Thor G.','Runa E.','Magnus A.','Siri Z.',
    'Viggo I.','Maja O.','Kasper U.','Liv Q.','Soren J.','Agnes Y.','Elias C.','Hedda X.','Olof H.','Hanna W.',
    'Niko B.','Aino L.','Mikael R.','Hilda S.','Eero M.','Aino P.','Juho D.','Aava V.','Onni K.','Lumi T.',
    'Ari F.','Ella N.','Aapo G.','Vilja E.','Valtteri A.','Helmi Z.','Veikko I.','Iida O.','Tapio U.','Lilja Q.',
    'Sander J.','Nora Y.','Mikkel C.','Laerke X.','Oliver H.','Freja W.','Malte B.','Emma L.','Noah R.','Clara S.',
];

/**
 * Generate fake leaderboard rows.
 *
 * @param string $month       'YYYY-MM' — seed prefix for deterministic randomness per month
 * @param string $mode        'monthly' or 'alltime'
 * @param int    $neededCount how many fakes to generate (0 = none)
 * @return array<int, array<string, int|string>>
 */
function getFakeLeaderboardData(string $month, string $mode, int $neededCount): array {
    if ($neededCount <= 0) return [];
    $total = min($neededCount, count(FAKE_LEADERBOARD_NAMES));
    $result = [];

    for ($i = 1; $i <= $total; $i++) {
        $fakeId = -$i;
        mt_srand(crc32($month . '_' . $fakeId));

        // Realistic long-tail distribution
        if ($i <= 20) {
            $totalLeads = mt_rand(50, 200);
            $step2      = mt_rand(15, min(50, $totalLeads));
        } elseif ($i <= 100) {
            $totalLeads = mt_rand(10, 50);
            $step2      = mt_rand(2, min(15, $totalLeads));
        } else {
            $totalLeads = mt_rand(1, 15);
            $step2      = mt_rand(0, min(5, $totalLeads));
        }

        $row = [
            'leadid'        => $fakeId,
            'display_name'  => FAKE_LEADERBOARD_NAMES[$i - 1],
            'total_leads'   => $totalLeads,
            'step2_members' => $step2,
        ];

        if ($mode === 'monthly') {
            $row['month_leads']   = (int)round($totalLeads * (mt_rand(10, 40) / 100));
            $row['month_members'] = (int)round($step2      * (mt_rand(10, 40) / 100));
        }
        $result[] = $row;
    }
    mt_srand(); // reset for any subsequent mt_rand usage
    return $result;
}
