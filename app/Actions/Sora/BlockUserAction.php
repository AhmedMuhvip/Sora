<?php

declare(strict_types=1);

namespace App\Actions\Sora;

use App\Models\Block;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BlockUserAction
{
    public function execute(User $blocker, User $blocked): Block
    {
        if ($blocker->is($blocked)) {
            throw ValidationException::withMessages([
                'user' => ['CANNOT_BLOCK_SELF'],
            ]);
        }

        return DB::transaction(function () use ($blocker, $blocked): Block {
            Follow::query()
                ->where(fn ($query) => $query->where('follower_id', $blocker->id)->where('following_id', $blocked->id))
                ->orWhere(fn ($query) => $query->where('follower_id', $blocked->id)->where('following_id', $blocker->id))
                ->delete();

            return Block::query()->firstOrCreate([
                'blocker_id' => $blocker->id,
                'blocked_id' => $blocked->id,
            ]);
        });
    }
}
