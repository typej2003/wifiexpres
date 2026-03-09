<?php

namespace App\Imports;
use App\Models\User;
use App\Models\DatosBasicos;
use App\Models\Client;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ImportUser implements ToModel, WithStartRow
{
    /**
     * EXCEL COLUMNS FORMAT
     * NAME | EMAIL
     * @param array $row
     */
    public function model(array $row)
    {
        $password = Str::random(8);

        $userExists = User::whereEmail($row[1])->first();

        if ( ! $userExists) {
            $user = User::create(
                [
                    'name'     => $row[0],
                    'email'    => $row[1],
                    'password' => bcrypt($password),
                ]
            );

            DatosBasicos::create([
                'user_id' => $user->id,
                'cellphonecode' => $row[2],
                'cellphone' => $row[3],
            ]);
    
            Client::create([
                'user_id' => $user->id,
                'comercio_id' => $this->comercio_id,
            ]);

            
            return $user;
        }
    }

    public function startRow(): int {
        return 2;
    }
}