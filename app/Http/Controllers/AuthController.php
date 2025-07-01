<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Mail\ResendCredentialsMail;
use App\Models\DetailUserNotifications;
use App\Models\User;
use App\Services\DetailMenuItemService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use HasResponse;
    /** @var  DetailMenuItemService*/
    private $detailMenuItemService;

    public function __construct(DetailMenuItemService $detailMenuItemService)
    {
        // $this->middleware('auth:api', ['except' => ['login',  'resendCredentials', 'validateToken', 'refresh', '']]);
        $this->detailMenuItemService = $detailMenuItemService;
    }

    public function login(Request $request)
    {
        try {
            // ✅ Validar que el campo 'n_document' y 'password' estén presentes y sean del tipo correcto
            $validator = Validator::make($request->all(), [
                'n_document' => 'required|string|max:15',
                'password'   => 'required|string',
            ]);

            // Si la validación falla, devuelve una respuesta con los errores y código 400 (Bad Request)
            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 402);
            }

            // ✅ Extrae solo los campos necesarios del request (por seguridad)
            $credentials = $request->only('n_document', 'password');

            // ⚠️ Intenta autenticar al usuario usando los datos ingresados
            $token = Auth::attempt($credentials);

            // Si las credenciales son incorrectas, devolver 401 (Unauthorized)
            if (!$token) {
                return $this->errorResponse('Credenciales inválidas', 401);
            }

            // 🔐 Obtiene el usuario autenticado (gracias al token generado por Auth::attempt)
            $user = Auth::user();

            // Usuario inactivo: devolver 403 (Forbidden)
            if ($user->status !== Status::ACTIVE->value) {
                return $this->errorResponse('Usuario inactivo', 403); // Devuelve error si el usuario está inactivo
            }

            // ✅ Todo correcto: retorna respuesta de éxito con el token de autenticación incluido
            return $this->successResponse('OK', $this->respondWithToken($token));
        } catch (\Throwable $th) {
            Log::info($th);
            // Devuelve una respuesta genérica de error interno del servidor
            return $this->errorResponse('Error interno del servidor', 500);
        }
    }



    public function me(Request $request)
    {
        try {
            $user = Auth::user();
            $user = User::activeForID(Auth::id())->first()->load('rrhh', 'role');
            $notifications = DetailUserNotifications::where('idreceiver', $user->id)
                ->notificationSend()->active()->count();

            if (!isset($user->id)) {
                return $this->errorResponse('Credenciales inválidas', 401);
            }
            $user->countN = $notifications;
            $user->idrole = $user->idrole;
            $user->role = $user->role->name;
            $user->data = $user->rrhh;
            $user->data->photo = isset($user->rrhh->photo) ? config('common.app_back_url') . '/' . $user->rrhh->photo : '/assets/img/profiles/avatar-19.jpg';
            $view = $this->detailMenuItemService->getDetailViewUserLogin();
            $view2 = $this->detailMenuItemService->getDetailTrat($user);
            $user->views = $view->original['data']['detail'];
            $user->modules = $view2->original['data']['detail'];

            return $this->successResponse('OK', $user);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    protected function respondWithToken($token)
    {
        try {
            return [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 20000,
            ];
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function logout()
    {
        try {
            Auth::logout();
            return $this->successResponse('Successfully logged out');
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function refresh()
    {
        try {
            return response()->json([
                'status' => true,
                'user' => Auth::user(),
                'authorisation' => [
                    'token' => Auth::refresh(),
                    'type' => 'bearer',
                ]
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function resendMailCode(Request $request)
    {
        try {
            $user = User::activeForDocument($request['n_document'])->first();

            if (!$user) {
                return $this->errorResponse('Usuario inválido', 403);
            }

            // Generar código aleatorio
            $code = random_int(100000, 999999);

            // Guardar código temporalmente (p. ej., por 15 minutos)
            Cache::put("password_reset_code_{$user->n_document}", $code, now()->addMinutes(1));

            // Enviar código por correo
            Mail::to($user->email)->send(new ResendCredentialsMail($code));

            return $this->successResponse('Código enviado al correo');
        } catch (\Exception $e) {
            return $this->errorResponse('Error: ' . $e->getMessage(), 500);
        }
    }


    public function resetCredentialsMandatory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'n_document' => 'required|exists:tbl_user,n_document',
            'confirmPassword' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            $user = User::activeForDocument($request->n_document)->first();
            $user->password = bcrypt($request->confirmPassword);
            $user->encrypted_password = Crypt::encryptString($request->confirmPassword);
            $user->save();

            DB::commit();
            return $this->successResponse('Contraseña actualizada exitosamente', $user->fresh());
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->errorResponse("Error al actualizar la contraseña" . $th, 500);
        }
    }


    public function resetCredentials(Request $request)
    {
        DB::beginTransaction();
        try {

            $password = User::activeForID($request['id'])->first()->encrypted_password;
            $pass = Crypt::decryptString($password);
            if ($pass == $request['password']) {
                $new_pass = $request['confirmPassword'];
                if ($new_pass != $request['password']) {
                    $user = User::updateOrCreate(['id' => $request['id']], [
                        'password' => bcrypt($request['confirmPassword']),
                        'encrypted_password' => Crypt::encryptString($request['confirmPassword']),
                    ]);
                    $user->fresh();
                    DB::commit();
                    return $this->successResponse('Contraseña actualizada exitosamente');
                }
                return $this->errorResponse("La contraseña anterior y la contraseña nueva son las mismas", 422);
            }
            return $this->errorResponse("La contraseña no es correcta", 403);
        } catch (\Throwable $th) {
            Log::error($th);
            DB::rollback();
            return $this->errorResponse('Error interno del servidor', 500);
        }
    }

    public function validateToken()
    {
        try {
            try {
                $user = JWTAuth::parseToken()->authenticate();
                if ($user) {
                    return 'true';
                }
                return 'false';
            } catch (JWTException $e) {
                return 'false';
            }
        } catch (\Throwable $th) {
        }
    }

    public function updateExpoPushToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token_epn' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $authUser = JWTAuth::user();
        if (!$authUser) {
            return $this->errorResponse('Usuario no logueado', 401);
        }

        // Sólo actualizamos si es diferente
        if ($authUser->token_epn !== $request->input('token_epn')) {
            DB::beginTransaction();
            try {
                User::where('id', $authUser->id)->update(['token_epn' => $request->input('token_epn')]);
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        }

        return $this->successResponse('Token guardado y/o actualizado con éxito');
    }

    public function setPushNotificationStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status_push' => 'required|string|in:active,inactive', // Puedes validar valores esperados
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        $authUser = JWTAuth::user();
        if (!$authUser) {
            return $this->errorResponse('Usuario no logueado', 401);
        }

        DB::beginTransaction();
        try {
            User::where('id', $authUser->id)->update(['status_notification_push' => $request->input('status_push')]);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

        return $this->successResponse('Estado de notificación actualizado con éxito');
    }
}
