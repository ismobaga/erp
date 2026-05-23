<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('erp.portal.documents.title') }} — {{ $client->company_name ?: $client->contact_name }}</title>
    <style>
        @include('portal.partials.styles')
        .doc-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f4f9;
        }
        .doc-item:last-child { border-bottom: none; }
        .doc-ext {
            width: 40px;
            height: 40px;
            background: #002045;
            color: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 800;
            flex-shrink: 0;
        }
        .doc-info { flex: 1; min-width: 0; }
        .doc-name { font-size: 14px; font-weight: 700; color: #002045; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .doc-meta { font-size: 12px; color: #57657a; margin-top: 2px; }
    </style>
</head>
<body>

@include('portal.partials.nav')

<div class="container">

    @php
        $allEmpty = $clientDocs->isEmpty() && $projectDocs->isEmpty() && $invoiceDocs->isEmpty();
    @endphp

    @if($allEmpty)
        <div class="card">
            <div class="empty">
                <div class="empty-icon">📁</div>
                <p style="font-size:16px;font-weight:700;margin-bottom:8px;">{{ __('erp.portal.documents.empty') }}</p>
                <p>{{ __('erp.portal.documents.empty_hint') }}</p>
            </div>
        </div>
    @else

        @if($clientDocs->isNotEmpty())
            <div class="card">
                <div class="card-title">{{ __('erp.portal.documents.client_docs') }}</div>
                @foreach($clientDocs as $doc)
                    <div class="doc-item">
                        <div class="doc-ext">{{ $doc->extensionLabel() }}</div>
                        <div class="doc-info">
                            <div class="doc-name">{{ $doc->file_name }}</div>
                            <div class="doc-meta">
                                {{ $doc->resolvedCategory() }}
                                @if($doc->size_bytes) · {{ $doc->humanSize() }} @endif
                                · {{ __('erp.portal.documents.uploaded') }} {{ $doc->created_at?->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                @endforeach
                <div style="margin-top:14px;">
                    @if($clientDocs->previousPageUrl() || $clientDocs->nextPageUrl())
                        <div class="portal-pagination">
                            @if($clientDocs->previousPageUrl())
                                <a class="portal-pagination-link" href="{{ $clientDocs->previousPageUrl() }}" rel="prev" aria-label="Previous page">←</a>
                            @else
                                <span class="portal-pagination-text">←</span>
                            @endif
                            @if($clientDocs->nextPageUrl())
                                <a class="portal-pagination-link" href="{{ $clientDocs->nextPageUrl() }}" rel="next" aria-label="Next page">→</a>
                            @else
                                <span class="portal-pagination-text">→</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($projectDocs->isNotEmpty())
            <div class="card">
                <div class="card-title">{{ __('erp.portal.documents.project_docs') }}</div>
                @foreach($projectDocs as $doc)
                    <div class="doc-item">
                        <div class="doc-ext">{{ $doc->extensionLabel() }}</div>
                        <div class="doc-info">
                            <div class="doc-name">{{ $doc->file_name }}</div>
                            <div class="doc-meta">
                                {{ $doc->resolvedCategory() }}
                                @if($doc->size_bytes) · {{ $doc->humanSize() }} @endif
                                · {{ __('erp.portal.documents.uploaded') }} {{ $doc->created_at?->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                @endforeach
                <div style="margin-top:14px;">
                    @if($projectDocs->previousPageUrl() || $projectDocs->nextPageUrl())
                        <div class="portal-pagination">
                            @if($projectDocs->previousPageUrl())
                                <a class="portal-pagination-link" href="{{ $projectDocs->previousPageUrl() }}" rel="prev" aria-label="Previous page">←</a>
                            @else
                                <span class="portal-pagination-text">←</span>
                            @endif
                            @if($projectDocs->nextPageUrl())
                                <a class="portal-pagination-link" href="{{ $projectDocs->nextPageUrl() }}" rel="next" aria-label="Next page">→</a>
                            @else
                                <span class="portal-pagination-text">→</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @if($invoiceDocs->isNotEmpty())
            <div class="card">
                <div class="card-title">{{ __('erp.portal.documents.invoice_docs') }}</div>
                @foreach($invoiceDocs as $doc)
                    <div class="doc-item">
                        <div class="doc-ext">{{ $doc->extensionLabel() }}</div>
                        <div class="doc-info">
                            <div class="doc-name">{{ $doc->file_name }}</div>
                            <div class="doc-meta">
                                {{ $doc->resolvedCategory() }}
                                @if($doc->size_bytes) · {{ $doc->humanSize() }} @endif
                                · {{ __('erp.portal.documents.uploaded') }} {{ $doc->created_at?->format('d/m/Y') ?? '—' }}
                            </div>
                        </div>
                    </div>
                @endforeach
                <div style="margin-top:14px;">
                    @if($invoiceDocs->previousPageUrl() || $invoiceDocs->nextPageUrl())
                        <div class="portal-pagination">
                            @if($invoiceDocs->previousPageUrl())
                                <a class="portal-pagination-link" href="{{ $invoiceDocs->previousPageUrl() }}" rel="prev" aria-label="Previous page">←</a>
                            @else
                                <span class="portal-pagination-text">←</span>
                            @endif
                            @if($invoiceDocs->nextPageUrl())
                                <a class="portal-pagination-link" href="{{ $invoiceDocs->nextPageUrl() }}" rel="next" aria-label="Next page">→</a>
                            @else
                                <span class="portal-pagination-text">→</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

    @endif
</div>

<footer>
    <p>{{ $company?->company_name ?: config('app.name') }} · {{ __('erp.portal.secure_portal') }} · {{ __('erp.portal.do_not_share') }}</p>
</footer>
</body>
</html>
