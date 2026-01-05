<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Publication;
use Illuminate\Http\Request;

class CoverImageController extends Controller
{
    public function getTemplate(Publication $publication, string $templateName = 'newspaper.nyt')
    {
        // TODO: templateName should be stored in either publication or collection
        return view("covers.$templateName", [
            'publication' => $publication,
            'articles' => $publication->articles,
        ]);
    }
}
