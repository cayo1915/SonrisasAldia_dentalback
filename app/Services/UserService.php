<?php

namespace App\Services;

use App\Enums\Status;
use App\Http\Resources\UserResource;
use App\Mail\SendVerifyEmail;
use App\Models\User;
use App\Models\RRHH;
use App\Traits\HasResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class UserService
{
    use HasResponse;

    public function getUser($withPagination, $params)
    {
        try {
            $user = User::filters()->active();

            $user = $user->orderBy('id', 'desc');
            $user = !empty($withPagination)
                ? $user->paginate($withPagination['perPage'], page: $withPagination['page'])
                : $user->get();
            $paginationTotal = null;
            if (!empty($withPagination)) {
                $paginationTotal = $user->total();
            }
            $user = UserResource::collection($user->load('role', 'rrhh'));
            return $this->successPaginationResponse('Lista exitosa', $paginationTotal, $user);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getAvailableDoctors($params, $withPagination)
    {
        try {
            $doctor = User::where('idrole', 2)->active();
            #En caso de cualquier otro usuario se separa por sucursales

            if( !isset($params['allDoctors'])) {
                $doctor = $doctor->whereHas('agenda', function ($q) {
                                $q->active(); // scope en Agenda: status = activo
                            });
            }

            $doctor = !empty($withPagination)
                ? $doctor->paginate($withPagination['perPage'], page: $withPagination['page'])
                : $doctor->get();

            $paginationTotal = null;
            if (!empty($withPagination)) {
                $paginationTotal = $doctor->total();
            }

            $doctor = $doctor->load(
                'role',
                'rrhh',
                'agenda.agendaDaysHours.day'
            );
            $doctor = UserResource::collection($doctor);

            return $this->successPaginationResponse('Lista exitosa', $paginationTotal, $doctor);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function getUserById($id, $params)
    {
        try {
            $user = User::activeForID($id)->first();
            return $this->successResponse('Lista exitosa', UserResource::make($user->load('role', 'rrhh')));
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createUser($params)
    {
        DB::beginTransaction();
        try {
            // Verificar si el usuario ya está registrado
            $usuarioExistente = User::activeForDocument($params['n_document'])->first();
            if ($usuarioExistente) {
                return $this->errorResponse('El usuario ya se encuentra registrado', 409);
            }

            // Crear RRHH si no existe
            $rrhh = RRHH::where('n_document', $params['n_document'])
                ->where('status', Status::ACTIVE->value)->first();
            if (!$rrhh) {
                $validator = Validator::make($params, [
                    'email'      => ['required', 'email', 'max:150', 'unique:tbl_rrhh,email'],
                ]);

                if ($validator->fails()) {
                    return $this->errorResponse('El correo electronico ingresado ya fue registrado', 409);
                }

                $rrhh = RRHH::create([
                    'n_document' => $params['n_document'],
                    'name'       => $params['name'],
                    'surname'    => $params['surname'] ?? '',
                    'birth_date' => $params['birth_date'] ?? null,
                    'phone'      => $params['phone'] ?? null,
                    'email'      => $params['email'],
                    'idcharge'   => $params['idcharge'] ?? null,
                ]);
            }

            // Crear usuario
            $password = $params['password'] ?? 'demo';
            $user = User::create([
                'idrrhh'                => $rrhh->id,
                'idrole'                => $params['idrole'],
                'n_document'            => $rrhh->n_document,
                'email'                 => $params['email'],
                'password'              => bcrypt($password),
                'encrypted_password'    => Crypt::encryptString($password),
            ]);

            // Generar URL de verificación
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
            );
            // $verificationUrl = str_replace('http://localhost:8000', config('common.app_front_url'), $verificationUrl);
            Mail::to($user->email)->send(new SendVerifyEmail($verificationUrl));

            DB::commit();
            return $this->successResponse('Usuario creado satisfactoriamente', $user, 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function changeRole($id, $params)
    {
        DB::beginTransaction();
        try {
            // Obtener usuario por ID
            $user = User::findOrFail($id);

            // Validar si ya existe otro usuario con el mismo documento y rol
            $existing = User::where('n_document', $user->n_document)
                ->where('idrole', $params['idrole'])
                ->where('id', '!=', $user->id)
                ->active()
                ->first();

            if ($existing) {
                return $this->errorResponse('Ya existe un usuario con este documento y rol', 409);
            }

            // Actualizar el rol
            $user->idrole = $params['idrole'];
            $user->save();

            DB::commit();
            $user = UserResource::make($user->load('role', 'rrhh'));
            return $this->successResponse('Rol actualizado correctamente', $user);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse('Error al cambiar el rol: ' . $th->getMessage(), 500);
        }
    }

    public function updateUser($id, $params)
    {
        DB::beginTransaction();
        try {
            // Buscar usuario
            $user = User::active()->where('id', $id)->first();
            if (!$user) {
                return $this->errorResponse('El usuario no existe', 404);
            }

            // Verificar si el documento está siendo cambiado y ya existe en otro usuario
            if (isset($params['n_document']) && $params['n_document'] !== $user->n_document) {
                $usuarioExistente = User::activeForDocument($params['n_document'])->where('id', '!=', $id)->first();
                if ($usuarioExistente) {
                    return $this->errorResponse('Ya existe otro usuario con ese documento', 409);
                }
            }

            // Actualizar RRHH si corresponde
            $rrhh = RRHH::activeForId($user->idrrhh)->first();
            if ($rrhh) {
                $rrhh->update([
                    'n_document' => $params['n_document'] ?? $rrhh->n_document,
                    'name'       => $params['name'] ?? $rrhh->name,
                    'surname'    => $params['surname'] ?? $rrhh->surname,
                    'birth_date' => $params['birth_date'] ?? $rrhh->birth_date,
                    'phone'      => $params['phone'] ?? $rrhh->phone,
                    'email'      => $params['email'] ?? $rrhh->email,
                    'idcharge'   => $params['idcharge'] ?? $rrhh->idcharge,
                ]);
            }

            // Actualizar User
            $password = $params['password'] ?? 'demo';
            $user->update([
                'n_document' => $params['n_document'] ?? $user->n_document,
                'email'      => $params['email'] ?? $user->email,
                'idrole'     => $params['idrole'] ?? $user->idrole,
                'password'              => bcrypt($password),
                'encrypted_password'    => Crypt::encryptString($password),
            ]);

            DB::commit();
            return $this->successResponse('Usuario actualizado satisfactoriamente',  UserResource::make($user->load('role', 'rrhh')));
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }


    public function generateUrlVerify($iduser)
    {
        try {
            if (!isset($iduser)) {
                return $this->errorResponse('el identificador de usuario es requerido', 400);
            }
            $user = User::activeForID($iduser)->first();
            if (!isset($user->id)) {
                return $this->errorResponse('El usuario no existe', 400);
            }
            if (isset($user->email_verified_at)) {
                return $this->errorResponse('El usuario  ya se encuentra verificado', 400);
            }

            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify', // Nombre de la ruta
                now()->addMinutes(60), // Expiración del enlace (en minutos)
                ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())] // Parámetros requeridos
            );
            $verificationUrl = str_replace('http://localhost:8000', config('common.app_front_url'), $verificationUrl);

            Mail::to($user->email)->send(new SendVerifyEmail($verificationUrl));

            return $this->successResponse('Verificiación enviada, revise su email', $user);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }

    public function delete($id, $params)
    {
        DB::beginTransaction();
        try {
            if (!isset($id)) {
                return $this->errorResponse('iduser es requerido', 400);
            }
            $user = User::activeForID($id)->first();
            if (!$user) {
                return $this->errorResponse('El usuario no existe', 404);
            }
            $user->markAsDeleted();

            DB::commit();
            return $this->successResponse('Usuario eliminado satisfactoriamente', $user);
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }
    }
}
