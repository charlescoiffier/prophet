<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ThemeResource;
use App\Models\Theme;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('Personnalités', weight: 3)]
class ThemeController extends Controller
{
    /**
     * Lister les thèmes
     *
     * @unauthenticated
     */
    public function index(): AnonymousResourceCollection
    {
        return ThemeResource::collection(
            Theme::withCount(['cards' => fn ($query) => $query->visible()])->orderBy('name')->get()
        );
    }
}
