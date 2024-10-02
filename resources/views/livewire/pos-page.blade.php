<section class="antialiased">
    <div class="container mx-auto p-4">
        <div class="flex flex-col lg:flex-row gap-8">
            <div class="lg:w-2/3 space-y-6">
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
                            class="block w-full p-4 ps-10 text-sm bg-white dark:bg-gray-900 text-gray-900 border border-gray-300 rounded-lg focus:ring-yellow-500 focus:border-yellow-500 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-yellow-500 dark:focus:border-yellow-500"
                            placeholder="Search product" required />
                    </div>
                </div>
                @if ($products->isEmpty())
                    <p class="text-xl text-center my-6">Empty Product</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                        @foreach ($products as $product)
                            <div class="flex flex-col p-4 bg-white dark:bg-gray-900 rounded-lg shadow cursor-pointer"
                                wire:click="addItem({{ $product->id }})">
                                <img src="{{ asset('storage/' . $product->image) }}"
                                    alt="{{ $product->name . ' image' }}"
                                    class="w-full h-40 object-cover rounded mb-4" />
                                <div class="flex-grow">
                                    <h3 class="font-semibold">{{ $product->name }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-300">Stock :
                                        {{ $product->stock }}</p>
                                </div>
                                <span class="font-bold mt-2">Rp.{{ Number::format($product->price) }}</span>
                            </div>
                        @endforeach
                    </div>
                    {{ $products->links() }}
                @endif
            </div>
            <x-filament::section class="lg:w-1/3 h-fit">
                <x-slot name="heading">
                    Checkout Form
                </x-slot>
                <form wire:submit.prevent="cashPopup">
                    <div class="flow-root mb-4">
                        @if (empty($cartItems))
                            <p class="text-sm text-center my-6">Empty Cart</p>
                        @else
                            <div class="-my-3 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($cartItems ?? [] as $key => $value)
                                    <div class="flex items-center justify-between py-3">
                                        <div class="flex lg:mx-auto gap-4 items-center lg:flex-col xl:flex-row">
                                            <div class="h-16 w-16 md:h-20 md:w-20">
                                                <img src="{{ asset('storage/' . $value['image']) }}"
                                                    alt="{{ $value['name'] . ' image' }}"
                                                    class="h-full w-full object-cover object-center rounded-md">
                                            </div>
                                            <div class="space-y-1">
                                                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ $value['name'] }}</h3>
                                                <p class="text-xs text-gray-600 dark:text-gray-400">Rp.
                                                    {{ Number::format($value['price']) }}</p>
                                                <div class="flex items-center mt-2">
                                                    <x-filament::button size="xs"
                                                        wire:click="removeItem('{{ $key }}')" type="button"
                                                        color="gray" icon="heroicon-m-minus"></x-filament::button>
                                                    <input
                                                        class="w-10 text-center border-0 bg-transparent text-sm font-medium text-gray-900 focus:outline-none focus:ring-0 dark:text-white"
                                                        value="{{ $value['quantity'] }}" type="text"
                                                        inputmode="numeric" name="quantity" id="quantity" readonly />
                                                    <x-filament::button size="xs"
                                                        wire:click="addItem({{ $value['id'] }})" type="button"
                                                        color="gray" icon="heroicon-m-plus"></x-filament::button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <dl class="flex items-center justify-between gap-4 mt-6">
                            <dt class="text-base font-bold text-gray-900 dark:text-white">Total</dt>
                            <dd class="text-base font-bold text-gray-900 dark:text-white">Rp.
                                {{ Number::format($this->getTotalPrice()) }}</dd>
                        </dl>
                    </div>
                    {{ $this->checkoutForm }}
                    <x-filament::button color="warning" type="submit" class="w-full mt-4">
                        Checkout
                    </x-filament::button>
                </form>
                <div x-data="{ showModal: @entangle('showModal') }" x-show="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
                    @click.away="showModal = false">
                    <!-- Main modal -->
                    <div class="relative p-4 w-full max-w-md max-h-full" @click.away="showModal = false">
                        <!-- Modal content -->
                        <div class="relative bg-gray-100 dark:bg-gray-800 rounded-lg shadow" @click.stop>
                            <div
                                class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600">
                                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                                    Confirm Cash Payment
                                </h3>
                                <button type="button" @click="showModal = false"
                                    class="end-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white"
                                    data-modal-hide="authentication-modal">
                                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                        fill="none" viewBox="0 0 14 14">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                                    </svg>
                                    <span class="sr-only">Close modal</span>
                                </button>
                            </div>
                            <!-- Modal body -->
                            <div class="p-4 md:p-5">
                                <div class="space-y-6">
                                    {{ $this->confirmForm }}
                                    <x-filament::button wire:click="saveOrder" color="warning" type="submit"
                                        class="w-full">
                                        Submit
                                    </x-filament::button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    </div>
</section>
