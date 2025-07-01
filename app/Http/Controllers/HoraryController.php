<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\HoraryService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;

class HoraryController extends Controller
{
    use HasResponse;
    /** @var  HoraryService*/
    private $horaryService;

    public function __construct(HoraryService $horaryService)
    {
        $this->middleware('auth:api', ['except' => ['availableTimes']]);
        $this->horaryService = $horaryService;
    }

    public function availableTimes(Request $request)
    {
        return $this->horaryService->availableTimes($request->all());
    }
}
