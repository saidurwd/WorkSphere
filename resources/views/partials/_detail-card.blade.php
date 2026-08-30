@props([
    'title',
    'items' => [],
    'emptyMessage' => 'No records found.',
    'viewAllRoute' => null,
    'viewAllLabel' => null,
])

<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
        @if($viewAllRoute)
            <a href="{{ $viewAllRoute }}" class="btn btn-sm btn-ghost">{{ $viewAllLabel ?? 'View All' }}</a>
        @endif
    </div>

    <div class="card-body" style="padding: 0;">
        @if(count($items))
            <div>
                @foreach($items as $item)
                <a href="{{ $item['url'] ?? '#' }}" style="text-decoration: none; color: inherit; display: block;">
                    <div style="padding: 0.875rem 1.25rem; border-bottom: 1px solid var(--border);">
                        <div class="user-cell">
                            <div class="user-cell-avatar">
                                {{ $item['avatar'] ?? strtoupper(substr($item['title'] ?? '', 0, 1)) }}
                            </div>
                            <div class="user-cell-info" style="flex: 1; min-width: 0;">
                                <div class="user-cell-name" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $item['title'] ?? '' }}</div>
                                @if(!empty($item['subtitle']))
                                    <div class="user-cell-email">{{ $item['subtitle'] }}</div>
                                @endif
                            </div>
                            @if(!empty($item['badge']))
                                <span class="badge {{ $item['badge']['class'] ?? 'badge-secondary' }}" style="flex-shrink: 0;">
                                    {{ $item['badge']['text'] ?? '' }}
                                </span>
                            @endif
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        @else
            <div style="padding: 2rem 1.25rem; text-align: center; color: var(--muted-foreground);">
                <p style="margin: 0;">{{ $emptyMessage }}</p>
            </div>
        @endif
    </div>
</div>
