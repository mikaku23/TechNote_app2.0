@extends('template_admin.layout')

@section('title', 'System Settings')

@section('css')
<style>
    .system-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .system-card {
        padding: 22px;
        border-radius: 24px;
    }

    .mode-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
    }

    .mode-left {
        display: flex;
        gap: 14px;
        align-items: flex-start;
    }

    .mode-icon {
        width: 50px;
        height: 50px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, .06);
        border: 1px solid rgba(255, 255, 255, .08);
        flex-shrink: 0;
    }

    .mode-desc {
        margin: 8px 0 0;
        color: var(--text-light);
        line-height: 1.7;
    }

    .mode-footer {
        margin-top: 18px;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }

    .mode-state {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .state-on {
        background: rgba(34, 197, 94, .14);
        color: #86efac;
    }

    .state-off {
        background: rgba(239, 68, 68, .14);
        color: #fca5a5;
    }

    @media (max-width: 992px) {
        .system-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <div>
        <h1>System Settings</h1>
        <p>Kelola mode utama TechNoteApp 2.0.</p>
    </div>
</div>

@if(session('success'))
<div class="tn-alert tn-alert-success">
    <strong>Success!</strong>
    <p>{{ session('success') }}</p>
</div>
@endif

<div class="system-grid">
    @foreach($items as $item)
    <div class="glass card system-card motion-card">
        <div class="mode-top">
            <div class="mode-left">
                <div class="mode-icon">
                    <i data-lucide="{{ $item['icon'] }}"></i>
                </div>

                <div>
                    <h3 style="margin:0;">{{ $item['title'] }}</h3>
                    <p class="mode-desc">{{ $item['description'] }}</p>
                </div>
            </div>

            <span class="mode-state {{ $item['enabled'] ? 'state-on' : 'state-off' }}">
                {{ $item['enabled'] ? 'Aktif' : 'Nonaktif' }}
            </span>
        </div>

        <div class="mode-footer">
            <div>
                <strong>Status:</strong>
                <span>{{ $item['enabled'] ? 'Enabled' : 'Disabled' }}</span>
            </div>

            <form method="POST" action="{{ route('setting.sistem.toggle') }}">
                @csrf
                <input type="hidden" name="mode" value="{{ $item['key'] }}">
                <input type="hidden" name="value" value="{{ $item['enabled'] ? 0 : 1 }}">

                <button
                    type="submit"
                    class="btn-primary"
                    data-tn-confirm
                    data-tn-type="{{ $item['enabled'] ? 'warning' : 'success' }}"
                    data-tn-title="{{ $item['enabled'] ? 'Nonaktifkan '.$item['title'].'?' : 'Aktifkan '.$item['title'].'?' }}"
                    data-tn-message="{{ $item['enabled'] ? 'Fitur ini akan dimatikan sementara.' : 'Fitur ini akan diaktifkan kembali.' }}"
                    data-tn-proceed-text="{{ $item['enabled'] ? 'Nonaktifkan' : 'Aktifkan' }}">
                    <i data-lucide="{{ $item['enabled'] ? 'toggle-left' : 'toggle-right' }}"></i>
                    {{ $item['enabled'] ? 'Nonaktifkan' : 'Aktifkan' }}
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endsection