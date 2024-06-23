<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Transaction;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Widgets\TableWidget as BaseWidget;

class WaitingTransaction extends BaseWidget

{
    protected static ?int $sort = 3;
    public function table(Table $table): Table
    {
        return $table
        ->query(
            Transaction::query()->whereStatus('waiting')
        )
        ->columns([
            TextColumn::make('user.name')
                ->numeric()
                ->sortable()->weight(FontWeight::Bold),
            
            TextColumn::make('listing.title')
                ->numeric()
                ->sortable(),
            TextColumn::make('start_date')
                ->date()
                ->sortable(),
            TextColumn::make('end_date')
                ->date()
                ->sortable(),
            TextColumn::make('total_days')
                ->numeric()
                ->sortable(),
            TextColumn::make('total_price')
                ->money('IDR')
                ->sortable()
                ->weight(FontWeight::Bold),
            TextColumn::make('status')->badge()->color(fn(string $state): string => match($state) {
                'waiting' => 'gray',
                'approved' => 'info',
                'canceled' => 'danger',
            }),
        ])
        ->actions([
            Action::make('approve')
                ->button()
                ->color('success')
                ->requiresConfirmation()
                ->action(function(Transaction $transaction) {
                    Transaction::find($transaction->id)->update([
                        'status' => 'approved'
                    ]);
                    Notification::make()->success()->title('Transaction Approved!')->body('Transaction has been approved successfully')->icon('heroicon-o-check')->send();
                })
                ->hidden(fn(Transaction $transaction) => $transaction->status !== 'waiting')
        ]);
    }
}
