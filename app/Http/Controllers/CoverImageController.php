<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Publication;
use Illuminate\Http\Request;

class CoverImageController extends Controller
{
    public function getTemplate(Publication $publication, string $templateName = 'newspaper.nyt')
    {
        return view("covers.$templateName", [
            'publication' => $publication,
            'articles' => $publication->articles,
        ]);
    }
}
