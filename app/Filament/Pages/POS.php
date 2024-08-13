<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class POS extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?int $navigationSort = 0;
    protected static string $view = 'filament.pages.pos';
}
