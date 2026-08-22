<?php

declare(strict_types=1);

namespace App\Http\Resources\Sora;

use App\Models\Bookmark;
use App\Models\Thread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Bookmark $bookmark */
        $bookmark = $this->resource;
        $thread = null;

        if ($bookmark->bookmarkable instanceof Thread) {
            $thread = $bookmark->bookmarkable;
            $thread->setAttribute('is_bookmarked_by_viewer', true);
        }

        return [
            'id' => $bookmark->id,
            'thread' => $thread ? new ThreadCardResource($thread) : null,
            'created_at' => $bookmark->created_at?->toISOString(),
        ];
    }
}
