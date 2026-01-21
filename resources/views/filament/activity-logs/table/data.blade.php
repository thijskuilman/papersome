<table class="min-w-full border border-slate-200 dark:border-slate-700 text-sm">
    <thead>
    <tr class="bg-slate-100 dark:bg-slate-800">
        <th class="px-3 py-2 border border-slate-200 dark:border-slate-700 text-left text-slate-700 dark:text-slate-200">
            Key
        </th>
        <th class="px-3 py-2 border border-slate-200 dark:border-slate-700 text-left text-slate-700 dark:text-slate-200">
            Value
        </th>
    </tr>
    </thead>
    <tbody>
    @foreach ($getRecord()->data as $key => $value)
        <tr class="bg-white dark:bg-slate-900">
            <td class="px-3 py-2 border border-slate-200 dark:border-slate-700 font-medium text-slate-700 dark:text-slate-200">
                {{ $key }}
            </td>
            <td class="px-3 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300">
                {{ is_array($value) ? json_encode($value) : $value }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

