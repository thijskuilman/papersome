<?php

namespace App\Dto;

class ArticleImage
{
    public function __construct(public string $tempPath, public string $fileName) {}
}
