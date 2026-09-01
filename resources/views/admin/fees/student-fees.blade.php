<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Fees — {{ $student->name }}
            </h2>
            <a href="{{ route('admin.fees.balances') }}" class="text-m text-white hover:text-gray-400">← Back to Balances</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">{{ session('error') }}</div>
            @endif

            <!-- Student info -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-4">
                <div class="flex justify-between items-start flex-wrap gap-4">
                    <div>
                        <p class="text-lg font-semibold text-gray-900">{{ $student->name }}</p>
                        <p class="text-sm text-gray-500">{{ $student->admission_number ?? 'No admission number' }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $student->stream ? $student->stream->grade->name . ' ' . $student->stream->name : 'Not assigned to a stream' }}
                        </p>
                    </div>
                    <!-- Term filter -->
                    <form method="GET" class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Term:</label>
                        <select name="term_id" onchange="this.form.submit()"
                            class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select</option>
                            @foreach($terms as $term)
                                <option value="{{ $term->id }}" {{ $selectedTermId == $term->id ? 'selected' : '' }}>
                                    {{ $term->name }} {{ $term->is_active ? '(Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>

            @if($selectedTermId)
                <!-- Balance summary -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500">Expected</p>
                        <p class="text-2xl font-bold text-gray-800">KES {{ number_format($expected, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500">Paid</p>
                        <p class="text-2xl font-bold text-green-600">KES {{ number_format($paid, 2) }}</p>
                    </div>
                    <div class="bg-white shadow-sm sm:rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500">Balance</p>
                        <p class="text-2xl font-bold {{ $balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                            KES {{ number_format($balance, 2) }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Applicable fees -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 mb-3">Fee Breakdown</h3>
                        @if($fees->count() > 0)
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b">
                                        <th class="py-2 text-left text-gray-500">Fee</th>
                                        <th class="py-2 text-right text-gray-500">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($fees as $fee)
                                        <tr class="border-b">
                                            <td class="py-2 text-gray-700">
                                                {{ $fee->name }}
                                                @if($fee->grade)
                                                    <span class="text-xs text-gray-400">({{ $fee->grade->name }})</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-right font-medium">{{ number_format($fee->amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="font-bold">
                                        <td class="pt-2">Total</td>
                                        <td class="pt-2 text-right">{{ number_format($expected, 2) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        @else
                            <p class="text-gray-500 text-sm">No fees defined for this term/grade.</p>
                        @endif
                    </div>

                    <!-- Record payment form -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 mb-3">Record Payment</h3>
                        <form method="POST" action="{{ route('admin.fees.student.payment', $student->id) }}">
                            @csrf
                            <input type="hidden" name="term_id" value="{{ $selectedTermId }}">

                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Amount (KES) *</label>
                                <input type="number" name="amount" step="0.01" min="1"
                                    value="{{ old('amount') }}"
                                    placeholder="{{ $balance > 0 ? number_format($balance, 2) : '0.00' }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Payment Date *</label>
                                <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                                @error('payment_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Payment Method *</label>
                                <select name="payment_method" class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                                    @foreach(\App\Enums\PaymentMethod::cases() as $method)
                                        <option value="{{ $method->value }}" {{ old('payment_method') == $method->value ? 'selected' : '' }}>{{ $method->label() }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="mb-3">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Reference Number</label>
                                <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                                    placeholder="Receipt / Transaction ID"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <div class="mb-4">
                                <label class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                                <textarea name="notes" rows="2"
                                    class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notes') }}</textarea>
                            </div>

                            <button type="submit" class="w-full bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Record Payment
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Payment history -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Payment History</h3>
                    @if($payments->count() > 0)
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Recorded By</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($payments as $payment)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700">{{ $payment->payment_date->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 font-semibold text-green-700">KES {{ number_format($payment->amount, 2) }}</td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 py-1 text-xs rounded-full {{ $payment->payment_method->badgeClass() }}">
                                                {{ $payment->payment_method->label() }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-500">{{ $payment->reference_number ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $payment->recordedBy->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $payment->notes ?? '—' }}</td>
                                        <td class="px-4 py-3 flex gap-3 items-center">
                                            <a href="{{ route('admin.fees.payment.receipt', $payment->id) }}"
                                                target="_blank"
                                                class="text-blue-500 hover:text-blue-700 text-xs">Receipt</a>
                                            <form action="{{ route('admin.fees.payment.delete', $payment->id) }}" method="POST" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 text-xs"
                                                    onclick="return confirm('Delete this payment record?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-500 text-sm">No payments recorded for this term.</p>
                    @endif
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Select a term above to view fee details.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
