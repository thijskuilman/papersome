<?php

namespace App\Filament\Resources\Sources\RelationManagers;

use App\Models\Article;
use App\Models\Source;
use App\Services\FeedService;
use App\Services\ReadabilityService;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ArticlesRelationManager extends RelationManager
{
    protected static string $relationship = 'articles';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
            ]);
    }


    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
//                TextEntry::make('title')
//                    ->extraAttributes(['style' => 'font-size: 20px; font-weight: bold;'])
//                    ->hiddenLabel()
//                    ->columnSpanFull(),

                TextEntry::make('html_content')
                    ->hiddenLabel()
                    ->html()
                    ->extraAttributes(['class' => 'fi-prose'])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([

                ImageColumn::make('image'),

                TextColumn::make('title')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('published_at')
                    ->since(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('parse-all-articles')
                    ->color('gray')
                    ->label('Parse all articles')
                    ->action(function () {
                        $source = $this->ownerRecord;

                        foreach ($source->articles as $article) {
                            app(ReadabilityService::class)->parseArticleContent($article);
                        }
                    }),

                Action::make('feed')
                    ->label('Refresh feed')
                    ->action(fn() => app(FeedService::class)->storeArticlesFromSource($this->ownerRecord)),

            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn(Article $article) => $article->title),

                Action::make('open')
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->url(function (Article $article) {
                        $urlPrefix = $article->source->prefix_parse_url;
                        return ($urlPrefix ?? '') . $article->url;
                    })
                    ->openUrlInNewTab(),

                Action::make('parse')
                    ->action(fn(Article $article) => app(ReadabilityService::class)->parseArticleContent($article)),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
