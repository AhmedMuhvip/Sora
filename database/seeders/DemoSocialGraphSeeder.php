<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSocialGraphSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['testuser', 'sora'],
            ['testuser', 'sara'],
            ['testuser', 'layla'],
            ['ahmed', 'sara'],
            ['ahmed', 'layla'],
            ['layla', 'sara'],
            ['omar', 'sora'],
            ['sara', 'sora'],
            ['nina', 'sora'],
            ['nina', 'sara'],
            ['nina', 'testuser'],
            ['yusuf', 'testuser'],
            ['yusuf', 'sara'],
            ['mariam', 'testuser'],
            ['mariam', 'layla'],
            ['testuser', 'nina'],
            ['testuser', 'yusuf'],
            ['sora', 'sara'],
            ['sora', 'layla'],
            ['sara', 'layla'],
            ['omar', 'layla'],
        ] as [$follower, $following]) {
            $this->seedFollow($this->user($follower), $this->user($following));
        }

        $this->seedBlock($this->user('testuser'), $this->user('blocked_user'));
        $this->seedBlock($this->user('blocked_user'), $this->user('mariam'));
    }

    private function user(string $username): User
    {
        return User::query()->where('username', $username)->firstOrFail();
    }

    private function seedFollow(User $follower, User $following): Follow
    {
        return Follow::query()->firstOrCreate(Follow::factory()->raw([
            'follower_id' => $follower->id,
            'following_id' => $following->id,
        ]));
    }

    private function seedBlock(User $blocker, User $blocked): Block
    {
        Follow::query()
            ->where(fn ($query) => $query->where('follower_id', $blocker->id)->where('following_id', $blocked->id))
            ->orWhere(fn ($query) => $query->where('follower_id', $blocked->id)->where('following_id', $blocker->id))
            ->delete();

        return Block::query()->firstOrCreate(Block::factory()->raw([
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
        ]));
    }
}
