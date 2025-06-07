<?php

use App\Models\Answer;
use App\Models\Question;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $questions = [];

    public bool $confirmingQuestionDeletion = false;
    public bool $confirmingAnswerDeletion = false;
    public int $questionIdToDelete;
    public int $answerIdToDelete;

    public function mount(): void
    {
        $this->questions = Question::with('answers')->limit(10);
    }

    public function confirmDeleteQuestion($questionId): void
    {
        $this->questionIdToDelete = $questionId;
        $this->confirmingQuestionDeletion = true;
    }

    public function deleteQuestion(): void
    {
        Question::find($this->questionIdToDelete)->delete();
        $this->confirmingQuestionDeletion = false;
    }

    public function confirmDeleteAnswer($answerId): void
    {
        $this->answerIdToDelete = $answerId;
        $this->confirmingAnswerDeletion = true;
    }

    public function deleteAnswer(): void
    {
        Answer::find($this->answerIdToDelete)->delete();
        $this->confirmingAnswerDeletion = false;
    }
}; ?>
<section class="w-full">
    <!-- Заголовок и кнопка добавления -->
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-medium">Управление Trip Vibe Bot</h1>
        <button
            wire:click="$dispatch('openModal', { component: 'bots.trip-vibe-bot.create-question' })"
            class="inline-flex items-center gap-2 rounded-md bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d32a03] dark:bg-[#FF4433] dark:hover:bg-[#e53929]"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Добавить вопрос
        </button>
    </div>

    <!-- Список вопросов -->
    <div
        class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#161615]">
        <!-- Пустое состояние -->
        @if($questions->isEmpty())
            <div class="flex h-full flex-col items-center justify-center text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-[#706f6c] dark:text-[#A1A09A]" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                <h3 class="mt-2 text-lg font-medium">Нет вопросов</h3>
                <p class="mt-1 text-[#706f6c] dark:text-[#A1A09A]">Начните с добавления первого вопроса для вашего
                    бота</p>
                <button
                    wire:click="$dispatch('openModal', { component: 'bots.trip-vibe-bot.create-question' })"
                    class="mt-4 inline-flex items-center gap-2 rounded-md bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d32a03] dark:bg-[#FF4433] dark:hover:bg-[#e53929]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Добавить вопрос
                </button>
            </div>
        @else
            <div class="space-y-6">
                @foreach($questions as $question)
                    <div
                        class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-[#1e1e1d]">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium">{{ $question->text }}</h3>
                                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">ID: {{ $question->id }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    wire:click="$dispatch('openModal', { component: 'bots.trip-vibe-bot.edit-question', arguments: { question: {{ $question->id }} } })"
                                    class="rounded-md p-2 text-[#706f6c] hover:bg-neutral-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-neutral-800 dark:hover:text-[#eeeeec]"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                    </svg>
                                </button>
                                <button
                                    wire:click="confirmDeleteQuestion({{ $question->id }})"
                                    class="rounded-md p-2 text-[#706f6c] hover:bg-red-100 hover:text-red-600 dark:text-[#A1A09A] dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                         stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Ответы на вопрос -->
                        <div class="mt-4 space-y-3">
                            @foreach($question->answers as $answer)
                                <div
                                    class="rounded-md border border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-700 dark:bg-[#252525]">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-medium">{{ $answer->text }}</p>
                                            <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                Реакция: <span class="font-medium">{{ $answer->reaction }}</span>
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button
                                                wire:click="$dispatch('openModal', { component: 'bots.trip-vibe-bot.edit-answer', arguments: { answer: {{ $answer->id }} } })"
                                                class="rounded-md p-1 text-[#706f6c] hover:bg-neutral-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-neutral-800 dark:hover:text-[#eeeeec]"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                                </svg>
                                            </button>
                                            <button
                                                wire:click="confirmDeleteAnswer({{ $answer->id }})"
                                                class="rounded-md p-1 text-[#706f6c] hover:bg-red-100 hover:text-red-600 dark:text-[#A1A09A] dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                          d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Кнопка добавления ответа -->
                            <button
                                wire:click="$dispatch('openModal', { component: 'bots.trip-vibe-bot.create-answer', arguments: { question: {{ $question->id }} } })"
                                class="mt-2 flex items-center gap-1 text-sm text-[#706f6c] hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:text-[#eeeeec]"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                     stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 4v16m8-8H4"/>
                                </svg>
                                Добавить ответ
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

