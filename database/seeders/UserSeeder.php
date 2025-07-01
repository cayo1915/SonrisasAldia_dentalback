<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\RRHH;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_PE');

        // Crear administrador general
        $this->createUser('Administrador', 'General', 'admin@dentalsoft.com', 1);

        // Recepcionistas
        $this->createUser('Genesis', 'Recepcion', 'genesis@dentalsoft.com', 4);
        $this->createUser('Sol', 'Recepcion', 'sol@dentalsoft.com', 4);

        // Doctores y doctoras
        $doctores = ['Elizabett', 'Jhanyna', 'Yael', 'Alexis', 'Yennifher', 'Priscila', 'Henry', 'Linda'];
        foreach ($doctores as $nombre) {
            $this->createUser($nombre, 'Médico', strtolower($nombre) . '@dentalsoft.com', 2);
        }

        // Administración (registrados como ADMINISTRADOR)
        $admins = ['Lenin', 'Rodrigo', 'Jose'];
        foreach ($admins as $nombre) {
            $this->createUser($nombre, 'Administrador', strtolower($nombre) . '@dentalsoft.com', 1);
        }

        // Pacientes demo
        for ($i = 1; $i <= 5; $i++) {
            $nombre = $faker->firstName;
            $apellido = $faker->lastName;
            $this->createUser($nombre, $apellido, strtolower($nombre) . $i . '@pacientes.com', 3);
        }
    }

    private function createUser(string $name, string $surname, string $email, int $role)
    {
        $rrhh = RRHH::create([
            'n_document' => fake()->unique()->numerify('7#######'),
            'name' => $name,
            'surname' => $surname,
            'birth_date' => fake()->date('Y-m-d', '1990-01-01'),
            'phone' => '945345789',
            'email' => $email,
            'idcharge' => null,
        ]);

        User::create([
            'idrrhh' => $rrhh->id,
            'idrole' => $role,
            'n_document' => $rrhh->n_document,
            'email' => $email,
            'password' => Hash::make('demo'),
            'encrypted_password' => Crypt::encryptString('demo'),
        ]);
    }
}
