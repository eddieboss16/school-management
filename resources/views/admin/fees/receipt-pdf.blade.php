<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; padding: 12px; }
    .center { text-align: center; }
    .school-name { font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
    .receipt-title { font-size: 11px; font-weight: bold; text-transform: uppercase; margin: 6px 0; letter-spacing: 2px; }
    .divider { border-top: 1px dashed #999; margin: 6px 0; }
    .divider-solid { border-top: 1px solid #333; margin: 6px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 2px 0; vertical-align: top; }
    td.label { color: #555; width: 45%; }
    td.value { font-weight: bold; text-align: right; }
    .amount-row td { font-size: 12px; padding-top: 4px; }
    .footer { text-align: center; color: #555; margin-top: 8px; font-size: 9px; }
    .receipt-no { font-size: 9px; color: #666; }
    .badge { display: inline-block; padding: 1px 6px; border: 1px solid #333; border-radius: 3px; font-size: 9px; text-transform: uppercase; }
</style>
</head>
<body>

<div class="center">
    <div class="school-name">{{ config('app.name') }}</div>
    <div style="font-size:9px; color:#555; margin-top:2px;">School Fee Payment Receipt</div>
</div>

<div class="divider-solid"></div>

<div class="receipt-no center">Receipt No: #{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
<div style="font-size:9px; color:#555; text-align:center;">{{ $payment->payment_date->format('d M Y') }}</div>

<div class="divider"></div>

<table>
    <tr>
        <td class="label">Student</td>
        <td class="value">{{ $payment->student->name }}</td>
    </tr>
    <tr>
        <td class="label">Adm. No.</td>
        <td class="value">{{ $payment->student->admission_number ?? '—' }}</td>
    </tr>
    <tr>
        <td class="label">Class</td>
        <td class="value">
            {{ $payment->student->stream
                ? $payment->student->stream->grade->name . ' ' . $payment->student->stream->name
                : '—' }}
        </td>
    </tr>
    <tr>
        <td class="label">Term</td>
        <td class="value">{{ $payment->term->name ?? '—' }}</td>
    </tr>
</table>

<div class="divider"></div>

<table>
    <tr>
        <td class="label">Payment Method</td>
        <td class="value">
            <span class="badge">{{ strtoupper($payment->payment_method->label()) }}</span>
        </td>
    </tr>
    @if($payment->reference_number)
    <tr>
        <td class="label">Reference</td>
        <td class="value">{{ $payment->reference_number }}</td>
    </tr>
    @endif
</table>

<div class="divider-solid"></div>

<table>
    <tr class="amount-row">
        <td class="label" style="font-size:12px; font-weight:bold;">AMOUNT PAID</td>
        <td class="value" style="font-size:14px;">KES {{ number_format($payment->amount, 2) }}</td>
    </tr>
</table>

<div class="divider"></div>

@if($payment->notes)
<div style="font-size:9px; color:#555; margin-bottom:6px;">Notes: {{ $payment->notes }}</div>
@endif

<div class="footer">
    Recorded by: {{ $payment->recordedBy->name ?? 'System' }}<br>
    Printed: {{ now()->format('d M Y H:i') }}<br><br>
    This is an official receipt. Keep for your records.
</div>

</body>
</html>
