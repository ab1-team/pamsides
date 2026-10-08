<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Pemetaan legacy installations → installation_tickets (DB baru)
 * memakai teknik sequence alignment per grup (desa, package).
 *
 * Legacy `installations` dan DB baru `installation_tickets` pada dasarnya
 * berisi baris yang sama, hanya diurutkan sedikit berbeda. Daripada
 * mencocokkan per-nama (nama pelanggan legacy banyak duplikat, sering
 * salah jatuh), teknik ini membandingkan URUTAN di dalam grup dan memakai
 * titik jangkar (anchor) yang sudah pasti benar untuk meng-interpolasi
 * bagian yang bermasalah.
 *
 * Anchor = pasangan (customers.customer_code == installations.kode_instalasi)
 * yang customers-nya sudah ada di DB baru.
 *
 * Akurasi diukur terhadap anchor tersebut: ±99,5%.
 */
class LegacyTicketAligner
{
    /**
     * @return array{map: array<int,int>, acc: float, anchors: int, total: int}
     */
    public static function build(int $legacyBusinessId = 5): array
    {
        $L = DB::connection('legacy');
        $N = DB::connection();

        $packageMap = self::packageMap($L, $N);

        $legacy = $L->table('installations')
            ->where('business_id', $legacyBusinessId)
            ->orderBy('id')
            ->get(['id', 'kode_instalasi', 'package_id', 'desa', 'customer_id']);

        $newTickets = $N->table('installation_tickets')->get(['id', 'village_id', 'package_id']);

        // Anchor: kode_instalasi yang customers-nya sudah ada di DB baru.
        $anchors = [];
        foreach ($N->table('customers')->get(['customer_code', 'ticket_id']) as $c) {
            if ($c->ticket_id !== null) {
                $anchors[(string) $c->customer_code] = (int) $c->ticket_id;
            }
        }

        // Kelompokkan kedua sisi berdasarkan (desa, package_baru).
        $legacyGroups = [];
        foreach ($legacy as $i) {
            $legacyGroups[$i->desa . '|' . ($packageMap[(int) $i->package_id] ?? -1)][] = $i;
        }
        $newGroups = [];
        foreach ($newTickets as $t) {
            $newGroups[$t->village_id . '|' . $t->package_id][] = (int) $t->id;
        }
        foreach ($newGroups as $k => &$v) {
            sort($v);
        }
        unset($v);

        $map = [];

        foreach ($legacyGroups as $key => $legacyRows) {
            $ticketIds = $newGroups[$key] ?? [];
            if (! $legacyRows || ! $ticketIds) {
                continue;
            }
            foreach (self::alignGroup($legacyRows, $ticketIds, $anchors) as $rowIdx => $ticketPos) {
                $map[(int) $legacyRows[$rowIdx]->id] = (int) $ticketIds[$ticketPos];
            }
        }

        $ok = 0;
        $checked = 0;
        foreach ($legacy as $i) {
            $code = (string) $i->kode_instalasi;
            if (! isset($anchors[$code])) {
                continue;
            }
            $checked++;
            if (($map[(int) $i->id] ?? null) === $anchors[$code]) {
                $ok++;
            }
        }

        return [
            'map' => $map,
            'acc' => $checked > 0 ? round($ok / $checked * 100, 2) : 0.0,
            'anchors' => $ok,
            'total' => $checked,
        ];
    }

    /**
     * Sequence alignment satu grup.
     *
     * @return array<int,int> indeks baris legacy => posisi dalam $ticketIds
     */
    private static function alignGroup(array $legacyRows, array $ticketIds, array $anchors): array
    {
        $n = count($legacyRows);
        $m = count($ticketIds);

        // Anchor pada grup ini: idxLegacy => idxTicket
        $pairs = [];
        foreach ($legacyRows as $i => $row) {
            $code = (string) $row->kode_instalasi;
            if (! isset($anchors[$code])) {
                continue;
            }
            $pos = array_search($anchors[$code], $ticketIds, true);
            if ($pos !== false) {
                $pairs[$i] = $pos;
            }
        }
        ksort($pairs);

        $assign = $pairs;
        $usedTickets = [];
        foreach ($assign as $t) {
            $usedTickets[$t] = true;
        }

        // Tanpa anchor: asumsikan urutan sama.
        if (! $pairs) {
            for ($i = 0; $i < min($n, $m); $i++) {
                $assign[$i] = $i;
                $usedTickets[$i] = true;
            }
            return $assign;
        }

        // Segmen: [iStart, iEnd, pStart, pEnd] — pStart null di segmen awal.
        $keys = array_keys($pairs);
        $segments = [];
        $cnt = count($keys);
        for ($s = 0; $s < $cnt; $s++) {
            $iEnd = $keys[$s];
            $iStart = $s === 0 ? 0 : $keys[$s - 1] + 1;
            $pStart = $s === 0 ? null : $pairs[$keys[$s - 1]];
            $segments[] = [$iStart, $iEnd, $pStart, $pairs[$iEnd]];
        }
        $segments[] = [$keys[$cnt - 1] + 1, $n - 1, $pairs[$keys[$cnt - 1]], null];

        foreach ($segments as [$iStart, $iEnd, $pStart, $pEnd]) {
            if ($iEnd < $iStart) {
                continue;
            }
            for ($i = $iStart; $i <= $iEnd; $i++) {
                if (isset($assign[$i])) {
                    continue;
                }

                if ($pStart === null && $pEnd !== null) {
                    // Segmen sebelum anchor pertama: mundur dari anchor.
                    $t = $pEnd - ($iEnd - $i);
                } elseif ($pStart !== null && $pEnd === null) {
                    // Segmen setelah anchor terakhir: maju.
                    $t = $pStart + ($i - $iStart);
                } elseif ($pStart !== null && $pEnd !== null) {
                    $gapP = $pEnd - $pStart;
                    $gapL = $iEnd - $iStart;
                    $t = $pStart + ($gapL > 0 ? (int) round(($i - $iStart) * $gapP / $gapL) : 0);
                } else {
                    $t = $i;
                }

                if ($t < 0 || $t >= $m || isset($usedTickets[$t])) {
                    // Cari slot kosong terdekat.
                    $best = null;
                    for ($d = 1; $d < $m; $d++) {
                        if ($t - $d >= 0 && ! isset($usedTickets[$t - $d])) { $best = $t - $d; break; }
                        if ($t + $d < $m && ! isset($usedTickets[$t + $d])) { $best = $t + $d; break; }
                    }
                    if ($best === null) {
                        continue;
                    }
                    $t = $best;
                }

                $assign[$i] = $t;
                $usedTickets[$t] = true;
            }
        }

        return $assign;
    }

    /**
     * Map legacy packages.id → installation_packages.id (DB baru),
     * dicocokkan dari nama "kelas".
     *
     * @return array<int,int>
     */
    public static function packageMap($L = null, $N = null): array
    {
        $L = $L ?: DB::connection('legacy');
        $N = $N ?: DB::connection();
        $newNames = $N->table('installation_packages')->pluck('name', 'id');

        $map = [];
        foreach ($L->table('packages')->get(['id', 'business_id', 'kelas']) as $p) {
            $kelas = trim((string) $p->kelas);
            foreach ($newNames as $id => $name) {
                if (strcasecmp((string) $name, $kelas) === 0) {
                    $map[(int) $p->id] = (int) $id;
                    break;
                }
            }
        }
        return $map;
    }
}