<div>
    <livewire:article-preview
        :source="$getRecord()"
        :html-query-filters="$getState()['html_query_filters']"
    />
</div>
