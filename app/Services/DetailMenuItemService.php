<?php

namespace App\Services;

use App\Models\MenuItems;
use App\Models\SubMenuItems;
use App\Traits\HasResponse;
use Illuminate\Support\Facades\Auth;

class DetailMenuItemService
{
    use HasResponse;
    public function getDetailViewUserLogin()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return $this->errorResponse('usuario invalido', 401);
            }
            $submenu = SubMenuItems::where('link', '!=', NULL)->active()->get();
            $detailViewRole = [];
            foreach ($submenu as $row) {
                $idsrole = json_decode($row->idsrole);
                if (in_array($user->idrole, $idsrole)) {
                    array_push($detailViewRole, $row);
                }
            }
            // $detailViewRole = DetailViewRoleResource::collection($detailViewRole);
            return $this->successResponse('Lectura exitosa', $detailViewRole);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function getDetailTrat($user)
    {
        try {
            $menuItems = MenuItems::with([
                'detailMenuItems.subMenuItems',   // Primer nivel de submenús
                'detailMenuItems.subMenuItems2',  // Segundo nivel de submenús
                'detailMenuItems.subMenuItems3'   // Tercer nivel de submenús
            ])->get();

            $arraySubMenuItems = [];
            $userRoleId = $user->idrole;  // El id del role del usuario actual
            foreach ($menuItems as $menu) {
                $idsroleSubMenu = json_decode($menu->idsrole, true);
                // Verificar si el rol actual del usuario está permitido en el submenú
                if (in_array($userRoleId, $idsroleSubMenu)) {

                    $menuData = [
                        'label' => $menu->label,
                        'submenuOpen' => $menu->submenuOpen,
                        'showSubRoute' => $menu->showSubRoute,
                        'submenuHdr' => $menu->submenuHdr,
                        'submenuItems' => []
                    ];

                    foreach ($menu->detailMenuItems as $detail) {
                        // Validar que el submenú esté activo (status == 1) y que el rol esté permitido
                        if (!is_null($detail->subMenuItems) && $detail->subMenuItems->status == 1) {
                            // Decodificar el array de roles del submenú
                            $idsroleSubMenu = json_decode($detail->subMenuItems->idsrole, true);

                            // Verificar si el rol actual del usuario está permitido en el submenú
                            if (in_array($userRoleId, $idsroleSubMenu)) {
                                // Verificar si el submenú ya existe usando el id
                                $existingSubMenuIndex = array_search($detail->subMenuItems->id ?? null, array_column($menuData['submenuItems'], 'subMenuId'));

                                if ($existingSubMenuIndex === false) {
                                    // Si el submenú no existe, lo agregamos
                                    $subMenuData = [
                                        'subMenuId' => $detail->subMenuItems->id ?? null,
                                        'label' => $detail->subMenuItems->label ?? 'No Submenu',
                                        'icon'  => $detail->subMenuItems->icon,
                                        'link' => $detail->subMenuItems->link,
                                        'submenu'  => $detail->subMenuItems->submenu,
                                        'showSubRoute'  => $detail->subMenuItems->showSubRoute,
                                        'submenuItems' => []
                                    ];

                                    $menuData['submenuItems'][] = $subMenuData;
                                    $existingSubMenuIndex = count($menuData['submenuItems']) - 1;
                                }

                                // Agregar subMenu2 y subMenu3 si existen, están activos y el rol es permitido
                                if (!is_null($detail->id_submenu_items2) && $detail->subMenuItems2->status == 1) {
                                    // Decodificar el array de roles de subMenu2
                                    $idsroleSubMenu2 = json_decode($detail->subMenuItems2->idsrole, true);

                                    // Verificar si el rol actual del usuario está permitido en subMenu2
                                    if (in_array(
                                        $userRoleId,
                                        $idsroleSubMenu2
                                    )) {
                                        $existingSubMenu2Index = array_search($detail->subMenuItems2->id ?? null, array_column($menuData['submenuItems'][$existingSubMenuIndex]['submenuItems'], 'subMenu2Id'));

                                        if ($existingSubMenu2Index === false) {
                                            // Si subMenu2 no existe, lo agregamos
                                            $subMenu2Data = [
                                                'subMenu2Id' => $detail->subMenuItems2->id ?? null,
                                                'label' => $detail->subMenuItems2->label ?? 'No Submenu2',
                                                'icon'  => $detail->subMenuItems2->icon,
                                                'link' => $detail->subMenuItems2->link,
                                                'submenu'  => $detail->subMenuItems2->submenu,
                                                'showSubRoute'  => $detail->subMenuItems2->showSubRoute,
                                                'submenuItems' => []
                                            ];

                                            // Agregar subMenu3 si existe, está activo y el rol es permitido
                                            if (
                                                !is_null($detail->id_submenu_items3) && $detail->subMenuItems3->status == 1
                                            ) {
                                                // Decodificar el array de roles de subMenu3
                                                $idsroleSubMenu3 = json_decode($detail->subMenuItems3->idsrole, true);

                                                // Verificar si el rol actual del usuario está permitido en subMenu3
                                                if (in_array($userRoleId, $idsroleSubMenu3)) {
                                                    $subMenu2Data['submenuItems'][] = [
                                                        'subMenu3Id' => $detail->subMenuItems3->id ?? null,
                                                        'label' => $detail->subMenuItems3->label ?? 'No Submenu3',
                                                        'icon'  => $detail->subMenuItems3->icon,
                                                        'link' => $detail->subMenuItems3->link,
                                                        'submenu'  => $detail->subMenuItems3->submenu,
                                                        'showSubRoute'  => $detail->subMenuItems3->showSubRoute,
                                                    ];
                                                }
                                            }

                                            $menuData['submenuItems'][$existingSubMenuIndex]['submenuItems'][] = $subMenu2Data;
                                        } else {
                                            // Si subMenu2 ya existe, verificar y agregar subMenu3 si está activo y el rol es permitido
                                            if (!is_null($detail->id_submenu_items3) && $detail->subMenuItems3->status == 1) {
                                                // Decodificar el array de roles de subMenu3
                                                $idsroleSubMenu3 = json_decode($detail->subMenuItems3->idsrole, true);

                                                // Verificar si el rol actual del usuario está permitido en subMenu3
                                                if (in_array($userRoleId, $idsroleSubMenu3)) {
                                                    $existingSubMenu3Index = array_search($detail->subMenuItems3->id ?? null, array_column($menuData['submenuItems'][$existingSubMenuIndex]['submenuItems'][$existingSubMenu2Index]['submenuItems'], 'subMenu3Id'));

                                                    if ($existingSubMenu3Index === false) {
                                                        $menuData['submenuItems'][$existingSubMenuIndex]['submenuItems'][$existingSubMenu2Index]['submenuItems'][] = [
                                                            'subMenu3Id' => $detail->subMenuItems3->id ?? null,
                                                            'label' => $detail->subMenuItems3->label ?? 'No Submenu3',
                                                            'link' => $detail->subMenuItems3->link,
                                                        ];
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $arraySubMenuItems[] = $menuData;
                }
            }


            return $this->successResponse('Lectura exitosa', $arraySubMenuItems);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
