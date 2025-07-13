<?php

namespace App\Console\Commands;

use App\Models\Question;
use Illuminate\Console\Command;
use Telegram\Bot\FileUpload\InputFile;
use Telegram\Bot\Laravel\Facades\Telegram;

class UploadQuestionsGifs extends Command
{
    protected $signature = 'gifs:upload';
    protected $description = 'Upload all questions GIFs to Telegram and save file_ids';

    public function handle(): void
    {
        $questions = Question::whereNull('telegram_file_id')->get();

        if ($questions->isEmpty()) {
            $this->info('All GIFs already uploaded!');
            return;
        }

        $this->info("Uploading {$questions->count()} GIFs...");

        foreach ($questions as $question) {
            try {
                $filePath = public_path("gifs/{$question->id}.gif");

                if (!file_exists($filePath)) {
                    $this->error("GIF not found for question {$question->id}");
                    continue;
                }

                $response = Telegram::sendAnimation([
                    'chat_id' => config('telegram.admin_chat_id'),
                    'animation' => InputFile::create("https://mdk-bots.ru/gifs/{$question->id}.MP4"),
                    'caption' => "GIF for question {$question->id}"
                ]);

                $question->update(['telegram_file_id' => $response->animation->fileId]);
                $this->info("Uploaded question {$question->id}");
            } catch (\Exception $e) {
                $this->error("Error uploading question {$question->id}: " . $e->getMessage());
            }
        }

        $this->info('Done!');
    }
}