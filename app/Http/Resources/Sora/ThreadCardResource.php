<?php

declare(strict_types=1);

namespace App\Http\Resources\Sora;

use App\Models\Thread;
use App\Models\ThreadMessage;
use Illuminate\Http\Request;

class ThreadCardResource extends ThreadResource
{
    public function toArray(Request $request): array
    {
        /** @var Thread $thread */
        $thread = $this->resource;
        $data = parent::toArray($request);
        $firstMessage = $this->firstMessage($thread);
        $data['messages'] = $firstMessage !== null
            ? [new ThreadMessageResource($firstMessage, $thread)]
            : [];
        $data['reply_count'] = max(0, (int) ($thread->messages_count ?? $thread->messages()->count()) - 1);

        return $data;
    }

    private function firstMessage(Thread $thread): ?ThreadMessage
    {
        if ($thread->relationLoaded('firstMessage')) {
            return $thread->firstMessage;
        }

        if ($thread->relationLoaded('messages')) {
            return $thread->messages->first();
        }

        return null;
    }
}
