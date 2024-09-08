<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Filters\TrashedFilter;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationGroup = 'Shop';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()->schema([
                    Forms\Components\Select::make('customer_id')
                        ->searchable()
                        ->relationship('customer', 'name')
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->placeholder('Enter name')
                                ->columns(1),
                            Forms\Components\Select::make('gender')
                                ->placeholder('Select gender')
                                ->options([
                                    'Laki-laki' => 'Laki-laki',
                                    'Perempuan' => 'Perempuan',
                                ])
                                ->required()
                                ->native(false)
                                ->columns(1),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->unique()
                                ->required()
                                ->placeholder('Enter email')
                                ->columns(1),
                            Forms\Components\TextInput::make('phone')
                                ->required()
                                ->placeholder('Enter phone')
                                ->columns(1),
                        ])->columns(2)
                        ->createOptionAction(function (Action $action) {
                            return $action
                                ->modalHeading('Create customer')
                                ->modalSubmitActionLabel('Create customer')
                                ->modalWidth('lg');
                        }),
                    Forms\Components\RichEditor::make('notes')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Repeater::make('items')
                    ->relationship()
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->options(function () {
                                return Product::where('stock', '>', 0)
                                    ->where('is_visible', true)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn($state, Forms\Set $set) => $set('stock', Product::find($state)?->stock ?? 0))
                            ->afterStateUpdated(fn($state, Forms\Set $set) => $set('price', Product::find($state)?->price ?? 0))
                            ->afterStateUpdated(fn($state, Forms\Set $set) => $set('sub_total', Product::find($state)?->price ?? 0))
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->searchable()
                            ->columnSpan([
                                'md' => 4,
                            ]),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->default(1)
                            ->minValue(1)
                            ->lte('stock')
                            ->reactive()
                            ->afterStateUpdated(fn($state, Set $set, Get $get) => $set('sub_total', $state * $get('price'))),
                        Forms\Components\TextInput::make('stock')
                            ->label('Stock')
                            ->numeric()
                            ->disabled()
                            ->readOnly(),
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->prefix('Rp.')
                            ->readOnly()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan([
                                'md' => 2,
                            ]),
                        Forms\Components\TextInput::make('sub_total')
                            ->numeric()
                            ->prefix('Rp.')
                            ->readOnly()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan([
                                'md' => 2,
                            ]),
                    ])
                    ->afterStateHydrated(function (Get $get, Set $set) {
                        foreach ($get('items') as $index => $item) {
                            $product = Product::find($item['product_id']);
                            $set('items.' . $index . '.stock', $product?->stock);
                            $set('items.' . $index . '.price', $product?->price);
                        }
                    })
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateTotals($get, $set);
                    })
                    ->deleteAction(
                        fn(Action $action) => $action->after(fn(Get $get, Set $set) => self::updateTotals($get, $set)),
                    )
                    ->extraItemActions([
                        Action::make('openProduct')
                            ->tooltip('Open product')
                            ->icon('heroicon-m-arrow-top-right-on-square')
                            ->url(function (array $arguments, Repeater $component): ?string {
                                $itemData = $component->getRawItemState($arguments['item']);
                                $product = Product::find($itemData['product_id']);
                                if (!$product) {
                                    return null;
                                }
                                return ProductResource::getUrl('edit', ['record' => $product]);
                            }, shouldOpenInNewTab: true)
                            ->hidden(fn(array $arguments, Repeater $component): bool => blank($component->getRawItemState($arguments['item'])['product_id'])),
                    ])
                    ->columns([
                        'md' => 10,
                    ])
                    ->required(),
                Section::make('Payment Information')->schema([
                    Forms\Components\Select::make('payment_id')
                        ->relationship('payment', 'payment_method')
                        ->native(false)
                        ->reactive()
                        ->required()
                        ->options(function () {
                            return Payment::where('is_visible', true)->pluck('payment_method', 'id');
                        }),
                    Forms\Components\TextInput::make('total')
                        ->numeric()
                        ->readOnly()
                        ->prefix('Rp.')
                        ->afterStateHydrated(function (Get $get, Set $set) {
                            self::updateTotals($get, $set);
                        }),
                    Forms\Components\TextInput::make('paid')
                        ->numeric()
                        ->live()
                        ->gte('total')
                        ->visible(fn(Get $get): int => $get('payment_id') == 1)
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            self::updateTotals($get, $set);
                        }),
                    Forms\Components\TextInput::make('money_changes')
                        ->readOnly()
                        ->numeric()
                        ->visible(fn(Get $get): int => $get('payment_id') == 1)
                        ->afterStateHydrated(function (Get $get, Set $set) {
                            self::updateTotals($get, $set);
                        }),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('payment.payment_method')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid')
                    ->placeholder('-')
                    ->numeric()
                    ->sortable()
                    ->money('IDR', locale: 'id'),
                Tables\Columns\TextColumn::make('money_changes')
                    ->placeholder('-')
                    ->numeric()
                    ->sortable()
                    ->money('IDR', locale: 'id'),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable()
                    ->money('IDR', locale: 'id'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    // fungsi untuk kembalian dan total belanja dari produk yang sudah dipilih
    public static function updateTotals(Get $get, Set $set): void
    {
        $selectedProducts = collect($get('items'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));

        $prices = Product::find($selectedProducts->pluck('product_id'))->pluck('price', 'id');

        $total = $selectedProducts->reduce(function ($total, $product) use ($prices) {
            return $total + ($prices[$product['product_id']] * $product['quantity']);
        }, 0);

        $set('total', number_format($total, 0, '.', ''));

        $total = $get('total');
        $paid = intval($get('paid')); // or floatval($get('paid')) if you expect floats

        if ($paid > $total) {
            $moneyChanges = $paid - intval($total); // or floatval($total)
            $set('money_changes', number_format($moneyChanges, 0, '.', ''));
        } else {
            $set('money_changes', 0);
        }
    }
}
