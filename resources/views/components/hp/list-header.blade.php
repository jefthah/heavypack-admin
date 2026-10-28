@props([
    'breadcrumbs' => [],
    'heading',
    'subheading' => null,
    'actions' => [],
    'metrics' => [],
    'metricsLabel' => null,
])

<div class="hp-list-header">
    <x-filament-panels::header
        :breadcrumbs="$breadcrumbs"
        :heading="$heading"
        :subheading="$subheading"
        :actions="$actions"
    />

    @if (filled($metrics))
        <dl class="hp-overview" @if (filled($metricsLabel)) aria-label="{{ $metricsLabel }}" @endif>
            @foreach ($metrics as $metric)
                @php
                    $progress = $metric['progress'] ?? null;
                @endphp

                <div @class(['hp-metric', 'hp-metric--primary' => $metric['primary'] ?? false, 'hp-metric--link' => filled($metric['url'] ?? null)])>
                    <div class="hp-metric__body">
                        <dt>
                            <span>{{ $metric['label'] }}</span>

                            @if ($progress !== null)
                                <span class="hp-metric__percent">{{ $progress }}%</span>
                            @endif
                        </dt>

                        <dd>
                            <span class="hp-metric__value">
                                {{ $metric['value'] }}
                                <span class="hp-metric__unit">{{ $metric['unit'] }}</span>
                            </span>

                            @if ($progress !== null)
                                <span
                                    class="hp-metric__progress"
                                    role="progressbar"
                                    aria-label="{{ $metric['label'] }}"
                                    aria-valuenow="{{ $progress }}"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                >
                                    <span style="width: {{ $progress }}%"></span>
                                </span>
                            @endif

                            @if (filled($metric['note'] ?? null))
                                <span class="hp-metric__note">{{ $metric['note'] }}</span>
                            @endif

                            @if (filled($metric['warning'] ?? null))
                                <span class="hp-metric__warning">
                                    <x-filament::icon icon="heroicon-m-exclamation-triangle" />
                                    {{ $metric['warning'] }}
                                </span>
                            @endif

                            @if (filled($metric['url'] ?? null))
                                <a href="{{ $metric['url'] }}" class="hp-metric__link">
                                    {{ $metric['linkLabel'] ?? 'Lihat' }}
                                    <x-filament::icon icon="heroicon-m-arrow-right" />
                                </a>
                            @endif
                        </dd>
                    </div>

                    @if (filled($metric['icon'] ?? null))
                        <span class="hp-metric__icon" aria-hidden="true">
                            <x-filament::icon :icon="$metric['icon']" />
                        </span>
                    @endif
                </div>
            @endforeach
        </dl>
    @endif
</div>
