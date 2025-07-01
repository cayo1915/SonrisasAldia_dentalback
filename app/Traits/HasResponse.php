<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

trait HasResponse
{
    /**
     * Default structure to prepare any json response
     *
     * @param string $message
     * @param int $code
     * @return array
     */
    public function defaultStructure($code = JsonResponse::HTTP_OK, $message = 'OK', $data = null, $bool, $detail2 = null)
    {
        return [

            'timestamp' => Carbon::now()->toDateTimeString(),
            'code' => $code,
            'status' => $bool,
            'data'  => $this->returnMessage($message, $data, $detail2)
        ];
    }

    /**
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function defaultResponse($message = 'OK', $code = JsonResponse::HTTP_NO_CONTENT)
    {
        $structure = $this->defaultStructure($code, $message, null, null);

        return response()->json($structure, $code);
    }

    /**
     * @param $data
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($message = 'OK', $data = null, $code = JsonResponse::HTTP_OK,  $detail2 = null)
    {
        // $code = JsonResponse::HTTP_OK;
        // if (request()->isMethod('post')) {
        //     $code = JsonResponse::HTTP_CREATED;
        // }
        $structure = $this->defaultStructure($code, $message, $data, true, $detail2);
        // $structure['data'] = $data;

        return response()->json($structure, $code);
    }

    /**
     * @param $errors
     * @param string $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse($message, $code, $errors = null, $detail2 = null)
    {
        $errorsIsArray = is_array($errors);
        $errors = !$errorsIsArray || ($errorsIsArray && count($errors) > 0) ? $errors : null;
        $structure = $this->defaultStructure($code, $message, $errors, false, $detail2);

        return response()->json($structure, $code);
    }

    /**
     * @param bool $bool
     * @param string $message
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnMessage($message, $data = null, $detail2 = null)
    {
        if (is_null($data)) {
            return [
                'message' => $message,
            ];
        }
        if (!is_null($detail2)) {
            return  [
                'message' => $message,
                'detail' => $data,
                'detail2' => $detail2
            ];
        }
        return  [
            'message' => $message,
            'detail' => $data
        ];
    }

    /**
     * @param $paginate
     * @return \Illuminate\Http\JsonResponse
     */
    public function validatePagination($paginate)
    {
        if (empty($paginate)) {
            return [];
        }
        if (!isset($paginate['perPage']) || !isset($paginate['page'])) {
            return [];
        }
        return $paginate;
    }

    # Estructura de paginación
    public function successPaginationResponse($message = 'OK', $total, $data)
    {
        $code = JsonResponse::HTTP_OK;
        $structure = $this->paginationStructure($code, $message, $total, $data);

        return response()->json($structure, $code);
    }

    # Estructura para paginación
    private function paginationStructure($code, $message, $total, $data)
    {
        return [
            'timestamp' => Carbon::now()->toDateTimeString(),
            'code' => $code,
            'status' => true,
            'data'  => $this->returnMessagePagination($message, $data, $total)
        ];
    }

    # Detalle para paginación
    public function returnMessagePagination($message, $data = null, $total = null)
    {
        $return = ['message' => $message];

        if (isset($data)) $return['detail'] = $data;

        if (isset($total)) $return['total'] = $total;

        return $return;
    }

    # Estructura para mensaje de error en operaciones try - catch
    public function externalError($message, $errors = null)
    {
        $structure = $this->defaultStructure(500, 'Ocurrió un problema ' . $message, $errors, false);

        return response()->json($structure, 500);
    }
}
