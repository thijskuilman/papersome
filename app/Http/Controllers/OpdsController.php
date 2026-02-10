<?php

namespace App\Http\Controllers;

use App\Models\Publication;
use App\Models\User;
use App\Services\OpdsService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;

class OpdsController
{
    public function __construct(private readonly OpdsService $opds) {}

    public function userPublications(User $user): Response
    {
        $publications = Publication::query()
            ->whereHas('collection', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotNull('epub_file_path')
            ->latest('updated_at')
            ->with(['collection.user'])
            ->take(20)
            ->get();

        $xml = $this->opds->buildUserPublicationsFeed($user, $publications);

        /** @var ResponseFactory $response */
        $response = response();

        return $response->make($xml, 200, [
            'Content-Type' => 'application/atom+xml;profile=opds-catalog;kind=acquisition; charset=UTF-8',
        ]);
    }
}
