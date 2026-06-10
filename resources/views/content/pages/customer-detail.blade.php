@extends('layouts/layoutMaster')

@section('title', 'Customer Detail - Pages')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/notyf/notyf.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
])

<style>
    .info-label {
        width: 40%;
        font-weight: 500;
        color: #6c757d;
    }

    .customer-stat-card {
        border-left: 3px solid;
    }

    .customer-stat-card.primary {
        border-color: #696cff;
    }

    .customer-stat-card.success {
        border-color: #71dd37;
    }

    .customer-stat-card.warning {
        border-color: #ffab00;
    }

    .customer-stat-card.info {
        border-color: #03c3ec;
    }
</style>
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/notyf/notyf.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
])
@endsection

@section('page-script')
@vite('resources/assets/js/app-customer-detail.js')
@endsection

@section('content')

@include('_partials.toast-message')

<div class="card">
    <div class="card-header border-bottom">

        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h5 class="card-title mb-1 fw-semibold text-primary">
                    Customer — {{ $customer->full_name }}
                </h5>
                <div class="d-flex flex-wrap gap-5 align-items-center">
                    <small class="text-muted">
                        <span class="fw-medium text-dark">ID:</span>
                        {{ $customer->shopify_legacy_id ?? $customer->id }}
                    </small>
                    <small>
                        <span class="fw-medium text-dark">State:</span>
                        @php
                        $stateClass = match($customer->state) {
                        'ENABLED' => 'bg-label-success',
                        'DISABLED' => 'bg-label-danger',
                        'INVITED' => 'bg-label-warning',
                        default => 'bg-label-secondary',
                        };
                        @endphp
                        <span class="badge rounded-pill {{ $stateClass }}">{{ $customer->state }}</span>
                    </small>
                    <small>
                        <span class="fw-medium text-dark">Verified:</span>
                        @if($customer->verified_email)
                        <span class="badge rounded-pill bg-label-success">Yes</span>
                        @else
                        <span class="badge rounded-pill bg-label-secondary">No</span>
                        @endif
                    </small>
                    @if($customer->isActive())
                    <small>
                        <span class="badge rounded-pill bg-label-info">Active</span>
                    </small>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('customers') }}" class="btn btn-outline-secondary btn-sm" title="Back to list">
                    <i class="menu-icon icon-base ri ri-arrow-left-line"></i> Back
                </a>
            </div>
        </div>

        <hr class="mt-2 mb-3" />

        {{-- Quick stats row --}}
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <div class="card customer-stat-card primary mb-0">
                    <div class="card-body py-2 px-3">
                        <small class="text-muted d-block">Amount Spent</small>
                        <strong class="text-primary fs-5">
                            ${{ number_format($customer->amount_spent, 2) }}
                        </strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card customer-stat-card success mb-0">
                    <div class="card-body py-2 px-3">
                        <small class="text-muted d-block">Total Orders</small>
                        <strong class="text-success fs-5">{{ $customer->number_of_orders }}</strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card customer-stat-card info mb-0">
                    <div class="card-body py-2 px-3">
                        <small class="text-muted d-block">Addresses</small>
                        <strong class="text-info fs-5">{{ $addresses->count() }}</strong>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card customer-stat-card warning mb-0">
                    <div class="card-body py-2 px-3">
                        <small class="text-muted d-block">Metafields</small>
                        <strong class="text-warning fs-5">{{ $metafields->count() }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" id="customer_id" value="{{ $customer->id }}">

        <div class="row g-6">
            <div class="col-xl-12">
                <div class="nav-align-top nav-tabs-shadow">

                    <ul class="nav nav-tabs d-flex flex-nowrap overflow-x-auto" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab"
                                data-bs-toggle="tab" data-bs-target="#navs-top-profile"
                                aria-controls="navs-top-profile" aria-selected="true">
                                Profile
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab"
                                data-bs-toggle="tab" data-bs-target="#navs-top-addresses"
                                aria-controls="navs-top-addresses" aria-selected="false">
                                Addresses
                                <span class="badge bg-label-primary ms-1">{{ $addresses->count() }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab"
                                data-bs-toggle="tab" data-bs-target="#navs-top-metafields"
                                aria-controls="navs-top-metafields" aria-selected="false">
                                Metafields
                                <span class="badge bg-label-primary ms-1">{{ $metafields->count() }}</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        {{-- ===================== PROFILE TAB ===================== --}}
                        <div class="tab-pane fade show active" id="navs-top-profile" role="tabpanel">
                            <div class="row g-4 p-3">

                                {{-- Basic Info --}}
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="ri ri-user-line me-2"></i>Basic Information
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless table-sm mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td class="info-label">First Name</td>
                                                        <td>{{ $customer->first_name ?? '—' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Last Name</td>
                                                        <td>{{ $customer->last_name ?? '—' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Display Name</td>
                                                        <td>{{ $customer->display_name ?? '—' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Email</td>
                                                        <td>
                                                            <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Phone</td>
                                                        <td>{{ $customer->phone ?? '—' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Locale</td>
                                                        <td>
                                                            <span class="badge bg-label-info">{{ $customer->locale }}</span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">State</td>
                                                        <td>
                                                            <span class="badge rounded-pill {{ $stateClass }}">
                                                                {{ $customer->state }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Tax Exempt</td>
                                                        <td>
                                                            @if($customer->tax_exempt)
                                                            <span class="badge bg-label-warning">Yes</span>
                                                            @else
                                                            <span class="badge bg-label-secondary">No</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Email Verified</td>
                                                        <td>
                                                            @if($customer->verified_email)
                                                            <span class="badge bg-label-success">
                                                                <i class="ri ri-check-line me-1"></i>Verified
                                                            </span>
                                                            @else
                                                            <span class="badge bg-label-secondary">Not Verified</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @if($customer->note)
                                                    <tr>
                                                        <td class="info-label">Note</td>
                                                        <td>{{ $customer->note }}</td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- Shopify & Sync Info --}}
                                <div class="col-md-6">
                                    <div class="card h-100">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="ri ri-links-line me-2"></i>Shopify & Sync
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-borderless table-sm mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td class="info-label">Legacy ID</td>
                                                        <td>
                                                            <code>{{ $customer->shopify_legacy_id ?? '—' }}</code>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Shopify GID</td>
                                                        <td>
                                                            <small class="text-muted" style="font-size:11px; word-break:break-all;">
                                                                {{ $customer->shopify_customer_id }}
                                                            </small>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Amount Spent</td>
                                                        <td>
                                                            <strong class="text-success">
                                                                ${{ number_format($customer->amount_spent, 2) }}
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Total Orders</td>
                                                        <td>
                                                            <span class="badge bg-label-primary">
                                                                {{ $customer->number_of_orders }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Shopify Created</td>
                                                        <td>
                                                            {{ $customer->shopify_created_at
                                    ? $customer->shopify_created_at->format('Y-m-d H:i')
                                    : '—' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Shopify Updated</td>
                                                        <td>
                                                            {{ $customer->shopify_updated_at
                                    ? $customer->shopify_updated_at->format('Y-m-d H:i')
                                    : '—' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Last Synced</td>
                                                        <td>
                                                            {{ $customer->last_synced_at
                                    ? $customer->last_synced_at->format('Y-m-d H:i')
                                    : '—' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Local Created</td>
                                                        <td>
                                                            {{ $customer->created_at
                                    ? $customer->created_at->format('Y-m-d H:i')
                                    : '—' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td class="info-label">Local Updated</td>
                                                        <td>
                                                            {{ $customer->updated_at
                                    ? $customer->updated_at->format('Y-m-d H:i')
                                    : '—' }}
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{-- Tags --}}
                                @if($customer->tags && count($customer->tags) > 0)
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="ri ri-price-tag-3-line me-2"></i>Tags
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($customer->tags as $tag)
                                                <span class="badge bg-label-secondary fs-6">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Default Address preview --}}
                                @if($customer->defaultAddress)
                                @php $def = $customer->defaultAddress; @endphp
                                <div class="col-md-12">
                                    <div class="card border-primary">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <i class="ri ri-map-pin-line me-2"></i>Default Address
                                            </h6>
                                            <span class="badge bg-label-primary">Default</span>
                                        </div>
                                        <div class="card-body">
                                            <p class="mb-0">
                                                {{ $def->formatted_address }}
                                            </p>
                                            @if($def->phone)
                                            <small class="text-muted">
                                                <i class="ri ri-phone-line me-1"></i>{{ $def->phone }}
                                            </small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif

                            </div>
                        </div>

                        {{-- ===================== ADDRESSES TAB ===================== --}}
                        <div class="tab-pane fade" id="navs-top-addresses" role="tabpanel">
                            <div class="p-3">
                                @if($addresses->isEmpty())
                                <div class="text-center text-muted py-5">
                                    <i class="ri ri-map-pin-line" style="font-size: 2.5rem;"></i>
                                    <p class="mt-2 mb-0">No addresses found for this customer.</p>
                                </div>
                                @else
                                <div class="row g-4">
                                    @foreach($addresses as $address)
                                    <div class="col-md-6">
                                        <div class="card h-100 {{ $address->is_default ? 'border-primary' : '' }}">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <i class="ri ri-map-pin-2-line me-2"></i>
                                                    Address #{{ $loop->iteration }}
                                                </h6>
                                                @if($address->is_default)
                                                <span class="badge bg-label-primary">Default</span>
                                                @endif
                                            </div>
                                            <div class="card-body">
                                                <table class="table table-borderless table-sm mb-0">
                                                    <tbody>
                                                        @if($address->first_name || $address->last_name)
                                                        <tr>
                                                            <td class="info-label">Name</td>
                                                            <td>
                                                                {{ trim(($address->first_name ?? '') . ' ' . ($address->last_name ?? '')) ?: '—' }}
                                                            </td>
                                                        </tr>
                                                        @endif
                                                        @if($address->company)
                                                        <tr>
                                                            <td class="info-label">Company</td>
                                                            <td>{{ $address->company }}</td>
                                                        </tr>
                                                        @endif
                                                        <tr>
                                                            <td class="info-label">Address 1</td>
                                                            <td>{{ $address->address1 ?? '—' }}</td>
                                                        </tr>
                                                        @if($address->address2)
                                                        <tr>
                                                            <td class="info-label">Address 2</td>
                                                            <td>{{ $address->address2 }}</td>
                                                        </tr>
                                                        @endif
                                                        <tr>
                                                            <td class="info-label">City</td>
                                                            <td>{{ $address->city ?? '—' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="info-label">Province</td>
                                                            <td>
                                                                {{ $address->province ?? '—' }}
                                                                @if($address->province_code)
                                                                <small class="text-muted">({{ $address->province_code }})</small>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="info-label">ZIP</td>
                                                            <td>{{ $address->zip ?? '—' }}</td>
                                                        </tr>
                                                        <tr>
                                                            <td class="info-label">Country</td>
                                                            <td>
                                                                {{ $address->country ?? '—' }}
                                                                @if($address->country_code)
                                                                <span class="badge bg-label-secondary ms-1">
                                                                    {{ $address->country_code }}
                                                                </span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        @if($address->phone)
                                                        <tr>
                                                            <td class="info-label">Phone</td>
                                                            <td>{{ $address->phone }}</td>
                                                        </tr>
                                                        @endif
                                                        <tr>
                                                            <td class="info-label">Full Address</td>
                                                            <td>
                                                                <small class="text-muted">
                                                                    {{-- uses getFormattedAddressAttribute from CustomerAddress model --}}
                                                                    {{ $address->formatted_address ?: '—' }}
                                                                </small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td class="info-label">Shopify ID</td>
                                                            <td>
                                                                <small class="text-muted" style="font-size:11px; word-break:break-all;">
                                                                    {{ $address->shopify_address_id ?? '—' }}
                                                                </small>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- ===================== METAFIELDS TAB ===================== --}}
                        <div class="tab-pane fade" id="navs-top-metafields" role="tabpanel">
                            <div class="p-3">
                                @if($metafields->isEmpty())
                                <div class="text-center text-muted py-5">
                                    <i class="ri ri-database-2-line" style="font-size: 2.5rem;"></i>
                                    <p class="mt-2 mb-0">No metafields found for this customer.</p>
                                </div>
                                @else
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>Namespace</th>
                                                <th>Key</th>
                                                <th>Value</th>
                                                <th>Parsed Value</th>
                                                <th>Type</th>
                                                <th>Shopify Metafield ID</th>
                                                <th>Created</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($metafields as $metafield)
                                            @php
                                            // uses getParsedValueAttribute from CustomerMetafield model
                                            $parsed = $metafield->parsed_value;
                                            @endphp
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>
                                                    <span class="badge bg-label-secondary">
                                                        {{ $metafield->namespace }}
                                                    </span>
                                                </td>
                                                <td><code>{{ $metafield->key }}</code></td>
                                                <td>{{ $metafield->value }}</td>
                                                <td>
                                                    @if($metafield->type === 'boolean')
                                                    @if($parsed)
                                                    <span class="badge bg-label-success">true</span>
                                                    @else
                                                    <span class="badge bg-label-danger">false</span>
                                                    @endif
                                                    @elseif(is_array($parsed))
                                                    <code>{{ json_encode($parsed) }}</code>
                                                    @else
                                                    {{ $parsed }}
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-label-info">{{ $metafield->type }}</span>
                                                </td>
                                                <td>
                                                    <small class="text-muted" style="font-size:11px; word-break:break-all;">
                                                        {{ $metafield->shopify_metafield_id ?? '—' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    {{ $metafield->created_at
                                    ? $metafield->created_at->format('Y-m-d H:i')
                                    : '—' }}
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
