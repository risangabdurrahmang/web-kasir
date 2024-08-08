<section class="antialiased">
    <article class="mx-auto max-w-screen-xl px-4 md:px-0 2xl:px-0">
        <div class="mt-6 sm:mt-8 md:flex lg:items-start gap-8">
            <div class="md:w-2/3 space-y-8">
                <!-- Search Bar -->
                <div class="mx-auto">
                    <label for="default-search"
                        class="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white">Search</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z" />
                            </svg>
                        </div>
                        <input wire:model.live="search" type="search" id="default-search"
                            class="block w-full p-4 ps-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-yellow-500 focus:border-yellow-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-yellow-500 dark:focus:border-yellow-500"
                            placeholder="Cari produk" required />
                    </div>
                </div>
                <!-- Product List -->
                <div class="space-y-4">
                    <div class="grid grid-cols-1 justify-items-center gap-4 sm:grid-cols-2 md:grid-cols-3">
                        @foreach ($products as $product)
                            <div wire:click="addItem({{ $product->id }})"
                                class="w-full max-w-sm bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                                <a href="#">
                                    <img class="p-8 rounded-t-lg mx-auto"
                                        src="{{ asset('storage/' . $product->image) }}"
                                        alt="{{ $product->name . ' image' }}" />
                                </a>
                                <div class="px-5 pb-5">
                                    <a href="#">
                                        <h5
                                            class="text-2xl md:text-base lg:text-lg font-semibold tracking-tight text-gray-900 dark:text-white">
                                            {{ $product->name }}</h5>
                                    </a>
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="text-xl md:text-base lg:text-md font-bold text-gray-900 dark:text-white">Rp.{{ Number::format($product->price) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- Cart and Payment -->
            <div class="mt-6 md:w-1/3 space-y-6 sm:mt-8">
                <form wire:submit.prevent="saveOrder">
                    <div class="flow-root max-h-[500px] overflow-y-auto py-2">
                        @if (empty($cartItems))
                            <p class="text-sm text-center my-6">Keranjang Kosong</p>
                        @else
                            <div class="-my-3 divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($cartItems ?? [] as $key => $value)
                                    <div class="flex justify-between p-2">
                                        <div class="flex justify-between gap-4">
                                            <div class="h-full w-16 rounded-md">
                                                <img src="{{ asset('storage/' . $value['image']) }}"
                                                    alt="{{ $value['name'] . ' image' }}"
                                                    class="h-full w-full object-cover object-center rounded-md">
                                            </div>
                                            <div class="flex flex-col justify-between">
                                                <h3 class="text-sm">{{ $value['name'] }}</h3>
                                                <h3 class="text-xs">Rp. {{ Number::format($value['price']) }}</h3>
                                                <input class="w-8 h-8 rounded-md text-center text-xs bg-transparent"
                                                    value="{{ $value['quantity'] }}" type="text" inputmode="numeric"
                                                    name="quantity" id="quantity" readonly>
                                            </div>
                                        </div>
                                        <x-filament::button size="xs" class="h-10"
                                            wire:click="removeItem('{{ $key }}')" type="button"
                                            color="danger" icon="heroicon-m-trash"></x-filament::button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <!-- Payment Options -->
                    <div class="mt-6 grid w-full gap-6 grid-cols-2">
                        @foreach ($payments as $payment)
                            <label class="block mb-2 text-center cursor-pointer">
                                <input type="radio" wire:model="payment_id" value="{{ $payment->id }}"
                                    class="hidden peer" />
                                <div
                                    class="p-2.5 bg-transparent border border-yellow-300 text-gray-900 text-sm rounded-lg hover:bg-yellow-400 peer-checked:bg-yellow-400 peer-checked:text-white focus:ring-yellow-400 focus:border-yellow-400 dark:border-yellow-400 dark:placeholder-gray-400 dark:text-white dark:peer-checked:bg-yellow-400 dark:peer-checked:border-yellow-400">
                                    {{ $payment->payment_method }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_id')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                    <!-- Payment Input Fields -->
                    <ul class="mt-4 w-full space-y-4">
                        <li>
                            <input wire:model="paid" type="number" name="paid" id="paid" placeholder="Dibayar"
                                wire:change="$set('paid', $event.target.value)"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-yellow-500 focus:border-yellow-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-yellow-500 dark:focus:border-yellow-500" />
                            @error('paid')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </li>
                        <li>
                            <input wire:model.lazy="money_changes" value="{{ $money_changes }}" type="number" disabled
                                readonly name="money_changes" id="money_changes" placeholder="Kembalian"
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-yellow-500 focus:border-yellow-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-yellow-500 dark:focus:border-yellow-500" />
                        </li>
                    </ul>
                    <dl class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-base font-bold text-gray-900 dark:text-white">Total</dt>
                        <dd class="text-base font-bold text-gray-900 dark:text-white">Rp.
                            {{ Number::format($this->getTotalPrice()) }}</dd>
                    </dl>
                    <div class="space-y-3">
                        <button type="submit"
                            class="block w-full rounded-lg bg-yellow-400 px-5 py-2.5 text-sm font-medium text-white hover:bg-yellow-500 focus:outline-none focus:ring-4  focus:ring-yellow-400 dark:bg-yellow-400 dark:hover:bg-yellow-500 dark:focus:ring-yellow-400">Simpan
                            Pesanan</button>
                    </div>
                </form>
            </div>
        </div>
    </article>
</section>
