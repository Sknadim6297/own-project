@section('styles')
<style>
    .yukti-admin .yukti-metric-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 0.75rem;
    }
    .yukti-admin .yukti-metric {
        border: 1px solid var(--default-border, #e9edf4);
        border-radius: 0.5rem;
        padding: 0.9rem 1rem;
        background: #fff;
    }
    .yukti-admin .yukti-metric__label {
        font-size: 0.75rem;
        color: #6b7280;
        margin-bottom: 0.25rem;
    }
    .yukti-admin .yukti-metric__value {
        font-size: 1.25rem;
        font-weight: 600;
        line-height: 1.2;
    }
    .yukti-admin .yukti-status {
        display: inline-block;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        line-height: 1.4;
    }
    .yukti-admin .yukti-status--on { background: #d1fae5; color: #065f46; }
    .yukti-admin .yukti-status--off { background: #fee2e2; color: #991b1b; }
    .yukti-admin .yukti-status--joined { background: #e0e7ff; color: #3730a3; }
    .yukti-admin .yukti-status--activated { background: #fef3c7; color: #92400e; }
    .yukti-admin .yukti-status--rewarded { background: #d1fae5; color: #065f46; }
    .yukti-admin .yukti-status--rejected { background: #fee2e2; color: #991b1b; }
    .yukti-admin .yukti-status--invited { background: #f3f4f6; color: #374151; }
    .yukti-admin .yukti-status--credit { background: #dcfce7; color: #166534; }
    .yukti-admin .yukti-status--debit { background: #ffedd5; color: #9a3412; }
    .yukti-admin .table td, .yukti-admin .table th { vertical-align: middle; }
    .yukti-admin .yukti-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.8rem; }
    .yukti-admin .yukti-note {
        border-left: 3px solid #94a3b8;
        padding: 0.75rem 1rem;
        background: #f8fafc;
        border-radius: 0 0.35rem 0.35rem 0;
    }
</style>
@endsection
