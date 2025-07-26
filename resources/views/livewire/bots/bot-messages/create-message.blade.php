<div>
    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 dark:bg-zinc-800">
        <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">
                    Добавить новое сообщение
                </h3>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="group"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300">Группа</label>
                        <select
                                wire:model="group"
                                id="group"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-700 dark:border-gray-600 dark:text-white"
                        >
                            @foreach($availableGroups as $groupOption)
                                <option value="{{ $groupOption }}">{{ $groupOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Текст
                            сообщения</label>
                        <textarea
                                wire:model="text"
                                id="text"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-700 dark:border-gray-600 dark:text-white"
                        ></textarea>
                    </div>
                    <div>
                        <label for="order"
                               class="block text-sm font-medium text-gray-700 dark:text-gray-300">Порядок</label>
                        <input
                                wire:model="order"
                                type="number"
                                id="order"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-zinc-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-zinc-700">
        <button
                wire:click="save"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-4 py-2 bg-[#f53003] border border-transparent rounded-md font-medium text-sm text-white shadow-sm hover:bg-[#d32a03] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#f53003] dark:bg-[#FF4433] dark:hover:bg-[#e53929]"
        >
            Сохранить
        </button>
        <button
                wire:click="$dispatch('closeModal')"
                wire:loading.attr="disabled"
                class="mr-3 inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-medium text-sm text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
        >
            Отмена
        </button>
    </div>
</div>