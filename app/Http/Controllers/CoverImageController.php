<?php

namespace App\Http\Controllers;

use App\Models\Publication;

class CoverImageController extends Controller
{
    public function getTemplate(Publication $publication)
    {
        return view('covers.'.$publication->collection->cover_template->getView(), [
            'publication' => $publication,
            'articles' => $publication->articles,
        ]);
    }
}
