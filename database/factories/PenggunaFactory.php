<?php
namespace Database\Factories;
use App\Models\Pengguna; use Illuminate\Database\Eloquent\Factories\Factory; use Illuminate\Support\Facades\Hash; use Illuminate\Support\Str;
class PenggunaFactory extends Factory {
    protected $model=Pengguna::class; protected static ?string $kataSandi;
    public function definition(): array { return ['nama'=>fake()->name(),'email'=>fake()->unique()->safeEmail(),'no_hp'=>fake()->numerify('08##########'),'alamat'=>fake()->address(),'kata_sandi'=>static::$kataSandi??=Hash::make('password'),'peran'=>'pembeli','status'=>'aktif','remember_token'=>Str::random(10)]; }
}
