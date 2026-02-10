<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class OpdsService
{
    public function buildUserPublicationsFeed(User $user, Collection $publications): string
    {
        $updatedAt = $publications->max('updated_at') ?? Carbon::now();

        $selfUrl = Route::has('opds.user')
            ? route('opds.user', ['user' => $user])
            : url()->current();

        $xml = [];
        $xml[] = '<?xml version="1.0" encoding="utf-8"?>';
        $xml[] = '<feed xmlns="http://www.w3.org/2005/Atom" xmlns:opds="http://opds-spec.org/2010/catalog">';
        $xml[] = '  <id>tag:papersome:user:'.htmlspecialchars((string) $user->id, ENT_XML1).'</id>';
        $xml[] = '  <title>'.htmlspecialchars($user->name.' Publications', ENT_XML1).'</title>';
        $xml[] = '  <updated>'.Carbon::parse($updatedAt)->toAtomString().'</updated>';
        $xml[] = '  <link rel="self" href="'.htmlspecialchars($selfUrl, ENT_XML1).'" type="application/atom+xml;profile=opds-catalog;kind=acquisition" />';
        $xml[] = '  <link rel="start" href="'.htmlspecialchars($selfUrl, ENT_XML1).'" type="application/atom+xml;profile=opds-catalog;kind=navigation" />';

        foreach ($publications as $publication) {
            $entryId = $publication->tag ?: (string) $publication->getKey();
            $title = $publication->title ?: 'Publication';
            $updated = Carbon::parse($publication->updated_at ?? $publication->created_at ?? Carbon::now())->toAtomString();
            $href = Storage::disk('public')->url($publication->epub_file_path);

            if ($href === null) {
                continue;
            }

            $xml[] = '  <entry>';
            $xml[] = '    <id>urn:uuid:'.htmlspecialchars((string) $entryId, ENT_XML1).'</id>';
            $xml[] = '    <title>'.htmlspecialchars((string) $title, ENT_XML1).'</title>';
            $xml[] = '    <updated>'.$updated.'</updated>';
            $coverUrl = $publication->cover_image ? Storage::disk('public')->url($publication->cover_image) : null;
            if ($coverUrl !== null) {
                $xml[] = '    <link rel="http://opds-spec.org/image" type="'.$this->getImageMimeType($coverUrl).'" href="'.htmlspecialchars($coverUrl, ENT_XML1).'" />';
            }
            $xml[] = '    <link rel="http://opds-spec.org/acquisition" type="application/epub+zip" href="'.htmlspecialchars($href, ENT_XML1).'" />';
            $xml[] = '  </entry>';
        }

        $xml[] = '</feed>';

        return implode("\n", $xml);
    }

    private function getImageMimeType(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'image/*',
        };
    }
}
