<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <!-- Статистические карточки -->
        <div class="grid auto-rows-min gap-4 md:grid-cols-3">
            <!-- Карточка 1: Всего ботов -->
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#161615]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Всего ботов</p>
                        <p class="mt-1 text-3xl font-semibold">24</p>
                        <p class="mt-2 text-xs text-green-500">+3 за месяц</p>
                    </div>
                    <div class="rounded-lg bg-[#f5f5f4] p-3 dark:bg-[#252525]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#f53003] dark:text-[#FF4433]"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Карточка 2: Активные пользователи -->
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#161615]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Активные пользователи</p>
                        <p class="mt-1 text-3xl font-semibold">12,458</p>
                        <p class="mt-2 text-xs text-green-500">+1,024 за неделю</p>
                    </div>
                    <div class="rounded-lg bg-[#f5f5f4] p-3 dark:bg-[#252525]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#f53003] dark:text-[#FF4433]"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Карточка 3: Сообщений сегодня -->
            <div class="relative overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#161615]">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-[#706f6c] dark:text-[#A1A09A]">Сообщений сегодня</p>
                        <p class="mt-1 text-3xl font-semibold">42,156</p>
                        <p class="mt-2 text-xs text-green-500">+12% с вчера</p>
                    </div>
                    <div class="rounded-lg bg-[#f5f5f4] p-3 dark:bg-[#252525]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-[#f53003] dark:text-[#FF4433]"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- График активности -->
        <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#161615]">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium">Активность ботов за месяц</h3>
                <select class="rounded-md border border-neutral-200 bg-white px-3 py-1 text-sm text-[#1b1b18] dark:border-neutral-700 dark:bg-[#161615] dark:text-[#eeeeec]">
                    <option class="bg-white text-[#1b1b18] dark:bg-[#161615] dark:text-[#eeeeec]">30 дней</option>
                    <option class="bg-white text-[#1b1b18] dark:bg-[#161615] dark:text-[#eeeeec]">7 дней</option>
                    <option class="bg-white text-[#1b1b18] dark:bg-[#161615] dark:text-[#eeeeec]">24 часа</option>
                </select>
            </div>

            <!-- Заглушка для графика (в реальном проекте можно подключить Chart.js или другой библиотекой) -->
            <div class="mt-6 h-[calc(100%-50px)] w-full">
                <div class="flex h-full items-center justify-center">
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             class="mx-auto h-12 w-12 text-[#706f6c] dark:text-[#A1A09A]" fill="none"
                             viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <p class="mt-2 text-[#706f6c] dark:text-[#A1A09A]">
                            График активности будет отображаться здесь<br>
                            (создание dashboard, не оплаченно)<br>
                            <a href="https://mdk-bots.ru/admin">Админка тут https://mdk-bots.ru/admin</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Список последних действий -->
        <div class="rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-[#161615]">
            <h3 class="mb-4 text-lg font-medium">Последние действия</h3>
            <div class="space-y-4">
                <div class="flex items-start">
                    <div class="mr-3 mt-1 rounded-full bg-green-100 p-1.5 dark:bg-green-900/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600 dark:text-green-400"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium">Новый бот добавлен</p>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Бот "MDK Support" успешно подключен к
                            системе</p>
                        <p class="mt-1 text-xs text-[#A1A09A]">10 минут назад</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <div class="mr-3 mt-1 rounded-full bg-blue-100 p-1.5 dark:bg-blue-900/30">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600 dark:text-blue-400"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium">Обновление расписания</p>
                        <p class="text-sm text-[#706f6c] dark:text-[#A1A09A]">Расписание бота "MDK News" обновлено</p>
                        <p class="mt-1 text-xs text-[#A1A09A]">2 часа назад</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
