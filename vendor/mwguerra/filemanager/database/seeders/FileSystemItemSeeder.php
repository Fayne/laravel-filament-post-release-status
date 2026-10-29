<?php

namespace MWGuerra\FileManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use MWGuerra\FileManager\Enums\FileSystemItemType;
use MWGuerra\FileManager\Enums\FileType;

class FileSystemItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modelClass = config('filemanager.model');

        DB::transaction(function () use ($modelClass) {
            // Clear existing data
            $modelClass::truncate();

            // Create Projects folder
            $projects = $modelClass::create([
                'name' => 'Projects',
                'type' => FileSystemItemType::Folder->value,
                'parent_id' => null,
            ]);

            // Create demo video in Projects
            $modelClass::create([
                'name' => 'demo-video.mp4',
                'type' => FileSystemItemType::File->value,
                'file_type' => FileType::Video->value,
                'parent_id' => $projects->id,
                'size' => 15728640,
                'duration' => 120,
                'thumbnail' => '/images/video-thumbnail.png',
            ]);

            // Create demo image in Projects
            $modelClass::create([
                'name' => 'screenshot.png',
                'type' => FileSystemItemType::File->value,
                'file_type' => FileType::Image->value,
                'parent_id' => $projects->id,
                'size' => 524288,
            ]);

            // Create Tutorials folder
            $tutorials = $modelClass::create([
                'name' => 'Tutorials',
                'type' => FileSystemItemType::Folder->value,
                'parent_id' => null,
            ]);

            // Create intro video in Tutorials
            $modelClass::create([
                'name' => 'intro.mp4',
                'type' => FileSystemItemType::File->value,
                'file_type' => FileType::Video->value,
                'parent_id' => $tutorials->id,
                'size' => 8388608,
                'duration' => 60,
                'thumbnail' => '/images/tutorial-video.png',
            ]);

            // Create document in Tutorials
            $modelClass::create([
                'name' => 'tutorial-guide.pdf',
                'type' => FileSystemItemType::File->value,
                'file_type' => FileType::Document->value,
                'parent_id' => $tutorials->id,
                'size' => 1048576,
            ]);

            // Create presentation video at root
            $modelClass::create([
                'name' => 'presentation.mp4',
                'type' => FileSystemItemType::File->value,
                'file_type' => FileType::Video->value,
                'parent_id' => null,
                'size' => 52428800,
                'duration' => 300,
                'thumbnail' => '/images/presentation-video.png',
            ]);

            // Create audio file at root
            $modelClass::create([
                'name' => 'background-music.mp3',
                'type' => FileSystemItemType::File->value,
                'file_type' => FileType::Audio->value,
                'parent_id' => null,
                'size' => 5242880,
                'duration' => 180,
            ]);
        });
    }
}
