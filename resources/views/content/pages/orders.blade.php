@extends('layouts/layoutMaster')

@section('title', 'Order List - Pages')


@section('vendor-style')

    @vite(['resources/assets/vendor/libs/notyf/notyf.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])



    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])

    <style>
        /* Apply to all td starting from the 3rd column */
        .datatables-users>tbody>tr>td:nth-child(n+3) {
            padding-left: 3px !important;
            padding-right: 2px !important;
        }

        /* Apply to all th starting from the 3rd column */
        .table thead tr th:nth-child(n+3) {
            padding-left: 3px !important;
            padding-right: 2px !important;
        }

        .fixedHeader-floating thead {
            background-color: #E7E7FF !important;
            /* Light gray background */
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            /* Optional shadow */
            transition: background-color 0.3s ease-in-out;

        }

        #failedReasonModal {
            z-index: 1092;
        }

        #redeemKeyModal {
            z-index: 1092;
        }

        .modal-backdrop.show:nth-of-type(2) {
            z-index: 1055;
        }

        /*.modal.show {*/
        /*  z-index: 1050;*/
        /*}*/
        /*.modal-backdrop.show {*/
        /*  z-index: 1040;*/
        /*}*/
        /*.modal.second-modal.show {*/
        /*  z-index: 1060; !* higher than the first modal *!*/
        /*}*/
        /*.modal.second-modal + .modal-backdrop.show {*/
        /*  z-index: 1055;*/
        /*}*/
    </style>

@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/notyf/notyf.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/bootstrap-select/bootstrap-select.js', 'resources/assets/vendor/libs/cleave-zen/cleave-zen.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/cleave-zen/cleave-zen.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])

@endsection

@section('page-script')

    @vite('resources/assets/js/app-order-list.js')
@endsection

@section('content')

    @include('_partials.toast-message')

    <div class="modal fade second-modal" id="failedReasonModal" tabindex="-1" aria-labelledby="failedReasonModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="failedReasonModalLabel">Failed Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre id="failedReasonContent" class="bg-light p-3 rounded"></pre>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="redeemKeyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header" id="redeemModalHeader">
                    <h5 class="modal-title text-white" id="redeemModalTitle">
                        <i class="ri ri-shield-check-line me-2" id="redeemModalIcon"></i>
                        Redemption Successful
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div id="redeemKeyContainer" class="row g-3">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="orderItemsModal" tabindex="-1" aria-labelledby="orderItemsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2" id="orderItemsModalLabel" style="display: block !important;">
                        Order Items: <span class="text-muted fs-6" id="order_id"></span>
                        <br>
                        Country: <span class="text-muted fs-6" id="country_code"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="paymentInfoBox" class="mb-3 d-none"></div>

                    <div class="table-responsive">
                        <table class="table table-striped" id="orderItemsTable">
                            <thead>
                                <tr>
                                    <th class="">Product</th>
                                    <th class="">Supp. Order ID</th>
                                    <th class="">Currency</th>

                                    <th class="">Sales</th>

                                    <th class="">Cost</th>

                                    {{--                <th class="">Key ID</th> --}}
                                    {{--                <th class="">Retailer Order ID</th> --}}
                                    {{--                <th class="">Discount</th> --}}
                                    <th class="">VAT</th>
                                    {{-- <th class="">Gateway</th>
                                    <th class="">PaymentID</th> --}}
                                    <th class="">Redeemed At</th>

                                </tr>
                            </thead>
                            <tbody>
                                <!-- Items will be inserted dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Users List Table -->
    <div class="card">
        <div class="card-header border-bottom">
            <h5 class="card-title mb-0" id="all_products_title">Orders</h5>


        </div>
        <div class="row mt-2 mx-2 " id="filter-section">

            <div class="col-md-2">
                <div class="form-floating form-floating-outline mb-6">
                    <input type="text" class="form-control dt-input " id="order_id_2game" placeholder="ID/SHOPIFY ID"
                        data-column=1 data-column-index="0" />
                    <label for="order_id_2game">SHOPIFY ID</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating form-floating-outline mb-6">
                    <input type="text" class="form-control dt-input product-id-filter" id="product_id" placeholder="SKU"
                        data-column=3 data-column-index="2" />
                    <label for="product_id">SKU</label>
                </div>
            </div>


            <div class="col-md-2">
                <div class="form-floating form-floating-outline mb-6">
                    <select class="form-select dt-input status-filter " id="status" aria-label="All Status" data-column=4
                        data-column-index="3">
                        <option value="">All Status</option>
                        <option value="PENDING">PENDING</option>
                        <option value="PROCESSING">PROCESSING</option>
                        <option value="COMPLETED">COMPLETED</option>
                        {{--            <option value="PARTIALLY_COMPLETED">PARTIALLY_COMPLETED</option> --}}
                        <option value="FAILED">FAILED</option>
                        <option value="VALIDATION_FAILED">VALIDATION_FAILED</option>
                        <option value="CANCELLED ">CANCELLED</option>
                    </select>
                    <label for="status">STATUS</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating form-floating-outline mb-6">
                    <select class="form-select dt-input source-filter" id="source" aria-label="All Suppliers"
                        data-column=11 data-column-index="10">
                        <option value="">All Suppliers</option>
                        <option value="1">ztorm</option>
                        <option value="2">incomm</option>
                        <option value="3">point nexus</option>
                        <option value="4">genba</option>
                    </select>
                    <label for="source">Supplier</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating form-floating-outline mb-6">
                    <select class="form-select dt-input country-filter" id="country" aria-label="All Countries"
                        data-column=13 data-column-index="12">
                        <option value="">All Countries</option>
                        @foreach($countries as $country)
                          <option value="{{ $country }}">{{ $country }}</option>
                        @endforeach
                    </select>
                    <label for="country">Country</label>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-2">
                <div class="form-floating form-floating-outline mb-6">
                    <input type="text" class="form-control dt-date flatpickr-range dt-input" id="date_range"
                        data-column="12" data-column-index="11" name="dt_date" placeholder="StartDate to EndDate" />
                    <label for="date_range">Start Date - End Date</label>
                </div>

                <!-- hidden fields (optional, keep if needed for backend filtering) -->
                <input type="hidden" class="form-control dt-date start_date dt-input" data-column="12"
                    data-column-index="11" name="value_from_start_date" />
                <input type="hidden" class="form-control dt-date end_date dt-input" data-column="12"
                    data-column-index="11" name="value_from_end_date" />
            </div>


        </div>

        <div class="card-datatable table-responsive">


            <table class="datatables-users table border-top">
                <thead>
                    <tr>

                        <th></th>
                        <th title="ID">ID</th>
                        <th title="SHOPIFY ORDER ID">SHOPIFY ID</th>
                        <th title="PRODUCT TITLE">PRODUCTS</th>
                        <th title="STATUS">STATUS</th>
                        <th title="QTY">QTY</th>
                        <th title="SALES">SALES</th>
                        <th title="COST">COST</th>
                        <th title="VAT">VAT</th>
                        <th title="Payment Fee">Payment Fee</th>


                        <th title="Profit">Profit</th>

                        <th title="SUPPLIER">SUPPLIER</th>
                        <th title="DATE"> DATE</th>
                        <th></th>
                    </tr>
                </thead>
            </table>
        </div>


        {{--    @include('_partials/_modals/modal-create-app') --}}

    </div>

@endsection
