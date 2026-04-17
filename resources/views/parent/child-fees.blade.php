<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $child->name }}'s Fees
            </h2>
            <a href="{{ route('parent.dashboard') }}" class="text-sm text-gray-600 hover:text-gray-900">← Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <!-- Term selector -->
            <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-4">
                <form method="GET" class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-700">Term:</label>
                    <select name="term_id" onchange="this.form.submit()"
                        class="rounded-md border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select Term</option>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ $selectedTermId == $term->id ? 'selected' : '' }}>
                                {{ $term->name }} {{ $term->is_active ? '(Active)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if($selectedTermId)
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-white shadow-sm sm:rounded-lg p-4 text-center">
                        <p class="text-xs text-gray-500">Total Fees</p>
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
                        @if($balance <= 0)
                            <p class="text-xs text-green-600 mt-1">All cleared ✓</p>
                        @else
                            <p class="text-xs text-red-500 mt-1">Please pay at the school office</p>
                        @endif
                    </div>
                </div>

                @if($fees->count() > 0)
                    <div class="bg-white shadow-sm sm:rounded-lg mb-6 p-6">
                        <h3 class="font-semibold text-gray-800 mb-3">Fee Breakdown</h3>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="py-2 text-left text-gray-500">Fee</th>
                                    <th class="py-2 text-right text-gray-500">Amount (KES)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fees as $fee)
                                    <tr class="border-b">
                                        <td class="py-2 text-gray-700">{{ $fee->name }}</td>
                                        <td class="py-2 text-right">{{ number_format($fee->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="font-bold">
                                    <td class="pt-2">Total</td>
                                    <td class="pt-2 text-right">{{ number_format($expected, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                @endif

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
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($payments as $payment)
                                    <tr>
                                        <td class="px-4 py-3">{{ $payment->payment_date->format('M d, Y') }}</td>
                                        <td class="px-4 py-3 font-semibold text-green-700">KES {{ number_format($payment->amount, 2) }}</td>
                                        <td class="px-4 py-3">{{ ucfirst($payment->payment_method) }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $payment->reference_number ?? '—' }}</td>
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
                    Select a term to view fee details.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
