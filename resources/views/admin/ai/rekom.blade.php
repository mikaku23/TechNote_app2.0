@extends('template_admin.layout')

@section('title', 'AI Rekomendasi')

@section('css')
<style>
    .ai-page {
        padding: 24px;
    }

    .glass {
        background: rgba(15, 23, 42, .55);
        border: 1px solid rgba(255, 255, 255, .10);
        box-shadow: 0 20px 60px rgba(0, 0, 0, .28);
        backdrop-filter: blur(26px);
        -webkit-backdrop-filter: blur(26px);
        border-radius: 24px;
    }

    .toolbar {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        margin-bottom: 18px;
    }

    .toolbar p {
        margin: 6px 0 0;
        color: rgba(226, 232, 240, .7);
    }

    .btn-glass {
        color: #f8fafc;
        background: rgba(255, 255, 255, .08);
        border: 1px solid rgba(255, 255, 255, .12);
        padding: 12px 16px;
        border-radius: 999px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .rekom-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .rekom-item {
        padding: 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .rekom-item strong {
        display: block;
        margin-bottom: 6px;
    }

    .rekom-item p,
    .rekom-item small {
        color: rgba(226, 232, 240, .72);
    }

    .badge {
        display: inline-flex;
        padding: 6px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .pending {
        background: rgba(251, 191, 36, .12);
        color: #fde68a;
    }

    .accepted {
        background: rgba(34, 197, 94, .12);
        color: #bbf7d0;
    }

    .ignored {
        background: rgba(239, 68, 68, .12);
        color: #fecaca;
    }
</style>
@endsection

@section('content')
<div class="ai-page">
    <div class="toolbar">
        <div>
            <h1 style="margin:0;">AI Rekomendasi</h1>
            <p>Rekomendasi AI yang tersimpan di tabel ai_recommendations.</p>
        </div>
        <a href="{{ route('admin.ai.index') }}" class="btn-glass">
            <i data-lucide="arrow-left"></i> Kembali
        </a>
    </div>

    <div class="glass" style="padding:18px;">
        <div class="rekom-list">
            @forelse($recommendations as $rekom)
            <div class="rekom-item">
                <span class="badge {{ $rekom->status }}">{{ $rekom->status }}</span>
                <strong>{{ $rekom->ticket_number ? 'Ticket '.$rekom->ticket_number : 'Tanpa Ticket' }}</strong>
                <p>{{ $rekom->recommendation }}</p>
                <small>
                    reason: {{ $rekom->reason ?? '-' }} · {{ $rekom->created_at }}
                </small>
            </div>
            @empty
            <div class="rekom-item">Belum ada rekomendasi.</div>
            @endforelse
        </div>

        <div style="margin-top:16px;">
            {{ $recommendations->links() }}
        </div>
    </div>
</div>
@endsection