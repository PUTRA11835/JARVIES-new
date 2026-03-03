<div class="space-y-6">
    <!-- General Information -->
    <div>
        <h3 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">General Information</h3>

        <div class="grid grid-cols-6 gap-4">
            <!-- Customer Code (Read-only) -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Customer Code</label>
                <input type="text" id="customerCode" value="{{ $customer->customer_code ?? 'AUTO' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Title -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Title</label>
                <input type="text" id="title" value="{{ $customer->basicData->title ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Name 1 -->
            <div class="col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Name 1</label>
                <input type="text" id="name1" value="{{ $customer->basicData->name_1 ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Search Term 1 -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Search Term 1</label>
                <input type="text" id="searchTerm1" value="{{ $customer->basicData->search_term_1 ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- External Number -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">External Number</label>
                <input type="text" id="externalNumber" value="{{ $customer->basicData->external_number ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Name 2 -->
            <div class="col-span-2 row-start-2 col-start-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Name 2</label>
                <input type="text" id="name2" value="{{ $customer->basicData->name_2 ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Search Term 2 -->
            <div class="col-span-1 row-start-2 col-start-5">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Search Term 2</label>
                <input type="text" id="searchTerm2" value="{{ $customer->basicData->search_term_2 ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Audit Information -->
            <div class="col-span-6 mt-6 pt-4 border-t border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Audit Information</h4>
                <div class="grid grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <div class="flex gap-4">
                            <span class="text-sm font-semibold text-gray-700 w-32">Created By</span>
                            <span id="createdBy" class="text-sm text-gray-600">
                                {{ $customer->basicData->created_by ?? '-' }}
                            </span>
                        </div>
                        <div class="flex gap-4">
                            <span class="text-sm font-semibold text-gray-700 w-32">Created On</span>
                            <span id="createdOn" class="text-sm text-gray-600">
                                @if($customer->basicData && $customer->basicData->created_on)
                                    {{ \Carbon\Carbon::parse($customer->basicData->created_on)->format('d.m.Y H:i:s') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex gap-4">
                            <span class="text-sm font-semibold text-gray-700 w-32">Last Changed By</span>
                            <span id="lastChangedBy" class="text-sm text-gray-600">
                                {{ $customer->basicData->last_changed_by ?? '-' }}
                            </span>
                        </div>
                        <div class="flex gap-4">
                            <span class="text-sm font-semibold text-gray-700 w-32">Last Changed On</span>
                            <span id="lastChangedOn" class="text-sm text-gray-600">
                                @if($customer->basicData && $customer->basicData->last_changed_on)
                                    {{ \Carbon\Carbon::parse($customer->basicData->last_changed_on)->format('d.m.Y H:i:s') }}
                                @else
                                    -
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div>
        <h3 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">Customer Information</h3>

        <div class="grid grid-cols-6 gap-4">
            <!-- Customer Group -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Customer Group</label>
                <input type="text" id="customerGroup" value="{{ $customer->basicData->customer_group ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Credit Limit Type -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Credit Limit Type</label>
                <input type="text" id="creditLimitType" value="{{ $customer->basicData->credit_limit_type ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Industry Sector -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Industry Sector</label>
                <input type="text" id="industrySector" value="{{ $customer->basicData->industry_sector ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- EC Account Executive -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">EC Account Executive</label>
                <input type="text" id="ecAccountExecutive" value="{{ $customer->basicData->ec_account_executive ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Authorization Group -->
            <div class="col-span-1">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Authorization Group</label>
                <input type="text" id="authorizationGroup" value="{{ $customer->basicData->authorization_group ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Block -->
            <div class="col-span-1 flex items-center pt-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="block"
                        {{ (!empty($customer->basicData->block)) ? 'checked' : '' }}
                        class="w-4 h-4 text-red-800 border-gray-300 rounded" disabled>
                    <label for="block" class="text-sm font-semibold text-gray-700">Block</label>
                </div>
            </div>

            <!-- Customer Category -->
            <div class="col-span-2 row-start-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">Customer Category</label>
                <input type="text" id="customerCategory" value="{{ $customer->basicData->customer_category ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- SAP Account Executive -->
            <div class="col-span-2 row-start-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">SAP Account Executive</label>
                <input type="text" id="sapAccountExecutive" value="{{ $customer->basicData->sap_account_executive ?? '' }}"
                    class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-gray-50 text-gray-600" readonly>
            </div>

            <!-- Deletion Flag -->
            <div class="col-span-1 row-start-2 flex items-center pt-4">
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="deletionFlag"
                        {{ (!empty($customer->basicData->deletion_flag)) ? 'checked' : '' }}
                        class="w-4 h-4 text-red-800 border-gray-300 rounded" disabled>
                    <label for="deletionFlag" class="text-sm font-semibold text-gray-700">Deletion Flag</label>
                </div>
            </div>
        </div>
    </div>
</div>
