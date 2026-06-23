@extends('template_admin.layout')

@section('title', 'AI Tasks')

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

    .task-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .task-item {
        padding: 16px;
        border-radius: 18px;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .task-item strong {
        display: block;
        margin-bottom: 6px;
    }

    .task-item p,
    .task-item small {
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

    .running {
        background: rgba(59, 130, 246, .12);
        color: #93c5fd;
    }

    .completed {
        background: rgba(34, 197, 94, .12);
        color: #bbf7d0;
    }

    .failed {
        background: rgba(239, 68, 68, .12);
        color: #fecaca;
    }
</style>
@endsection

@section('content')
<div class="ai-page">
    <div class="toolbar">
        <div>
            <h1 style="margin:0;">AI Tasks</h1>
            <p>Daftar tugas AI yang tersimpan di tabel ai_tasks.</p>
        </div>
        <a href="{{ route('admin.ai.index') }}" class="btn-glass">
            <i data-lucide="arrow-left"></i> Kembali
        </a>
    </div>

    <div class="glass" style="padding:18px;">
        <div class="task-list">
            @forelse($tasks as $task)
            <div class="task-item">
                <span class="badge {{ $task->status }}">{{ $task->status }}</span>
                <strong>{{ $task->task_name }}</strong>
                <p>{{ \Illuminate\Support\Str::limit($task->instruction, 180) }}</p>
                <small>
                    {{ $task->user_name ?? 'System' }} · {{ $task->created_at }}
                </small>
            </div>
            @empty
            <div class="task-item">Belum ada task.</div>
            @endforelse
        </div>

        <div style="margin-top:16px;">
            {{ $tasks->links() }}
        </div>
    </div>
</div>
@endsection