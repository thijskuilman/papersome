<?php

namespace App\Enums;

enum SourceFormEvent: string
{
    case StartRssVerification = 'start_rss_verification';

    case ResetRssVerification = 'reset_rss_verification';

    case HtmlQueryFiltersUpdated = 'html_query_filters_updated';
}
