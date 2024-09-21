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
                            placeholder="Search product" required />
                    </div>
                </div>
                <!-- Product List -->
                <div class="space-y-4">
                    @if ($products->isEmpty())
                        <p class="text-xl text-center my-6">Empty Product</p>
                    @else
                        <div class="grid grid-cols-1 justify-items-center gap-4 sm:grid-cols-2 md:grid-cols-3">
                            @foreach ($products as $product)
                                <div wire:click="addItem({{ $product->id }})"
                                    class="w-full h-40 max-w-sm sm:max-w-full flex md:flex-col md:h-full justify-start items-center bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
                                    <a href="#">
                                        <img class="p-6 rounded-t-lg mx-auto h-44"
                                            src="{{ asset('storage/' . $product->image) }}"
                                            alt="{{ $product->name . ' image' }}" />
                                    </a>
                                    <div class="px-5 pt-4 md:pt-0 pb-4">
                                        <a href="#">
                                            <h5
                                                class="text-xl md:text-md font-bold tracking-tight text-gray-900 dark:text-white">
                                                {{ $product->name }}</h5>
                                        </a>
                                        <div class="flex flex-col justify-between mt-2 gap-4">
                                            <span
                                                class="text-lg sm:text-base md:text-sm font-semibold text-gray-850 dark:text-white">Rp.{{ Number::format($product->price) }}</span>
                                            <span
                                                class="text-lg sm:text-base md:text-sm font-semibold text-gray-800 dark:text-white">Stock
                                                : {{ $product->stock }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        {{ $products->links() }}
                    @endif
                </div>
            </div>
            <!-- Cart and Payment -->
            <x-filament::section class="mt-6 md:w-1/3 space-y-6 sm:mt-8 md:mt-0">
                <x-slot name="heading">
                    Checkout Form
                </x-slot>
                <form wire:submit.prevent="cashPopup">
                    <div class="flow-root">
                        @if (empty($cartItems))
                            <p class="text-sm text-center my-6">Empty Cart</p>
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
                                            <div class="space-y-2">
                                                <h3 class="text-sm">{{ $value['name'] }}</h3>
                                                <h3 class="text-xs">Rp. {{ Number::format($value['price']) }}</h3>
                                                <div class="flex items-center">
                                                    <x-filament::button size="xs"
                                                        wire:click="removeItem('{{ $key }}')" type="button"
                                                        color="gray" icon="heroicon-m-minus"></x-filament::button>
                                                    <input
                                                        class="w-10 shrink-0 border-0 bg-transparent text-center text-sm font-medium text-gray-900 focus:outline-none focus:ring-0 dark:text-white"
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
                    </div>
                    <!-- Customer Search -->
                    <div x-data="{
                        open: false,
                        search: '',
                        selectedId: @entangle('customer_id').defer,
                        options: @js($customers),
                        get filteredOptions() {
                            if (!this.search.trim()) return this.options;
                            return this.options.filter(option => option.name.toLowerCase().includes(this.search.toLowerCase()));
                        },
                        get selectedOption() {
                            if (!this.selectedId) return null;
                            return this.options.find(option => option.id === this.selectedId);
                        },
                        selectOption(option) {
                            this.selectedId = option.id;
                            this.search = option.name;
                            this.open = false;
                            @this.customer_id = option.id;
                        }
                    }">
                        <div class="relative mt-6">
                            <input type="text" x-model="search" @focus="open = true" @click.away="open = false"
                                placeholder="Search customer"
                                class="relative w-full text-normal border-gray-300 dark:border-gray-700 rounded-md bg-gray-50 dark:bg-gray-950 py-2 pl-3 pr-10 text-left shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-700 focus:border-yellow-500 focus:ring-yellow-500">
                            <div x-show="open" x-cloak
                                class="absolute z-50 mt-1 max-h-52 w-full rounded-md bg-gray-50 dark:bg-gray-950 py-1 text-base shadow-lg">
                                <ul class="max-h-52 overflow-auto">
                                    <template x-for="option in filteredOptions" :key="option.id">
                                        <li @click="selectOption(option)"
                                            class="cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-gray-200 dark:hover:bg-gray-700 hover:text-gray-800 dark:hover:text-gray-300"
                                            :class="{
                                                'bg-yellow-500 text-black dark:text-white': selectedOption &&
                                                    selectedOption
                                                    .id === option.id
                                            }">
                                            <span x-text="option.name" class="font-normal block truncate"></span>
                                        </li>
                                    </template>
                                    <li x-show="filteredOptions.length === 0"
                                        class="cursor-default select-none py-2 pl-3 pr-9 text-gray-500">
                                        No customer found
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- Payment Options -->
                    <div class="mt-6 w-full">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model="payment_id">
                                <option value="">Select payment</option>
                                @foreach ($payments as $payment)
                                    <option value="{{ $payment->id }}">{{ $payment->name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                    <dl class="flex items-center justify-between gap-4 py-4">
                        <dt class="text-base font-bold text-gray-900 dark:text-white">Total</dt>
                        <dd class="text-base font-bold text-gray-900 dark:text-white">Rp.
                            {{ Number::format($this->getTotalPrice()) }}</dd>
                    </dl>
                    <div class="space-y-3">
                        <x-filament::button color="warning" type="submit" class="w-full">
                            Checkout
                        </x-filament::button>
                    </div>
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
                                    <div>
                                        <x-filament::input.wrapper>
                                            <x-filament::input wire:model="paid" type="number" name="paid"
                                                id="paid" placeholder="Paid"
                                                wire:change="$set('paid', $event.target.value)" />
                                        </x-filament::input.wrapper>
                                    </div>
                                    <div>
                                        <x-filament::input.wrapper>
                                            <x-filament::input wire:model.lazy="change" value="{{ $change }}"
                                                type="number" disabled readonly name="change" id="change"
                                                placeholder="Change" />
                                        </x-filament::input.wrapper>
                                    </div>
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
    </article>
</section>
