<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{

    protected static array $statuses = [
        'in stock' => 'in stock',
        'sold out' => 'sold out',
        'coming soon' => 'coming soon',
    ];

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('price')->required()->rule('numeric'),
                Forms\Components\Radio::make('status')
                    ->options(self::$statuses),
                Forms\Components\Select::make('category_id')->relationship('category','name'),
               

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextInputColumn::make('name')
                    ->sortable() 
                    ->searchable()
                    ->rules(['required', 'min:3']), 
                Tables\Columns\TextColumn::make('price')
                    ->sortable()
                    ->money('usd')
                    ->getStateUsing( function (Product $record) : float{
                        return $record->price / 100;
                    })
                    ->alignEnd(), 
                    // ->alignment(Alignment::End)
                Tables\Columns\ToggleColumn::make('is_active'),
                //Tables\Columns\CheckboxColumn::make('is_active'), 
                Tables\Columns\TextColumn::make('status')
                    ->badge() 
                    ->colors([
                        'warning' => static fn ($state): bool => $state === 'coming soon',
                        'success' => static fn ($state): bool => $state === 'in stock',
                        'danger' => static fn ($state): bool => $state === 'sold out',
                    ]),
                    
                Tables\Columns\TextColumn::make('category.name')
                    
                    ->url(function (Product $product): string {
                        return CategoryResource::getUrl('edit', [
                            'record' => $product->category_id
                        ]);
                    })
                    ->label('Category name'),
                Tables\Columns\TextColumn::make('tags.name')
                    ->badge(),
                    Tables\Columns\TextColumn::make('created_at')
                    ->since(),
            ])
            ->defaultSort('price','desc') 
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                ->options(self::$statuses),
                Tables\Filters\SelectFilter::make('category')
                ->relationship('category', 'name'),
                Tables\Filters\Filter::make('created_from')
                ->form([
                    Forms\Components\DatePicker::make('created_from'),
                ])
                
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                        );
                }),
                
            Tables\Filters\Filter::make('created_until')
                ->form([
                    Forms\Components\DatePicker::make('created_until'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['created_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                        );
                }),
        ], layout: Tables\Enums\FiltersLayout::AboveContent)
    ->filtersFormColumns(4) 
            ->actions([
                Tables\Actions\EditAction::make(),
                //Tables\Actions\DeleteAction::make(), 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
