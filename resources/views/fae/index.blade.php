@extends('layouts.app')

@section('title', 'FAE Team')
@section('breadcrumb', 'FAE Team')

@section('content')
<section class="content">

    <div class="page-intro">
        <div>
            <span class="welcome-label">TEAM MANAGEMENT</span>
            <h1>FAE Team</h1>
            <p>Manage field engineers and assign regional work.</p>
        </div>
        <a href="{{ route('tasks.index') }}" class="outline-button" style="text-decoration:none;">View tasks</a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Team Members</h2>
                <p>{{ count($faeList) }} registered field application engineers</p>
            </div>
        </div>

        <form method="POST" action="{{ route('fae.store') }}" class="fae-add-form" enctype="multipart/form-data">
            @csrf
            <input class="form-control" name="name" required placeholder="Full name">
            <div style="display:flex; gap:6px; align-items:center;">
                <input class="form-control" id="fae_code_input" name="fae_code" required placeholder="FAE code" style="flex:1; text-transform:uppercase; letter-spacing:1px;">
                <button type="button" id="btn-generate-code" class="outline-button btn-sm" title="Generate a random secure FAE code" style="white-space:nowrap;">
                    ⟳ Generate
                </button>
            </div>
            <select class="form-control" name="department_id" required>
                <option value="">Select Department</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}">{{ $department->department_name }}</option>
                @endforeach
            </select>
            <button class="primary-button btn-sm" type="submit">＋ Add FAE</button>
        </form>

        <div class="fae-grid">
            @forelse($faeList as $fae)
                @php
                    $avgProgress = round($fae->avg_progress);
                @endphp
                <article class="fae-card">
                    <div>
                        <div class="fae-card-top">
                            <div class="fae-avatar-box" style="background:var(--primary);color:white;">
                                @if(!empty($fae->profile_image))
                                    <img class="fae-profile-image" src="{{ \App\Services\UploadService::url($fae->profile_image) }}" alt="{{ $fae->name }} profile picture">
                                @else
                                    {{ strtoupper(substr($fae->name, 0, 2)) }}
                                @endif
                            </div>
                            <div class="fae-info">
                                <h3>{{ $fae->name }}</h3>
                                <span class="fae-code-badge">{{ $fae->fae_code }}</span>
                                <div class="fae-dept">{{ $fae->department_name }}</div>
                            </div>
                        </div>

                        <div class="fae-stats-row">
                            <div class="fae-stat-col">
                                <span>Tasks</span>
                                <strong>{{ (int)$fae->total_tasks }}</strong>
                            </div>
                            <div class="fae-stat-col">
                                <span>Completed</span>
                                <strong style="color:var(--green);">{{ (int)$fae->completed_tasks }}</strong>
                            </div>
                        </div>

                        <div class="fae-progress-bar-wrap">
                            <div class="fae-progress-label">
                                <span>Average progress</span>
                                <strong>{{ $avgProgress }}%</strong>
                            </div>
                            <div class="progress-bar">
                                @php $faeBarClass = $avgProgress <= 25 ? 'progress-red' : ($avgProgress <= 50 ? 'progress-orange' : ($avgProgress <= 75 ? 'progress-gold' : 'progress-green')); @endphp
                                <div class="{{ $faeBarClass }}" style="width:{{ $avgProgress }}%;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="fae-card-actions">
                        <a href="{{ route('tasks.index', ['assign_fae' => $fae->id]) }}" class="btn-add-task-fae" style="text-decoration:none;">
                            ＋ Add task
                        </a>
                        <a href="{{ route('fae.link', ['code' => $fae->fae_code]) }}" class="outline-button" target="_blank" rel="noopener" style="text-decoration:none;">
                            Open link
                        </a>
                        <form method="POST" action="{{ route('fae.destroy', $fae->id) }}" onsubmit="return confirm('Remove this FAE? Assigned tasks will become unassigned.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger-outline btn-sm">Remove</button>
                        </form>
                    </div>
                </article>
            @empty
                <div style="grid-column: 1 / -1; text-align:center; padding:36px; color:var(--text-light);">
                    <p>No FAE members registered yet. Use the form above to add one.</p>
                </div>
            @endforelse
        </div>
    </div>

    <footer>
        <span>Monitoring System © 2026</span>
        <span>FAE management</span>
    </footer>
</section>
@endsection

@push('scripts')
<script>
    // Generates a cryptographically random uppercase alphanumeric code
    function generateFaeCode(length = 8) {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no ambiguous chars (0, O, 1, I)
        const arr = new Uint8Array(length);
        crypto.getRandomValues(arr);
        return Array.from(arr, b => chars[b % chars.length]).join('');
    }

    const codeInput = document.getElementById('fae_code_input');
    const generateBtn = document.getElementById('btn-generate-code');

    if (codeInput && generateBtn) {
        // Auto-generate on page load if field is empty
        if (!codeInput.value.trim()) {
            codeInput.value = generateFaeCode();
        }

        generateBtn.addEventListener('click', function () {
            codeInput.value = generateFaeCode();
            codeInput.focus();
        });

        // Force uppercase as user types
        codeInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }
</script>
@endpush
