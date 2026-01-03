<?php

namespace App\Dto;

class ArticleChapter
{
    public function __construct(
        public string $title,
        public string $fileName,
        public string $content,
    )
    {
    }
}
