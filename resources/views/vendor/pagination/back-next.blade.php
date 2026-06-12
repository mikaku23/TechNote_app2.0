@if ($paginator->hasPages())
<nav class="pagination-wrapper" aria-label="Pagination">
    <ul class="pagination pagination-back-next">

        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
            @if ($paginator->onFirstPage())
            <span class="page-link page-link-nav">
                <i data-lucide="chevron-left"></i>
                Back
            </span>
            @else
            <a
                class="page-link page-link-nav"
                href="{{ $paginator->previousPageUrl() }}"
                rel="prev">
                <i data-lucide="chevron-left"></i>
                Back
            </a>
            @endif
        </li>

        <li class="page-item page-indicator">
            <span class="page-link page-link-info">
                Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
            </span>
        </li>

        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
            @if ($paginator->hasMorePages())
            <a
                class="page-link page-link-nav"
                href="{{ $paginator->nextPageUrl() }}"
                rel="next">
                Next
                <i data-lucide="chevron-right"></i>
            </a>
            @else
            <span class="page-link page-link-nav">
                Next
                <i data-lucide="chevron-right"></i>
            </span>
            @endif
        </li>

    </ul>
</nav>
@endif