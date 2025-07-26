<?php

use App\Models\Question;
use App\Models\Answer;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public $questions = [];
    public $confirmingQuestionDeletion = false;
    public $confirmingAnswerDeletion = false;
    public $questionIdToDelete;
    public $answerIdToDelete;

    public function mount()
    {
        $this->questions = Question::all();
    }

    /**
     * Подтверждение удаления вопроса
     */
    public function confirmDeleteQuestion($questionId): void
    {
        $this->questionIdToDelete = $questionId;
        $this->confirmingQuestionDeletion = true;
    }

    /**
     * Удаление вопроса
     */
    public function deleteQuestion(): void
    {
        Question::find($this->questionIdToDelete)->delete();
        $this->confirmingQuestionDeletion = false;
        $this->resetPage();
    }

    /**
     * Подтверждение удаления ответа
     */
    public function confirmDeleteAnswer($answerId): void
    {
        $this->answerIdToDelete = $answerId;
        $this->confirmingAnswerDeletion = true;
    }

    /**
     * Удаление ответа
     */
    public function deleteAnswer(): void
    {
        Answer::find($this->answerIdToDelete)->delete();
        $this->confirmingAnswerDeletion = false;
    }
}; ?>

<div>
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <!-- Заголовок и кнопка добавления -->
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-medium">Управление Ozon Travel Vibe Bot</h1>
            <button
                    wire:click="$dispatch('openModal', { component: 'bots.questions.create-question' })"
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
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#161615]">
            <div class="space-y-6">
                @foreach($questions as $question)
                    <div class="rounded-lg border border-neutral-200 bg-white p-4 shadow-sm dark:border-neutral-700 dark:bg-[#1e1e1d]">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-medium">{{ $question->text }}</h3>
                                <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">ID: {{ $question->id }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                        wire:click="$dispatch('openModal', { component: 'bots.questions.edit-question', arguments: { question: {{ $question->id }} } })"
                                        class="rounded-md p-2 text-[#706f6c] hover:bg-neutral-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-neutral-800 dark:hover:text-[#eeeeec]"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                    </svg>
                                </button>
                                <button
                                        wire:click="confirmDeleteQuestion({{ $question->id }})"
                                        class="rounded-md p-2 text-[#706f6c] hover:bg-red-100 hover:text-red-600 dark:text-[#A1A09A] dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                         viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                              d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Ответы на вопрос -->
                        <div class="mt-4 space-y-3">
                            @foreach($question->answers as $answer)
                                <div class="rounded-md border border-neutral-200 bg-neutral-50 p-3 dark:border-neutral-700 dark:bg-[#252525]">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-medium">{{ $answer->text }}</p>
                                            <p class="mt-1 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                                Реакция: <span class="font-medium">{{ $answer->reaction }}</span>
                                            </p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button
                                                    wire:click="$dispatch('openModal', { component: 'bots.questions.edit-answer', arguments: { answer: {{ $answer->id }} } })"
                                                    class="rounded-md p-1 text-[#706f6c] hover:bg-neutral-100 hover:text-[#1b1b18] dark:text-[#A1A09A] dark:hover:bg-neutral-800 dark:hover:text-[#eeeeec]"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="1.5"
                                                          d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                                </svg>
                                            </button>
                                            <button
                                                    wire:click="confirmDeleteAnswer({{ $answer->id }})"
                                                    class="rounded-md p-1 text-[#706f6c] hover:bg-red-100 hover:text-red-600 dark:text-[#A1A09A] dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                     viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          stroke-width="1.5" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <!-- Кнопка добавления ответа -->
                            <button
                                    wire:click="$dispatch('openModal', { component: 'bots.questions.create-answer', arguments: { question: {{ $question->id }} } })"
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

            <!-- Пустое состояние -->
            @if($questions->isEmpty())
                <div class="flex h-full flex-col items-center justify-center text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-[#706f6c] dark:text-[#A1A09A]"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                    <h3 class="mt-2 text-lg font-medium">Нет вопросов</h3>
                    <p class="mt-1 text-[#706f6c] dark:text-[#A1A09A]">Начните с добавления первого вопроса для вашего
                        бота</p>
                    <button
                            wire:click="$dispatch('openModal', { component: 'bots.questions.create-question' })"
                            class="mt-4 inline-flex items-center gap-2 rounded-md bg-[#f53003] px-4 py-2 text-sm font-medium text-white hover:bg-[#d32a03] dark:bg-[#FF4433] dark:hover:bg-[#e53929]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                             stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Добавить вопрос
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Модальное окно подтверждения удаления вопроса -->
    @if($confirmingQuestionDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-500 bg-opacity-75 transition-opacity">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-zinc-800">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-zinc-800">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                    Удалить вопрос
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Вы уверены, что хотите удалить этот вопрос? Все связанные ответы также будут
                                        удалены.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-zinc-700">
                        <button
                                wire:click="$set('confirmingQuestionDeletion', false)"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-medium text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Отмена
                        </button>
                        <button
                                wire:click="deleteQuestion"
                                wire:loading.attr="disabled"
                                class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-medium text-sm text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Модальное окно подтверждения удаления ответа -->
    @if($confirmingAnswerDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-500 bg-opacity-75 transition-opacity">
            <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="inline-block transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:align-middle dark:bg-zinc-800">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-zinc-800">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                                    Удалить ответ
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Вы уверены, что хотите удалить этот ответ?
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-zinc-700">
                        <button
                                wire:click="$set('confirmingAnswerDeletion', false)"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-medium text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                        >
                            Отмена
                        </button>
                        <button
                                wire:click="deleteAnswer"
                                wire:loading.attr="disabled"
                                class="ml-3 inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-medium text-sm text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                        >
                            Удалить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
