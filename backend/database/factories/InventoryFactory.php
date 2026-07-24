<?php

namespace Database\Factories;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'nama_barang' => 'Test Item '.Str::random(4),
            'tgl_beli' => now()->subMonths(rand(1, 36))->toDateString(),
            'unit' => 1,
            'harsat' => 1_000_000,
            'umur_ekonomis' => 48,
            'jenis' => '1',
            'kategori' => '1',
            'status' => 'Baik',
            'tgl_validasi' => null,
        ];
    }

    public function tanah(): static
    {
        return $this->state(fn () => [
            'nama_barang' => 'Tanah '.Str::random(3),
            'kategori' => '1',
            'jenis' => '1',
            'harsat' => 500_000_000,
            'unit' => 1,
            'umur_ekonomis' => 0,
        ]);
    }

    public function gedung(): static
    {
        return $this->state(fn () => [
            'nama_barang' => 'Gedung '.Str::random(3),
            'kategori' => '2',
            'jenis' => '1',
            'harsat' => 1_200_000_000,
            'unit' => 1,
            'umur_ekonomis' => 240,
        ]);
    }

    public function kendaraan(): static
    {
        return $this->state(fn () => [
            'nama_barang' => 'Kendaraan '.Str::random(3),
            'kategori' => '3',
            'jenis' => '1',
            'harsat' => 180_000_000,
            'unit' => 1,
            'umur_ekonomis' => 96,
        ]);
    }

    public function peralatan(): static
    {
        return $this->state(fn () => [
            'nama_barang' => 'Peralatan '.Str::random(3),
            'kategori' => '4',
            'jenis' => '1',
            'harsat' => 8_500_000,
            'unit' => 1,
            'umur_ekonomis' => 48,
        ]);
    }

    public function rusak(): static
    {
        return $this->state(fn () => [
            'status' => 'Rusak',
            'tgl_validasi' => now()->toDateString(),
        ]);
    }

    public function hilang(): static
    {
        return $this->state(fn () => [
            'status' => 'Hilang',
            'tgl_validasi' => now()->toDateString(),
        ]);
    }

    public function dijual(): static
    {
        return $this->state(fn () => [
            'status' => 'Dijual',
            'tgl_validasi' => now()->toDateString(),
        ]);
    }
}
