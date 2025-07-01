<?php

namespace App\Http\Controllers;

use App\Services\DetailMenuItemService;
use App\Traits\HasResponse;
use Illuminate\Http\Request;

class DetailMenuItemController extends Controller
{
    use HasResponse;
    /** @var  DetailMenuItemService*/
    private $detailMenuItemService;

    public function __construct(DetailMenuItemService $detailMenuItemService)
    {
        $this->detailMenuItemService = $detailMenuItemService;
    }

    public function getDetailViewUserLogin(Request $request)
    {
        return $this->detailMenuItemService->getDetailViewUserLogin($request);
    }

}
