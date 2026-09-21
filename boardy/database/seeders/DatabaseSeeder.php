<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@boardy.local',
            'password' => bcrypt('password')
        ]);

        $otherUsers = User::factory()
            ->count(4)
            ->create();

        //Собрал 5 пользователей вместе
        $allUsers = $otherUsers->push($testUser);

        //10 случайных постов привязанные к созданым пользователям ($allUsers)
        $posts = Post::factory()
            ->count(10)
            ->recycle($allUsers)
            ->create();

        //25 случайных комментариев ривязанные к созданым пользователям ($allUsers) и постам (
        Comment::factory()
            ->count(25)
            ->recycle($allUsers)
            ->recycle($posts)
            ->create();
    }
}
