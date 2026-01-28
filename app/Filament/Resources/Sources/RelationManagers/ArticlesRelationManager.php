<?php

namespace App\Filament\Resources\Sources\RelationManagers;

use App\Models\Article;
use App\Services\FeedService;
use App\Services\ReadabilityService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                ImageEntry::make('image')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->extraImgAttributes(['style' => 'width: 100%; object-fit: cover; height: 400px;']),

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
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('published_at')
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('published_at', 'desc')
            ->headerActions([
                Action::make('parse-all-articles')
                    ->color('gray')
                    ->icon(Heroicon::DocumentMagnifyingGlass)
                    ->label('Parse all articles')
                    ->action(function (): void {
                        $source = $this->ownerRecord;

                        foreach ($source->articles as $article) {
                            app(ReadabilityService::class)->setHtmlContentForArticle($article);
                        }
                    }),

                Action::make('feed')
                    ->color('gray')
                    ->icon(Heroicon::ArrowPath)
                    ->label('Refresh feed')
                    ->action(fn () => app(FeedService::class)->storeArticlesFromSource($this->ownerRecord)),

            ])
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->modalHeading(fn (Article $article) => $article->title),

                Action::make('open')
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->url(function (Article $article): string {
                        $urlPrefix = $article->source->prefix_parse_url;

                        return ($urlPrefix ?? '').$article->url;
                    })
                    ->openUrlInNewTab(),

                Action::make('parse')
                    ->action(fn (Article $article) => app(ReadabilityService::class)->setHtmlContentForArticle($article)),
            ]);
    }
}
